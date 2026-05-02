<?php

namespace App\Livewire\Public\Champions;

use App\Models\AssessmentScore;
use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.scoreboard')]
class Index extends Component
{
    public $eventner;
    public $categories = [];
    public $selectedCategoryId;
    public $allRankings = [];

    public function mount($scoringCode)
    {
        $this->eventner = Eventner::where('scoring_code', $scoringCode)->firstOrFail();

        $this->categories = $this->eventner->competitionCategories()->get()->toArray();

        if (count($this->categories) > 0) {
            $this->selectedCategoryId = $this->categories[0]['id'];
        }

        $this->calculateRankings();
    }

    public function switchCategory($categoryId)
    {
        $this->selectedCategoryId = $categoryId;
        $this->calculateRankings();
    }

    public function calculateRankings()
    {
        $this->allRankings = [];

        $championCategories = ChampionCategory::with(['assessmentCategories.subCategories.criterias', 'rankTitles'])
            ->where('eventner_id', $this->eventner->id)
            ->get();

        $participants = Registration::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->selectedCategoryId)
            ->orderBy('nama_sekolah')
            ->get();

        $allScores = AssessmentScore::where('eventner_id', $this->eventner->id)
            ->whereIn('registration_id', $participants->pluck('id'))
            ->get()
            ->groupBy('registration_id');

        foreach ($championCategories as $champion) {
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
                $ps['title'] = null;

                foreach ($champion->rankTitles as $rt) {
                    if ($rt->coversRank($rank)) {
                        $ps['title'] = $rt->title;
                        break;
                    }
                }
            }
            unset($ps);

            // Only include participants with scores > 0
            $filtered = array_filter($participantScores, fn($ps) => $ps['total'] > 0);

            if (count($filtered) > 0) {
                $this->allRankings[] = [
                    'champion' => $champion,
                    'rankTitles' => $champion->rankTitles,
                    'participants' => array_values($filtered),
                ];
            }
        }
    }

    public function render()
    {
        return view('livewire.public.champions.index', [
            'eventner' => $this->eventner,
        ])->title('Pengumuman Juara - ' . $this->eventner->nama_event);
    }
}
