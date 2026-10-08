<?php

namespace App\Livewire\Admin\Eventner;

use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\Eventner;
use App\Models\Registration;

#[Layout('layouts.admin')]
class Modul extends Component
{
    public $eventnerId;
    public $eventner;

    /** Batas baris untuk tabel berpotensi ratusan baris (tiket, drawing). */
    public const BATAS_BARIS = 100;

    public $tickets;
    public $drawingRegistrations;

    public function mount($id)
    {
        $this->eventnerId = $id;

        // Satu eager load untuk seluruh modul — masing-masing satu query,
        // tanpa N+1 saat blade membaca relasinya.
        $this->eventner = Eventner::with([
            'tickets.venue',
            'voteTransactions',
            'championCategories',
            'eventRundowns',
            'overlaySetting',
            'sponsors',
            'tenants',
            'certificateTemplates',
            'faqs',
            'galleries',
            'signatures',
            'bankAccounts',
            'registrationFields',
            'venues',
        ])->findOrFail($id);

        // Sengaja query sendiri: ini tabel paling besar, dibatasi 100 terakhir.
        $this->tickets = $this->eventner->tickets()
            ->with('venue')
            ->orderBy('created_at', 'desc')
            ->limit(self::BATAS_BARIS)
            ->get();

        $this->drawingRegistrations = Registration::with('competitionCategory')
            ->where('eventner_id', $id)
            ->whereNotNull('qr_token')
            ->orderBy('created_at', 'desc')
            ->limit(self::BATAS_BARIS)
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.eventner.modul')
            ->title('Modul Event: ' . $this->eventner->nama_event . ' - ' . app_name());
    }
}
