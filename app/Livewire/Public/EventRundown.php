<?php

namespace App\Livewire\Public;

use Livewire\Component;
use App\Models\Eventner;
use Livewire\Attributes\Layout;

#[Layout('layouts.frontend')]
class EventRundown extends Component
{
    public $eventner;

    public function mount($slug = null)
    {
        $resolved = app()->bound('current_eventner') ? app('current_eventner') : null;
        if ($resolved) {
            $this->eventner = $resolved;
            $this->eventner->loadMissing('eventRundowns');
        } else {
            $this->eventner = Eventner::with('eventRundowns')
                ->approved()->where('slug', $slug)->firstOrFail();
        }
    }

    public function render()
    {
        return view('livewire.public.event-rundown')
            ->title('Rundown Acara - ' . $this->eventner->nama_event)
            ->layoutData(['eventner' => $this->eventner]);
    }
}
