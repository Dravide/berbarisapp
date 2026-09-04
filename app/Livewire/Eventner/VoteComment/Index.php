<?php

namespace App\Livewire\Eventner\VoteComment;

use App\Models\Registration;
use App\Models\VoteTransaction;
use App\Traits\FeatureGatedComponent;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Komentar Voting - BARIS APP')]
class Index extends Component
{
    use WithPagination;
    use FeatureGatedComponent;

    protected string $requiredFeature = 'vote_transactions';

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';
    public string $filterRegistration = '';
    public string $filterTier = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterRegistration' => ['except' => ''],
        'filterTier' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    /** Band vote per tier (filter tier = band terkecil yang dicapai). */
    public const TIERS = [
        'mvp' => 1000,
        'legend' => 500,
        'elite' => 100,
        'hot' => 50,
        'populer' => 10,
    ];

    public function mount()
    {
        $this->bootFeatureGate();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterRegistration()
    {
        $this->resetPage();
    }

    public function updatingFilterTier()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'filterRegistration', 'filterTier', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    /** Tier dari votes_earned — tinggi ke rendah, first match wins. */
    public static function tierOf(int $votes): ?string
    {
        return match (true) {
            $votes >= 1000 => 'mvp',
            $votes >= 500 => 'legend',
            $votes >= 100 => 'elite',
            $votes >= 50 => 'hot',
            $votes >= 10 => 'populer',
            default => null,
        };
    }

    public function render()
    {
        $eventner = auth()->user()->eventner;
        abort_unless($eventner, 403, 'Anda belum memiliki data Event terdaftar.');

        $eventnerId = $eventner->id;

        // Daftar kontingen untuk dropdown filter
        $registrations = Registration::where('eventner_id', $eventnerId)
            ->orderBy('nama_sekolah')
            ->with('competitionCategory:id,name,parent_id')
            ->get(['id', 'nama_sekolah', 'label_pasukan', 'npsn', 'competition_category_id']);

        // Query komentar: transaksi PAID dengan isi komentar
        $query = VoteTransaction::query()
            ->where('eventner_id', $eventnerId)
            ->where('status', 'PAID')
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->with(['registration:id,nama_sekolah,label_pasukan,npsn,competition_category_id', 'registration.competitionCategory:id,name']);

        // Filter search (nama pemilih / isi komentar)
        if ($this->search !== '') {
            $needle = '%' . $this->search . '%';
            $query->where(function ($w) use ($needle) {
                $w->where('voter_name', 'like', $needle)
                    ->orWhere('comment', 'like', $needle);
            });
        }

        // Filter kontingen
        if ($this->filterRegistration !== '') {
            $query->where('registration_id', $this->filterRegistration);
        }

        // Filter tier (band terkecil yang dicapai)
        if ($this->filterTier !== '' && isset(self::TIERS[$this->filterTier])) {
            $min = self::TIERS[$this->filterTier];
            $query->where('votes_earned', '>=', $min);
        }

        // Filter rentang tanggal bayar
        if ($this->dateFrom !== '') {
            $query->whereDate('paid_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo !== '') {
            $query->whereDate('paid_at', '<=', $this->dateTo);
        }

        $comments = $query->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->paginate(20);

        // Summary keseluruhan (tanpa filter)
        $base = VoteTransaction::where('eventner_id', $eventnerId)
            ->where('status', 'PAID')
            ->whereNotNull('comment')
            ->where('comment', '!=', '');

        $summary = (clone $base)->selectRaw('COUNT(*) as total_comments, COALESCE(SUM(votes_earned), 0) as total_votes, COUNT(DISTINCT registration_id) as total_registrations')->first();
        $summary->top_tier_count = (clone $base)->where('votes_earned', '>=', 50)->count();

        return view('livewire.eventner.vote-comment.index', [
            'comments' => $comments,
            'registrations' => $registrations,
            'summary' => $summary,
        ])->title('Komentar Voting - ' . $eventner->nama_event);
    }
}
