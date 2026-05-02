<?php

namespace App\Livewire\Eventner\ScoreRecap;

use App\Models\AssessmentCategory;
use App\Models\AssessmentScore;
use App\Models\CompetitionCategory;
use App\Models\Registration;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Index extends Component
{
    public $eventner;
    public $selectedCategoryId;

    protected $queryString = [
        'selectedCategoryId' => ['except' => ''],
    ];

    public function mount()
    {
        $this->eventner = Auth::user()->eventner;

        if (!$this->eventner) {
            abort(403, 'Anda belum memiliki data Event terdaftar.');
        }

        // Default to first category if none selected
        if (!$this->selectedCategoryId) {
            $first = $this->eventner->competitionCategories->first();
            if ($first) {
                $this->selectedCategoryId = $first->id;
            }
        }
    }

    public function selectCategory($id)
    {
        $this->selectedCategoryId = $id;
    }

    public function render()
    {
        $categories = $this->eventner->competitionCategories()->withCount('registrations')->get();
        $selectedCategory = null;
        $scoringData = collect();

        $assessmentCategories = AssessmentCategory::with(['subCategories.criterias'])
            ->where('eventner_id', $this->eventner->id)
            ->get();

        if ($this->selectedCategoryId) {
            $selectedCategory = CompetitionCategory::find($this->selectedCategoryId);

            $participants = Registration::where('competition_category_id', $this->selectedCategoryId)
                ->orderBy('nama_sekolah')
                ->get();

            $allScores = AssessmentScore::with('assessmentCriteria')
                ->where('eventner_id', $this->eventner->id)
                ->whereIn('registration_id', $participants->pluck('id'))
                ->get()
                ->groupBy('registration_id');

            $data = [];
            foreach ($participants as $participant) {
                $participantScores = $allScores->get($participant->id, collect());

                // Sum scores per criteria across all judges, weighted
                $criteriaTotals = [];
                foreach ($participantScores as $score) {
                    $cid = $score->assessment_criteria_id;
                    $criteriaWeight = $score->assessmentCriteria->weight ?? 1;
                    $criteriaTotals[$cid] = ($criteriaTotals[$cid] ?? 0) + ((int) $score->score * $criteriaWeight);
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

                $data[] = [
                    'participant' => $participant,
                    'criteriaTotals' => $criteriaTotals,
                    'categoryTotals' => $categoryTotals,
                    'grandTotal' => $grandTotal,
                ];
            }

            // Sort by grand total descending
            usort($data, fn($a, $b) => $b['grandTotal'] <=> $a['grandTotal']);
            $scoringData = collect($data);
        }

        return view('livewire.eventner.score-recap.index', [
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'assessmentCategories' => $assessmentCategories,
            'scoringData' => $scoringData,
        ])->title('Rekap Nilai - ' . $this->eventner->nama_event);
    }
}
