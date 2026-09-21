<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\VoteTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Penjaga satu-QR-aktif per email pembeli.
 *
 * AutoGoPay bisa memblokir akun merchant kalau terlalu banyak QRIS PENDING
 * dibuat dalam waktu singkat. Sebelum ini tiap klik "Bayar" di halaman vote
 * maupun tiket langsung memanggil generateQris() dan membuat baris PENDING
 * baru — satu pembeli yang menekan tombol berkali-kali menumpuk QR hidup di
 * sisi gateway. Kelas ini satu-satunya penjaga untuk itu: dipakai jalur web
 * (`Public\EventVote`, `Public\EventTicket`) dan jalur API Flutter
 * (`Api\V1\VoteController`, `Api\V1\TicketController`). Jangan duplikasi
 * pengecekannya di pemanggil.
 *
 * Kuncinya email, bukan user_id: vote & tiket sama-sama anonim.
 *
 * Hidup/matinya baris ditentukan oleh TENGGA DI DB, bukan statusnya. Baris
 * yang tetap PENDING selamanya (webhook kedaluwarsa tidak pernah sampai, dan
 * jendela rekonsiliasi 24 jam berbasis created_at sudah melewatinya) tidak
 * boleh memblokir pemiliknya terus-menerus — karena itu ada dua ambang:
 *
 *   - lewat `payable_until`                  → baris tidak dikembalikan lagi
 *                                              (pembeli boleh membuat QR baru)
 *   - lewat `payable_until + GRACE_HOURS`    → ditandai EXPIRED oleh sweepExpired()
 *
 * Di antara kedua ambang barisnya DIBIARKAN PENDING supaya jalur pulih
 * EXPIRED→PAID dan rekonsiliasi gateway tetap bisa mengklaimnya (lihat
 * claimPaid() di kedua model).
 */
class PendingPaymentGuard
{
    /**
     * Umur QR yang dipakai kalau gateway tidak mengirim `expiry_time`.
     *
     * Dipatok konservatif: lebih baik terlalu pendek daripada menahan pembeli.
     */
    public const WINDOW_MINUTES = 10;

    /**
     * Tenggang bayar setelah QR dinyatakan kedaluwarsa, sekaligus tenggat
     * sapuan. Di bawah jendela rekonsiliasi 24 jam supaya barisnya masih
     * tercakup jalur pulih EXPIRED→PAID sebelum dibiarkan bebas.
     */
    public const GRACE_HOURS = 15;

    /** Kolom email per model — satu-satunya perbedaan antara vote dan tiket. */
    private const EMAIL_COLUMNS = [
        VoteTransaction::class => 'voter_email',
        Ticket::class => 'buyer_email',
    ];

    /**
     * Baris PENDING yang masih hidup untuk (event, email) ini, atau null kalau
     * pembeli boleh membuat QR baru.
     *
     * Sengaja tanpa penulisan apa pun: penandaan EXPIRED dijalankan terjadwal
     * oleh sweepExpired(), bukan di sini — method ini dipanggil pada SETIAP
     * klik "Bayar" di halaman publik.
     *
     * @param  class-string<Model>  $model
     */
    public static function find(string $model, int $eventnerId, ?string $email): ?Model
    {
        $kolom = self::EMAIL_COLUMNS[$model] ?? null;
        $kunci = self::normalizeEmail($email);

        // Email kosong tidak boleh jadi kunci: baris lama ber-email NULL akan
        // saling cocok dan mengunci pembeli yang tidak berhubungan.
        if ($kolom === null || $kunci === '') {
            return null;
        }

        $row = $model::where('eventner_id', $eventnerId)
            ->where($kolom, $kunci)
            ->where('status', 'PENDING')
            ->orderByDesc('id')
            ->first();

        // Tanpa qr_url tidak ada yang bisa dibuka ulang — biarkan pembeli
        // membuat QR baru daripada menampilkan kotak kosong.
        if (! $row || ! $row->qr_url) {
            return null;
        }

        return self::isLapsed($row) ? null : $row;
    }

    /** Email dinormalkan jadi kunci gerbang; dipakai saat menulis maupun mencari. */
    public static function normalizeEmail(?string $email): string
    {
        return mb_strtolower(trim((string) $email));
    }

    /** Tenggat yang ditampilkan hitungan mundur: tenggat dari gateway. */
    public static function expiryForDisplay(Model $row): Carbon
    {
        return $row->expires_at
            ?? $row->payable_until
            ?? self::fallbackDeadline($row);
    }

