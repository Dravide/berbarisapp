<?php

namespace App\Livewire\Admin\Eventner;

use Livewire\Component;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\VoteTransaction;
use App\Models\CompetitionCategory;
use App\Models\Judge;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
class Show extends Component
{
    public $eventnerId;
    public $eventner;
    public $totalRevenue = 0;
    public $totalRegistrations = 0;
    public $totalCategories = 0;
    public $totalJudges = 0;
    public $recentRegistrations;

    public function mount($id)
    {
        $this->eventnerId = $id;
        $this->loadData();
    }

    public function loadData()
    {
        $this->eventner = Eventner::with(['user', 'competitionCategories'])
            ->findOrFail($this->eventnerId);

        // Stats
        $this->totalRevenue = VoteTransaction::where('eventner_id', $this->eventnerId)
            ->where('status', 'PAID')
            ->sum('amount');

        $this->totalRegistrations = Registration::where('eventner_id', $this->eventnerId)->count();
        $this->totalCategories = $this->eventner->competitionCategories()->count();
        
        // Count unique judges in this eventner (through categories)
        $this->totalJudges = Judge::where('eventner_id', $this->eventnerId)->count();

        // Recent registrations
        $this->recentRegistrations = Registration::with('competitionCategory')
            ->where('eventner_id', $this->eventnerId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.eventner.show')
            ->title('Detail Event: ' . $this->eventner->nama_event . ' - BARIS APP');
    }
}
