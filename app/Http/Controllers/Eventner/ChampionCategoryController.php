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

        $championCategories = ChampionCategory::with('assessmentCategories.subCategories.criterias')
            ->where('eventner_id', $eventner->id)
            ->get();

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
            $criteriaIds = [];
            foreach ($champion->assessmentCategories as $ac) {
                foreach ($ac->subCategories as $sub) {
                    foreach ($sub->criterias as $crit) {
                        $criteriaIds[] = $crit->id;
                    }
                }
            }

            $participantScores = [];
            foreach ($participants as $participant) {
                $scores = $allScores->get($participant->id, collect());
                $total = 0;
                foreach ($scores as $score) {
                    if (in_array($score->assessment_criteria_id, $criteriaIds)) {
                        $total += (int) $score->score;
                    }
                }
                $participantScores[] = [
                    'participant' => $participant,
                    'total' => $total,
                ];
            }

            usort($participantScores, fn($a, $b) => $b['total'] <=> $a['total']);

            // Limit by quantity
            $participantScores = array_slice($participantScores, 0, $champion->quantity);

            $rank = 1;
            foreach ($participantScores as $index => &$ps) {
                if ($index > 0 && $ps['total'] < $participantScores[$index - 1]['total']) {
                    $rank = $index + 1;
                }
                $ps['rank'] = $rank;
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
