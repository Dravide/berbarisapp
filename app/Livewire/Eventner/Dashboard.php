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
    public $voteRevenue = 0;
    public $feeRevenue = 0;

    // Trial & Feature gating
    public $trialDaysLeft = 0;
    public $isTrialExpired = false;
    public $lockedFeatures = [];

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

        // Trial & feature gate info
        $this->trialDaysLeft = $this->eventner->trialDaysLeft();
        $this->isTrialExpired = $this->eventner->isTrialExpired();
        $this->lockedFeatures = $this->eventner->lockedFeatures();
    }

    public function loadData()
    {
        $eventnerId = $this->eventner->id;

        // Stats
        $this->voteRevenue = VoteTransaction::where('eventner_id', $eventnerId)
            ->where('status', 'PAID')
            ->sum('amount');

        $this->ticketRevenue = Ticket::where('eventner_id', $eventnerId)
            ->whereIn('status', ['PAID', 'CHECKED_IN'])
            ->sum('total_amount');

        $feeRevenue = Registration::where('eventner_id', $eventnerId)
            ->where('payment_status', 'paid')
            ->sum('total_fee');

        $this->totalRevenue = $this->voteRevenue + $this->ticketRevenue + (float) $feeRevenue;

        $feeRevenue = Registration::where('eventner_id', $eventnerId)
            ->where('payment_status', 'paid')
            ->sum('total_fee');

        $this->totalRegistrations = Registration::where('eventner_id', $eventnerId)->count();
        $this->totalCategories = $this->eventner->competitionCategories()->whereNotNull('parent_id')->count();
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

        // Default to first child category for top participants chart
        if (!$this->selectedChartCategory) {
            $first = $this->eventner->competitionCategories()->whereNotNull('parent_id')->first();
            if ($first) {
                $this->selectedChartCategory = $first->id;
            }
        }
        $this->loadTopParticipants();
    }

    public function loadScoringProgress()
    {
        $categories = $this->eventner->competitionCategories()->whereNotNull('parent_id')->get();
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
                'name' => $category->full_name,
                'total' => $totalParticipants,
                'scored' => $scoredParticipants,
                'percentage' => $percentage,
            ];
        }
    }

    public function loadRevenueData()
    {
        $eventnerId = $this->eventner->id;
        $startDate = now()->subDays(29)->format('Y-m-d');

        // Single GROUP BY queries instead of 90 per-day queries
        $voteRevenues = VoteTransaction::where('eventner_id', $eventnerId)
            ->where('status', 'PAID')
            ->where('paid_at', '>=', $startDate)
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $ticketRevenues = Ticket::where('eventner_id', $eventnerId)
            ->whereIn('status', ['PAID', 'CHECKED_IN'])
            ->where('paid_at', '>=', $startDate)
            ->selectRaw('DATE(paid_at) as date, SUM(total_amount) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $feeRevenues = Registration::where('eventner_id', $eventnerId)
            ->where('payment_status', 'paid')
            ->where('payment_verified_at', '>=', $startDate)
            ->selectRaw('DATE(payment_verified_at) as date, SUM(total_fee) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $this->revenueData = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $displayDate = now()->subDays($i)->format('d M');

            $vote = (int) ($voteRevenues[$date] ?? 0);
            $ticket = (int) ($ticketRevenues[$date] ?? 0);
            $fee = (int) ($feeRevenues[$date] ?? 0);

            $this->revenueData[] = [
                'date' => $displayDate,
                'vote' => $vote,
                'ticket' => $ticket,
                'total' => (int) ($vote + $ticket + $fee),
            ];
        }
    }

    public function updatedSelectedChartCategory()
    {
        $this->loadTopParticipants();
    }

    public function getDrawingDataProperty()
    {
        $eventnerId = $this->eventner->id;
        $categories = $this->eventner->competitionCategories()->withCount([
            'registrations',
            'registrations as drawn_count' => function ($q) {
                $q->whereNotNull('urutan_tampil');
            },
        ])->get();

        return $categories->map(function ($cat) {
            return [
                'name' => $cat->full_name,
                'drawn' => $cat->drawn_count,
                'total' => $cat->registrations_count,
            ];
        });
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
        $categories = $this->eventner->competitionCategories()->whereNotNull('parent_id')->with('parent')->withCount('registrations')->get();

        return view('livewire.eventner.dashboard', [
            'categories' => $categories,
        ])->title('Dashboard Event - ' . $this->eventner->nama_event);
    }
}
