<?php

namespace App\Livewire\Eventner\Livestream;

use App\Models\Eventner;
use App\Models\OverlaySetting;
use App\Models\VoteTransaction;
use App\Models\Registration;
use App\Models\CompetitionCategory;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.admin')]
#[Title('Livestream Overlay')]
class Manage extends Component
{
    public $eventner;
    public $overlaySetting;
    public $totalVoteCount = 0;
    public $totalParticipants = 0;
    public $categories = [];

    // Toggle booleans for overlay components
    public $show_header = true;
    public $show_vote_leaderboard = true;
    public $show_kegiatan = true;
    public $show_footer = true;
    public $marquee_text = '';
    public $components = [];

    public function mount()
    {
        $this->eventner = Auth::user()->eventner;

        if (!$this->eventner) {
            abort(403, 'Anda belum memiliki data Event terdaftar.');
        }

        $this->loadStats();
        $this->loadSettings();
    }

    private function loadStats()
    {
        $this->totalVoteCount = VoteTransaction::where('eventner_id', $this->eventner->id)
            ->where('status', 'PAID')
            ->sum('votes_earned');

        $categories = CompetitionCategory::where('eventner_id', $this->eventner->id)
            ->whereNotNull('parent_id')
            ->withCount('registrations')
            ->get();

        $this->categories = $categories;
        $this->totalParticipants = 0;
        foreach ($categories as $cat) {
            $this->totalParticipants += $cat->registrations_count ?? 0;
        }
    }

    private function loadSettings()
    {
        $this->overlaySetting = OverlaySetting::firstOrCreate(
            ['eventner_id' => $this->eventner->id],
            [
                'show_header' => true,
                'show_vote_leaderboard' => true,
                'show_kegiatan' => true,
                'show_footer' => true,
                'marquee_text' => null,
                'components' => [],
            ]
        );

        $this->show_header = $this->overlaySetting->show_header;
        $this->show_vote_leaderboard = $this->overlaySetting->show_vote_leaderboard;
        $this->show_kegiatan = $this->overlaySetting->show_kegiatan;
        $this->show_footer = $this->overlaySetting->show_footer;
        $this->marquee_text = $this->overlaySetting->marquee_text ?? '';
    }

    public function saveSettings()
    {
        $this->overlaySetting->update([
            'show_header' => $this->show_header,
            'show_vote_leaderboard' => $this->show_vote_leaderboard,
            'show_kegiatan' => $this->show_kegiatan,
            'show_footer' => $this->show_footer,
            'marquee_text' => $this->marquee_text,
        ]);

        session()->flash('success', 'Pengaturan overlay berhasil disimpan.');
        $this->loadStats();
    }

    public function render()
    {
        return view('livewire.eventner.livestream.manage');
    }
}
