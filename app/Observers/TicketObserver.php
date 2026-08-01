<?php

namespace App\Observers;

use App\Models\Ticket;
use Illuminate\Support\Facades\Log;

class TicketObserver
{
    public function updated(Ticket $ticket): void
    {
        if (!$ticket->wasChanged('status')) return;

        try {
            if ($ticket->status === 'PAID') {
                app(\App\Services\FcmService::class)->sendToModel(
                    $ticket,
                    'Tiket Berhasil! 🎟️',
                    "Tiket untuk {$ticket->eventner->nama_event} — {$ticket->buyer_name} ({$ticket->quantity}x) berhasil.",
                    [
                        'type' => 'tiket_bought',
                        'ticket_id' => (string) $ticket->id,
                        'event_slug' => $ticket->eventner->slug,
                    ]
                );
            }
        } catch (\Throwable $e) {
            Log::warning('FCM notification failed (TicketObserver)', [
                'id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
