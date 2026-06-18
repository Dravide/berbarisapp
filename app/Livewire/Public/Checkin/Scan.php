<?php

namespace App\Livewire\Public\Checkin;

use App\Models\Eventner;
use App\Models\Ticket;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.frontend')]
#[Title('Check-in Tiket')]
class Scan extends Component
{
    public Eventner $eventner;
    public string $manualCode = '';
    public ?array $result = null;

    /**
     * Barcode yang di-emit oleh scanner. Livewire listener memanggil lookupTicket().
     */
    public string $scannedCode = '';

    public function mount(string $token)
    {
        // Token sendiri adalah secret — tidak perlu PIN/session.
        $this->eventner = Eventner::where('checkin_token', $token)
            ->where('ticket_active', true)
            ->firstOrFail();
    }

    /**
     * Dipanggil dari button. Hanya dispatch event ke JS untuk tampilkan modal.
     * Logic check-in sebenarnya di confirmCheckIn (dari JS setelah user konfirmasi).
     */
    public function askConfirm(int $ticketId, string $orderCode): void
    {
        $this->dispatch('checkin:ask-confirm', [
            'id' => $ticketId,
            'code' => $orderCode,
        ]);
    }

    /**
     * Cari tiket berdasarkan order_code (dipanggil oleh scanner + input manual).
     * Tidak langsung check-in — hanya tampilkan hasil agar panitia konfirmasi manual.
     */
    public function lookupTicket(?string $code = null)
    {
        $code = $code !== null ? trim($code) : trim($this->scannedCode);
        $this->scannedCode = '';
        $this->manualCode = '';

        if ($code === '') {
            return;
        }

        $ticket = Ticket::where('eventner_id', $this->eventner->id)
            ->where('order_code', strtoupper($code))
            ->first();

        if (!$ticket) {
            $this->result = ['kind' => 'not_found', 'code' => strtoupper($code)];
            return;
        }

        $this->result = match ($ticket->status) {
            'PENDING' => ['kind' => 'pending', 'ticket' => $ticket],
            'EXPIRED' => ['kind' => 'expired', 'ticket' => $ticket],
            'CHECKED_IN' => ['kind' => 'already', 'ticket' => $ticket],
            'PAID' => ['kind' => 'ready', 'ticket' => $ticket],
            default => ['kind' => 'not_found', 'code' => $ticket->order_code],
        };
    }

    public function confirmCheckIn(int $ticketId)
    {
        $ticket = Ticket::where('eventner_id', $this->eventner->id)->findOrFail($ticketId);

        if ($ticket->status !== 'PAID') {
            $this->result = ['kind' => 'not_ready', 'ticket' => $ticket];
            return;
        }

        $ticket->update([
            'status' => 'CHECKED_IN',
            'checked_in_at' => now(),
            'checked_in_by' => null,
        ]);

        $this->result = ['kind' => 'success', 'ticket' => $ticket->fresh()];
    }

    public function render()
    {
        return view('livewire.public.checkin.scan', [
            'eventner' => $this->eventner,
        ])->layoutData(['eventner' => $this->eventner]);
    }
}
