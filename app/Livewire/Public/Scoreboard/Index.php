<?php

namespace App\Livewire\Public\Scoreboard;

use Livewire\Component;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\AssessmentScore;
use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use Livewire\Attributes\Layout;

#[Layout('layouts.scoreboard')]
class Index extends Component
{
    public $eventner;
    public $scoringCode;
    public $selectedCategoryId = null;
    public $categories = [];
    public $previousRanks = []; // Track previous ranks for animation
    public $activeInputSchool = null; // Track school currently being scored

    public $selectedChampionCategoryId = null;
    public $championCategory = null;
    public $championCategories = [];
    public $selectedOption = null; // "cat:{id}" atau "champion:{id}" — binding dropdown

    public function mount($scoringCode, $competitionCategoryId = null, $championCategoryId = null)
    {
        $this->scoringCode = $scoringCode;
        $this->eventner = Eventner::where('scoring_code', $scoringCode)->firstOrFail();

        // Kategori lomba: hanya child (yang punya parent), parent tidak ikut
        $this->categories = CompetitionCategory::where('eventner_id', $this->eventner->id)
            ->whereNotNull('parent_id')
            ->with('parent')
            ->orderBy('name')
            ->get();

        // Fallback: event dengan kategori flat (tanpa hierarki)
        if ($this->categories->isEmpty()) {
            $this->categories = CompetitionCategory::where('eventner_id', $this->eventner->id)
                ->orderBy('name')
                ->get();
        }

        $this->championCategories = ChampionCategory::where('eventner_id', $this->eventner->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($championCategoryId) {
            $this->selectedChampionCategoryId = $championCategoryId;
            $this->championCategory = ChampionCategory::with(['assessmentSubCategories.criterias', 'rankTitles'])
                ->where('eventner_id', $this->eventner->id)
                ->findOrFail($championCategoryId);
        }

        // Determine active competition category
        $requestCategoryId = request()->query('category_id');
        if ($competitionCategoryId) {
            $this->selectedCategoryId = $competitionCategoryId;
        } elseif ($requestCategoryId) {
            $this->selectedCategoryId = $requestCategoryId;
        } elseif ($this->categories->isNotEmpty()) {
            $this->selectedCategoryId = $this->categories->first()->id;
        }

        $this->selectedOption = $this->selectedChampionCategoryId
            ? 'champion:' . $this->selectedChampionCategoryId
            : 'cat:' . $this->selectedCategoryId;
    }

    public function updatedSelectedOption($value)
    {
        if (str_starts_with((string) $value, 'champion:')) {
            $this->switchChampionCategory(substr($value, strlen('champion:')));

            return;
        }

        $this->switchCategory(str_starts_with((string) $value, 'cat:')
            ? substr($value, strlen('cat:'))
            : $value);
    }

    public function switchCategory($categoryId)
    {
        $this->selectedCategoryId = $categoryId;
        $this->previousRanks = [];
        // Kembali ke mode kategori lomba: matikan mode champion
        $this->selectedChampionCategoryId = null;
        $this->championCategory = null;
        $this->selectedOption = 'cat:' . $categoryId;
    }

    public function switchChampionCategory($championCategoryId)
    {
        $this->selectedChampionCategoryId = $championCategoryId;
        $this->championCategory = ChampionCategory::with(['assessmentSubCategories.criterias', 'rankTitles'])
            ->where('eventner_id', $this->eventner->id)
            ->findOrFail($championCategoryId);
        $this->previousRanks = [];
        $this->selectedOption = 'champion:' . $championCategoryId;
    }

    public function getRankingsProperty()
    {
        if (!$this->selectedCategoryId && !$this->selectedChampionCategoryId) {
            return collect();
        }

        $participants = Registration::where('eventner_id', $this->eventner->id)
            ->when(!$this->selectedChampionCategoryId, fn ($q) => $q->where('competition_category_id', $this->selectedCategoryId))
            ->with('participants')
            ->orderBy('nama_sekolah')
            ->get();

        $allScores = AssessmentScore::with('assessmentCriteria')
            ->where('eventner_id', $this->eventner->id)
            ->whereIn('registration_id', $participants->pluck('id'))
            ->get()
            ->groupBy('registration_id');

        // Build criteria filter if champion category is selected
        $criteriaMap = null;
        if ($this->selectedChampionCategoryId && $this->championCategory) {
            $criteriaMap = [];
            foreach ($this->championCategory->assessmentSubCategories as $sub) {
                foreach ($sub->criterias as $crit) {
                    $criteriaMap[$crit->id] = $crit->weight ?? 1;
                }
            }
        }

        $rankings = [];
        foreach ($participants as $participant) {
            $scores = $allScores->get($participant->id, collect());
            $total = 0;
            foreach ($scores as $score) {
                if ($criteriaMap !== null) {
                    // Filter: only calculate scores matching the champion category rubrics
                    if (isset($criteriaMap[$score->assessment_criteria_id])) {
                        $weight = $criteriaMap[$score->assessment_criteria_id];
                        $total += (int) $score->score * $weight;
                    }
                } else {
                    // Default: sum all criteria scores (weighted)
                    $weight = $score->assessmentCriteria->weight ?? 1;
                    $total += (int) $score->score * $weight;
                }
            }

            $rankings[] = [
                'id' => $participant->id,
                'nama_sekolah' => $participant->display_name,
                'npsn' => $participant->npsn,
                'total' => $total,
                'participants' => $participant->participants,
            ];
        }

        usort($rankings, fn($a, $b) => $b['total'] <=> $a['total']);

        // Assign ranks (handle ties) and determine direction
        $rank = 1;
        foreach ($rankings as $i => &$item) {
            if ($i > 0 && $item['total'] < $rankings[$i - 1]['total']) {
                $rank = $i + 1;
            }
            $item['rank'] = $rank;

            // Determine matching rank title if champion category is selected
            $item['title'] = null;
            if ($this->selectedChampionCategoryId && $this->championCategory) {
                foreach ($this->championCategory->rankTitles as $rt) {
                    if ($rt->coversRank($rank)) {
                        $item['title'] = $rt->title;
                        break;
                    }
                }
            }

            // Compare with previous ranks
            $prev = $this->previousRanks[$item['id']] ?? null;
            if ($prev !== null) {
                if ($rank < $prev) {
                    $item['direction'] = 'up';
                } elseif ($rank > $prev) {
                    $item['direction'] = 'down';
                } else {
                    $item['direction'] = 'same';
                }
            } else {
                $item['direction'] = 'same';
            }
        }
        unset($item);

        // Cache ranks for next poll comparison
        $this->previousRanks = collect($rankings)->pluck('rank', 'id')->toArray();

        // Check active scoring in last 15 seconds — scope ke kategori/champion aktif
        // supaya badge "Menilai" tidak bocor dari kategori lain
        $latestScore = AssessmentScore::with('registration')
            ->where('eventner_id', $this->eventner->id)
            ->whereIn('registration_id', $participants->pluck('id'))
            ->orderBy('updated_at', 'desc')
            ->first();

        if ($latestScore && $latestScore->updated_at->gt(now()->subSeconds(15))) {
            $this->activeInputSchool = $latestScore->registration->display_name;
        } else {
            $this->activeInputSchool = null;
        }

        return $rankings;
    }

    public function render()
    {
        return view('livewire.public.scoreboard.index', [
            'rankings' => $this->getRankingsProperty(),
        ]);
    }
}
