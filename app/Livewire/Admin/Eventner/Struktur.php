<?php

namespace App\Livewire\Admin\Eventner;

use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Judge;
use App\Support\PenugasanJuri;

#[Layout('layouts.admin')]
class Struktur extends Component
{
    public $eventnerId;
    public $eventner;

    public function mount($id)
    {
        $this->eventnerId = $id;
        $this->eventner = Eventner::findOrFail($id);
    }

    /**
     * Pohon tingkat lomba: induk dulu dengan anak-anaknya, lengkap
     * grup/babak/seri per tingkat.
     */
    public function getLevelsProperty()
    {
        return CompetitionCategory::query()
            ->where('eventner_id', $this->eventnerId)
            ->whereNull('parent_id')
            ->with([
                'children' => fn ($q) => $q->with('gelombang')->withCount('registrations')->orderBy('sort_order')->orderBy('id'),
                'venue',
                'groups',
                'rounds',
                'series',
                'gelombang',
            ])
            ->withCount('registrations')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /** Juri milik event ini, dengan rubrik yang dicentang kepadanya. */
    public function getJudgesProperty()
    {
        return Judge::with([
            'assessmentCategories.competitionCategory.parent',
            'assessmentCategories.competitionSeries',
            'assessmentCategories.competitionRound',
        ])
            ->where('eventner_id', $this->eventnerId)
            ->orderBy('id')
            ->get();
    }

    /**
     * Baris penugasan yang dipegang tiap juri — lihat App\Support\PenugasanJuri.
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection>
     */
    public function penugasanPerJuri()
    {
        return PenugasanJuri::perJuri($this->eventnerId);
    }

    public function render()
    {
        return view('livewire.admin.eventner.struktur')
            ->title('Struktur & Juri: ' . $this->eventner->nama_event . ' - ' . app_name());
    }
}
