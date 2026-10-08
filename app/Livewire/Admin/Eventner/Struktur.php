<?php

namespace App\Livewire\Admin\Eventner;

use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Judge;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

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
                'children' => fn ($q) => $q->withCount('registrations')->orderBy('sort_order')->orderBy('id'),
                'venue',
                'groups',
                'rounds',
                'series',
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
     * Baris penugasan yang dipegang tiap juri, dari competition_group_judge.
     *
     * Salinan assignmentsByJudge() di Eventner\Judge\Index — sumber yang sama
     * dengan yang dibaca tablet juri, supaya modal rincian tidak bisa menampilkan
     * tugas yang berbeda dari yang benar-benar dinilai. Satu baris = satu label:
     * nama grup, atau "Final"/"Belum Bergrup"/"Seluruh Tingkat".
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection>
     */
    public function penugasanPerJuri()
    {
        return DB::table('competition_group_judge as cgj')
            ->join('judges as j', 'j.id', '=', 'cgj.judge_id')
            ->leftJoin('competition_groups as cg', 'cg.id', '=', 'cgj.competition_group_id')
            ->leftJoin('competition_categories as cc', 'cc.id', '=', 'cgj.competition_category_id')
            ->leftJoin('competition_categories as induk', fn (JoinClause $join) => $join->on('induk.id', '=', 'cc.parent_id'))
            ->where('j.eventner_id', $this->eventnerId)
            ->orderBy('cgj.competition_category_id')
            ->orderBy('cgj.scope')
            ->orderBy('cgj.competition_group_id')
            ->get([
                'cgj.judge_id',
                'cgj.scope',
                'cgj.competition_category_id',
                'cg.name as group_name',
                'cc.name as level_name',
                'induk.name as parent_name',
            ])
            ->groupBy('judge_id')
            ->map(fn ($baris) => $baris
                ->groupBy('competition_category_id')
                ->map(fn ($perTingkat) => [
                    'name' => trim(
                        ($perTingkat->first()->parent_name ? $perTingkat->first()->parent_name . ' — ' : '')
                        . $perTingkat->first()->level_name
                    ),
                    'items' => $perTingkat->map(fn ($b) => $b->scope === \App\Models\CompetitionGroup::SCOPE_GROUP
                        ? $b->group_name
                        : \App\Models\CompetitionGroup::SCOPE_LABELS[$b->scope])->values(),
                ])
                ->values());
    }

    public function render()
    {
        return view('livewire.admin.eventner.struktur')
            ->title('Struktur & Juri: ' . $this->eventner->nama_event . ' - ' . app_name());
    }
}
