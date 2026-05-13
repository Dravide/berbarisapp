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
        $user = auth()->user();

        if (!$user->is_active) {
            return;
        }

        if ($user->role === 'Admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($user->role === 'Eventner') {
            return redirect()->route('eventner.dashboard');
        }
    }

    public function render()
    {
        if (!auth()->user()->is_active) {
            return view('livewire.dashboard.inactive');
        }

        return view('livewire.dashboard.index');
    }
}
