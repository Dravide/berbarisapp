<?php

namespace App\Jobs;

use App\Models\Ticket;
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

    /** Batas umur transaksi yang dicek (jam) — transaksi lebih tua dianggap selesai. */
    public int $maxAgeHours = 6;

    /** Batch maksimal per siklus (pool paralel, sekali jalan ~10 detik). */
    public int $batchSize = 100;

    public function handle(): void
    {
        $since = now()->subHours($this->maxAgeHours);

        $pendingVotes = VoteTransaction::where('status', 'PENDING')
            ->whereNotNull('autogopay_transaction_id')
            ->where('created_at', '>=', $since)
            ->get();

        $pendingTickets = Ticket::where('status', 'PENDING')
            ->whereNotNull('autogopay_transaction_id')
            ->where('created_at', '>=', $since)
            ->get();

        $total = $pendingVotes->count() + $pendingTickets->count();
        if ($total === 0) {
            return;
        }

        // Pool paralel: semua cek status serentak, key = transaction_id
        $service = new AutoGoPay();
        $txnIds = $pendingVotes->pluck('autogopay_transaction_id')
            ->merge($pendingTickets->pluck('autogopay_transaction_id'))
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
                $claimed = Ticket::where('id', $ticket->id)
                    ->where('status', 'PENDING')
                    ->update([
                        'status' => 'PAID',
                        'paid_at' => now(),
                        // QR code diisi jalur webhook settlement (punya amount utk
                        // verifikasi) — polling tidak lengkapi qr_code_path.
                    ]);

                if ($claimed) {
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

        if ($synced > 0) {
            Log::info("Auto-sync selesai: {$synced}/{$total} transaksi diperbarui.");
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
