<?php

namespace App\Livewire\Eventner\VoteBooster;

use App\Models\VoteBooster;
use App\Traits\FeatureGatedComponent;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.admin')]
#[Title('Vote Booster')]
class Index extends Component
{
    use FeatureGatedComponent;

    protected string $requiredFeature = 'vote_booster';

    public $starts_at = '';
    public $ends_at = '';
    public $vote_multiplier = 2;

    public function mount()
    {
        $this->bootFeatureGate();
        if (!Auth::user()->eventner) abort(403);
    }

    public function save()
    {
        $this->validate([
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'vote_multiplier' => 'required|integer|min:2|max:100',
        ]);

        VoteBooster::create([
            'eventner_id' => Auth::user()->eventner->id,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'vote_multiplier' => $this->vote_multiplier,
            'is_active' => true,
        ]);

        session()->flash('success', 'Vote Booster berhasil ditambahkan.');
        $this->reset(['starts_at', 'ends_at', 'vote_multiplier']);
        $this->vote_multiplier = 2;
    }

    public function delete($id)
    {
        VoteBooster::where('eventner_id', Auth::user()->eventner->id)->findOrFail($id)->delete();
        session()->flash('success', 'Vote Booster dihapus.');
    }

    public function toggleActive($id)
    {
        $booster = VoteBooster::where('eventner_id', Auth::user()->eventner->id)->findOrFail($id);
        $booster->update(['is_active' => !$booster->is_active]);
    }

    public function render()
    {
        $eventner = Auth::user()->eventner;

        $boosters = VoteBooster::where('eventner_id', $eventner->id)
            ->orderByDesc('starts_at')
            ->get();

        $activeNow = VoteBooster::where('eventner_id', $eventner->id)
            ->active()
            ->orderByDesc('vote_multiplier')
            ->first();

        return view('livewire.eventner.vote-booster.index', [
            'boosters' => $boosters,
            'activeNow' => $activeNow,
            'eventner' => $eventner,
        ]);
    }
}
