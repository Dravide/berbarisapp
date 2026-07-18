<?php

namespace App\Livewire\Eventner\VoteSettings;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.admin')]
#[Title('Pengaturan Vote')]
class Index extends Component
{
    public $vote_active = false;
    public $vote_price = 1000;

    public function mount()
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) abort(403);

        $this->vote_active = (bool) $eventner->vote_active;
        $this->vote_price = $eventner->vote_price ?? 1000;
    }

    public function save()
    {
        $eventner = Auth::user()->eventner;
        $eventner->update([
            'vote_active' => $this->vote_active,
            'vote_price' => $this->vote_price,
        ]);
        session()->flash('success', 'Pengaturan vote berhasil disimpan.');
    }

    public function render()
    {
        return view('livewire.eventner.vote-settings.index');
    }
}
