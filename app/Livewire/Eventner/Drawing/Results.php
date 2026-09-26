<?php

namespace App\Livewire\Eventner\Drawing;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Locked;
use App\Models\Registration;
use App\Models\CompetitionGroup;

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

    /** Daftar grup tingkat terpilih (untuk pemilih di view). */
    public $groups = [];

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
     * Registrasi tingkat + grup yang sedang dilihat.
     */
    private function scopedRegistrations()
    {
        return Registration::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $this->activeTab)
            ->when($this->activeGroupId !== '', function ($q) {
                $q->where('competition_group_id', $this->activeGroupId);
            });
    }

    public function switchTab($categoryId)
    {
        $this->activeTab = $categoryId;
        $this->activeGroupId = '';
        $this->groups = $this->groupsOfActiveTab();
    }

    /**
     * Ganti grup. Grup dari DOM wajib milik tingkat yang sedang dilihat.
     */
    public function switchGroup($groupId)
    {
        $this->activeGroupId = '';

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

        $results = $this->scopedRegistrations()
                ->whereNotNull('urutan_tampil')
                ->orderBy('urutan_tampil')
                ->get();

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
