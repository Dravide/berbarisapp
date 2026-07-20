<?php

namespace App\Livewire\Public;

use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\VoteTransaction;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.livestream')]
class LivestreamOverlay extends Component
{
    public $eventner;
    public $mode = 'full';
    public $categories = [];
    public $topVote = [];
    public $totalVoteCount = 0;
    public $overlaySetting = null;
    public $overlayComments = [];
    public $currentCommentIndex = 0;

    protected $queryString = [
        'mode' => ['except' => 'full'],
    ];

    protected $allowedModes = ['full', 'greenscreen', 'vote', 'kegiatan', 'custom'];

    public function mount($slug)
    {
        $this->eventner = Eventner::where('slug', $slug)->firstOrFail();

        // Validate mode
        if (!in_array($this->mode, $this->allowedModes)) {
            $this->mode = 'full';
        }

        // Load competition categories (except greenscreen mode)
        if ($this->mode !== 'greenscreen') {
            $this->categories = CompetitionCategory::where('eventner_id', $this->eventner->id)
                ->whereNotNull('parent_id')
                ->with('parent')
                ->withCount('registrations')
                ->orderBy('name')
                ->get();
        }

        // Load vote data for modes that need it
        if (in_array($this->mode, ['full', 'vote', 'custom'])) {
            $this->loadVoteData();
        }

        // Load comments for full + custom modes
        if (in_array($this->mode, ['full', 'custom'])) {
            $this->loadComments();
        }

        // Load custom overlay settings
        if ($this->mode === 'custom') {
            $this->overlaySetting = \App\Models\OverlaySetting::where('eventner_id', $this->eventner->id)->first();
        }
    }

    public function refreshVoteData()
    {
        if (in_array($this->mode, ['full', 'vote', 'custom'])) {
            $this->loadVoteData();
        }
        if (in_array($this->mode, ['full', 'custom'])) {
            $this->loadComments();
        }
    }

    private function loadComments()
    {
        $this->overlayComments = \App\Models\VoteTransaction::where('eventner_id', $this->eventner->id)
            ->where('status', 'PAID')
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->with('registration')
            ->orderByDesc('paid_at')
            ->limit(50)
            ->get()
            ->toArray();
    }

    private function loadVoteData()
    {
        $this->topVote = Registration::where('eventner_id', $this->eventner->id)
            ->withSum(['voteTransactions as total_votes' => function ($q) {
                $q->where('status', 'PAID');
            }], 'votes_earned')
            ->orderByDesc('total_votes')
            ->limit(10)
            ->get()
            ->toArray();

        $this->totalVoteCount = VoteTransaction::where('eventner_id', $this->eventner->id)
            ->where('status', 'PAID')
            ->sum('votes_earned');
    }

    public function render()
    {
        $categoriesData = $this->categories;
        $topVoteData = $this->topVote;

        $totalParticipants = 0;
        if ($categoriesData) {
            foreach ($categoriesData as $cat) {
                $totalParticipants += $cat->registrations_count ?? 0;
            }
        }

        return view('livewire.public.livestream-overlay', [
            'categoriesData' => $categoriesData,
            'topVoteData' => $topVoteData,
            'totalParticipants' => $totalParticipants,
            'overlaySetting' => $this->overlaySetting,
            'overlayComments' => $this->overlayComments,
        ])->title('Livestream Overlay - ' . $this->eventner->nama_event);
    }
}
