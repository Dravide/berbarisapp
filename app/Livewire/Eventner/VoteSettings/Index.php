<?php

namespace App\Livewire\Eventner\VoteSettings;

use App\Traits\FeatureGatedComponent;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.admin')]
#[Title('Pengaturan Vote')]
class Index extends Component
{
    use FeatureGatedComponent;

    protected string $requiredFeature = 'vote_settings';

    public $vote_active = false;
    public $vote_price = 1000;
    public $vote_start = '';
    public $vote_end = '';

    public function mount()
    {
        $this->bootFeatureGate();
        $eventner = Auth::user()->eventner;
        if (!$eventner) abort(403);

        $this->vote_active = (bool) $eventner->vote_active;
        $this->vote_price = $eventner->vote_price ?? 1000;
        $this->vote_start = $eventner->vote_start ? \Carbon\Carbon::parse($eventner->vote_start)->format('Y-m-d\TH:i') : '';
        $this->vote_end = $eventner->vote_end ? \Carbon\Carbon::parse($eventner->vote_end)->format('Y-m-d\TH:i') : '';
    }

    public function save()
    {
        $eventner = Auth::user()->eventner;
        $eventner->update([
            'vote_active' => $this->vote_active,
            'vote_price' => $this->vote_price,
            'vote_start' => $this->vote_start ?: null,
            'vote_end' => $this->vote_end ?: null,
        ]);
        session()->flash('success', 'Pengaturan vote berhasil disimpan.');
    }

    public function render()
    {
        return view('livewire.eventner.vote-settings.index');
    }
}
