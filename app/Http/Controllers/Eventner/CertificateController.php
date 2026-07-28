<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\CertificateTemplate;
use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\Registration;
use App\Models\ScoreDeduction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CertificateController extends Controller
{
    public function downloadPdf(Request $request)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $templateId = $request->query('template_id');
        $championCategoryId = $request->query('champion_category_id');
        $competitionCategoryId = $request->query('competition_category_id');

        if (!$templateId || !$championCategoryId || !$competitionCategoryId) {
            abort(422, 'Template, kategori juara, dan kategori lomba wajib dipilih.');
        }

        // Load template
        $template = CertificateTemplate::where('eventner_id', $eventner->id)
            ->with('textFields')
            ->findOrFail($templateId);

        // Load champion category
        $championCategory = ChampionCategory::where('eventner_id', $eventner->id)
            ->with(['assessmentSubCategories.criterias', 'rankTitles', 'tiebreakSubCategories.criterias'])
            ->findOrFail($championCategoryId);

        $competitionCategory = CompetitionCategory::findOrFail($competitionCategoryId);

        // Build criteria weight maps
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

        // All criteria weight map for other_total
        $allCriteriaWeightMap = AssessmentCriteria::whereIn(
            'assessment_sub_category_id',
            \App\Models\AssessmentSubCategory::whereIn(
                'assessment_category_id',
                \App\Models\AssessmentCategory::where('eventner_id', $eventner->id)->pluck('id')
            )->pluck('id')
        )->pluck('weight', 'id')->toArray();

        // Get participants for this competition category
        $participants = Registration::where('eventner_id', $eventner->id)
            ->where('competition_category_id', $competitionCategoryId)
            ->with('participants')
            ->orderBy('nama_sekolah')
            ->get();

        // Get all scores grouped by registration
        $allScores = AssessmentScore::where('eventner_id', $eventner->id)
            ->whereIn('registration_id', $participants->pluck('id'))
            ->get()
            ->groupBy('registration_id');

        // Get deductions
        $allDeductions = ScoreDeduction::where('eventner_id', $eventner->id)
            ->get()
            ->groupBy('registration_id');

        // Calculate rankings
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

        // Take top N and assign ranks/titles
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
                'participant' => $ps['participant'],
                'rank' => $rank,
                'title' => $title,
                'total' => $ps['total'],
            ];
        }

        if (empty($winners)) {
            abort(404, 'Belum ada data juara untuk kategori ini.');
        }

        $data = [
            'eventner' => $eventner,
            'template' => $template,
            'championCategory' => $championCategory,
            'competitionCategory' => $competitionCategory,
            'winners' => $winners,
        ];

        $pdf = Pdf::loadView('eventner.certificate.pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', '0mm')
            ->setOption('margin-bottom', '0mm')
            ->setOption('margin-left', '0mm')
            ->setOption('margin-right', '0mm');

        $filename = 'Sertifikat_' . str_replace(['/', '\\'], '-', $championCategory->name)
            . '_' . str_replace(['/', '\\'], '-', $competitionCategory->name) . '.pdf';

        return $pdf->download($filename);
    }
}
