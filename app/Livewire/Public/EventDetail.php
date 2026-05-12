<?php

namespace App\Livewire\Public;

use Livewire\Component;
use App\Models\Eventner;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.frontend')]
class EventDetail extends Component
{
    public $eventner;

    public function mount($slug)
    {
        $this->eventner = Eventner::with([
            'competitionCategories.registrations' => function ($query) {
                $query->withSum(['voteTransactions as total_votes' => function($q) {
                    $q->where('status', 'PAID');
                }], 'votes_earned');
            }, 
            'competitionCategories.registrations.participants',
            'judges.assessmentCategories'
        ])
            ->where('slug', $slug)->firstOrFail();
    }

    #[Title('Detail Event')]
    public function render()
    {
        return view('livewire.public.event-detail', [
            'eventner' => $this->eventner
        ])->title($this->eventner->nama_event . ' - BARIS APP')
         ->layoutData(['eventner' => $this->eventner]);
    }
}
