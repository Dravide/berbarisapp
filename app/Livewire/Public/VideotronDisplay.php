<?php

namespace App\Livewire\Public;

use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\CompetitionRoundRegistration;
use App\Models\Eventner;
use App\Models\EventRundown;
use App\Models\LandingPartner;
use App\Models\Registration;
use App\Models\Sponsor;
use App\Models\VoteTransaction;
use Livewire\Component;
use Livewire\Attributes\Layout;

/**
 * Videotron Display — layar big-screen di venue, beda kasus dari overlay OBS.
 * Overlay dikomposit di atas video; halaman ini ditampilkan UTUH di layar
 * venue (welcome, urutan tampil, klasemen vote, juara, rundown, sponsor,
 * loop). Tipografi besar, kontras tinggi, satu ide per layar, tanpa operator:
 * semua mode terisi dari data yang sudah ada, segar via wire:poll.
 */
#[Layout('layouts.videotron')]
class VideotronDisplay extends Component
{
    public $eventner;

    /** Mode konten aktif; mode tak dikenal jatuh ke welcome. */
    public string $mode = 'welcome';

    public array $modes = [];

    protected $queryString = [
        'mode' => ['except' => 'welcome'],
        'categoryId' => ['except' => null],
        'groupId' => ['except' => null],
        'championId' => ['except' => null],
    ];

    /** Skop opsional per mode (tingkat / grup / kategori juara). */
    public $categoryId = null;
    public $groupId = null;
    public $championId = null;

    /** Hasil per mode, diisi ulang setiap refreshData(). */
    public $categories = [];
    public $groups = [];
    public $drawingQueue = [];
    public $drawingDone = [];
    public $topVote = [];
    public $totalVoteCount = 0;
    public $championRanking = null;
    public $championName = null;
    public $championTitle = null;
    public $rundowns = [];
    public $sponsorLogos = [];

    protected $allowedModes = ['welcome', 'drawing', 'vote', 'champion', 'rundown', 'sponsor', 'loop'];

    public function mount($slug = null)
    {
        $resolved = app()->bound('current_eventner') ? app('current_eventner') : null;
        if ($resolved) {
            $this->eventner = $resolved;
        } else {
            $this->eventner = Eventner::approved()->where('slug', $slug)->firstOrFail();
        }

        if (! in_array($this->mode, $this->allowedModes, true)) {
            $this->mode = 'welcome';
        }

        $this->modes = $this->allowedModes;

        $this->categories = CompetitionCategory::where('eventner_id', $this->eventner->id)
            ->whereNotNull('parent_id')
            ->with('parent')
            ->orderBy('name')
            ->get();

        // Skop drawing butuh daftar grup tingkat terpilih.
        if ($this->categoryId) {
            $this->groups = CompetitionGroup::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $this->categoryId)
                ->orderBy('sort_order')->orderBy('name')
                ->get();
        }

