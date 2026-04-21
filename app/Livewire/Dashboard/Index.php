<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
#[Title('Dashboard - BARIS APP')]
class Index extends Component
{
    public function mount()
    {
        $role = auth()->user()->role;

        if ($role === 'Admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($role === 'Eventner') {
            return redirect()->route('eventner.dashboard');
        }
    }

    public function render()
    {
        return view('livewire.dashboard.index');
    }
}
