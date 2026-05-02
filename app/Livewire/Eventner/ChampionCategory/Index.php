<?php

namespace App\Livewire\Eventner\ChampionCategory;

use App\Models\AssessmentCategory;
use App\Models\AssessmentScore;
use App\Models\ChampionCategory;
use App\Models\ChampionRankTitle;
use App\Models\CompetitionCategory;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Index extends Component
{
    public $eventner;
    public $name = '';
    public $description = '';
    public $selectedAssessmentCategories = [];
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
        $this->eventner = Auth::user()->eventner;

        if (!$this->eventner) {
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
        $this->selectedAssessmentCategories = $champion->assessmentCategories()->pluck('assessment_categories.id')->map(fn($id) => (string) $id)->toArray();
        $this->showForm = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'selectedAssessmentCategories' => 'required|array|min:1',
        ], [
            'name.required' => 'Nama kategori juara wajib diisi.',
            'selectedAssessmentCategories.required' => 'Pilih minimal satu rubrik penilaian.',
            'selectedAssessmentCategories.min' => 'Pilih minimal satu rubrik penilaian.',
        ]);

        $data = [
            'eventner_id' => $this->eventner->id,
            'name' => strip_tags($this->name),
            'description' => strip_tags($this->description) ?: null,
        ];

        if ($this->editingId) {
            $champion = ChampionCategory::where('eventner_id', $this->eventner->id)->findOrFail($this->editingId);
            $champion->update($data);
        } else {
            $champion = ChampionCategory::create($data);
        }

        $champion->assessmentCategories()->sync($this->selectedAssessmentCategories);

        $this->resetForm();
        session()->flash('success', $this->editingId ? 'Kategori juara berhasil diperbarui.' : 'Kategori juara berhasil ditambahkan.');
    }

    public function delete($id)
    {
        $champion = ChampionCategory::where('eventner_id', $this->eventner->id)->findOrFail($id);
        $champion->assessmentCategories()->detach();
        $champion->delete();
        session()->flash('success', 'Kategori juara berhasil dihapus.');
    }

    public function cancel()
    {
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->name = '';
        $this->description = '';
        $this->selectedAssessmentCategories = [];
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
        $rankTitle = ChampionRankTitle::whereHas('championCategory', fn($q) =>
            $q->where('eventner_id', $this->eventner->id)
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
            $rankTitle = ChampionRankTitle::findOrFail($this->editingRankTitleId);
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
        ChampionRankTitle::whereHas('championCategory', fn($q) =>
            $q->where('eventner_id', $this->eventner->id)
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
        $championCategories = ChampionCategory::with(['assessmentCategories.subCategories.criterias', 'rankTitles'])
            ->where('eventner_id', $this->eventner->id)
            ->get();

        $assessmentCategories = AssessmentCategory::with('subCategories.criterias')
            ->where('eventner_id', $this->eventner->id)
            ->get();

        $competitionCategories = $this->eventner->competitionCategories()->withCount('registrations')->get();

        // Calculate rankings for each champion category
        $rankings = collect();
        $rankTitleMap = collect();
        if ($this->selectedCompetitionCategoryId) {
            $participants = \App\Models\Registration::where('competition_category_id', $this->selectedCompetitionCategoryId)
                ->orderBy('nama_sekolah')
                ->get();

            $allScores = AssessmentScore::where('eventner_id', $this->eventner->id)
                ->whereIn('registration_id', $participants->pluck('id'))
                ->get()
                ->groupBy('registration_id');

            foreach ($championCategories as $champion) {
                $assessmentCatIds = $champion->assessmentCategories->pluck('id');

                $criteriaMap = [];
                foreach ($champion->assessmentCategories as $ac) {
                    foreach ($ac->subCategories as $sub) {
                        foreach ($sub->criterias as $crit) {
                            $criteriaMap[$crit->id] = $crit->weight ?? 1;
                        }
                    }
                }

                $participantScores = [];
                foreach ($participants as $participant) {
                    $scores = $allScores->get($participant->id, collect());
                    $total = 0;
                    foreach ($scores as $score) {
                        if (isset($criteriaMap[$score->assessment_criteria_id])) {
                            $weight = $criteriaMap[$score->assessment_criteria_id];
                            $total += (int) $score->score * $weight;
                        }
                    }
                    $participantScores[] = [
                        'participant' => $participant,
                        'total' => $total,
                    ];
                }

                usort($participantScores, fn($a, $b) => $b['total'] <=> $a['total']);

                $rank = 1;
                foreach ($participantScores as $index => &$ps) {
                    if ($index > 0 && $ps['total'] < $participantScores[$index - 1]['total']) {
                        $rank = $index + 1;
                    }
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
            'championCategories' => $championCategories,
            'assessmentCategories' => $assessmentCategories,
            'competitionCategories' => $competitionCategories,
            'rankings' => $rankings,
            'rankTitleMap' => $rankTitleMap,
        ])->title('Kategori Juara - ' . $this->eventner->nama_event);
    }
}
