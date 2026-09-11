<?php

namespace App\Livewire\Public\JudgeScoring;

use App\Models\AssessmentCategory;
use App\Models\AssessmentScore;
use App\Models\Judge;
use App\Models\Registration;
use App\Services\ScoreFinalizationService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Penilaian juri via tablet selama lomba berlangsung.
 *
 * Token di URL adalah satu-satunya gerbang (pola sama dengan
 * event.checkin.scan / magic.link) — tidak ada PIN maupun session login.
 * Identitas juri diikat server-side dari token; klien tidak pernah
 * mengirim judge_id.
 */
#[Layout('layouts.frontend')]
#[Title('Penilaian Juri')]
class Index extends Component
{
    #[Locked]
    public int $judgeId;

    #[Locked]
    public int $eventnerId;

    /** Kriteria yang boleh dinilai juri ini — dipakai sebagai guard IDOR. */
    #[Locked]
    public array $allowedCriteriaIds = [];

    /** Registrasi yang sedang dinilai — hanya boleh di-set via selectParticipant(). */
    #[Locked]
    public $selectedRegistrationId = null;

    public string $view = 'categories'; // categories | participants | scoring
    public $selectedCategoryId = null;
    public array $scores = [];
    public bool $isFinalized = false;
    public string $saveStatus = ''; // '' | 'saved' | 'error'

    public function mount(string $token)
    {
        $judge = Judge::where('access_token', $token)->firstOrFail();

        $this->judgeId = $judge->id;
        $this->eventnerId = $judge->eventner_id;
    }

    public function getJudgeProperty(): Judge
    {
        return Judge::where('eventner_id', $this->eventnerId)->findOrFail($this->judgeId);
    }

    public function getEventnerProperty()
    {
        return \App\Models\Eventner::findOrFail($this->eventnerId);
    }

    /** Kategori lomba yang ditugaskan ke juri ini (inversi loadJudges di dashboard). */
    public function getCategoriesProperty()
    {
        $rubricCategoryIds = AssessmentCategory::where('eventner_id', $this->eventnerId)
            ->whereHas('judges', fn ($q) => $q->where('judges.id', $this->judgeId))
            ->pluck('competition_category_id');

        return \App\Models\CompetitionCategory::where('eventner_id', $this->eventnerId)
            ->whereNotNull('parent_id')
            ->where(function ($q) use ($rubricCategoryIds) {
                $q->whereIn('id', $rubricCategoryIds->filter()->all())
                  ->orWhereIn('id', $this->globalRubricCategoryIds());
            })
            ->with('parent')
            ->withCount('registrations')
            ->get();
    }

    /** Kategori lomba yang punya rubrik global (competition_category_id NULL). */
    private function globalRubricCategoryIds(): array
    {
        $hasGlobal = AssessmentCategory::where('eventner_id', $this->eventnerId)
            ->whereNull('competition_category_id')
            ->whereHas('judges', fn ($q) => $q->where('judges.id', $this->judgeId))
            ->exists();

        if (!$hasGlobal) {
            return [];
        }

        return \App\Models\CompetitionCategory::where('eventner_id', $this->eventnerId)
            ->whereNotNull('parent_id')
            ->pluck('id')
            ->all();
    }

    public function selectCategory($id)
    {
        abort_unless($this->categories->pluck('id')->contains((int) $id), 403);

        $this->selectedCategoryId = $id;
        $this->view = 'participants';
    }

    public function backToCategories()
    {
        $this->view = 'categories';
        $this->selectedCategoryId = null;
        $this->resetScoringState();
    }

    public function selectParticipant($id)
    {
        $registration = Registration::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $this->selectedCategoryId)
            ->findOrFail($id);

