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

    public function mount($scoringCode)
    {
        $this->scoringCode = $scoringCode;
        $this->eventner = Eventner::where('scoring_code', $scoringCode)->firstOrFail();

        $this->categories = CompetitionCategory::where('eventner_id', $this->eventner->id)
            ->orderBy('name')
            ->get();

        if ($this->categories->isNotEmpty()) {
            $this->selectedCategoryId = $this->categories->first()->id;
        }
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

        // Assign ranks (handle ties)
        $rank = 1;
        foreach ($rankings as $i => &$item) {
            if ($i > 0 && $item['total'] < $rankings[$i - 1]['total']) {
                $rank = $i + 1;
            }
            $item['rank'] = $rank;
        }
        unset($item);

        return $rankings;
    }

    public function render()
    {
        return view('livewire.public.scoreboard.index', [
            'rankings' => $this->getRankingsProperty(),
        ]);
    }
}
