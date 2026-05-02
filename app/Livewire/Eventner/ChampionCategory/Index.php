<?php

namespace App\Livewire\Eventner\ChampionCategory;

use App\Models\AssessmentCategory;
use App\Models\AssessmentScore;
use App\Models\ChampionCategory;
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

    public function mount()
    {
        $this->eventner = Auth::user()->eventner;

        if (!$this->eventner) {
            abort(403, 'Anda belum memiliki data Event terdaftar.');
        }

        // Default to first competition category
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

    public function render()
    {
        $championCategories = ChampionCategory::with('assessmentCategories.subCategories.criterias')
            ->where('eventner_id', $this->eventner->id)
            ->get();

        $assessmentCategories = AssessmentCategory::with('subCategories.criterias')
            ->where('eventner_id', $this->eventner->id)
            ->get();

        $competitionCategories = $this->eventner->competitionCategories()->withCount('registrations')->get();

        // Calculate rankings for each champion category
        $rankings = collect();
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

                // Get all criteria IDs and their weights from selected assessment categories
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

                // Sort by total descending
                usort($participantScores, fn($a, $b) => $b['total'] <=> $a['total']);

                // Assign ranks
                $rank = 1;
                foreach ($participantScores as $index => &$ps) {
                    if ($index > 0 && $ps['total'] < $participantScores[$index - 1]['total']) {
                        $rank = $index + 1;
                    }
                    $ps['rank'] = $rank;
                }
                unset($ps);

                $rankings[$champion->id] = collect($participantScores);
            }
        }

        return view('livewire.eventner.champion-category.index', [
            'championCategories' => $championCategories,
            'assessmentCategories' => $assessmentCategories,
            'competitionCategories' => $competitionCategories,
            'rankings' => $rankings,
        ])->title('Kategori Juara - ' . $this->eventner->nama_event);
    }
}
