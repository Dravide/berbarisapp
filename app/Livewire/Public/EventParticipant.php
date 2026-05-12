<?php

namespace App\Livewire\Public;

use Livewire\Component;
use App\Models\Eventner;
use Livewire\Attributes\Layout;

#[Layout('layouts.frontend')]
class EventParticipant extends Component
{
    public $eventner;

    public function mount($slug)
    {
        $this->eventner = Eventner::with(['competitionCategories.registrations' => function ($q) {
            $q->where('status_berkas', '!=', 'dibatalkan')
              ->orderBy('urutan_tampil', 'asc');
        }, 'competitionCategories.registrations.participants'])
            ->where('slug', $slug)->firstOrFail();
    }

    public function render()
    {
        return view('livewire.public.event-participant')
            ->title('Daftar Peserta - ' . $this->eventner->nama_event)
            ->layoutData(['eventner' => $this->eventner]);
    }
}
