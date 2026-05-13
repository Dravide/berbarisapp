<?php

namespace App\Livewire\Eventner\Scoring;

use App\Models\AssessmentCategory;
use App\Models\AssessmentScore;
use App\Models\CompetitionCategory;
use App\Models\DeductionCategory;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\ScoreDeduction;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Index extends Component
{
    public $eventner;
    public $view = 'categories'; // 'categories', 'participants', 'scoring'
    public $selectedCategoryId;
    public $search = '';
    public $selectedRegistrationId;
    public $selectedRegistration;
    public $scores = []; // [criteria_id => 'score_value']
    public $saveStatus = ''; // '', 'saved', 'error'
    public $isFinalized = false;

    // Judge support
    public $selectedJudgeId;
    public $judges = [];

    // Deduction support
    public $deductions = []; // [deduction_criteria_id => amount]
    public $deductionCategories = [];
    public $deductionSaveStatus = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedCategoryId' => ['except' => ''],
    ];

    public function mount()
    {
        $this->eventner = Auth::user()->eventner;

        if (!$this->eventner) {
            abort(403, 'Anda belum memiliki data Event terdaftar.');
        }

        if ($this->selectedCategoryId) {
            $this->view = 'participants';
        }
    }

    public function selectCategory($id)
    {
        $this->selectedCategoryId = $id;
        $this->view = 'participants';
    }

    public function backToCategories()
    {
        $this->view = 'categories';
        $this->selectedCategoryId = null;
        $this->search = '';
        $this->selectedRegistrationId = null;
        $this->selectedRegistration = null;
        $this->selectedJudgeId = null;
        $this->judges = [];
        $this->isFinalized = false;
    }

    public function selectParticipant($id)
    {
        $this->selectedRegistrationId = $id;
        $this->selectedRegistration = Registration::with('competitionCategory')->find($id);
        $this->view = 'scoring';

        // Load judges for this competition category
        $this->loadJudges();

        // Auto-select first judge if available
        if (count($this->judges) > 0) {
            $this->selectedJudgeId = $this->judges[0]->id;
        }

        $this->loadExistingScores();
        $this->loadDeductions();
    }

    public function updatedSelectedJudgeId()
    {
        $this->loadExistingScores();
        $this->saveStatus = '';
    }

    public function loadJudges()
    {
        $category = $this->selectedRegistration->competitionCategory;
        if ($category) {
            $this->judges = $category->judges()->get();
        }

        // Fallback: if no judges assigned to category, get all event judges
        if (empty($this->judges) || count($this->judges) === 0) {
            $this->judges = $this->eventner->judges()->get();
        }
    }

    public function backToParticipants()
    {
        $this->view = 'participants';
        $this->scores = [];
        $this->selectedRegistrationId = null;
        $this->selectedRegistration = null;
        $this->saveStatus = '';
        $this->selectedJudgeId = null;
        $this->judges = [];
        $this->isFinalized = false;
        $this->deductions = [];
        $this->deductionCategories = [];
        $this->deductionSaveStatus = '';
    }

    public function loadExistingScores()
    {
        $this->scores = [];
        $this->isFinalized = false;

        if (!$this->selectedJudgeId) {
            return;
        }

        $existingScores = AssessmentScore::where('registration_id', $this->selectedRegistrationId)
            ->where('eventner_id', $this->eventner->id)
            ->where('judge_id', $this->selectedJudgeId)
            ->get();

        foreach ($existingScores as $score) {
            $this->scores[$score->assessment_criteria_id] = $score->score;
            if ($score->is_finalized) {
                $this->isFinalized = true;
            }
        }
    }

    public function saveScores()
    {
        if (!$this->selectedJudgeId || $this->isFinalized) {
            $this->saveStatus = 'error';
            return;
        }

        $eventnerId = $this->eventner->id;
        $registrationId = $this->selectedRegistrationId;
        $judgeId = $this->selectedJudgeId;

        foreach ($this->scores as $criteriaId => $scoreValue) {
            if ($scoreValue === '' || $scoreValue === null) {
                continue;
            }

            AssessmentScore::updateOrCreate(
                [
                    'registration_id' => $registrationId,
                    'assessment_criteria_id' => $criteriaId,
                    'judge_id' => $judgeId,
                ],
                [
                    'eventner_id' => $eventnerId,
                    'score' => $scoreValue,
                ]
            );
        }

        $this->saveStatus = 'saved';
    }

    public function finalizeScores()
    {
        if (!$this->selectedJudgeId || $this->isFinalized) {
            return;
        }

        // Save scores first to ensure latest data is finalized
        $this->saveScores();

        // Mark all scores for this judge and registration as finalized
        AssessmentScore::where('registration_id', $this->selectedRegistrationId)
            ->where('eventner_id', $this->eventner->id)
            ->where('judge_id', $this->selectedJudgeId)
            ->update(['is_finalized' => true]);

        $this->isFinalized = true;
        $this->saveStatus = 'finalized';
        session()->flash('success', 'Penilaian berhasil difinalisasi dan dikunci.');
    }

    public function resetScores()
    {
        if (!$this->selectedJudgeId || !$this->selectedRegistrationId) {
            return;
        }

        AssessmentScore::where('registration_id', $this->selectedRegistrationId)
            ->where('eventner_id', $this->eventner->id)
            ->where('judge_id', $this->selectedJudgeId)
            ->delete();

        $this->scores = [];
        $this->saveStatus = '';
        session()->flash('success', 'Nilai berhasil direset.');
    }

    public function loadDeductions()
    {
        $this->deductions = [];
        $this->deductionSaveStatus = '';

        $this->deductionCategories = DeductionCategory::with('criterias')
            ->where('eventner_id', $this->eventner->id)
            ->orderBy('sort_order')
            ->get();

        // Load existing deductions for this registration
        $existingDeductions = ScoreDeduction::where('registration_id', $this->selectedRegistrationId)
            ->where('eventner_id', $this->eventner->id)
            ->get();

        foreach ($existingDeductions as $deduction) {
            $this->deductions[$deduction->deduction_criteria_id] = $deduction->amount;
        }
    }

    public function saveDeductions()
    {
        if (!$this->selectedRegistrationId) {
            return;
        }

        foreach ($this->deductions as $criteriaId => $amount) {
            if ($amount === '' || $amount === null || (int) $amount === 0) {
                // Remove if set to 0 or empty
                ScoreDeduction::where('registration_id', $this->selectedRegistrationId)
                    ->where('eventner_id', $this->eventner->id)
                    ->where('deduction_criteria_id', $criteriaId)
                    ->delete();
                continue;
            }

            ScoreDeduction::updateOrCreate(
                [
                    'registration_id' => $this->selectedRegistrationId,
                    'eventner_id' => $this->eventner->id,
                    'deduction_criteria_id' => $criteriaId,
                ],
                [
                    'amount' => (int) $amount,
                ]
            );
        }

        $this->deductionSaveStatus = 'saved';
    }

    public function render()
    {
        $participants = collect();
        $selectedCategory = null;
        $assessmentCategories = collect();

        if ($this->selectedCategoryId) {
            $selectedCategory = CompetitionCategory::find($this->selectedCategoryId);

            $query = Registration::where('competition_category_id', $this->selectedCategoryId);

            if ($this->search) {
                $query->where(function ($q) {
                    $q->where('nama_sekolah', 'like', '%' . $this->search . '%')
                        ->orWhere('nama_pelatih', 'like', '%' . $this->search . '%');
                });
            }

            $participants = $query->get();
        }

        if ($this->view === 'scoring' && $this->selectedRegistration) {
            $category = $this->selectedRegistration->competitionCategory;
            if ($category) {
                $judgeIds = $category->judges()->pluck('judges.id');
                if ($judgeIds->isNotEmpty()) {
                    $assessmentCategories = AssessmentCategory::with(['subCategories.criterias'])
                        ->where('eventner_id', $this->eventner->id)
                        ->whereHas('judges', function ($q) use ($judgeIds) {
                            $q->whereIn('judges.id', $judgeIds);
                        })
                        ->get();
                }

                if ($assessmentCategories->isEmpty()) {
                    $assessmentCategories = AssessmentCategory::with(['subCategories.criterias'])
                        ->where('eventner_id', $this->eventner->id)
                        ->get();
                }
            }
        }

        // Calculate per-judge totals for the current registration
        $judgeTotals = collect();
        if ($this->view === 'scoring' && $this->selectedRegistration && count($this->judges) > 0) {
            $allJudgeScores = AssessmentScore::where('registration_id', $this->selectedRegistrationId)
                ->where('eventner_id', $this->eventner->id)
                ->whereIn('judge_id', collect($this->judges)->pluck('id'))
                ->get()
                ->groupBy('judge_id');

            foreach ($this->judges as $judge) {
                $judgeScores = $allJudgeScores->get($judge->id, collect());
                $total = $judgeScores->sum(fn($s) => (int) $s->score);
                $filled = $judgeScores->count();
                $judgeTotals->push([
                    'judge' => $judge,
                    'total' => $total,
                    'filled' => $filled,
                ]);
            }
        }

        // Calculate total deductions for current registration
        $totalDeductions = 0;
        if ($this->view === 'scoring') {
            foreach ($this->deductions as $amount) {
                if ($amount !== '' && $amount !== null) {
                    $totalDeductions += abs((int) $amount);
                }
            }
        }

        return view('livewire.eventner.scoring.index', [
            'participants' => $participants,
            'selectedCategory' => $selectedCategory,
            'categories' => $this->eventner->competitionCategories->loadCount('registrations'),
            'assessmentCategories' => $assessmentCategories,
            'judgeTotals' => $judgeTotals,
            'totalDeductions' => $totalDeductions,
        ])->title('Input Nilai - ' . $this->eventner->nama_event);
    }
}
