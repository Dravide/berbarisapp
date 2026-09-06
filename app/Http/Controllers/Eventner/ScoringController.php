<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use App\Models\AssessmentCategory;
use App\Models\AssessmentScore;
use App\Models\CompetitionCategory;
use App\Models\DeductionCategory;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\ScoreDeduction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class ScoringController extends Controller
{
    public function downloadCsv(Request $request)
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

        // Petakan deduction_criteria_id => assessment_category_id (hanya yang menempel kategori)
        $deductionCats = DeductionCategory::with('criterias')
            ->where('eventner_id', $eventner->id)
            ->whereNotNull('assessment_category_id')
            ->get();
        $critToAssessment = [];
        foreach ($deductionCats as $dc) {
            foreach ($dc->criterias as $c) {
                $critToAssessment[$c->id] = $dc->assessment_category_id;
            }
        }
        $allDeductions = ScoreDeduction::where('eventner_id', $eventner->id)
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

            // Pengurangan per kategori
            $participantDeductions = $allDeductions->get($participant->id, collect());
            $deductionByCat = [];
            $totalDeduction = 0;
            foreach ($participantDeductions as $d) {
                $aid = $critToAssessment[$d->deduction_criteria_id] ?? null;
                if ($aid !== null) {
                    $amt = (float) $d->amount;
                    if ($amt > 0) {
                        $amt = -$amt;
                    }
                    $deductionByCat[$aid] = ($deductionByCat[$aid] ?? 0) + $amt;
                    $totalDeduction += $amt;
                }
            }
            $finalScore = $grandTotal + $totalDeduction;

            $scoringData[] = [
                'participant' => $participant,
                'criteriaTotals' => $criteriaTotals,
                'categoryTotals' => $categoryTotals,
                'categoryDeductions' => $deductionByCat,
                'grandTotal' => $grandTotal,
                'totalDeduction' => $totalDeduction,
                'finalScore' => $finalScore,
            ];
        }

        // Sort by final score descending (ranking)
        usort($scoringData, fn($a, $b) => $b['finalScore'] <=> $a['finalScore']);

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
            $row1[] = 'Pengurangan';
            $row2[] = '';
            $row1[] = 'Nilai Akhir';
            $row2[] = '';
            $row1[] = 'Rank';
            $row2[] = '';

            fputcsv($file, $row1);
            fputcsv($file, $row2);

            // Data rows
            foreach ($scoringData as $index => $data) {
                $row = [
                    $index + 1,
                    $data['participant']->display_name,
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
                $row[] = $data['totalDeduction'] != 0 ? $data['totalDeduction'] : 0;
                $row[] = $data['finalScore'];
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

        // Kategori penilaian hanya yang terikat kategori lomba pendaftaran ini
        // (atau yang umum/tidak terikat) — sama seperti halaman Input Nilai.
        $compCategoryId = $registration->competition_category_id;

        $assessmentCategories = AssessmentCategory::with(['subCategories.criterias'])
            ->where('eventner_id', $eventner->id)
            ->where(function ($q) use ($compCategoryId) {
                $q->where('competition_category_id', $compCategoryId)
                  ->orWhereNull('competition_category_id');
            })
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

        // Juri hanya yang ditugaskan (Tugaskan Kategori) ke format penilaian
        // kategori lomba pendaftaran ini — sama seperti halaman Input Nilai.
        $judges = Judge::where('eventner_id', $eventner->id)
            ->whereHas('assessmentCategories', function ($q) use ($compCategoryId) {
                $q->where('assessment_categories.eventner_id', $eventner->id)
                    ->where(function ($sq) use ($compCategoryId) {
                        $sq->where('assessment_categories.competition_category_id', $compCategoryId)
                           ->orWhereNull('assessment_categories.competition_category_id');
                    });
            })
            ->get();
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

        // Pengurangan (baik menempel pada kategori maupun umum)
        $deductionCategories = DeductionCategory::with(['criterias', 'assessmentCategory'])
            ->where('eventner_id', $eventner->id)
            ->orderBy('sort_order')
            ->get();

        $scoreDeductions = ScoreDeduction::where('eventner_id', $eventner->id)
            ->where('registration_id', $registrationId)
            ->get();

        // Petakan deduction_criteria_id => assessment_category_id
        $critToAssessment = [];
        foreach ($deductionCategories as $dc) {
            foreach ($dc->criterias as $c) {
                if ($dc->assessment_category_id) {
                    $critToAssessment[$c->id] = $dc->assessment_category_id;
                }
            }
        }

        // Akumulasikan pengurangan
        $categoryDeductions = [];
        $totalDeduction = 0;
        foreach ($scoreDeductions as $d) {
            $amt = (float) $d->amount;
            if ($amt > 0) {
                $amt = -$amt;
            }

            $aid = $critToAssessment[$d->deduction_criteria_id] ?? null;
            if ($aid !== null) {
                $categoryDeductions[$aid] = ($categoryDeductions[$aid] ?? 0) + $amt;
            }

            $totalDeduction += $amt;
        }

        $data = [
            'eventner' => $eventner,
            'registration' => $registration,
            'assessmentCategories' => $assessmentCategories,
            'criteriaTotals' => $criteriaTotals,
            'categoryTotals' => $categoryTotals,
            'categoryDeductions' => $categoryDeductions,
            'grandTotal' => $grandTotal,
            'judges' => $judges,
            'judgeScores' => $judgeScores,
            'judgeCategoryTotals' => $judgeCategoryTotals,
            'deductionCategories' => $deductionCategories,
            'scoreDeductions' => $scoreDeductions->keyBy('deduction_criteria_id'),
            'totalDeduction' => $totalDeduction,
            'finalScore' => $grandTotal + $totalDeduction,
        ];

        $pdf = Pdf::loadView('eventner.scoring.pdf_participant', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', '10mm')
            ->setOption('margin-bottom', '10mm')
            ->setOption('margin-left', '5mm')
            ->setOption('margin-right', '5mm');

        $name = str_replace(['/', '\\'], '-', $registration->display_name);
        $filename = 'Nilai_' . $name . '.pdf';
        return $pdf->download($filename);
    }
}
