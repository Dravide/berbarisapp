<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
#[Title('Dashboard - BARIS APP')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.dashboard.index');
    }
}
