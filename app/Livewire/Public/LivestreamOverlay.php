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
    public $selectedCategoryId = null;
    public $selectedCategory = null;

    protected $queryString = [
        'mode' => ['except' => 'full'],
        'selectedCategoryId' => ['except' => null],
    ];

    protected $allowedModes = ['full', 'greenscreen', 'vote', 'comments', 'category', 'kegiatan', 'custom'];

    public function mount($slug = null)
    {
        $resolved = app()->bound('current_eventner') ? app('current_eventner') : null;
        if ($resolved) {
            $this->eventner = $resolved;
        } else {
            $this->eventner = Eventner::approved()->where('slug', $slug)->firstOrFail();
        }

        // Validate mode
        if (!in_array($this->mode, $this->allowedModes)) {
            $this->mode = 'full';
        }

        // Load competition categories (except greenscreen + comments mode)
        if (!in_array($this->mode, ['greenscreen', 'comments'])) {
            $this->categories = CompetitionCategory::where('eventner_id', $this->eventner->id)
                ->whereNotNull('parent_id')
                ->with('parent')
                ->withCount('registrations')
                ->orderBy('name')
                ->get();
        }

        // Resolve kategori untuk mode category
        if ($this->mode === 'category') {
            if (!$this->selectedCategoryId && $this->categories->isNotEmpty()) {
                $this->selectedCategoryId = $this->categories->first()->id;
            }
            if ($this->selectedCategoryId) {
                $this->selectedCategory = $this->categories->firstWhere('id', $this->selectedCategoryId);
            }
        }

        // Load vote data for modes that need it
        if (in_array($this->mode, ['full', 'vote', 'custom'])) {
            $this->loadVoteData();
        }

        // Load vote data per kategori untuk mode category
        if ($this->mode === 'category') {
            $this->loadVoteData($this->selectedCategoryId);
        }

        // Load comments for full + comments + custom modes
        if (in_array($this->mode, ['full', 'comments', 'custom'])) {
            $this->loadComments();
        }

        // Load comments per kategori untuk mode category
        if ($this->mode === 'category') {
            $this->loadComments($this->selectedCategoryId);
        }

        // Load custom overlay settings
        if ($this->mode === 'custom') {
            $this->overlaySetting = \App\Models\OverlaySetting::where('eventner_id', $this->eventner->id)->first();
        }
    }

    public function switchCategory($categoryId)
    {
        $this->selectedCategoryId = $categoryId;
        $this->selectedCategory = $this->categories->firstWhere('id', $categoryId);
        $this->loadVoteData($this->selectedCategoryId);
        $this->loadComments($this->selectedCategoryId);
    }

    public function refreshVoteData()
    {
        if (in_array($this->mode, ['full', 'vote', 'custom'])) {
            $this->loadVoteData();
        }
        if (in_array($this->mode, ['full', 'comments', 'custom'])) {
            $this->loadComments();
        }
        if ($this->mode === 'category') {
            $this->loadVoteData($this->selectedCategoryId);
            $this->loadComments($this->selectedCategoryId);
        }
    }

    private function loadComments($categoryId = null)
    {
        $this->overlayComments = \App\Models\VoteTransaction::where('eventner_id', $this->eventner->id)
            ->where('status', 'PAID')
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->when($categoryId, fn ($q) => $q->whereHas('registration', fn ($r) => $r->where('competition_category_id', $categoryId)))
            ->with('registration')
            ->orderByDesc('paid_at')
            ->limit(50)
            ->get()
            ->toArray();
    }

    private function loadVoteData($categoryId = null)
    {
        $this->topVote = Registration::where('eventner_id', $this->eventner->id)
            ->when($categoryId, fn ($q) => $q->where('competition_category_id', $categoryId))
            ->withSum(['voteTransactions as total_votes' => function ($q) {
                $q->where('status', 'PAID');
            }], 'votes_earned')
            ->orderByDesc('total_votes')
            ->limit(10)
            ->get()
            ->toArray();

        $this->totalVoteCount = VoteTransaction::where('eventner_id', $this->eventner->id)
            ->where('status', 'PAID')
            ->when($categoryId, fn ($q) => $q->whereHas('registration', fn ($r) => $r->where('competition_category_id', $categoryId)))
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
            'selectedCategory' => $this->selectedCategory,
        ])->title('Livestream Overlay - ' . $this->eventner->nama_event);
    }
}
