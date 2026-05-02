<?php

namespace App\Livewire\Public;

use App\Models\AssessmentCategory;
use App\Models\AssessmentScore;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class EventScoring extends Component
{
    public $eventner;
    public $view = 'gate'; // 'gate', 'categories', 'participants', 'scoring'
    public $selectedCategoryId;
    public $search = '';
    public $selectedRegistrationId;
    public $selectedRegistration;
    public $scoringCodeInput = '';
    public $authenticated = false;
    public $scores = []; // [criteria_id => 'score_value']
    public $saveStatus = ''; // '', 'saved', 'error'

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedCategoryId' => ['except' => ''],
    ];

    public function mount($slug)
    {
        $this->eventner = Eventner::where('slug', $slug)->firstOrFail();

        if ($this->selectedCategoryId) {
            $this->view = 'participants';
        }
    }

    public function authenticate()
    {
        if (RateLimiter::tooManyAttempts('scoring-gate:' . request()->ip(), $maxAttempts = 5)) {
            session()->flash('scoring_error', 'Terlalu banyak percobaan. Silakan coba lagi dalam satu menit.');
            return;
        }

        RateLimiter::hit('scoring-gate:' . request()->ip(), $decaySeconds = 60);

        if (empty($this->eventner->scoring_code)) {
            session()->flash('scoring_error', 'Kode penilaian belum diatur oleh penyelenggara.');
            return;
        }

        if ($this->scoringCodeInput === $this->eventner->scoring_code) {
            $this->authenticated = true;
            $this->view = 'categories';
            $this->scoringCodeInput = '';
        } else {
            session()->flash('scoring_error', 'Kode akses tidak valid.');
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
    }

    public function selectParticipant($id)
    {
        $this->selectedRegistrationId = $id;
        $this->selectedRegistration = Registration::with('competitionCategory')->find($id);
        $this->view = 'scoring';
        
        // Load existing scores for this participant
        $this->loadExistingScores();
    }

    public function backToParticipants()
    {
        $this->view = 'participants';
        $this->scores = [];
        $this->selectedRegistrationId = null;
        $this->selectedRegistration = null;
        $this->saveStatus = '';
    }

    public function loadExistingScores()
    {
        $existingScores = AssessmentScore::where('registration_id', $this->selectedRegistrationId)
            ->where('eventner_id', $this->eventner->id)
            ->get();

        foreach ($existingScores as $score) {
            $this->scores[$score->assessment_criteria_id] = $score->score;
        }
    }

    public function saveScores()
    {
        if (RateLimiter::tooManyAttempts('scoring-submit:' . request()->ip(), $maxAttempts = 10)) {
            session()->flash('scoring_error', 'Terlalu banyak permintaan. Silakan coba lagi nanti.');
            return;
        }

        RateLimiter::hit('scoring-submit:' . request()->ip(), $decaySeconds = 60);

        $eventnerId = $this->eventner->id;
        $registrationId = $this->selectedRegistrationId;

        foreach ($this->scores as $criteriaId => $scoreValue) {
            if ($scoreValue === '' || $scoreValue === null) {
                continue;
            }

            AssessmentScore::updateOrCreate(
                [
                    'registration_id' => $registrationId,
                    'assessment_criteria_id' => $criteriaId,
                ],
                [
                    'eventner_id' => $eventnerId,
                    'score' => $scoreValue,
                ]
            );
        }

        $this->saveStatus = 'saved';
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
            // Load assessment criteria via competitionCategory -> judges -> assessmentCategories
            $category = $this->selectedRegistration->competitionCategory;
            if ($category) {
                // Get judges assigned to this competition category
                $judgeIds = $category->judges()->pluck('judges.id');
                if ($judgeIds->isNotEmpty()) {
                    // Get assessment categories assigned to these judges
                    $assessmentCategories = AssessmentCategory::with(['subCategories.criterias'])
                        ->where('eventner_id', $this->eventner->id)
                        ->whereHas('judges', function ($q) use ($judgeIds) {
                            $q->whereIn('judges.id', $judgeIds);
                        })
                        ->get();
                }

                // Fallback: if no judges assigned, get all assessment categories for this event
                if ($assessmentCategories->isEmpty()) {
                    $assessmentCategories = AssessmentCategory::with(['subCategories.criterias'])
                        ->where('eventner_id', $this->eventner->id)
                        ->get();
                }
            }
        }

        $layout = $this->authenticated ? 'layouts.frontend' : 'layouts.auth';

        return view('livewire.public.event-scoring', [
            'participants' => $participants,
            'selectedCategory' => $selectedCategory,
            'categories' => $this->eventner->competitionCategories->loadCount('registrations'),
            'assessmentCategories' => $assessmentCategories,
        ])->layout($layout)->layoutData(['eventner' => $this->eventner])->title('Input Nilai - ' . $this->eventner->nama_event);
    }
}
