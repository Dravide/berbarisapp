<?php

namespace App\Livewire\Public\Checkin;

use App\Models\Eventner;
use App\Models\EventnerVenue;
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
     * Tempat yang dijaga oleh token ini. null = token event (menerima tiket
     * dari gerbang mana pun).
     */
    public ?int $gateVenueId = null;
    public ?string $gateVenueName = null;

    /**
     * Barcode yang di-emit oleh scanner. Livewire listener memanggil lookupTicket().
     */
    public string $scannedCode = '';

    public function mount(string $token)
    {
        $gateVenue = null;

        // Token sendiri adalah secret — tidak perlu PIN/session.
        $eventner = Eventner::where('checkin_token', $token)
            ->where('ticket_active', true)
            ->first();

        if (!$eventner) {
            // Bukan token event — coba token gerbang tempat. Efeknya sama:
            // token tetap menentukan satu eventner, jadi tidak ada jalur lintas event.
            $gateVenue = EventnerVenue::where('checkin_token', $token)->first();

            abort_if(!$gateVenue, 404);

            $eventner = $gateVenue->eventner;
        }

        // Token venue milik event yang tiketnya mati juga harus 404 — resolusi
        // eventner disamakan untuk kedua jenis token.
        abort_if(!$eventner || !$eventner->ticket_active, 404);

        $this->eventner = $eventner;

        if ($gateVenue) {
            $this->gateVenueId = $gateVenue->id;
            $this->gateVenueName = $gateVenue->name;
        }
    }

    /**
     * Tiket ini boleh masuk di gerbang yang dijaga token sekarang?
     *
     * Tiket tanpa tempat (event satu tempat / data lama) diterima di gerbang
     * mana pun — perilaku sebelum fitur tempat.
     */
    private function gateAllows(Ticket $ticket): bool
    {
        if ($this->gateVenueId === null || $ticket->venue_id === null) {
            return true;
        }

        return (int) $ticket->venue_id === (int) $this->gateVenueId;
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

        $ticket = Ticket::with('venue')
            ->where('eventner_id', $this->eventner->id)
            ->where('order_code', strtoupper($code))
            ->first();

        if (!$ticket) {
            $this->result = ['kind' => 'not_found', 'code' => strtoupper($code)];
            return;
        }

        // Gerbang salah: dicek lebih dulu supaya petugas langsung mengarahkan
        // penonton, bukan menandai tiket sudah terpakai di tempat yang keliru.
        if (!$this->gateAllows($ticket)) {
            $this->result = [
                'kind' => 'wrong_venue',
                'ticket' => $ticket,
                'gate' => $this->gateVenueName,
            ];
            return;
        }

        $this->result = match ($ticket->status) {
            'PENDING' => ['kind' => 'pending', 'ticket' => $ticket],
            'EXPIRED' => ['kind' => 'expired', 'ticket' => $ticket],
            'CHECKED_IN' => ['kind' => 'already', 'ticket' => $ticket],
            'PAID', 'ACTIVE' => ['kind' => 'ready', 'ticket' => $ticket],
            default => ['kind' => 'not_found', 'code' => $ticket->order_code],
        };
    }

    public function confirmCheckIn(int $ticketId)
    {
        $ticket = Ticket::with('venue')->where('eventner_id', $this->eventner->id)->findOrFail($ticketId);

        // Tiket satu tempat tidak boleh masuk gerbang lain — ditegakkan di sini
        // juga, bukan hanya di lookupTicket, supaya permintaan langsung ke
        // komponen tidak bisa menembusnya.
        if (!$this->gateAllows($ticket)) {
            $this->result = [
                'kind' => 'wrong_venue',
                'ticket' => $ticket,
                'gate' => $this->gateVenueName,
            ];
            return;
        }

        if (!in_array($ticket->status, ['PAID', 'ACTIVE'])) {
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
