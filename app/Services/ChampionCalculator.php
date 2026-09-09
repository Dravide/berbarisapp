<?php

namespace App\Services;

use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\ChampionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\ScoreDeduction;
use Illuminate\Support\Collection;

/**
 * Hitung pemenang (juara) per kategori juara — logika yang sama dengan
 * CertificateController::downloadPdf. Diekstrak supaya bisa dipakai ulang
 * untuk notifikasi FCM tanpa duplikasi.
 */
class ChampionCalculator
{
    /**
     * @return array{0: Eventner, 1: ChampionCategory, 2: array} [eventner, category, winners]
     */
    public function winners(ChampionCategory $championCategory): array
    {
        $eventner = $championCategory->eventner;

        // Criteria weight maps (dari subcategory yang ter-assign)
        $criteriaMap = [];
        foreach ($championCategory->assessmentSubCategories as $sub) {
            foreach ($sub->criterias as $crit) {
                $criteriaMap[$crit->id] = $crit->weight ?? 1;
            }
        }

        $tiebreakCriteriaMap = [];
        foreach ($championCategory->tiebreakSubCategories as $sub) {
            foreach ($sub->criterias as $crit) {
                $tiebreakCriteriaMap[$crit->id] = $crit->weight ?? 1;
            }
        }

        // All criteria weight map (untuk other_total)
        $allCriteriaWeightMap = AssessmentCriteria::whereIn(
            'assessment_sub_category_id',
            AssessmentSubCategory::whereIn(
                'assessment_category_id',
                AssessmentCategory::where('eventner_id', $eventner->id)->pluck('id')
            )->pluck('id')
        )->pluck('weight', 'id')->toArray();

        // Semua registration event ini
        $participants = Registration::where('eventner_id', $eventner->id)
            ->with('participants')
            ->orderBy('nama_sekolah')
            ->get();

        $allScores = AssessmentScore::where('eventner_id', $eventner->id)
            ->whereIn('registration_id', $participants->pluck('id'))
            ->get()
            ->groupBy('registration_id');

        $allDeductions = ScoreDeduction::where('eventner_id', $eventner->id)
            ->get()
            ->groupBy('registration_id');

        $participantScores = [];
        foreach ($participants as $participant) {
            $scores = $allScores->get($participant->id, collect());

            $total = 0;
            $tiebreakTotal = 0;
            $otherTotal = 0;

            foreach ($scores as $score) {
                $weight = $criteriaMap[$score->assessment_criteria_id] ?? null;
                if ($weight !== null) {
                    $total += (int) $score->score * $weight;
                } else {
                    $weightOther = $allCriteriaWeightMap[$score->assessment_criteria_id] ?? 1;
                    $otherTotal += (int) $score->score * $weightOther;
                }

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

        // Sort
        usort($participantScores, function ($a, $b) {
            if ($b['total'] !== $a['total']) return $b['total'] <=> $a['total'];
            if ($b['tiebreak_total'] !== $a['tiebreak_total']) return $b['tiebreak_total'] <=> $a['tiebreak_total'];
            if ($b['other_total'] !== $a['other_total']) return $b['other_total'] <=> $a['other_total'];
            if ($a['deduction'] !== $b['deduction']) return $a['deduction'] <=> $b['deduction'];
            return $a['urutan_tampil'] <=> $b['urutan_tampil'];
        });

        $participantScores = array_slice($participantScores, 0, $championCategory->quantity);

        $winners = [];
        foreach ($participantScores as $index => $ps) {
            $rank = $index + 1;
            $title = null;
            foreach ($championCategory->rankTitles as $rt) {
                if ($rt->coversRank($rank)) {
                    $title = $rt->title;
                    break;
                }
            }
            $winners[] = [
                'registration' => $ps['participant'],
                'rank' => $rank,
                'title' => $title,
                'total' => $ps['total'],
            ];
        }

        return [$eventner, $championCategory, $winners];
    }
}
