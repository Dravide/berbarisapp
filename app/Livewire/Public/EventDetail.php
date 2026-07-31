<?php

namespace App\Livewire\Public;

use App\Models\Eventner;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.frontend')]
class EventDetail extends Component
{
    public $eventner;

    // Tab state
    public $tab = 'info';
    public $registration_status;

    protected $queryString = [
        'tab' => ['except' => 'info'],
    ];

    public function mount($slug = null)
    {
        $resolved = app()->bound('current_eventner') ? app('current_eventner') : null;
        if ($resolved) {
            $this->eventner = $resolved;
        } else {
            $this->eventner = Eventner::with([
                'competitionCategories.registrations' => function ($query) {
                    $query->withSum(['voteTransactions as total_votes' => function ($q) {
                        $q->where('status', 'PAID');
                    }], 'votes_earned')
                        ->where('status_berkas', '!=', 'dibatalkan')
                        ->orderBy('urutan_tampil', 'asc');
                },
                'competitionCategories.registrations.participants',
                'judges.assessmentCategories',
                'sponsors' => function ($query) {
                    $query->where('is_active', true)->orderBy('sort_order')->latest();
                },
                'tenants' => function ($query) {
                    $query->where('is_active', true)->orderBy('sort_order')->latest();
                },
                'competitionCategories.children.judges',
                'competitionCategories.children.registrations',
            ])->approved()->where('slug', $slug)->firstOrFail();
        }

        $this->registration_status = $this->eventner->registration_status ?? 'open';

        if (!in_array($this->tab, ['info', 'participants', 'vote'])) {
            $this->tab = 'info';
        }
    }

    public function setTab($tab)
    {
        $this->tab = $tab;
    }

    /**
     * Leaderboard voting: top kontingen per kategori lomba.
     * Hanya tampil kalau vote sudah dimulai — atau sudah selesai (vote_end terlewat).
     */
    #[Computed]
    public function voteLeaderboard()
    {
        $now = now();
        $voteStart = $this->eventner->vote_start ? \Carbon\Carbon::parse($this->eventner->vote_start) : null;
        $voteEnd = $this->eventner->vote_end ? \Carbon\Carbon::parse($this->eventner->vote_end) : null;

        // Sembunyikan kalau vote_start belum sampai
        if ($voteStart && $now->lt($voteStart)) {
            return collect();
        }

        $perCategory = [];

        foreach ($this->eventner->competitionCategories->whereNotNull('parent_id') as $cat) {
            $top = $cat->registrations
                ->sortByDesc('total_votes')
                ->take(5)
                ->values();

            $perCategory[] = [
                'category' => $cat,
                'top' => $top,
            ];
        }

        return empty($perCategory) ? collect() : $perCategory;
    }

    public function render()
    {
        return view('livewire.public.event-detail', [
            'eventner' => $this->eventner,
        ])->title($this->eventner->nama_event . ' - ' . ($this->eventner->diselenggarakan_oleh ?: 'BARIS APP'))
            ->layoutData(['eventner' => $this->eventner]);
    }
}
