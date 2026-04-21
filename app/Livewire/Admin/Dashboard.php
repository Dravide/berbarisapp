<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\VoteTransaction;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
#[Title('Admin Dashboard - BARIS APP')]
class Dashboard extends Component
{
    public $totalEventners = 0;
    public $totalRegistrations = 0;
    public $totalRevenue = 0;

    public function mount()
    {
        $this->totalEventners = Eventner::count();
        $this->totalRegistrations = Registration::count();
        $this->totalRevenue = VoteTransaction::where('status', 'PAID')->sum('amount');
    }

    public function render()
    {
        return view('livewire.admin.dashboard');
    }
}
