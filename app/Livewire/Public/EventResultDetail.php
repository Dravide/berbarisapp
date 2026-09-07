<?php

namespace App\Livewire\Public;

use App\Models\AssessmentScore;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\ScoreDeduction;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.frontend')]
class EventResultDetail extends Component
{
    public $eventner;
    public $registration;

    public function mount($slug = null, $registration = null)
    {
        $resolved = app()->bound('current_eventner') ? app('current_eventner') : null;
        if ($resolved) {
            $this->eventner = $resolved;
        } else {
            $this->eventner = Eventner::approved()->where('slug', $slug)->firstOrFail();
        }

        // Scope ke eventner milik slug — cegah akses lintas event
        $this->registration = Registration::where('eventner_id', $this->eventner->id)
            ->with(['participants', 'competitionCategory.parent'])
            ->findOrFail($registration);
    }

    public function render()
    {
        // Ambil semua skor finalized untuk registrasi ini
        $scores = AssessmentScore::where('registration_id', $this->registration->id)
            ->where('is_finalized', true)
            ->with([
                'judge:id,name,photo',
                'assessmentCriteria.subCategory:id,name,assessment_category_id',
            ])
            ->get();

        // Susun struktur: judge -> [sub_category -> [criteria => nilai]]
        $judgeMap = [];       // judge_id => judge model
        $judgeScores = [];    // judge_id => total
        $rowsByJudge = [];    // judge_id => [sub_id => [sub_name, items[], subtotal]]
        $subMap = [];         // sub_id => sub model cache

        foreach ($scores as $score) {
            $crit = $score->assessmentCriteria;
            if (!$crit) {
                continue;
            }
            $sub = $crit->subCategory;
            if (!$sub) {
                continue;
            }

            $judgeId = $score->judge_id;
            $subMap[$sub->id] = $sub;

            if (!isset($judgeMap[$judgeId])) {
                $judgeMap[$judgeId] = $score->judge;
                $judgeScores[$judgeId] = 0;
                $rowsByJudge[$judgeId] = [];
            }
            if (!isset($rowsByJudge[$judgeId][$sub->id])) {
                $rowsByJudge[$judgeId][$sub->id] = [
                    'sub' => $sub,
                    'items' => [],
                    'subtotal' => 0,
                ];
            }

            $weight = (float) ($crit->weight ?? 1);
            $weighted = (int) $score->score * $weight;

            $rowsByJudge[$judgeId][$sub->id]['items'][] = [
                'criteria' => $crit,
                'score' => (int) $score->score,
                'weight' => $weight,
                'weighted' => $weighted,
            ];
            $rowsByJudge[$judgeId][$sub->id]['subtotal'] += $weighted;
            $judgeScores[$judgeId] += $weighted;
        }

        // Urutkan juri by nama
        $judges = collect($judgeMap)->sortBy(fn($j) => strtolower($j->name ?? ''))->values();

        // Potongan nilai
        $deductions = ScoreDeduction::where('registration_id', $this->registration->id)
            ->orderBy('amount')
            ->get();

        $grandTotal = array_sum($judgeScores);
        $totalDeduction = (int) $deductions->sum('amount');
        $finalTotal = $grandTotal - $totalDeduction;

        return view('livewire.public.event-result-detail', [
            'eventner' => $this->eventner,
            'registration' => $this->registration,
            'judges' => $judges,
            'judgeMap' => $judgeMap,
            'judgeScores' => $judgeScores,
            'rowsByJudge' => $rowsByJudge,
            'deductions' => $deductions,
            'grandTotal' => $grandTotal,
            'totalDeduction' => $totalDeduction,
            'finalTotal' => $finalTotal,
        ])->title('Detail Penilaian - ' . $this->registration->display_name . ' - ' . $this->eventner->nama_event)
            ->layoutData(['eventner' => $this->eventner]);
    }
}
