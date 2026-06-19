<?php

namespace App\Livewire\Public\Scoreboard;

use Livewire\Component;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\AssessmentScore;
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

    public function mount($scoringCode, $categoryId = null)
    {
        $this->scoringCode = $scoringCode;
        $this->eventner = Eventner::where('scoring_code', $scoringCode)->firstOrFail();

        $this->categories = CompetitionCategory::where('eventner_id', $this->eventner->id)
            ->orderBy('name')
            ->get();

        if ($categoryId) {
            $this->selectedCategoryId = $categoryId;
        } elseif ($this->categories->isNotEmpty()) {
            $this->selectedCategoryId = $this->categories->first()->id;
        }
    }

    public function switchCategory($categoryId)
    {
        $this->selectedCategoryId = $categoryId;
        $this->previousRanks = [];
    }

    public function getRankingsProperty()
    {
        if (!$this->selectedCategoryId) {
            return collect();
        }

        $participants = Registration::where('competition_category_id', $this->selectedCategoryId)
            ->with('participants')
            ->orderBy('nama_sekolah')
            ->get();

        $allScores = AssessmentScore::with('assessmentCriteria')
            ->where('eventner_id', $this->eventner->id)
            ->whereIn('registration_id', $participants->pluck('id'))
            ->get()
            ->groupBy('registration_id');

        $rankings = [];
        foreach ($participants as $participant) {
            $scores = $allScores->get($participant->id, collect());
            $total = 0;
            foreach ($scores as $score) {
                $weight = $score->assessmentCriteria->weight ?? 1;
                $total += (int) $score->score * $weight;
            }

            $rankings[] = [
                'id' => $participant->id,
                'nama_sekolah' => $participant->nama_sekolah,
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

        // Check active scoring in last 15 seconds
        $latestScore = AssessmentScore::with('registration')
            ->where('eventner_id', $this->eventner->id)
            ->orderBy('updated_at', 'desc')
            ->first();

        if ($latestScore && $latestScore->updated_at->gt(now()->subSeconds(15))) {
            $this->activeInputSchool = $latestScore->registration->nama_sekolah;
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
