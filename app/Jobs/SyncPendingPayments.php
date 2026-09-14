<?php

namespace App\Jobs;

use App\Models\Eventner;
use App\Models\Ticket;
use App\Models\User;
use App\Models\VoteTransaction;
use App\Services\AutoGoPay;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sinkron transaksi PENDING (vote + tiket) dgn status AutoGoPay via HTTP pool.
 * Scheduler (bootstrap/schedule.php) dispatch job ini tiap 5 menit; ShouldBeUnique
 * mencegah dobel job kalau siklus sebelumnya masih jalan.
 * Worker supervisor (aaPanel) yang mengeksekusi queue-nya.
 */
class SyncPendingPayments implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Batas umur transaksi PENDING yang dicek (jam).
     *
     * Dipatok 24 jam, bukan 6: QRIS AutoGoPay berumur jauh lebih panjang,
     * dan pembeli bisa menyelesaikan pembayaran setelah QR-nya tercatat
     * kedaluwarsa di sisi gateway (QR terlanjur discan, webhook telat).
     * Dengan jendela 6 jam, transaksi seperti itu berhenti direkonsiliasi
     * dan uangnya tidak pernah dikreditkan.
     */
    public int $maxAgeHours = 24;

    /**
     * Batas umur transaksi EXPIRED yang masih dicek ulang (jam).
     *
     * Transaksi yang sudah ditandai EXPIRED tetap direkonsiliasi selama
     * jendela ini: kalau gateway ternyata mencatat settlement, statusnya
     * dinaikkan ke PAID — jalur EXPIRED → PAID.
     */
    public int $expiredGraceHours = 24;

    /** Batch maksimal per siklus (pool paralel, sekali jalan ~10 detik). */
    public int $batchSize = 100;

    /** Umur pendaftaran belum dibayar sebelum dibersihkan (jam). */
    public int $abandonedAfterHours = 24;

    public function handle(): void
    {
        // Satu kunci bersama dengan command payment:sync-pending — lihat
        // PaymentSyncLock. withoutOverlapping() mengunci per-jadwal, bukan
        // antar-jadwal, jadi keduanya bisa menembak /qris/status bersamaan.
        \App\Support\PaymentSyncLock::run(fn () => $this->sync());
    }

    private function sync(): void
    {
        // Selalu dijalankan, termasuk saat tidak ada transaksi vote/tiket pending —
        // pendaftaran nyangkut tidak berhubungan dengan transaksi pending, dan
        // handle() punya early return kalau daftar transaksinya kosong.
        $this->cleanupAbandonedRegistrations();

        $since = now()->subHours($this->maxAgeHours);
        $graceSince = now()->subHours($this->expiredGraceHours);

        // Transaksi yang masih bisa dibayar.
        $pendingVotes = VoteTransaction::where('status', 'PENDING')
            ->whereNotNull('autogopay_transaction_id')
            ->where('created_at', '>=', $since)
            ->get();

        $pendingTickets = Ticket::where('status', 'PENDING')
            ->whereNotNull('autogopay_transaction_id')
            ->where('created_at', '>=', $since)
            ->get();

        // Transaksi yang sudah ditandai kedaluwarsa tapi mungkin sudah lunas
        // di sisi gateway — jalur EXPIRED → PAID. Tanpa ini, pembayaran yang
        // masuk setelah QR dinyatakan kedaluwarsa tidak pernah dikreditkan.
        $expiredVotes = VoteTransaction::where('status', 'EXPIRED')
            ->whereNotNull('autogopay_transaction_id')
            ->where('updated_at', '>=', $graceSince)
            ->get();

        $expiredTickets = Ticket::where('status', 'EXPIRED')
            ->whereNotNull('autogopay_transaction_id')
            ->where('updated_at', '>=', $graceSince)
            ->get();

        $total = $pendingVotes->count() + $pendingTickets->count()
            + $expiredVotes->count() + $expiredTickets->count();
        if ($total === 0) {
            return;
        }

        // Pool paralel: semua cek status serentak, key = transaction_id
        $service = new AutoGoPay();
        $txnIds = $pendingVotes->pluck('autogopay_transaction_id')
            ->merge($pendingTickets->pluck('autogopay_transaction_id'))
            ->merge($expiredVotes->pluck('autogopay_transaction_id'))
            ->merge($expiredTickets->pluck('autogopay_transaction_id'))
            ->unique()
            ->values()
            ->all();

        $statuses = $service->checkStatusMany(array_slice($txnIds, 0, $this->batchSize));

        $synced = 0;

        foreach ($pendingVotes as $tx) {
            $status = $statuses[$tx->autogopay_transaction_id] ?? null;
            $mapped = $status !== null ? AutoGoPay::mapStatus($status) : null;

            if ($mapped === 'PAID') {
                $claimed = VoteTransaction::where('id', $tx->id)
                    ->where('status', 'PENDING')
                    ->update(['status' => 'PAID', 'paid_at' => now()]);

                if ($claimed) {
                    $synced++;
                    Log::info("Auto-sync: vote {$tx->autogopay_transaction_id} → PAID");
                }
            } elseif ($mapped !== null) {
                $claimed = VoteTransaction::where('id', $tx->id)
                    ->where('status', 'PENDING')
                    ->update(['status' => $mapped]);

                if ($claimed) {
                    $synced++;
                }
            }
        }

        foreach ($pendingTickets as $ticket) {
            $status = $statuses[$ticket->autogopay_transaction_id] ?? null;
            $mapped = $status !== null ? AutoGoPay::mapStatus($status) : null;

            if ($mapped === 'PAID') {
                // claimPaid() sekaligus membuat QR tiket masuk — sebelumnya QR
                // hanya dibuat di jalur webhook, sehingga tiket yang terkonfirmasi
                // lewat polling (webhook telat/gagal) jadi PAID tanpa QR.
                if ($ticket->claimPaid()) {
                    $synced++;
                    Log::info("Auto-sync: ticket {$ticket->autogopay_transaction_id} → PAID");
                }
            } elseif ($mapped !== null) {
                $claimed = Ticket::where('id', $ticket->id)
                    ->where('status', 'PENDING')
                    ->update(['status' => $mapped]);

                if ($claimed) {
                    $synced++;
                }
            }
        }

        // Jalur EXPIRED → PAID: pembayaran yang masuk setelah QR dinyatakan
        // kedaluwarsa tetap dikreditkan. Hanya naik status, tidak pernah mundur.
        foreach ($expiredVotes as $tx) {
            if (AutoGoPay::mapStatus($statuses[$tx->autogopay_transaction_id] ?? null) !== 'PAID') {
                continue;
            }

            if ($tx->claimPaid()) {
                $synced++;
                Log::info("Auto-sync: vote {$tx->autogopay_transaction_id} EXPIRED → PAID (pembayaran masuk setelah kedaluwarsa)");
            }
        }

        foreach ($expiredTickets as $ticket) {
            if (AutoGoPay::mapStatus($statuses[$ticket->autogopay_transaction_id] ?? null) !== 'PAID') {
                continue;
            }

            // claimPaid() sekaligus menerbitkan QR masuk, sama seperti jalur lain.
            if ($ticket->claimPaid()) {
                $synced++;
                Log::info("Auto-sync: ticket {$ticket->autogopay_transaction_id} EXPIRED → PAID (pembayaran masuk setelah kedaluwarsa)");

                try {
                    app(\App\Services\MailyService::class)->sendTicketConfirmation($ticket->fresh());
                } catch (\Exception $e) {
                    Log::warning('Maily.id: sendTicketConfirmation failed (expired recovery)', [
                        'error' => $e->getMessage(),
                        'order' => $ticket->order_code,
                    ]);
                }
            }
        }

        if ($synced > 0) {
            Log::info("Auto-sync selesai: {$synced}/{$total} transaksi diperbarui.");
        }
    }

    /**
     * Hapus pendaftaran eventner yang QRIS-nya kadaluarsa dan tidak pernah dibayar.
     *
     * Tanpa ini, user + eventner pending nyangkut: email/username masih dipakai
     * (unique di tabel users) sehingga "silakan daftar ulang" selalu gagal, dan
     * akunnya tidak bisa login karena is_active masih false.
     *
     * Hanya menyentuh baris yang JELAS belum dibayar:
     *  - registration_paid_at NULL — guard utama, sumber kebenaran pembayaran
     *  - status 'pending'          — belum pernah disetujui
     *  - is_active false           — jaring pengaman: jangan hapus akun yang aktif
     *  - plan 'paid'               — eventner gratis tidak lewat jalur QRIS
     *  - transaksi sudah kedaluwarsa
     * eventner.user_id cascade, jadi user-nya ikut terhapus dan emailnya bebas lagi.
     */
    private function cleanupAbandonedRegistrations(): void
    {
        $cutoff = now()->subHours($this->abandonedAfterHours);

        $candidates = Eventner::where('status', 'pending')
            ->where('plan', 'paid')
            ->whereNull('registration_paid_at')
            ->whereNotNull('autogopay_transaction_id')
            ->where('created_at', '<', $cutoff)
            ->with('user:id,is_active')
            ->get();

        $cleaned = 0;

        foreach ($candidates as $eventner) {
            if ($eventner->user?->is_active) {
                continue;
            }

            // Pastikan transaksinya memang sudah tidak bisa dibayar lagi.
            // Gagal cek / endpoint error → JANGAN hapus, coba lagi siklus berikutnya.
            try {
                $status = (new AutoGoPay)->checkStatus($eventner->autogopay_transaction_id);
            } catch (\Throwable $e) {
                Log::warning('Cleanup pendaftaran: cek status gagal, dilewati', [
                    'eventner_id' => $eventner->id,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            $txStatus = $status['data']['transaction_status'] ?? '';

            // Kalau ternyata sudah settlement, biarkan jalur normal yang memproses.
            if ($txStatus === 'settlement') {
                continue;
            }

            // pending → masih bisa dibayar, tunggu siklus berikutnya
            if (!in_array($txStatus, ['expire', 'cancel'], true)) {
                continue;
            }

            $userId = $eventner->user_id;
            $eventner->delete();
            User::where('id', $userId)->where('is_active', false)->delete();
            $cleaned++;

            Log::info('Pendaftaran eventner kadaluarsa dibersihkan', [
                'eventner_id' => $eventner->id,
                'transaction_id' => $eventner->autogopay_transaction_id,
                'status' => $txStatus,
            ]);
        }

        if ($cleaned > 0) {
            Log::info("Cleanup pendaftaran: {$cleaned} pendaftaran belum dibayar dihapus.");
        }
    }

    public function uniqueId(): string
    {
        return 'sync-pending-payments';
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function tries(): int
    {
        return 3;
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SyncPendingPayments gagal total: ' . $e->getMessage());
    }
}
