<?php

namespace App\Livewire\Public;

use App\Models\AssessmentScore;
use App\Models\ChampionCategory;
use App\Models\CompetitionGroup;
use App\Models\Eventner;
use App\Models\Registration;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.frontend')]
class EventResult extends Component
{
    public $eventner;
    public $categories = [];
    public $selectedCategoryId;
    public $allRankings = [];

    /**
     * Grup yang sedang dilihat. '' = peringkat gabungan tingkat (perilaku lama).
     */
    public $selectedGroupId = '';

    public function mount($slug = null)
    {
        $resolved = app()->bound('current_eventner') ? app('current_eventner') : null;
        if ($resolved) {
            $this->eventner = $resolved;
        } else {
            $this->eventner = Eventner::approved()->where('slug', $slug)->firstOrFail();
        }

        $this->categories = $this->eventner->competitionCategories()
            ->whereNotNull('parent_id')
            ->with('parent')
            ->get()
            ->toArray();

        if (count($this->categories) > 0) {
            $this->selectedCategoryId = $this->categories[0]['id'];
        }

        $this->calculateRankings();
    }

    /**
     * Grup milik tingkat terpilih.
     */
    public function getGroupsProperty()
    {
        if (! $this->selectedCategoryId) {
            return collect();
        }

        return CompetitionGroup::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->selectedCategoryId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function switchCategory($categoryId)
    {
        if ((string) $this->selectedCategoryId !== (string) $categoryId) {
            $this->selectedGroupId = '';
        }

        $this->selectedCategoryId = $categoryId;
        $this->calculateRankings();
    }

    /**
     * Ganti grup. Grup dari DOM wajib milik tingkat terpilih.
     */
    public function switchGroup($groupId)
    {
        $this->selectedGroupId = '';

        if ($groupId !== '' && $groupId !== null) {
            $ada = CompetitionGroup::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $this->selectedCategoryId)
                ->find($groupId);

            if (! $ada) {
                $this->addError('selectedGroupId', 'Grup tidak ditemukan pada tingkat lomba ini.');

                return;
            }

            $this->selectedGroupId = (string) $ada->id;
        }

        $this->resetErrorBag('selectedGroupId');
        $this->calculateRankings();
    }

    public function calculateRankings()
    {
        $this->allRankings = [];

        // Only fetch champion categories that are marked as public
        $championCategories = ChampionCategory::with(['assessmentSubCategories.criterias', 'rankTitles', 'tiebreakSubCategories.criterias', 'criterias', 'tiebreakCriterias'])
            ->where('eventner_id', $this->eventner->id)
            ->where('is_public', true)
            ->get();

        if ($championCategories->isEmpty()) {
            return;
        }

        // Saat grup dipilih, kategori juara yang rubriknya khusus grup lain
        // tidak ikut — supaya "Juara Grup A" dan "Juara Grup B" tidak saling
        // bercampur di satu tabel.
        if ($this->selectedGroupId !== '') {
            $championCategories = $championCategories
                ->filter(fn ($c) => $c->isVisibleFor($this->selectedCategoryId, $this->selectedGroupId))
                ->values();
        }

        $participants = Registration::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->selectedCategoryId)
            ->when($this->selectedGroupId !== '', fn ($q) => $q->where('competition_group_id', $this->selectedGroupId))
            ->orderBy('nama_sekolah')
            ->get();

        $allScores = AssessmentScore::where('eventner_id', $this->eventner->id)
            ->whereIn('registration_id', $participants->pluck('id'))
            ->get()
            ->groupBy('registration_id');

        $allDeductions = \App\Models\ScoreDeduction::where('eventner_id', $this->eventner->id)
            ->get()
            ->groupBy('registration_id');

        // Pengurangan ber-scope 'global' hanya berlaku di tingkat lombanya
        // sendiri — sanksi tingkat lain tidak boleh ikut terpotong.
        $deductionLevelMap = \App\Models\DeductionCategory::levelMapOfCriteria($this->eventner->id);

