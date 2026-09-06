<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use App\Models\AssessmentScore;
use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class ChampionCategoryController extends Controller
{
    public function downloadPdf(Request $request)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $competitionCategoryId = $request->query('competition_category_id');
        $competitionCategory = $competitionCategoryId ? CompetitionCategory::find($competitionCategoryId) : null;

        $championCategoryId = $request->query('champion_category_id');

        $championCategories = ChampionCategory::with(['assessmentSubCategories.criterias', 'assessmentSubCategories.category'])
            ->where('eventner_id', $eventner->id)
            ->when($championCategoryId, fn($q) => $q->where('id', $championCategoryId))
            ->get();

        // Kategori juara yang rubriknya milik tingkat lain tidak relevan —
        // nilainya tidak akan pernah terisi untuk peserta tingkat terpilih.
        // Sama seperti filter laman admin (ChampionCategory/Index::render).
        if ($competitionCategoryId) {
            $championCategories = $championCategories
                ->filter(fn($c) => $c->isVisibleFor($competitionCategoryId))
                ->values();
        }

        if ($championCategoryId && $championCategories->isEmpty()) {
            abort(404, 'Kategori juara tidak ditemukan.');
        }

        // Ambil data deduction
        $allDeductions = \App\Models\ScoreDeduction::where('eventner_id', $eventner->id)
            ->get()
            ->groupBy('registration_id');

        // Ambil semua kriteria beserta bobotnya untuk menghitung other_total
        $allCriteriaWeightMap = \App\Models\AssessmentCriteria::whereIn(
            'assessment_sub_category_id',
            \App\Models\AssessmentSubCategory::whereIn(
                'assessment_category_id',
                \App\Models\AssessmentCategory::where('eventner_id', $eventner->id)->pluck('id')
            )->pluck('id')
        )->pluck('weight', 'id')->toArray();

        // Get participants
        $participantsQuery = Registration::where('eventner_id', $eventner->id);
        if ($competitionCategoryId) {
            $participantsQuery->where('competition_category_id', $competitionCategoryId);
        }
        $participants = $participantsQuery->orderBy('nama_sekolah')->get();

        // Get all scores
        $allScores = AssessmentScore::where('eventner_id', $eventner->id)
            ->whereIn('registration_id', $participants->pluck('id'))
            ->get()
            ->groupBy('registration_id');

        // Calculate rankings per champion category
        $rankings = [];
        foreach ($championCategories as $champion) {
            $criteriaMap = [];
            foreach ($champion->assessmentSubCategories as $sub) {
                foreach ($sub->criterias as $crit) {
                    $criteriaMap[$crit->id] = $crit->weight ?? 1;
                }
            }

            // Kriteria untuk subkategori pertama (prioritas tie-break)
            $firstSub = $champion->assessmentSubCategories->first();
            $firstSubCriteriaIds = $firstSub ? $firstSub->criterias->pluck('id')->toArray() : [];

            $participantScores = [];
            foreach ($participants as $participant) {
                $scores = $allScores->get($participant->id, collect());

                $total = 0;
                $firstSubTotal = 0;
                $otherTotal = 0;

                foreach ($scores as $score) {
                    $weight = $criteriaMap[$score->assessment_criteria_id] ?? null;
                    if ($weight !== null) {
                        $scoreVal = (int) $score->score * $weight;
                        $total += $scoreVal;

                        if (in_array($score->assessment_criteria_id, $firstSubCriteriaIds)) {
                            $firstSubTotal += $scoreVal;
                        }
                    } else {
                        $weightOther = $allCriteriaWeightMap[$score->assessment_criteria_id] ?? 1;
                        $otherTotal += (int) $score->score * $weightOther;
                    }
                }

                $deductions = $allDeductions->get($participant->id, collect());
                // abs() per-baris: opsi pengurangan bisa tersimpan -5 maupun 5.
                $totalDeduction = $deductions->sum(fn($d) => abs((float) $d->amount));

                $participantScores[] = [
                    'participant' => $participant,
                    'total' => $total - $totalDeduction, // nilai bersih: dipakai sort + tampil
                    'gross_total' => $total,
                    'first_sub_total' => $firstSubTotal,
                    'other_total' => $otherTotal,
                    'deduction' => $totalDeduction,
                    'urutan_tampil' => $participant->urutan_tampil ?? 999999,
                ];
            }

            usort($participantScores, function ($a, $b) {
                if ($b['total'] !== $a['total']) {
                    return $b['total'] <=> $a['total'];
                }
                if ($b['first_sub_total'] !== $a['first_sub_total']) {
                    return $b['first_sub_total'] <=> $a['first_sub_total'];
                }
                if ($b['other_total'] !== $a['other_total']) {
                    return $b['other_total'] <=> $a['other_total'];
                }
                if ($a['deduction'] !== $b['deduction']) {
                    return $a['deduction'] <=> $b['deduction'];
                }
                return $a['urutan_tampil'] <=> $b['urutan_tampil'];
            });

            // Limit by quantity
            $participantScores = array_slice($participantScores, 0, $champion->quantity);

            foreach ($participantScores as $index => &$ps) {
                $ps['rank'] = $index + 1;
            }
            unset($ps);

            $rankings[$champion->id] = $participantScores;
        }

        $data = [
            'eventner' => $eventner,
            'competitionCategory' => $competitionCategory,
            'championCategories' => $championCategories,
            'rankings' => $rankings,
        ];

        $pdf = Pdf::loadView('eventner.champion-category.pdf_ranking', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', '10mm')
            ->setOption('margin-bottom', '10mm')
            ->setOption('margin-left', '5mm')
            ->setOption('margin-right', '5mm');

        $catName = $competitionCategory ? str_replace(['/', '\\'], '-', $competitionCategory->name) : 'Semua';
        $filename = 'Rekap_Juara_' . $catName . '.pdf';
        return $pdf->download($filename);
    }
}
