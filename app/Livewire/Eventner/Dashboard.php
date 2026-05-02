<?php

namespace App\Livewire\Eventner;

use Livewire\Component;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\VoteTransaction;
use App\Models\Ticket;
use App\Models\Judge;
use App\Models\AssessmentScore;
use App\Models\CompetitionCategory;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
class Dashboard extends Component
{
    public $eventner;
    public $totalRevenue = 0;
    public $totalRegistrations = 0;
    public $totalCategories = 0;
    public $totalJudges = 0;
    public $recentRegistrations;
    public $ticketRevenue = 0;

    // Chart data
    public $selectedChartCategory = null;
    public $scoringProgress = [];
    public $revenueData = [];
    public $topParticipants = [];

    public function mount()
    {
        $this->eventner = Auth::user()->eventner;

        if (!$this->eventner) {
            abort(403, 'Anda belum memiliki data Event terdaftar.');
        }

        $this->loadData();
    }

    public function loadData()
    {
        $eventnerId = $this->eventner->id;

        // Stats
        $this->totalRevenue = VoteTransaction::where('eventner_id', $eventnerId)
            ->where('status', 'PAID')
            ->sum('amount');

        $this->ticketRevenue = Ticket::where('eventner_id', $eventnerId)
            ->whereIn('status', ['PAID', 'CHECKED_IN'])
            ->sum('total_amount');

        $this->totalRegistrations = Registration::where('eventner_id', $eventnerId)->count();
        $this->totalCategories = $this->eventner->competitionCategories()->count();
        $this->totalJudges = Judge::where('eventner_id', $eventnerId)->count();

        // Recent registrations
        $this->recentRegistrations = Registration::with('competitionCategory')
            ->where('eventner_id', $eventnerId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Scoring progress per category
        $this->loadScoringProgress();

        // Revenue chart data (last 30 days)
        $this->loadRevenueData();

        // Default to first category for top participants chart
        if (!$this->selectedChartCategory) {
            $first = $this->eventner->competitionCategories->first();
            if ($first) {
                $this->selectedChartCategory = $first->id;
            }
        }
        $this->loadTopParticipants();
    }

    public function loadScoringProgress()
    {
        $categories = $this->eventner->competitionCategories;
        $judges = Judge::where('eventner_id', $this->eventner->id)->count();
        $judges = max($judges, 1);

        $this->scoringProgress = [];

        foreach ($categories as $category) {
            $registrations = Registration::where('competition_category_id', $category->id)->get();
            $totalParticipants = $registrations->count();

            if ($totalParticipants === 0) continue;

            $registrationIds = $registrations->pluck('id');

            // Count unique criteria that have been scored for each participant
            $scoredParticipants = 0;
            foreach ($registrations as $reg) {
                $uniqueJudges = AssessmentScore::where('registration_id', $reg->id)
                    ->where('eventner_id', $this->eventner->id)
                    ->distinct('judge_id')
                    ->count('judge_id');

                if ($uniqueJudges >= $judges) {
                    $scoredParticipants++;
                }
            }

            $percentage = round(($scoredParticipants / $totalParticipants) * 100);

            $this->scoringProgress[] = [
                'name' => $category->name,
                'total' => $totalParticipants,
                'scored' => $scoredParticipants,
                'percentage' => $percentage,
            ];
        }
    }

    public function loadRevenueData()
    {
        $eventnerId = $this->eventner->id;

        // Last 30 days revenue
        $this->revenueData = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');

            $voteRevenue = VoteTransaction::where('eventner_id', $eventnerId)
                ->where('status', 'PAID')
                ->whereDate('paid_at', $date)
                ->sum('amount');

            $ticketRevenue = Ticket::where('eventner_id', $eventnerId)
                ->whereIn('status', ['PAID', 'CHECKED_IN'])
                ->whereDate('paid_at', $date)
                ->sum('total_amount');

            $this->revenueData[] = [
                'date' => now()->subDays($i)->format('d M'),
                'vote' => (int) $voteRevenue,
                'ticket' => (int) $ticketRevenue,
                'total' => (int) ($voteRevenue + $ticketRevenue),
            ];
        }
    }

    public function updatedSelectedChartCategory()
    {
        $this->loadTopParticipants();
    }

    public function loadTopParticipants()
    {
        if (!$this->selectedChartCategory) {
            $this->topParticipants = [];
            return;
        }

        $participants = Registration::where('competition_category_id', $this->selectedChartCategory)
            ->orderBy('nama_sekolah')
            ->get();

        $allScores = AssessmentScore::with('assessmentCriteria')
            ->where('eventner_id', $this->eventner->id)
            ->whereIn('registration_id', $participants->pluck('id'))
            ->get()
            ->groupBy('registration_id');

        $data = [];
        foreach ($participants as $participant) {
            $scores = $allScores->get($participant->id, collect());
            $total = 0;
            foreach ($scores as $score) {
                $weight = $score->assessmentCriteria->weight ?? 1;
                $total += (int) $score->score * $weight;
            }
            $data[] = [
                'name' => $participant->nama_sekolah,
                'total' => $total,
            ];
        }

        // Sort and take top 10
        usort($data, fn($a, $b) => $b['total'] <=> $a['total']);
        $this->topParticipants = array_slice($data, 0, 10);
    }

    public function render()
    {
        $categories = $this->eventner->competitionCategories()->withCount('registrations')->get();

        return view('livewire.eventner.dashboard', [
            'categories' => $categories,
        ])->title('Dashboard Event - ' . $this->eventner->nama_event);
    }
}
