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
        $service = new AutoGoPay();
        $synced = 0;

        // Sync vote PENDING
        $pendingVotes = VoteTransaction::where('status', 'PENDING')
            ->whereNotNull('autogopay_transaction_id')
            ->where('created_at', '>=', now()->subHours(6)) // hanya 6 jam terakhir
            ->get();

        foreach ($pendingVotes as $tx) {
            try {
                $result = $service->checkStatus($tx->autogopay_transaction_id);
                $status = $result['data']['transaction_status'] ?? null;

                if ($status === 'settlement') {
                    $claimed = VoteTransaction::where('id', $tx->id)
                        ->where('status', 'PENDING')
                        ->update(['status' => 'PAID', 'paid_at' => now()]);

                    if ($claimed) {
                        $synced++;
                        Log::info("Sync: Vote {$tx->autogopay_transaction_id} → PAID");
                    }
                } elseif (in_array($status, ['expire', 'cancel'])) {
                    $claimed = VoteTransaction::where('id', $tx->id)
                        ->where('status', 'PENDING')
                        ->update(['status' => strtoupper($status)]);

                    if ($claimed) {
                        $synced++;
                    }
                }
            } catch (\Exception $e) {
                // skip — will retry next tick
            }
        }

        // Sync ticket PENDING
        $pendingTickets = Ticket::where('status', 'PENDING')
            ->whereNotNull('autogopay_transaction_id')
            ->where('created_at', '>=', now()->subHours(6))
            ->get();

        foreach ($pendingTickets as $ticket) {
            try {
                $result = $service->checkStatus($ticket->autogopay_transaction_id);
                $status = $result['data']['transaction_status'] ?? null;

                if ($status === 'settlement') {
                    $claimed = Ticket::where('id', $ticket->id)
                        ->where('status', 'PENDING')
                        ->update(['status' => 'PAID', 'paid_at' => now()]);

                    if ($claimed) {
                        $synced++;
                        Log::info("Sync: Ticket {$ticket->autogopay_transaction_id} → PAID");
                    }
                } elseif (in_array($status, ['expire', 'cancel'])) {
                    $claimed = Ticket::where('id', $ticket->id)
                        ->where('status', 'PENDING')
                        ->update(['status' => strtoupper($status)]);

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
