<?php

namespace App\Livewire\Eventner\Drawing;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Locked;
use App\Models\Registration;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\CompetitionRoundRegistration;

use App\Models\Eventner;

#[Layout('layouts.scoreboard')]
class Results extends Component
{
    public $slug;

    // Dikunci server-side — client tidak boleh ganti eventner.
    #[Locked]
    public $eventnerId;

    public $activeTab = '';
    public $categories = [];

    /**
     * Grup yang sedang dilihat. '' = seluruh tingkat (perilaku lama).
     */
    public $activeGroupId = '';

    /**
     * Babak yang sedang dilihat. '' = penyisihan / tingkat tanpa babak.
     */
    public $activeRoundId = '';

    /** Daftar grup tingkat terpilih (untuk pemilih di view). */
    public $groups = [];

    /** Daftar babak tingkat terpilih (untuk pemilih di view). */
    public $rounds = [];

    public function mount($slug = null)
    {
        $resolved = app()->bound('current_eventner') ? app('current_eventner') : null;
        if ($resolved) {
            $this->slug = $resolved->slug;
            $eventner = $resolved;
        } else {
            $this->slug = $slug;
            $eventner = Eventner::where('slug', $slug)->firstOrFail();
        }
        $this->eventnerId = $eventner->id;

        if ($eventner) {
            $this->categories = $eventner->competitionCategories()
                ->whereNotNull('parent_id')
                ->with('parent')
                ->get()
                ->toArray();
        }

        if (count($this->categories) > 0) {
            $this->activeTab = $this->categories[0]['id'];
        }

        $this->groups = $this->groupsOfActiveTab();
        $this->rounds = $this->roundsOfActiveTab();
    }

    /**
     * Grup milik tingkat yang sedang dilihat.
     */
    private function groupsOfActiveTab(): array
    {
        if ($this->activeTab === '') {
            return [];
        }

        return CompetitionGroup::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $this->activeTab)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn ($g) => ['id' => (string) $g->id, 'name' => $g->name])
            ->all();
    }

    /**
     * Babak milik tingkat yang sedang dilihat.
     */
    private function roundsOfActiveTab(): array
    {
        if ($this->activeTab === '') {
            return [];
        }

        return CompetitionRound::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $this->activeTab)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn ($r) => [
                'id' => (string) $r->id,
                'name' => $r->name,
                'isFinal' => $r->isFinal(),
            ])
            ->all();
    }

    /** Babak yang sedang dilihat, atau null bila tingkat ini tanpa babak. */
    private function activeRound(): ?CompetitionRound
    {
        if ($this->activeRoundId === '') {
            return null;
        }

        return CompetitionRound::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $this->activeTab)
            ->find($this->activeRoundId);
    }

    /** Apakah yang sedang dilihat adalah babak final. */
    private function isFinalRound(): bool
    {
        return (bool) $this->activeRound()?->isFinal();
    }

    /**
     * Registrasi tingkat + grup + babak yang sedang dilihat. Pool undian final
     * satu tingkat, jadi di babak final saringan grup dimatikan.
     */
    private function scopedRegistrations()
    {
        return Registration::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $this->activeTab)
            ->when(! $this->isFinalRound() && $this->activeGroupId !== '', function ($q) {
                $q->where('competition_group_id', $this->activeGroupId);
            })
            ->when($this->isFinalRound(), function ($q) {
                $q->whereIn('id', CompetitionRoundRegistration::where('eventner_id', $this->eventnerId)
                    ->where('competition_round_id', $this->activeRound()->id)
                    ->pluck('registration_id'));
            });
    }

    public function switchTab($categoryId)
    {
        $this->activeTab = $categoryId;
        $this->activeGroupId = '';
        $this->activeRoundId = '';
        $this->groups = $this->groupsOfActiveTab();
        $this->rounds = $this->roundsOfActiveTab();
    }

    /**
     * Ganti babak. Babak dari DOM wajib milik tingkat yang sedang dilihat.
     */
    public function switchRound($roundId)
    {
        $this->activeRoundId = '';
        $this->activeGroupId = '';

        $ada = collect($this->rounds)->firstWhere('id', (string) $roundId);

        if ($roundId !== '' && $roundId !== null && ! $ada) {
            $this->addError('activeRoundId', 'Babak tidak ditemukan pada tingkat lomba ini.');

            return;
        }

        if ($ada) {
            $this->activeRoundId = (string) $ada['id'];
        }

        $this->resetErrorBag('activeRoundId');
    }

    /**
     * Ganti grup. Grup dari DOM wajib milik tingkat yang sedang dilihat.
     */
    public function switchGroup($groupId)
    {
        $this->activeGroupId = '';

        if ($this->isFinalRound()) {
            return;
        }

        if ($groupId !== '' && $groupId !== null) {
            $ada = CompetitionGroup::where('eventner_id', $this->eventnerId)
                ->where('competition_category_id', $this->activeTab)
                ->find($groupId);

            if (! $ada) {
                $this->addError('activeGroupId', 'Grup tidak ditemukan pada tingkat lomba ini.');

                return;
            }

            $this->activeGroupId = (string) $ada->id;
        }

        $this->resetErrorBag('activeGroupId');
    }

    public function render()
    {
        $eventner = Eventner::findOrFail($this->eventnerId);

        // Di babak final nomor undiannya hidup di baris babak, bukan di kolom
        // registrasi.
        if ($this->isFinalRound()) {
            $nomorPerRegistrasi = CompetitionRoundRegistration::where('eventner_id', $this->eventnerId)
                ->where('competition_round_id', $this->activeRound()->id)
                ->whereNotNull('urutan_tampil')
                ->pluck('urutan_tampil', 'registration_id');

            $results = $this->scopedRegistrations()
                ->whereIn('id', $nomorPerRegistrasi->keys())
                ->get()
                ->each(fn ($reg) => $reg->urutan_tampil = $nomorPerRegistrasi->get($reg->id))
                ->sortBy('urutan_tampil')
                ->values();
        } else {
            $results = $this->scopedRegistrations()
                ->whereNotNull('urutan_tampil')
                ->orderBy('urutan_tampil')
                ->get();
        }

        $category = \App\Models\CompetitionCategory::where('eventner_id', $eventner->id)
            ->find($this->activeTab);
        $totalSchools = $this->activeGroupId !== ''
            ? $this->scopedRegistrations()->count()
            : ($category->kuota ?? $this->scopedRegistrations()->count());

        return view('livewire.eventner.drawing.results', [
            'results' => $results,
            'totalSchools' => $totalSchools,
            'eventner' => $eventner,
        ])->layoutData(['eventner' => $eventner])->title('Hasil Pengundian - ' . app_name());
    }
}
