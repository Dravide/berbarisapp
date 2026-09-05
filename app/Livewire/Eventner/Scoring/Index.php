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

    // Sandbox: latihan input nilai tanpa menyimpan apa pun ke database
    public $simulateMode = false;

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

    public function toggleSimulateMode()
    {
        $this->simulateMode = !$this->simulateMode;

        // Bersihkan state penilaian agar tidak terbawa antar mode
        $this->scores = [];
        $this->deductions = [];
        $this->saveStatus = '';
        $this->deductionSaveStatus = '';
        $this->isFinalized = false;
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
        // Scoping ke eventner sendiri — cegah IDOR ke registrasi tenant lain.
        $this->selectedRegistration = Registration::where('eventner_id', $this->eventner->id)
            ->with('competitionCategory')
            ->findOrFail($id);
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
        // Hanya juri yang ditugaskan (Tugaskan Kategori) ke format penilaian
        // kategori kompetisi ini, via assessment_category_judge.
        $category = $this->selectedRegistration->competitionCategory;
        if ($category) {
            $this->judges = Judge::where('eventner_id', $this->eventner->id)
                ->whereHas('assessmentCategories', function ($q) use ($category) {
                    $q->where('assessment_categories.eventner_id', $this->eventner->id)
                        ->where(function ($sq) use ($category) {
                            $sq->where('assessment_categories.competition_category_id', $category->id)
                               ->orWhereNull('assessment_categories.competition_category_id');
                        });
                })
                ->get();
        } else {
            $this->judges = [];
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

        if ($this->simulateMode || !$this->selectedJudgeId) {
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
        if ($this->simulateMode) return;

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
        if ($this->simulateMode || !$this->selectedJudgeId || $this->isFinalized) {
            return;
        }

        // Validate that all criteria are filled
        $compCategoryId = $this->selectedRegistration->competition_category_id ?? null;

        $assessmentCategories = AssessmentCategory::with(['subCategories.criterias'])
            ->where('eventner_id', $this->eventner->id)
            ->where(function ($q) use ($compCategoryId) {
                $q->where('competition_category_id', $compCategoryId)
                  ->orWhereNull('competition_category_id');
            })
            ->whereHas('judges', function ($q) {
                $q->where('judges.id', $this->selectedJudgeId);
            })
            ->get();

        if ($assessmentCategories->isEmpty()) {
            $assessmentCategories = AssessmentCategory::with(['subCategories.criterias'])
                ->where('eventner_id', $this->eventner->id)
                ->where(function ($q) use ($compCategoryId) {
                    $q->where('competition_category_id', $compCategoryId)
                      ->orWhereNull('competition_category_id');
                })
                ->get();
        }

        $missing = false;
        foreach ($assessmentCategories as $cat) {
            foreach ($cat->subCategories as $sub) {
                foreach ($sub->criterias as $crit) {
                    $value = $this->scores[$crit->id] ?? null;
                    if ($value === '' || $value === null) {
                        $missing = true;
                        break 3;
                    }
                }
            }
        }

        if ($missing) {
            $this->saveStatus = 'error';
            session()->flash('scoring_error', 'Semua kriteria nilai harus diisi sebelum melakukan finalisasi.');
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

        // Jika semua judge untuk registration ini sudah final → nilai selesai semua, kirim notif.
        $this->notifyIfAllJudgesFinalized();

        session()->flash('success', 'Penilaian berhasil difinalisasi dan dikunci.');
    }

    private function notifyIfAllJudgesFinalized(): void
    {
        $registration = Registration::find($this->selectedRegistrationId);
        if (!$registration) return;

        $category = $registration->competitionCategory;
        $judgeIds = $category
            ? Judge::where('eventner_id', $this->eventner->id)
                ->whereHas('assessmentCategories', function ($q) use ($category) {
                    $q->where('assessment_categories.eventner_id', $this->eventner->id)
                        ->where(function ($sq) use ($category) {
                            $sq->where('assessment_categories.competition_category_id', $category->id)
                               ->orWhereNull('assessment_categories.competition_category_id');
                        });
                })
                ->pluck('judges.id')
            : collect();
        if ($judgeIds->isEmpty()) return;

        $finalizedJudgeIds = AssessmentScore::where('registration_id', $registration->id)
            ->where('eventner_id', $this->eventner->id)
            ->where('is_finalized', true)
            ->distinct()
            ->pluck('judge_id');

        $allFinalized = $judgeIds->diff($finalizedJudgeIds)->isEmpty();
        if (!$allFinalized) return;

        try {
            app(\App\Notifications\NilaiFinal::class)->construct($registration)->send();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('FCM nilai_final notification failed', [
                'registration_id' => $registration->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function resetScores()
    {
        if ($this->simulateMode) {
            // Sandbox: cukup kosongkan state lokal, tidak perlu sentuh DB
            $this->scores = [];
            $this->saveStatus = '';
            return;
        }

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

        $compCategoryId = $this->selectedRegistration->competition_category_id ?? null;

        $this->deductionCategories = DeductionCategory::with('criterias')
            ->where('eventner_id', $this->eventner->id)
            ->whereNotNull('assessment_category_id')
            ->whereHas('assessmentCategory', function ($q) use ($compCategoryId) {
                $q->where(function ($sq) use ($compCategoryId) {
                    $sq->where('competition_category_id', $compCategoryId)
                       ->orWhereNull('competition_category_id');
                });
            })
            ->orderBy('sort_order')
            ->get();

        // Sandbox: mulai kosong, jangan muat pengurangan tersimpan
        if ($this->simulateMode) {
            return;
        }

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
        if ($this->simulateMode) {
            // Sandbox: tombolnya sudah didisable di view; jangan set status "saved" palsu
            return;
        }

        if (!$this->selectedRegistrationId) {
            return;
        }

        foreach ($this->deductions as $criteriaId => $amount) {
            if ($amount === '' || $amount === null || (float) $amount == 0) {
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
                    'amount' => (float) $amount,
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
            // Scoping ke eventner sendiri — cegah enumerasi kategori/registrasi tenant lain.
            $selectedCategory = CompetitionCategory::where('eventner_id', $this->eventner->id)
                ->find($this->selectedCategoryId);

            $query = Registration::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $this->selectedCategoryId);

            if ($this->search) {
                $query->where(function ($q) {
                    $q->where('nama_sekolah', 'like', '%' . $this->search . '%')
                        ->orWhere('nama_pelatih', 'like', '%' . $this->search . '%');
                });
            }

            $participants = $query->get();
        }

        if ($this->view === 'scoring' && $this->selectedRegistration) {
            $compCategoryId = $this->selectedRegistration->competition_category_id ?? null;

            $baseQuery = AssessmentCategory::with(['subCategories.criterias'])
                ->where('eventner_id', $this->eventner->id)
                ->where(function ($q) use ($compCategoryId) {
                    $q->where('competition_category_id', $compCategoryId)
                      ->orWhereNull('competition_category_id');
                });

            if ($this->selectedJudgeId) {
                $assessmentCategories = (clone $baseQuery)
                    ->whereHas('judges', function ($q) {
                        $q->where('judges.id', $this->selectedJudgeId);
                    })
                    ->get();
            }

            if (!isset($assessmentCategories) || $assessmentCategories->isEmpty()) {
                $assessmentCategories = $baseQuery->get();
            }
        }

        // Calculate per-judge totals for the current registration
        $judgeTotals = collect();
        if ($this->view === 'scoring' && $this->selectedRegistration && count($this->judges) > 0) {
            if ($this->simulateMode) {
                // Sandbox: hanya juri aktif yang punya nilai (state lokal, bukan DB)
                foreach ($this->judges as $judge) {
                    $isMine = $judge->id == $this->selectedJudgeId;
                    $filled = collect($this->scores)->filter(fn($v) => $v !== '' && $v !== null)->count();
                    $judgeTotals->push([
                        'judge' => $judge,
                        'total' => $isMine ? collect($this->scores)->sum(fn($v) => ($v === '' || $v === null) ? 0 : (float) $v) : 0,
                        'filled' => $isMine ? $filled : 0,
                    ]);
                }
            } else {
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
        }

        // Calculate total deductions for current registration
        $totalDeductions = 0;
        if ($this->view === 'scoring') {
            foreach ($this->deductions as $amount) {
                if ($amount !== '' && $amount !== null) {
                    $totalDeductions += abs((float) $amount);
                }
            }
        }

        return view('livewire.eventner.scoring.index', [
            'participants' => $participants,
            'selectedCategory' => $selectedCategory,
            'categories' => $this->eventner->competitionCategories()->whereNotNull('parent_id')->with('parent')->get()->loadCount('registrations'),
            'assessmentCategories' => $assessmentCategories,
            'judgeTotals' => $judgeTotals,
            'totalDeductions' => $totalDeductions,
        ])->title('Input Nilai - ' . $this->eventner->nama_event);
    }
}