        $this->selectedRegistrationId = $registration->id;
        $this->view = 'scoring';
        $this->loadCriteria();
    }

    /** Muat kriteria yang boleh dinilai + nilai yang sudah tersimpan. */
    private function loadCriteria(): void
    {
        $registration = $this->registration;
        $compCategoryId = $registration->competition_category_id;

        $base = fn () => AssessmentCategory::with(['subCategories.criterias'])
            ->where('eventner_id', $this->eventnerId)
            ->where(function ($q) use ($compCategoryId) {
                $q->where('competition_category_id', $compCategoryId)
                  ->orWhereNull('competition_category_id');
            });

        $categories = $base()
            ->whereHas('judges', fn ($q) => $q->where('judges.id', $this->judgeId))
            ->get();

        if ($categories->isEmpty()) {
            $categories = $base()->get();
        }

        $this->allowedCriteriaIds = $categories
            ->flatMap(fn ($cat) => $cat->subCategories->flatMap(
                fn ($sub) => $sub->criterias->pluck('id')
            ))
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->scores = [];
        $this->isFinalized = false;
        $this->saveStatus = '';

        $existing = AssessmentScore::where('registration_id', $registration->id)
            ->where('eventner_id', $this->eventnerId)
            ->where('judge_id', $this->judgeId)
            ->get();

        foreach ($existing as $score) {
            $this->scores[$score->assessment_criteria_id] = $score->score;
            if ($score->is_finalized) {
                $this->isFinalized = true;
            }
        }
    }

    public function getRegistrationProperty(): Registration
    {
        return Registration::where('eventner_id', $this->eventnerId)
            ->with('competitionCategory')
            ->findOrFail($this->selectedRegistrationId);
    }

    public function getAssessmentCategoriesProperty()
    {
        $compCategoryId = $this->registration->competition_category_id;

        $base = fn () => AssessmentCategory::with(['subCategories.criterias'])
            ->where('eventner_id', $this->eventnerId)
            ->where(function ($q) use ($compCategoryId) {
                $q->where('competition_category_id', $compCategoryId)
                  ->orWhereNull('competition_category_id');
            });

        $categories = $base()
            ->whereHas('judges', fn ($q) => $q->where('judges.id', $this->judgeId))
            ->get();

        return $categories->isEmpty() ? $base()->get() : $categories;
    }

    /**
     * Simpan satu kriteria begitu tombol nilai diketuk — satu roundtrip per
     * ketukan, jadi koneksi putus paling banyak menghilangkan satu ketukan
     * (bukan seluruh formulir).
     */
    public function setScore($criteriaId, $value)
    {
        if ($this->isFinalized) {
            return;
        }

        if (!in_array((int) $criteriaId, $this->allowedCriteriaIds, true)) {
            abort(403);
        }

        AssessmentScore::updateOrCreate(
            [
                'registration_id' => $this->selectedRegistrationId,
                'assessment_criteria_id' => $criteriaId,
                'judge_id' => $this->judgeId,
            ],
            [
                'eventner_id' => $this->eventnerId,
                'score' => $value,
            ]
        );

        $this->scores[$criteriaId] = $value;
        $this->saveStatus = 'saved';
    }

    public function finalize()
    {
        if ($this->isFinalized) {
            return;
        }

        $result = app(ScoreFinalizationService::class)->finalize(
            $this->eventnerId,
            $this->selectedRegistrationId,
            $this->judgeId,
        );

        if ($result['missing']) {
            $this->saveStatus = 'error';
            return;
        }

        $this->isFinalized = true;
        $this->saveStatus = 'finalized';

        app(ScoreFinalizationService::class)->notifyIfComplete($this->eventnerId, $this->registration);
    }

    /** Kembali ke daftar peserta setelah finalisasi. */
    public function backToParticipants()
    {
        $this->view = 'participants';
        $this->resetScoringState();
    }

    private function resetScoringState(): void
    {
        $this->selectedRegistrationId = null;
        $this->allowedCriteriaIds = [];
        $this->scores = [];
        $this->isFinalized = false;
        $this->saveStatus = '';
    }

    public function render()
    {
        $participants = collect();

        if ($this->selectedCategoryId) {
            $participants = Registration::where('eventner_id', $this->eventnerId)
                ->where('competition_category_id', $this->selectedCategoryId)
                ->with('competitionCategory')
                ->get()
                // Urutan panggung (hasil undian) dulu; yang belum diundian di bawah.
                ->sortBy([
                    fn ($a, $b) => ($a->urutan_tampil ?? PHP_INT_MAX) <=> ($b->urutan_tampil ?? PHP_INT_MAX),
                    fn ($a, $b) => strcmp($a->nama_sekolah ?? '', $b->nama_sekolah ?? ''),
                ])
                ->values();

            // Status penilaian juri ini per peserta.
            $status = AssessmentScore::where('eventner_id', $this->eventnerId)
                ->where('judge_id', $this->judgeId)
                ->whereIn('registration_id', $participants->pluck('id'))
                ->get()
                ->groupBy('registration_id');

            $participants = $participants->map(function ($reg) use ($status) {
                $rows = $status->get($reg->id, collect());
                $reg->judge_status = $rows->isEmpty()
                    ? 'belum'
                    : ($rows->every(fn ($r) => (bool) $r->is_finalized) ? 'final' : 'dinilai');
                return $reg;
            });
        }

        return view('livewire.public.judge-scoring.index', [
            'eventner' => $this->eventner,
            'judge' => $this->judge,
            'categories' => $this->categories,
            'participants' => $participants,
        ])->layoutData([
            'eventner' => $this->eventner,
            'robots' => 'noindex, nofollow',
        ])->title('Penilaian Juri - ' . $this->eventner->nama_event);
    }
}
