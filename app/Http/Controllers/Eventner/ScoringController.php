<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use App\Models\AssessmentCategory;
use App\Models\AssessmentScore;
use App\Models\CompetitionCategory;
use App\Models\DeductionCategory;
use App\Models\Registration;
use App\Models\ScoreDeduction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class ScoringController extends Controller
{
    public function downloadPdf(Request $request)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $categoryId = $request->query('category_id');

        // Get assessment categories for this event
        $assessmentCategories = AssessmentCategory::with(['subCategories.criterias'])
            ->where('eventner_id', $eventner->id)
            ->get();

        // Get participants - filtered by competition category if specified
        $participantsQuery = Registration::where('eventner_id', $eventner->id);
        $competitionCategory = null;

        if ($categoryId) {
            $competitionCategory = CompetitionCategory::find($categoryId);
            $participantsQuery->where('competition_category_id', $categoryId);
        }

        $participants = $participantsQuery->orderBy('nama_sekolah')->get();

        // Fetch all scores for these participants in one query
        $allScores = AssessmentScore::where('eventner_id', $eventner->id)
            ->whereIn('registration_id', $participants->pluck('id'))
            ->get()
            ->groupBy('registration_id');

        // Build scoring data per participant
        $scoringData = [];
        foreach ($participants as $participant) {
            $participantScores = $allScores->get($participant->id, collect());

            // Sum scores per criteria across all judges
            $criteriaTotals = [];
            foreach ($participantScores as $score) {
                $cid = $score->assessment_criteria_id;
                $criteriaTotals[$cid] = ($criteriaTotals[$cid] ?? 0) + (int) $score->score;
            }

            $categoryTotals = [];
            $grandTotal = 0;

            foreach ($assessmentCategories as $cat) {
                $catTotal = 0;
                foreach ($cat->subCategories as $sub) {
                    foreach ($sub->criterias as $crit) {
                        $catTotal += $criteriaTotals[$crit->id] ?? 0;
                    }
                }
                $categoryTotals[$cat->id] = $catTotal;
                $grandTotal += $catTotal;
            }

            $scoringData[] = [
                'participant' => $participant,
                'criteriaTotals' => $criteriaTotals,
                'categoryTotals' => $categoryTotals,
                'grandTotal' => $grandTotal,
            ];
        }

        // Sort by grand total descending (ranking)
        usort($scoringData, fn($a, $b) => $b['grandTotal'] <=> $a['grandTotal']);

        $data = [
            'eventner' => $eventner,
            'assessmentCategories' => $assessmentCategories,
            'scoringData' => $scoringData,
            'competitionCategory' => $competitionCategory,
        ];

        $categoryName = $competitionCategory ? str_replace(['/', '\\'], '-', $competitionCategory->name) : 'Semua';
        $filename = 'Rekap_Nilai_' . $categoryName . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($assessmentCategories, $scoringData, $eventner, $competitionCategory) {
            $file = fopen('php://output', 'w');
            // BOM for Excel UTF-8
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Title row
            fputcsv($file, [$eventner->nama_event . ' — ' . $eventner->diselenggarakan_oleh]);
            fputcsv($file, ['Rekap Penilaian — ' . ($competitionCategory ? $competitionCategory->name : 'Semua Kategori')]);
            fputcsv($file, ['Dicetak: ' . now()->translatedFormat('d F Y H:i')]);
            fputcsv($file, []);

            // Build header rows
            $row1 = ['No', 'Peserta', 'Pelatih'];
            $row2 = ['', '', ''];
            foreach ($assessmentCategories as $cat) {
                $criteriaCount = $cat->subCategories->sum(fn($s) => $s->criterias->count());
                $row1[] = $cat->name;
                for ($i = 1; $i < $criteriaCount; $i++) $row1[] = '';
                $row1[] = 'Sub ' . $cat->name;
                foreach ($cat->subCategories as $sub) {
                    foreach ($sub->criterias as $crit) {
                        $row2[] = $sub->name . ' - ' . $crit->name;
                    }
                }
                $row2[] = 'Subtotal';
            }
            $row1[] = 'Total';
            $row2[] = '';
            $row1[] = 'Rank';
            $row2[] = '';

            fputcsv($file, $row1);
            fputcsv($file, $row2);

            // Data rows
            foreach ($scoringData as $index => $data) {
                $row = [
                    $index + 1,
                    $data['participant']->nama_sekolah,
                    $data['participant']->nama_pelatih,
                ];
                foreach ($assessmentCategories as $cat) {
                    foreach ($cat->subCategories as $sub) {
                        foreach ($sub->criterias as $crit) {
                            $row[] = $data['criteriaTotals'][$crit->id] ?? '-';
                        }
                    }
                    $row[] = $data['categoryTotals'][$cat->id] ?? 0;
                }
                $row[] = $data['grandTotal'];
                $row[] = $index + 1;
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadParticipantPdf(Request $request)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $registrationId = $request->query('registration_id');
        if (!$registrationId) {
            abort(400, 'Registration ID diperlukan.');
        }

        $registration = Registration::with('competitionCategory')
            ->where('eventner_id', $eventner->id)
            ->findOrFail($registrationId);

        $assessmentCategories = AssessmentCategory::with(['subCategories.criterias'])
            ->where('eventner_id', $eventner->id)
            ->get();

        $allScores = AssessmentScore::where('eventner_id', $eventner->id)
            ->where('registration_id', $registrationId)
            ->get();

        // Sum scores per criteria across all judges
        $criteriaTotals = [];
        foreach ($allScores as $score) {
            $cid = $score->assessment_criteria_id;
            $criteriaTotals[$cid] = ($criteriaTotals[$cid] ?? 0) + (int) $score->score;
        }

        // Calculate totals
        $categoryTotals = [];
        $grandTotal = 0;
        foreach ($assessmentCategories as $cat) {
            $catTotal = 0;
            foreach ($cat->subCategories as $sub) {
                foreach ($sub->criterias as $crit) {
                    $catTotal += $criteriaTotals[$crit->id] ?? 0;
                }
            }
            $categoryTotals[$cat->id] = $catTotal;
            $grandTotal += $catTotal;
        }

        // Get judges
        $judges = $eventner->judges()->get();
        $judgeIds = $judges->pluck('id');

        // Build per-judge scores: [judge_id => [criteria_id => score]]
        $judgeScores = [];
        foreach ($allScores as $score) {
            if ($score->judge_id && $judgeIds->contains($score->judge_id)) {
                $judgeScores[$score->judge_id][$score->assessment_criteria_id] = (int) $score->score;
            }
        }

        // Per-judge category totals
        $judgeCategoryTotals = [];
        foreach ($judges as $judge) {
            $jScores = $judgeScores[$judge->id] ?? [];
            $catTotals = [];
            $jTotal = 0;
            foreach ($assessmentCategories as $cat) {
                $cTotal = 0;
                foreach ($cat->subCategories as $sub) {
                    foreach ($sub->criterias as $crit) {
                        $cTotal += $jScores[$crit->id] ?? 0;
                    }
                }
                $catTotals[$cat->id] = $cTotal;
                $jTotal += $cTotal;
            }
            $judgeCategoryTotals[$judge->id] = [
                'totals' => $catTotals,
                'grand' => $jTotal,
            ];
        }

        $data = [
            'eventner' => $eventner,
            'registration' => $registration,
            'assessmentCategories' => $assessmentCategories,
            'criteriaTotals' => $criteriaTotals,
            'categoryTotals' => $categoryTotals,
            'grandTotal' => $grandTotal,
            'judges' => $judges,
            'judgeScores' => $judgeScores,
            'judgeCategoryTotals' => $judgeCategoryTotals,
            'deductionCategories' => DeductionCategory::with('criterias')->where('eventner_id', $eventner->id)->orderBy('sort_order')->get(),
            'scoreDeductions' => ScoreDeduction::where('eventner_id', $eventner->id)->where('registration_id', $registrationId)->get()->keyBy('deduction_criteria_id'),
            'totalDeduction' => ScoreDeduction::where('eventner_id', $eventner->id)->where('registration_id', $registrationId)->sum('amount'),
            'finalScore' => $grandTotal + ScoreDeduction::where('eventner_id', $eventner->id)->where('registration_id', $registrationId)->sum('amount'),
        ];

        $pdf = Pdf::loadView('eventner.scoring.pdf_participant', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', '10mm')
            ->setOption('margin-bottom', '10mm')
            ->setOption('margin-left', '5mm')
            ->setOption('margin-right', '5mm');

        $name = str_replace(['/', '\\'], '-', $registration->nama_sekolah);
        $filename = 'Nilai_' . $name . '.pdf';
        return $pdf->download($filename);
    }
}
