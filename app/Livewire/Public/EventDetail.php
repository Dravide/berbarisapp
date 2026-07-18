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

    public function mount($slug)
    {
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
        ])->where('slug', $slug)->firstOrFail();

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
     * Keyed by competition_category_id, value: top 5 registrasi dengan vote PAID tertinggi.
     */
    #[Computed]
    public function voteLeaderboard()
    {
        $perCategory = [];

        foreach ($this->eventner->competitionCategories as $cat) {
            $top = $cat->registrations
                ->sortByDesc('total_votes')
                ->take(5)
                ->values();

            $perCategory[] = [
                'category' => $cat,
                'top' => $top,
            ];
        }

        return $perCategory;
    }

    #[Title('Detail Event')]
    public function render()
    {
        return view('livewire.public.event-detail', [
            'eventner' => $this->eventner,
        ])->title($this->eventner->nama_event . ' - BARIS APP')
            ->layoutData(['eventner' => $this->eventner]);
    }
}