        $allCriteriaWeightMap = \App\Models\AssessmentCriteria::whereIn(
            'assessment_sub_category_id',
            \App\Models\AssessmentSubCategory::whereIn(
                'assessment_category_id',
                \App\Models\AssessmentCategory::where('eventner_id', $this->eventner->id)->pluck('id')
            )->pluck('id')
        )->pluck('weight', 'id')->toArray();

        foreach ($championCategories as $champion) {
            $criteriaMap = $champion->scoringCriteriaWeights();

            // Build tiebreak criteria map
            $tiebreakCriteriaMap = $champion->tiebreakCriteriaWeights();

            $participantScores = [];
            foreach ($participants as $participant) {
                $scores = $allScores->get($participant->id, collect());

                $total = 0;
                $tiebreakTotal = 0;
                $otherTotal = 0;

                foreach ($scores as $score) {
                    $weight = $criteriaMap[$score->assessment_criteria_id] ?? null;
                    if ($weight !== null) {
                        $scoreVal = (int) $score->score * $weight;
                        $total += $scoreVal;
                    } else {
                        $weightOther = $allCriteriaWeightMap[$score->assessment_criteria_id] ?? 1;
                        $otherTotal += (int) $score->score * $weightOther;
                    }

                    // Tiebreak score (separate calculation)
                    $tbWeight = $tiebreakCriteriaMap[$score->assessment_criteria_id] ?? null;
                    if ($tbWeight !== null) {
                        $tiebreakTotal += (int) $score->score * $tbWeight;
                    }
                }

                $deductions = $allDeductions->get($participant->id, collect());
                $deductions = \App\Models\DeductionCategory::applicableToLevel(
                    $deductions,
                    $participant->competition_category_id,
                    $deductionLevelMap
                );
                $totalDeduction = $deductions->sum(fn ($d) => $d->magnitude);

                $participantScores[] = [
                    'participant' => $participant,
                    'total' => $total,
                    'tiebreak_total' => $tiebreakTotal,
                    'other_total' => $otherTotal,
                    'deduction' => $totalDeduction,
                    'urutan_tampil' => $participant->urutan_tampil ?? 999999,
                ];
            }

            usort($participantScores, function ($a, $b) {
                if ($b['total'] !== $a['total']) {
                    return $b['total'] <=> $a['total'];
                }
                if ($b['tiebreak_total'] !== $a['tiebreak_total']) {
                    return $b['tiebreak_total'] <=> $a['tiebreak_total'];
                }
                if ($b['other_total'] !== $a['other_total']) {
                    return $b['other_total'] <=> $a['other_total'];
                }
                if ($a['deduction'] !== $b['deduction']) {
                    return $a['deduction'] <=> $b['deduction'];
                }
                return $a['urutan_tampil'] <=> $b['urutan_tampil'];
            });

            // Peserta tanpa nilai (skor 0) bukan juara — buang SEBELUM nomor
            // peringkat dihitung, kalau tidak mereka menggeser peringkat juara.
            $participantScores = array_values(array_filter(
                $participantScores,
                fn($ps) => $ps['total'] > 0
            ));

            foreach ($participantScores as $index => &$ps) {
                $rank = $index + 1;
                $ps['rank'] = $rank;
                $ps['title'] = $champion->titleForRank($rank);
            }
            unset($ps);

            if (count($participantScores) > 0) {
                $this->allRankings[] = [
                    'champion' => $champion,
                    'rankTitles' => $champion->rankTitles,
                    'participants' => array_values($participantScores),
                ];
            }
        }
    }

    public function render()
    {
        return view('livewire.public.event-result', [
            'eventner' => $this->eventner,
            'groups' => $this->groups,
            'selectedGroupId' => $this->selectedGroupId,
        ])->title('Hasil Perlombaan - ' . $this->eventner->nama_event)
            ->layoutData(['eventner' => $this->eventner]);
    }
}