    /**
     * Sudah lewat tenggat bayarnya?
     *
     * Untuk baris lama yang belum punya `payable_until` (dibuat sebelum kolom
     * ini ada) dipakai created_at + WINDOW_MINUTES — bukan + GRACE_HOURS.
     * Baris lama memang sudah tua; memberi tenggang 15 jam untuknya sama
     * dengan membiarkannya memblokir pemiliknya selama itu.
     */
    public static function isLapsed(Model $row): bool
    {
        $tenggat = $row->payable_until ?? self::fallbackDeadline($row);

        return $tenggat->isPast();
    }

    /**
     * Batalkan QR di gateway lalu lepaskan barisnya.
     *
     * Mengikuti pola Upgrade::cancelOldQris(): balasan expire/cancel dari
     * gateway sama saja dengan berhasil dibatalkan. Kalau gateway MENOLAK,
     * barisnya sengaja TIDAK ditandai — meninggalkan QR yang masih bisa
     * dibayar sambil memberi tahu pembeli "sudah dibatalkan" adalah cara
     * kehilangan uang: webhook-nya tidak akan dikenali lagi.
     *
     * @return bool true bila baris sudah tidak menahan gerbang
     */
    public static function release(Model $row): bool
    {
        if ($row->status !== 'PENDING') {
            return true;
        }

        $transaksi = $row->autogopay_transaction_id;

        if ($transaksi) {
            try {
                $hasil = app(AutoGoPay::class)->cancelTransaction($transaksi);
            } catch (\Throwable $e) {
                Log::warning('PendingPaymentGuard: pembatalan QR gagal', [
                    'transaction_id' => $transaksi,
                    'error' => $e->getMessage(),
                ]);

                return false;
            }

            $status = $hasil['data']['transaction_status'] ?? null;

            if (! in_array($status, ['expire', 'cancel'], true) && ($hasil['success'] ?? false) !== true) {
                Log::warning('PendingPaymentGuard: gateway menolak pembatalan QR', [
                    'transaction_id' => $transaksi,
                    'response' => $hasil,
                ]);

                return false;
            }
        }

        // FAILED sudah cukup melepas gerbang: find() hanya mencari PENDING.
        $row->forceFill(['status' => 'FAILED', 'qr_url' => null])->save();

        return true;
    }

    /**
     * Bebaskan baris PENDING yang tenggat bayarnya sudah lewat jauh.
     *
     * Ini pengganti slot untuk baris yang webhook kedaluwarsanya tidak pernah
     * masuk: reconciler hanya menandai EXPIRED dari balasan gateway, sehingga
     * baris seperti itu tertahan PENDING selamanya. Dipanggil dari scheduler
     * (`SyncPendingTransactions` tiap menit), bukan tiap klik.
     *
     * @return int jumlah baris yang dibebaskan
     */
    public static function sweepExpired(): int
    {
        $batas = now()->subHours(self::GRACE_HOURS);

        $dibebaskan = VoteTransaction::where('status', 'PENDING')
            ->where('payable_until', '<', $batas)
            ->update(['status' => 'EXPIRED']);

        $dibebaskan += Ticket::where('status', 'PENDING')
            ->where('payable_until', '<', $batas)
            ->update(['status' => 'EXPIRED']);

        return $dibebaskan;
    }

    /**
     * Tenggat bayar dari balasan gateway, jatuh ke `now() + WINDOW_MINUTES`
     * bila gateway tidak mengirim `expiry_time`.
     */
    public static function deadlineFrom(mixed $expiryTime): Carbon
    {
        if (blank($expiryTime)) {
            return now()->addMinutes(self::WINDOW_MINUTES);
        }

        try {
            return Carbon::parse($expiryTime);
        } catch (\Throwable $e) {
            Log::warning('PendingPaymentGuard: expiry_time tidak terbaca', ['expiry_time' => $expiryTime]);

            return now()->addMinutes(self::WINDOW_MINUTES);
        }
    }

    /** `payable_until` = tenggat gateway + tenggang. Inilah yang mengikat. */
    public static function payableUntil(mixed $expiryTime): Carbon
    {
        return self::deadlineFrom($expiryTime)->copy()->addHours(self::GRACE_HOURS);
    }

    private static function fallbackDeadline(Model $row): Carbon
    {
        return $row->created_at?->copy()->addMinutes(self::WINDOW_MINUTES) ?? now();
    }
}
