<?php

namespace App\Livewire\Eventner;

use Livewire\Component;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\VoteTransaction;
use App\Models\Judge;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
class Dashboard extends Component
{
    public $eventner;
    public $totalRevenue = 0;
    public $totalRegistrations = 0;
    public $totalCategories = 0;
    public $totalJudges = 0;
    public $recentRegistrations;

    public function mount()
    {
        $this->eventner = Auth::user()->eventner;

        if (!$this->eventner) {
            abort(403, 'Anda belum memiliki data Event terdaftar.');
        }

        $this->loadData();
    }

    public function loadData()
    {
        $eventnerId = $this->eventner->id;

        // Stats
        $this->totalRevenue = VoteTransaction::where('eventner_id', $eventnerId)
            ->where('status', 'PAID')
            ->sum('amount');

        $this->totalRegistrations = Registration::where('eventner_id', $eventnerId)->count();
        $this->totalCategories = $this->eventner->competitionCategories()->count();
        $this->totalJudges = Judge::where('eventner_id', $eventnerId)->count();

        // Recent registrations
        $this->recentRegistrations = Registration::with('competitionCategory')
            ->where('eventner_id', $eventnerId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    public function render()
    {
        return view('livewire.eventner.dashboard')
            ->title('Dashboard Event - ' . $this->eventner->nama_event);
    }
}
