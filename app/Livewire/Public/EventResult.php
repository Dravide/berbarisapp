<?php

namespace App\Livewire\Public;

use App\Models\AssessmentScore;
use App\Models\ChampionCategory;
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

    public function mount($slug)
    {
        $this->eventner = Eventner::where('slug', $slug)->firstOrFail();

        $this->categories = $this->eventner->competitionCategories()->get()->toArray();

        if (count($this->categories) > 0) {
            $this->selectedCategoryId = $this->categories[0]['id'];
        }

        $this->calculateRankings();
    }

    public function switchCategory($categoryId)
    {
        $this->selectedCategoryId = $categoryId;
        $this->calculateRankings();
    }

    public function calculateRankings()
    {
        $this->allRankings = [];

        // Only fetch champion categories that are marked as public
        $championCategories = ChampionCategory::with(['assessmentSubCategories.criterias', 'rankTitles', 'tiebreakSubCategories.criterias'])
            ->where('eventner_id', $this->eventner->id)
            ->where('is_public', true)
            ->get();

        if ($championCategories->isEmpty()) {
            return;
        }

        $participants = Registration::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->selectedCategoryId)
            ->orderBy('nama_sekolah')
            ->get();

        $allScores = AssessmentScore::where('eventner_id', $this->eventner->id)
            ->whereIn('registration_id', $participants->pluck('id'))
            ->get()
            ->groupBy('registration_id');

        $allDeductions = \App\Models\ScoreDeduction::where('eventner_id', $this->eventner->id)
            ->get()
            ->groupBy('registration_id');

        $allCriteriaWeightMap = \App\Models\AssessmentCriteria::whereIn(
            'assessment_sub_category_id',
            \App\Models\AssessmentSubCategory::whereIn(
                'assessment_category_id',
                \App\Models\AssessmentCategory::where('eventner_id', $this->eventner->id)->pluck('id')
            )->pluck('id')
        )->pluck('weight', 'id')->toArray();

        foreach ($championCategories as $champion) {
            $criteriaMap = [];
            foreach ($champion->assessmentSubCategories as $sub) {
                foreach ($sub->criterias as $crit) {
                    $criteriaMap[$crit->id] = $crit->weight ?? 1;
                }
            }

            // Build tiebreak criteria map
            $tiebreakCriteriaMap = [];
            foreach ($champion->tiebreakSubCategories as $sub) {
                foreach ($sub->criterias as $crit) {
                    $tiebreakCriteriaMap[$crit->id] = $crit->weight ?? 1;
                }
            }

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
                $totalDeduction = $deductions->sum('amount');

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

            foreach ($participantScores as $index => &$ps) {
                $rank = $index + 1;
                $ps['rank'] = $rank;
                $ps['title'] = null;

                foreach ($champion->rankTitles as $rt) {
                    if ($rt->coversRank($rank)) {
                        $positionInGroup = $rank - $rt->rank_start + 1;
                        $ps['title'] = $rt->title . ' ' . $positionInGroup;
                        break;
                    }
                }
            }
            unset($ps);

            // Only include participants with scores > 0
            $filtered = array_filter($participantScores, fn($ps) => $ps['total'] > 0);

            if (count($filtered) > 0) {
                $this->allRankings[] = [
                    'champion' => $champion,
                    'rankTitles' => $champion->rankTitles,
                    'participants' => array_values($filtered),
                ];
            }
        }
    }

    public function render()
    {
        return view('livewire.public.event-result', [
            'eventner' => $this->eventner,
        ])->title('Hasil Perlombaan - ' . $this->eventner->nama_event)
            ->layoutData(['eventner' => $this->eventner]);
    }
}