        $this->loadSponsors();
        $this->refreshData();
    }

    public function refreshData()
    {
        // Mode loop memutar semua konten — muat semuanya.
        if (in_array($this->mode, ['loop', 'drawing', 'vote', 'champion', 'rundown'], true)) {
            $this->loadDrawing();
        }
        if (in_array($this->mode, ['loop', 'vote'], true)) {
            $this->loadVoteData();
        }
        if (in_array($this->mode, ['loop', 'champion'], true)) {
            $this->loadChampion();
        }
        if (in_array($this->mode, ['loop', 'rundown'], true)) {
            $this->loadRundowns();
        }
        if ($this->mode === 'loop') {
            $this->loadSponsors();
        }
    }

    /**
     * Urutan tampil: pola Drawing\Results — penyisihan baca
     * registrations.urutan_tampil, final baca baris babak
     * (CompetitionRoundRegistration). Tiga yang sudah tampil dipisah
     * ke footer; yang menunggu jadi antrean utama.
     */
    private function loadDrawing()
    {
        $this->drawingQueue = [];
        $this->drawingDone = [];

        if (! $this->categoryId) {
            return;
        }

        $finalRound = $this->groupId === null
            ? CompetitionRound::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $this->categoryId)
                ->get()
                ->first(fn ($r) => $r->isFinal())
            : null;

        if ($finalRound) {
            $nomor = CompetitionRoundRegistration::where('eventner_id', $this->eventner->id)
                ->where('competition_round_id', $finalRound->id)
                ->whereNotNull('urutan_tampil')
                ->pluck('urutan_tampil', 'registration_id');

            $semua = Registration::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $this->categoryId)
                ->whereIn('id', $nomor->keys())
                ->get()
                ->each(fn ($reg) => $reg->urutan_tampil = $nomor->get($reg->id))
                ->sortBy('urutan_tampil')
                ->values();
        } else {
            $semua = Registration::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $this->categoryId)
                ->when($this->groupId, fn ($q) => $q->where('competition_group_id', $this->groupId))
                ->whereNotNull('urutan_tampil')
                ->orderBy('urutan_tampil')
                ->get();
        }

        $this->drawingQueue = $semua->values()
            ->map(fn ($reg) => [
                'id' => $reg->id,
                'no' => $reg->urutan_tampil,
                'nama' => $reg->display_name,
            ])
            ->all();

        $this->drawingDone = array_splice($this->drawingQueue, 0, 3);
    }

    /** Klasemen vote — salin loadVoteData() overlay: hanya transaksi PAID. */
    private function loadVoteData()
    {
        $this->topVote = Registration::where('eventner_id', $this->eventner->id)
            ->when($this->categoryId, fn ($q) => $q->where('competition_category_id', $this->categoryId))
            ->withSum(['voteTransactions as total_votes' => function ($q) {
                $q->where('status', 'PAID');
            }], 'votes_earned')
            ->orderByDesc('total_votes')
            ->limit(10)
            ->get()
            ->filter(fn ($reg) => ($reg->total_votes ?? 0) > 0)
            ->values()
            ->toArray();

        $this->totalVoteCount = VoteTransaction::where('eventner_id', $this->eventner->id)
            ->where('status', 'PAID')
            ->when($this->categoryId, fn ($q) => $q->whereHas('registration', fn ($r) => $r->where('competition_category_id', $this->categoryId)))
            ->sum('votes_earned');
    }

    /**
     * Pengumuman juara satu kategori — perhitungan sama persis dengan
     * /champions (ChampionCalculator via peringkat di Champions\Index),
     * sumber cache yang sama. Hanya kategori is_public.
     */
    private function loadChampion()
    {
        $this->championRanking = null;
        $this->championName = null;
        $this->championTitle = null;

        $kategori = ChampionCategory::where('eventner_id', $this->eventner->id)
            ->where('is_public', true)
            ->when($this->championId, fn ($q) => $q->where('id', $this->championId))
            ->first();

        if (! $kategori) {
            return;
        }

        $this->championName = $kategori->name;

        $component = new \App\Livewire\Public\Champions\Index();
        $component->eventner = $this->eventner;
        $component->selectedCategoryId = $this->categoryId ?? $this->categories->first()?->id;
        $component->selectedGroupId = (string) ($this->groupId ?? '');
        $component->calculateRankings();

        $first = collect($component->allRankings)->firstWhere('champion.id', $kategori->id)
            ?? collect($component->allRankings)->first();

        if ($first && isset($first['participants'][0])) {
            $this->championRanking = collect($first['participants'])
                ->take(3)
                ->map(fn ($ps) => [
                    'rank' => $ps['rank'],
                    'title' => $ps['title'],
                    'nama' => $ps['participant']->display_name,
                    'total' => $ps['total'],
                ])
                ->all();
            $this->championTitle = $first['participants'][0]['title'] ?? $kategori->name;
        }
    }

    /** Rundown hari ini — baris "sekarang" ditentukan dari start_time. */
    private function loadRundowns()
    {
        $this->rundowns = EventRundown::where('eventner_id', $this->eventner->id)
            ->orderBy('start_time')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'title' => $r->title,
                'start' => $r->start_time?->format('H.i'),
            ])
            ->all();
    }

    /** Gabungan sponsor platform (landing_partners) + sponsor event, aktif. */
    private function loadSponsors()
    {
        $platform = LandingPartner::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($p) => ['name' => $p->name, 'logo' => $p->logo, 'type' => $p->type]);

        $event = Sponsor::where('eventner_id', $this->eventner->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($s) => ['name' => $s->name, 'logo' => $s->logo, 'type' => $s->type]);

        $this->sponsorLogos = $platform->merge($event)->values()->all();
    }

    public function render()
    {
        $rundownSekarang = null;
        if ($this->mode === 'rundown' || $this->mode === 'loop') {
            $sekarang = now()->format('H:i');
            foreach ($this->rundowns as $i => $r) {
                $berikut = $this->rundowns[$i + 1]['start'] ?? null;
                if ($r['start'] <= $sekarang && ($berikut === null || $sekarang < $berikut)) {
                    $rundownSekarang = $r['id'];
                    break;
                }
            }
        }

        return view('livewire.public.videotron-display', [
            'rundownSekarang' => $rundownSekarang,
        ])->title('Videotron - ' . $this->eventner->nama_event);
    }
}
