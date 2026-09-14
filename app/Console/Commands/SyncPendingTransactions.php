<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\VoteTransaction;
use App\Services\AutoGoPay;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncPendingTransactions extends Command
{
    protected $signature = 'payment:sync-pending';
    protected $description = 'Sync all PENDING transactions with AutoGoPay to catch missed webhooks';

    public function handle(): int
    {
        // Satu kunci bersama dengan job SyncPendingPayments — lihat
        // PaymentSyncLock. withoutOverlapping() hanya mengunci per-jadwal,
        // jadi tanpa ini command tiap menit bisa beradu dengan job tiap lima
        // menit untuk transaksi yang sama.
        \App\Support\PaymentSyncLock::run(fn () => $this->sync());

        return 0;
    }

    private function sync(): int
    {
        $service = new AutoGoPay();
        $synced = 0;

        // Jendela 24 jam: QRIS AutoGoPay berumur panjang, dan pembayaran bisa
        // masuk setelah QR tercatat kedaluwarsa. Jendela 6 jam membuat
        // transaksi seperti itu berhenti direkonsiliasi.
        $since = now()->subHours(24);

        // Sync vote PENDING + EXPIRED yang mungkin sudah lunas di gateway
        $pendingVotes = VoteTransaction::whereIn('status', ['PENDING', 'EXPIRED'])
            ->whereNotNull('autogopay_transaction_id')
            ->where('created_at', '>=', $since)
            ->get();

        foreach ($pendingVotes as $tx) {
            try {
                $result = $service->checkStatus($tx->autogopay_transaction_id);
                $status = $result['data']['transaction_status'] ?? null;
                $mapped = AutoGoPay::mapStatus($status);

                if ($mapped === 'PAID') {
                    // claimPaid() menerima PENDING maupun EXPIRED — satu baris
                    // satu transaksi, jadi tidak ada risiko dobel kredit.
                    if ($tx->claimPaid()) {
                        $synced++;
                        Log::info("Sync: Vote {$tx->autogopay_transaction_id} → PAID");
                    }
                } elseif ($mapped !== null && $tx->status === 'PENDING') {
                    $claimed = VoteTransaction::where('id', $tx->id)
                        ->where('status', 'PENDING')
                        ->update(['status' => $mapped]);

                    if ($claimed) {
                        $synced++;
                    }
                }
            } catch (\Exception $e) {
                // skip — will retry next tick
            }
        }

        // Sync ticket PENDING + EXPIRED yang mungkin sudah lunas di gateway
        $pendingTickets = Ticket::whereIn('status', ['PENDING', 'EXPIRED'])
            ->whereNotNull('autogopay_transaction_id')
            ->where('created_at', '>=', $since)
            ->get();

        foreach ($pendingTickets as $ticket) {
            try {
                $result = $service->checkStatus($ticket->autogopay_transaction_id);
                $status = $result['data']['transaction_status'] ?? null;
                $mapped = AutoGoPay::mapStatus($status);

                if ($mapped === 'PAID') {
                    if ($ticket->claimPaid()) {
                        $synced++;
                        Log::info("Sync: Ticket {$ticket->autogopay_transaction_id} → PAID");
                    }
                } elseif ($mapped !== null && $ticket->status === 'PENDING') {
                    $claimed = Ticket::where('id', $ticket->id)
                        ->where('status', 'PENDING')
                        ->update(['status' => $mapped]);

                    if ($claimed) {
                        $synced++;
                    }
                }
            } catch (\Exception $e) {
                // skip
            }
        }

        if ($synced > 0) {
            $this->info("Synced {$synced} transaction(s).");
        }

        return 0;
    }
}
