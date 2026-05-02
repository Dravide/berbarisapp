<?php

namespace App\Livewire\Eventner\Ticket;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Settings extends Component
{
    public $eventner;
    public $ticket_active = false;
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
        $this->ticket_price = $this->eventner->ticket_price ?? '';
        $this->ticket_description = $this->eventner->ticket_description ?? '';
        $this->ticket_max_per_order = $this->eventner->ticket_max_per_order ?? 10;
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
