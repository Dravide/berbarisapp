<?php

namespace App\Livewire\Eventner\Ticket;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Settings extends Component
{
    public $eventner;
    public $ticket_active = false;
    public $ticket_start = '';
    public $ticket_end = '';
    public $ticket_price = '';
    public $ticket_description = '';
    public $ticket_max_per_order = 10;

    public function mount()
    {
        $this->eventner = Auth::user()->eventner;

        if (!$this->eventner) {
            abort(403, 'Anda belum memiliki data Event terdaftar.');
        }

        $this->ticket_active = (bool) $this->eventner->ticket_active;
        $this->ticket_start = $this->eventner->ticket_start?->format('Y-m-d\TH:i') ?? '';
        $this->ticket_end = $this->eventner->ticket_end?->format('Y-m-d\TH:i') ?? '';
        $this->ticket_price = $this->eventner->ticket_price ?? '';
        $this->ticket_description = $this->eventner->ticket_description ?? '';
        $this->ticket_max_per_order = $this->eventner->ticket_max_per_order ?? 10;
    }

    /**
     * Generate token check-in statis (sekali). Tidak auto-regenerate.
     * Pakai regenerateCheckinToken() untuk rotate manual.
     */
    public function generateCheckinAccess()
    {
        if ($this->eventner->checkin_token) {
            return;
        }
        $this->eventner->checkin_token = Str::random(40);
        $this->eventner->save();
        session()->flash('success', 'Akses check-in dibuat. URL di bawah statis — tidak berubah.');
    }

    public function regenerateCheckinToken()
    {
        $this->eventner->checkin_token = Str::random(40);
        $this->eventner->save();
        session()->flash('success', 'Token check-in dirotasi. URL lama tidak berlaku lagi.');
    }

    public function revokeCheckinAccess()
    {
        $this->eventner->checkin_token = null;
        $this->eventner->save();
        session()->flash('success', 'Akses check-in dicabut.');
    }

    public function save()
    {
        $this->validate([
            'ticket_price' => 'required_if:ticket_active,true|nullable|numeric|min:0',
            'ticket_max_per_order' => 'required|integer|min:1|max:100',
            'ticket_description' => 'nullable|string|max:1000',
        ], [
            'ticket_price.required_if' => 'Harga tiket wajib diisi jika tiket aktif.',
            'ticket_price.min' => 'Harga tiket minimal 0.',
        ]);

        $this->eventner->update([
            'ticket_active' => $this->ticket_active,
            'ticket_start' => $this->ticket_start ?: null,
            'ticket_end' => $this->ticket_end ?: null,
            'ticket_price' => $this->ticket_active ? $this->ticket_price : null,
            'ticket_description' => $this->ticket_description ?: null,
            'ticket_max_per_order' => $this->ticket_max_per_order,
        ]);

        session()->flash('success', 'Pengaturan tiket berhasil disimpan.');
    }

    public function render()
    {
        return view('livewire.eventner.ticket.settings')
            ->title('Pengaturan Tiket - ' . $this->eventner->nama_event);
    }
}
