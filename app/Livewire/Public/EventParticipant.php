<?php

namespace App\Livewire\Public;

use Livewire\Component;
use App\Models\Eventner;
use Livewire\Attributes\Layout;

#[Layout('layouts.frontend')]
class EventParticipant extends Component
{
    public $eventner;

    public function mount($slug = null)
    {
        $resolved = app()->bound('current_eventner') ? app('current_eventner') : null;
        if ($resolved) {
            $this->eventner = $resolved;
            // Subdomain: load relasi if not yet loaded
            $this->eventner->loadMissing(['competitionCategories' => function ($q) {
                    $q->whereNotNull('parent_id')->orderBy('sort_order');
                }, 'competitionCategories.registrations' => function ($q) {
                    $q->where('status_berkas', '!=', 'dibatalkan')
                      ->orderBy('urutan_tampil', 'asc');
                }, 'competitionCategories.registrations.participants']);
        } else {
            $this->eventner = Eventner::with(['competitionCategories' => function ($q) {
                $q->whereNotNull('parent_id')->orderBy('sort_order');
            }, 'competitionCategories.registrations' => function ($q) {
                $q->where('status_berkas', '!=', 'dibatalkan')
                  ->orderBy('urutan_tampil', 'asc');
            }, 'competitionCategories.registrations.participants'])
                ->approved()->where('slug', $slug)->firstOrFail();
        }
    }

    public function render()
    {
        return view('livewire.public.event-participant')
            ->title('Daftar Peserta - ' . $this->eventner->nama_event)
            ->layoutData(['eventner' => $this->eventner]);
    }
}
