<?php

namespace App\Services;

use App\Models\Registration;
use App\Models\RegistrationPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya penulis `registrations.amount_paid` dan `payment_status`.
 *
 * Sebelum ini dua method terpisah (FinanceDashboard::verifyPayment dan
 * rejectPayment) masing-masing menulis status sendiri. Menambah DP berarti
 * menambah jalur ketiga, dan tiga jalur yang menghitung angka yang sama akan
 * cepat atau lambat menghasilkan angka berbeda untuk data yang sama. Service
 * ini memusatkannya: semua jalur verifikasi memanggilnya.
 *
 * `amount_paid` SELALU dijumlah ulang dari baris ber-status verified, tidak
 * pernah ditambah/dikurangi secara inkremental. Penjumlahan ulang itu idempoten
 * — menerima dua kali, atau menerima setelah menolak, tetap menghasilkan angka
 * yang sama dengan kenyataan barisnya.
 */
class PaymentRecorder
{
    /**
     * Terima satu bukti pembayaran.
     *
     * `$nominalDiterima` boleh berbeda dari nominal yang diklaim peserta:
     * panitia membaca mutasi rekening, bukan tulisan peserta. Bila null,
     * nominal klaim yang dipakai.
     */
    public function terima(RegistrationPayment $bayar, User $oleh, ?float $nominalDiterima = null): void
    {
        DB::transaction(function () use ($bayar, $oleh, $nominalDiterima) {
            $nominal = $nominalDiterima !== null
                ? max(0, round($nominalDiterima, 2))
                : (float) $bayar->amount;

            $bayar->update([
                'amount' => $nominal,
                'status' => RegistrationPayment::STATUS_VERIFIED,
                'verified_at' => now(),
                'verified_by' => $oleh->id,
                'rejection_reason' => null,
            ]);

            $this->syncStatus($bayar->registration, $oleh);
        });
    }

    /**
     * Tolak satu bukti pembayaran.
     *
     * Barisnya TIDAK dihapus: peserta perlu melihat bahwa buktinya pernah
     * ditolak dan mengapa, dan panitia perlu jejak siapa yang menolak.
     * Yang berubah hanya statusnya — dan karena syncStatus() menjumlah baris
     * verified saja, uang yang pernah benar-benar diterima tidak ikut hilang.
     */
    public function tolak(RegistrationPayment $bayar, User $oleh, ?string $alasan = null): void
    {
        DB::transaction(function () use ($bayar, $oleh, $alasan) {
            $bayar->update([
                'status' => RegistrationPayment::STATUS_REJECTED,
                'verified_at' => now(),
                'verified_by' => $oleh->id,
                'rejection_reason' => $alasan ? strip_tags($alasan) : null,
            ]);

            $this->syncStatus($bayar->registration, $oleh);
        });
    }

    /**
     * Hitung ulang ringkasan pembayaran pendaftaran dari barisnya.
     *
     * Aturan status, berurutan:
     *   - sisa tagihan nol        -> paid   (lunas)
     *   - ada uang masuk          -> dp_paid
     *   - ada bukti menunggu      -> pending_verification
     *   - selain itu              -> unpaid
     *
     * Pendaftaran gratis tidak pernah disentuh: `free` bukan keadaan yang
     * dicapai lewat pembayaran, dan menimpanya dengan `unpaid` akan membuat
     * portal menagih event yang tidak menarik biaya.
     */
    public function syncStatus(Registration $reg, ?User $oleh = null): void
    {
        if ($reg->payment_status === 'free') {
            return;
        }

        $reg->amount_paid = (float) $reg->registrationPayments()
            ->where('status', RegistrationPayment::STATUS_VERIFIED)
            ->sum('amount');

        $menunggu = $reg->registrationPayments()
            ->where('status', RegistrationPayment::STATUS_PENDING)
            ->exists();

        // Sisa dihitung dari angka yang BARU SAJA dijumlah, bukan lewat
        // Registration::sisaTagihan(): method itu punya cadangan untuk baris
        // lama berstatus `paid` yang belum punya catatan pembayaran, dan
        // cadangan itu akan membuat pendaftaran tetap "lunas" meski seluruh
        // buktinya baru saja ditolak.
        $sisa = max(0, (float) $reg->total_fee - (float) $reg->amount_paid);

        $reg->payment_status = match (true) {
            $sisa <= 0 => 'paid',
            (float) $reg->amount_paid > 0 => 'dp_paid',
            $menunggu => 'pending_verification',
            default => 'unpaid',
        };

        if ($reg->payment_status === 'paid') {
            // Hanya terisi saat BENAR-BENAR lunas: invoice gabungan dan laporan
            // lama membaca kolom ini, dan mengisinya saat DP akan mencetak
            // kwitansi "LUNAS" untuk pasukan yang baru bayar 30%.
            if (! $reg->payment_verified_at) {
                $reg->payment_verified_at = now();
            }
            if (! $reg->payment_verified_by) {
                $reg->payment_verified_by = $oleh?->id;
            }

            // Tenggat pelunasan tidak berlaku lagi setelah lunas.
            $reg->dp_deadline = null;
        } else {
            // Bisa terjadi bila pembayaran yang tadinya diterima lalu ditolak
            // karena salah input. Membiarkan tanggalnya terisi akan membuat
            // rekap dan grafik 30 hari tetap menghitung uang yang sudah
            // dibatalkan.
            $reg->payment_verified_at = null;
            $reg->payment_verified_by = null;

            if ($reg->sudahDp()) {
                // Tenggat dihitung sekali, saat DP pertama diterima — bukan
                // diperpanjang tiap ada pembayaran berikutnya.
                if (! $reg->dp_deadline) {
                    $reg->dp_deadline = $this->hitungTenggat($reg);
                }
            } else {
                $reg->dp_deadline = null;
            }
        }

        $reg->save();
    }

    /**
     * Tenggat pelunasan = hari DP diterima + dp_days milik tingkat lomba.
     * Null bila tingkat itu tidak memasang tenggat.
     */
    private function hitungTenggat(Registration $reg): ?\Carbon\Carbon
    {
        $hari = $reg->competitionCategory?->dp_days;

        return $hari ? now()->addDays((int) $hari) : null;
    }
}
