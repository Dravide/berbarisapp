<?php

namespace App\Livewire\Eventner\ChampionCategory;

use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\ChampionCategory;
use App\Models\ChampionRankTitle;
use App\Models\CompetitionCategory;
use App\Models\Registration;
use App\Models\ScoreDeduction;
use App\Notifications\JuaraDiumumkan;
use App\Services\ChampionCalculator;
use App\Services\FcmService;
use App\Traits\FeatureGatedComponent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Index extends Component
{
    use FeatureGatedComponent;

    protected string $requiredFeature = 'champion_categories';

    public $eventner;

    public $name = '';

    public $description = '';

    public $quantity = 1;

    public $isPublic = false;

    public $selectedSubCategories = [];

    public $selectedTiebreakSubCategories = [];

    public $editingId = null;

    public $showForm = false;

    public $selectedCompetitionCategoryId;

    public $expandedChampionId = null;

    // Rank title management
    public $rankTitleChampionId = null;

    public $rankTitle = '';

    public $rankStart = '';

    public $rankEnd = '';

    public $showRankTitleForm = false;

    public $editingRankTitleId = null;

    public function mount()
    {
        $this->bootFeatureGate();
        $this->eventner = Auth::user()->eventner;

        if (! $this->eventner) {
            abort(403, 'Anda belum memiliki data Event terdaftar.');
        }

        $first = $this->eventner->competitionCategories->first();
        if ($first) {
            $this->selectedCompetitionCategoryId = $first->id;
        }
    }

    public function selectCompetitionCategory($id)
    {
        $this->selectedCompetitionCategoryId = $id;
        $this->expandedChampionId = null;
    }

    public function toggleExpand($id)
    {
        $this->expandedChampionId = $this->expandedChampionId === $id ? null : $id;
    }

    public function create()
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit($id)
    {
        $champion = ChampionCategory::where('eventner_id', $this->eventner->id)->findOrFail($id);
        $this->editingId = $id;
        $this->name = $champion->name;
        $this->description = $champion->description ?? '';
        $this->quantity = $champion->quantity ?? 1;
        $this->isPublic = $champion->is_public ?? false;
        $this->selectedSubCategories = $champion->assessmentSubCategories()->pluck('assessment_sub_categories.id')->map(fn ($id) => (string) $id)->toArray();
        $this->selectedTiebreakSubCategories = $champion->tiebreakSubCategories()->pluck('assessment_sub_categories.id')->map(fn ($id) => (string) $id)->toArray();
        $this->showForm = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'selectedSubCategories' => 'required|array|min:1',
        ], [
            'name.required' => 'Nama kategori juara wajib diisi.',
            'quantity.required' => 'Jumlah juara wajib diisi.',
            'selectedSubCategories.required' => 'Pilih minimal satu rubrik penilaian.',
            'selectedSubCategories.min' => 'Pilih minimal satu rubrik penilaian.',
        ]);

        $data = [
            'eventner_id' => $this->eventner->id,
            'name' => strip_tags($this->name),
            'description' => strip_tags($this->description) ?: null,
            'quantity' => $this->quantity,
            'is_public' => $this->isPublic,
        ];

        $wasPublic = false;
        if ($this->editingId) {
            $champion = ChampionCategory::where('eventner_id', $this->eventner->id)->findOrFail($this->editingId);
            $wasPublic = (bool) $champion->is_public;
            $champion->update($data);
        } else {
            $champion = ChampionCategory::create($data);
        }

        $champion->assessmentSubCategories()->sync($this->selectedSubCategories);
        $champion->tiebreakSubCategories()->sync($this->selectedTiebreakSubCategories);

        // Juara baru diumumkan (transisi non-public → public): kirim notifikasi FCM ke semua pemenang.
        if ($this->isPublic && ! $wasPublic) {
            try {
                $this->notifyChampions($champion);
            } catch (\Throwable $e) {
                Log::warning('FCM champion notification failed', [
                    'champion_category_id' => $champion->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->resetForm();
        session()->flash('success', $this->editingId ? 'Kategori juara berhasil diperbarui.' : 'Kategori juara berhasil ditambahkan.');
    }

    private function notifyChampions(ChampionCategory $champion): void
    {
        [$eventner, $category, $winners] = app(ChampionCalculator::class)->winners($champion);

        $fcm = app(FcmService::class);

        foreach ($winners as $winner) {
            $registration = $winner['registration'];
            $label = $winner['title'] ?? ('Juara '.$winner['rank']);
            app(JuaraDiumumkan::class)
                ->construct($registration, $label)
                ->send();
        }
    }

    public function delete($id)
    {
        $champion = ChampionCategory::where('eventner_id', $this->eventner->id)->findOrFail($id);
        $champion->assessmentSubCategories()->detach();
        $champion->delete();
        session()->flash('success', 'Kategori juara berhasil dihapus.');
    }

    public function cancel()
    {
        $this->resetForm();
    }

    public function toggleCategory($categoryId)
    {
        // Scoping ke eventner sendiri — cegah baca struktur penilaian tenant lain.
        $category = AssessmentCategory::with('subCategories')
            ->where('eventner_id', $this->eventner->id)
            ->find($categoryId);
        if (! $category) {
            return;
        }

        $subIds = $category->subCategories->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        if (empty($subIds)) {
            return;
        }

        // Check if all are currently selected
        $selectedCount = count(array_intersect($subIds, $this->selectedSubCategories));
        $allSelected = $selectedCount === count($subIds);

        if ($allSelected) {
            // Remove all
            $this->selectedSubCategories = array_diff($this->selectedSubCategories, $subIds);
        } else {
            // Add missing ones
            $this->selectedSubCategories = array_values(array_unique(array_merge($this->selectedSubCategories, $subIds)));
        }
    }

    public function toggleTiebreakCategory($categoryId)
    {
        // Scoping ke eventner sendiri — cegah baca struktur penilaian tenant lain.
        $category = AssessmentCategory::with('subCategories')
            ->where('eventner_id', $this->eventner->id)
            ->find($categoryId);
        if (! $category) {
            return;
        }

        $subIds = $category->subCategories->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        if (empty($subIds)) {
            return;
        }

        $selectedCount = count(array_intersect($subIds, $this->selectedTiebreakSubCategories));
        $allSelected = $selectedCount === count($subIds);

        if ($allSelected) {
            $this->selectedTiebreakSubCategories = array_diff($this->selectedTiebreakSubCategories, $subIds);
        } else {
            $this->selectedTiebreakSubCategories = array_values(array_unique(array_merge($this->selectedTiebreakSubCategories, $subIds)));
        }
    }

    private function resetForm()
    {
        $this->name = '';
        $this->description = '';
        $this->quantity = 1;
        $this->isPublic = false;
        $this->selectedSubCategories = [];
        $this->selectedTiebreakSubCategories = [];
        $this->editingId = null;
        $this->showForm = false;
    }

    // ===== Rank Title Management =====

    public function showAddRankTitle($championId)
    {
        $this->rankTitleChampionId = $championId;
        $this->resetRankTitleForm();
        $this->showRankTitleForm = true;
    }

    public function editRankTitle($id)
    {
        $rankTitle = ChampionRankTitle::whereHas('championCategory', fn ($q) => $q->where('eventner_id', $this->eventner->id)
        )->findOrFail($id);

        $this->editingRankTitleId = $id;
        $this->rankTitleChampionId = $rankTitle->champion_category_id;
        $this->rankTitle = $rankTitle->title;
        $this->rankStart = $rankTitle->rank_start;
        $this->rankEnd = $rankTitle->rank_end;
        $this->showRankTitleForm = true;
    }

    public function saveRankTitle()
    {
        $this->validate([
            'rankTitle' => 'required|string|max:255',
            'rankStart' => 'required|integer|min:1',
            'rankEnd' => 'required|integer|min:1|gte:rankStart',
        ], [
            'rankTitle.required' => 'Nama gelar wajib diisi.',
            'rankStart.required' => 'Rank awal wajib diisi.',
            'rankEnd.required' => 'Rank akhir wajib diisi.',
            'rankEnd.gte' => 'Rank akhir harus >= rank awal.',
        ]);

        // Verify ownership
        ChampionCategory::where('eventner_id', $this->eventner->id)
            ->findOrFail($this->rankTitleChampionId);

        $maxSort = ChampionRankTitle::where('champion_category_id', $this->rankTitleChampionId)
            ->max('sort_order') ?? 0;

        if ($this->editingRankTitleId) {
            // Scoping ke eventner sendiri — cegah update rank title tenant lain via payload.
            $rankTitle = ChampionRankTitle::whereHas('championCategory', fn ($q) => $q->where('eventner_id', $this->eventner->id)
            )->findOrFail($this->editingRankTitleId);
            $rankTitle->update([
                'title' => strip_tags($this->rankTitle),
                'rank_start' => $this->rankStart,
                'rank_end' => $this->rankEnd,
            ]);
        } else {
            ChampionRankTitle::create([
                'champion_category_id' => $this->rankTitleChampionId,
                'title' => strip_tags($this->rankTitle),
                'rank_start' => $this->rankStart,
                'rank_end' => $this->rankEnd,
                'sort_order' => $maxSort + 1,
            ]);
        }

        $this->resetRankTitleForm();
        session()->flash('success', 'Gelar juara berhasil disimpan.');
    }

    public function deleteRankTitle($id)
    {
        ChampionRankTitle::whereHas('championCategory', fn ($q) => $q->where('eventner_id', $this->eventner->id)
        )->findOrFail($id)->delete();

        session()->flash('success', 'Gelar juara berhasil dihapus.');
    }

    private function resetRankTitleForm()
    {
        $this->rankTitle = '';
        $this->rankStart = '';
        $this->rankEnd = '';
        $this->editingRankTitleId = null;
        $this->showRankTitleForm = false;
    }

    public function render()
    {
        $championCategories = ChampionCategory::with(['assessmentSubCategories.criterias', 'rankTitles', 'tiebreakSubCategories.criterias'])
            ->where('eventner_id', $this->eventner->id)
            ->get();

        $assessmentCategories = AssessmentCategory::with(['subCategories.criterias', 'competitionCategory.parent'])
            ->where('eventner_id', $this->eventner->id)
            ->get();

        // Kategori juara hanya relevan di tingkat lomba yang rubriknya dipakai.
        // Sembunyikan kategori juara yang seluruh rubriknya milik tingkat lain —
        // nilainya tidak akan pernah terisi untuk peserta tingkat terpilih.
        $visibleChampionCategories = $championCategories->filter(function ($champion) {
            $subs = $champion->assessmentSubCategories;
            if ($subs->isEmpty()) {
                return true; // belum diatur rubriknya — biarkan tampil
            }

            return $subs->contains(function ($sub) {
                $cat = $sub->category;
                if (!$cat) {
                    return true;
                }

                // Rubrik global (competition_category_id null) berlaku di semua tingkat.
                return $cat->competition_category_id === null
                    || (string) $cat->competition_category_id === (string) $this->selectedCompetitionCategoryId;
            });
        });

        // Filter rubrik mengikuti tingkat lomba yang dipilih di filter halaman.
        // Rubrik global (competition_category_id null) ikut tampil. Bila tingkat
        // terpilih belum punya rubrik, tampilkan semua (fallback agar tidak kosong).
        $filteredAssessmentCategories = $assessmentCategories->filter(function ($cat) {
            if ($cat->competition_category_id === null) {
                return true; // rubrik global selalu tampil
            }

            return (string) $cat->competition_category_id === (string) $this->selectedCompetitionCategoryId;
        });

        if ($filteredAssessmentCategories->isEmpty()) {
            $filteredAssessmentCategories = $assessmentCategories;
        }

        // Kelompokkan rubrik per tingkat lomba (competition_category) agar tidak
        // tampil duplikat identik. Rubrik "global" (competition_category_id null)
        // dimasukkan ke grup tersendiri.
        $rubrikByLevel = collect();
        foreach ($filteredAssessmentCategories->groupBy('competition_category_id') as $ccId => $cats) {
            $level = null;
            if ($ccId) {
                $level = CompetitionCategory::where('eventner_id', $this->eventner->id)
                    ->with('parent')
                    ->find($ccId);
            }
            $rubrikByLevel->push([
                'id' => $ccId,
                'level_name' => $level ? $level->full_name : 'Semua Tingkat (Global)',
                'categories' => $cats->values(),
            ]);
        }
        $rubrikByLevel = $rubrikByLevel->sortBy('level_name')->values();

        $competitionCategories = $this->eventner->competitionCategories()->whereNotNull('parent_id')->withCount('registrations')->get();

        // Calculate rankings for each champion category
        $rankings = collect();
        $rankTitleMap = collect();
        if ($this->selectedCompetitionCategoryId) {
            // Scoping ke eventner sendiri — cegah baca registrasi tenant lain.
            $participants = Registration::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $this->selectedCompetitionCategoryId)
                ->orderBy('nama_sekolah')
                ->get();

            $allScores = AssessmentScore::where('eventner_id', $this->eventner->id)
                ->whereIn('registration_id', $participants->pluck('id'))
                ->get()
                ->groupBy('registration_id');

            // Ambil data deduction
            $allDeductions = ScoreDeduction::where('eventner_id', $this->eventner->id)
                ->get()
                ->groupBy('registration_id');

            // Ambil semua kriteria beserta bobotnya untuk menghitung other_total
            $allCriteriaWeightMap = AssessmentCriteria::whereIn(
                'assessment_sub_category_id',
                AssessmentSubCategory::whereIn(
                    'assessment_category_id',
                    AssessmentCategory::where('eventner_id', $this->eventner->id)->pluck('id')
                )->pluck('id')
            )->pluck('weight', 'id')->toArray();

            foreach ($visibleChampionCategories as $champion) {
                $criteriaMap = [];
                foreach ($champion->assessmentSubCategories as $sub) {
                    foreach ($sub->criterias as $crit) {
                        $criteriaMap[$crit->id] = $crit->weight ?? 1;
                    }
                }

                // Kriteria untuk tiebreak
                $tiebreakCriteriaMap = [];
                foreach ($champion->tiebreakSubCategories as $sub) {
                    foreach ($sub->criterias as $crit) {
                        $tiebreakCriteriaMap[$crit->id] = $crit->weight ?? 1;
                    }
                }

                $participantScores = [];
                foreach ($participants as $participant) {
                    $scores = $allScores->get($participant->id, collect());

                    $total = 0;
                    $tiebreakTotal = 0;
                    $otherTotal = 0;

                    foreach ($scores as $score) {
                        $weight = $criteriaMap[$score->assessment_criteria_id] ?? null;
                        if ($weight !== null) {
                            $scoreVal = (int) $score->score * $weight;
                            $total += $scoreVal;
                        } else {
                            $weightOther = $allCriteriaWeightMap[$score->assessment_criteria_id] ?? 1;
                            $otherTotal += (int) $score->score * $weightOther;
                        }

                        // Tiebreak score (separate calculation)
                        $tbWeight = $tiebreakCriteriaMap[$score->assessment_criteria_id] ?? null;
                        if ($tbWeight !== null) {
                            $tiebreakTotal += (int) $score->score * $tbWeight;
                        }
                    }

                    $deductions = $allDeductions->get($participant->id, collect());
                    $totalDeduction = $deductions->sum('amount');

                    $participantScores[] = [
                        'participant' => $participant,
                        'total' => $total,
                        'tiebreak_total' => $tiebreakTotal,
                        'other_total' => $otherTotal,
                        'deduction' => $totalDeduction,
                        'urutan_tampil' => $participant->urutan_tampil ?? 999999,
                    ];
                }

                usort($participantScores, function ($a, $b) {
                    if ($b['total'] !== $a['total']) {
                        return $b['total'] <=> $a['total'];
                    }
                    if ($b['tiebreak_total'] !== $a['tiebreak_total']) {
                        return $b['tiebreak_total'] <=> $a['tiebreak_total'];
                    }
                    if ($b['other_total'] !== $a['other_total']) {
                        return $b['other_total'] <=> $a['other_total'];
                    }
                    if ($a['deduction'] !== $b['deduction']) {
                        return $a['deduction'] <=> $b['deduction'];
                    }

                    return $a['urutan_tampil'] <=> $b['urutan_tampil'];
                });

                // Only take Top N based on quantity
                $participantScores = array_slice($participantScores, 0, $champion->quantity);

                foreach ($participantScores as $index => &$ps) {
                    $rank = $index + 1;
                    $ps['rank'] = $rank;

                    // Find matching rank title
                    $ps['title'] = null;
                    foreach ($champion->rankTitles as $rt) {
                        if ($rt->coversRank($rank)) {
                            $ps['title'] = $rt->title;
                            break;
                        }
                    }
                }
                unset($ps);

                $rankings[$champion->id] = collect($participantScores);
                $rankTitleMap[$champion->id] = $champion->rankTitles;
            }
        }

        return view('livewire.eventner.champion-category.index', [
            'championCategories' => $visibleChampionCategories,
            'assessmentCategories' => $assessmentCategories,
            'rubrikByLevel' => $rubrikByLevel,
            'competitionCategories' => $competitionCategories,
            'rankings' => $rankings,
            'rankTitleMap' => $rankTitleMap,
        ])->title('Kategori Juara - '.$this->eventner->nama_event);
    }
}
