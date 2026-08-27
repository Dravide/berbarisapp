<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Eventner;
use App\Models\Ticket;
use App\Models\VoteTransaction;
use Illuminate\Support\Facades\DB;

#[Layout('layouts.admin')]
#[Title('Pendapatan Platform - BARIS APP')]
class RevenueDashboard extends Component
{
    public $saasRevenue = 0;      // fee registrasi eventner paid
    public $ticketRevenue = 0;
    public $voteRevenue = 0;
    public $totalRevenue = 0;

    public $paidEventners = 0;
    public $freeActive = 0;
    public $trialExpired = 0;
    public $conversionRate = 0;

    public $monthlyData = [];
    public $topEventners = [];
    public $lockedTargetCount = 0;

    public function mount()
    {
        $this->loadSummary();
        $this->loadMonthlyData();
        $this->loadTopEventners();
    }

    private function loadSummary(): void
    {
        // SaaS murni: eventner yang sudah bayar paket (registration_paid_at terisi)
        $this->saasRevenue = (float) Eventner::whereNotNull('registration_paid_at')->count()
            * (int) \App\Models\Setting::get('eventner_plan_price', 150000);

        $this->ticketRevenue = (float) Ticket::whereIn('status', ['PAID', 'CHECKED_IN'])->sum('total_amount');
        $this->voteRevenue = (float) VoteTransaction::where('status', 'PAID')->sum('amount');

        $this->totalRevenue = $this->saasRevenue + $this->ticketRevenue + $this->voteRevenue;

        $this->paidEventners = Eventner::where('plan', 'paid')->count();

        $this->freeActive = Eventner::where('plan', 'free')
            ->where(function ($q) {
                $q->whereNull('trial_ends_at')
                  ->orWhere('trial_ends_at', '>', now());
            })
            ->count();

        $this->trialExpired = Eventner::where('plan', 'free')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now())
            ->count();

        $base = $this->paidEventners + $this->freeActive + $this->trialExpired;
        $this->conversionRate = $base > 0 ? round($this->paidEventners / $base * 100, 1) : 0;

        // Prospek: trial expired belum bayar
        $this->lockedTargetCount = $this->trialExpired;
    }

    private function loadMonthlyData(): void
    {
        $start = now()->subMonths(11)->startOfMonth();

        // DATE_FORMAT = MySQL; test suite pakai sqlite → ganti strftime
        $ymExpr = config('database.default') === 'sqlite'
            ? "strftime('%Y-%m', %col)"
            : "DATE_FORMAT(%col, '%Y-%m')";

        // Aktivasi paket per bulan (dari registration_paid_at)
        $activations = Eventner::select(
                DB::raw(str_replace('%col', 'registration_paid_at', $ymExpr) . ' as ym'),
                DB::raw('COUNT(*) as total')
            )
            ->whereNotNull('registration_paid_at')
            ->where('registration_paid_at', '>=', $start)
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $tickets = Ticket::select(
                DB::raw(str_replace('%col', 'paid_at', $ymExpr) . ' as ym'),
                DB::raw('SUM(total_amount) as total')
            )
            ->whereIn('status', ['PAID', 'CHECKED_IN'])
            ->where('paid_at', '>=', $start)
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $votes = VoteTransaction::select(
                DB::raw(str_replace('%col', 'paid_at', $ymExpr) . ' as ym'),
                DB::raw('SUM(amount) as total')
            )
            ->where('status', 'PAID')
            ->where('paid_at', '>=', $start)
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $planPrice = (int) \App\Models\Setting::get('eventner_plan_price', 150000);
        $this->monthlyData = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $ym = $month->format('Y-m');

            $saas = (int) ($activations[$ym] ?? 0) * $planPrice;
            $ticket = (float) ($tickets[$ym] ?? 0);
            $vote = (float) ($votes[$ym] ?? 0);

            $this->monthlyData[] = [
                'month' => $month->translatedFormat('M Y'),
                'saas' => (int) $saas,
                'ticket' => (int) $ticket,
                'vote' => (int) $vote,
                'total' => (int) ($saas + $ticket + $vote),
            ];
        }
    }

    private function loadTopEventners(): void
    {
        // Top eventner: gabungan transaksi tiket+vote, diurut dari yang tertinggi
        $this->topEventners = Eventner::query()
            ->join('users', 'users.id', '=', 'eventners.user_id')
            ->leftJoinSub(
                Ticket::selectRaw('eventner_id, SUM(total_amount) as ticket_total')
                    ->whereIn('status', ['PAID', 'CHECKED_IN'])
                    ->groupBy('eventner_id'),
                'tk',
                'tk.eventner_id',
                '=',
                'eventners.id'
            )
            ->leftJoinSub(
                VoteTransaction::selectRaw('eventner_id, SUM(amount) as vote_total')
                    ->where('status', 'PAID')
                    ->groupBy('eventner_id'),
                'vt',
                'vt.eventner_id',
                '=',
                'eventners.id'
            )
            ->whereRaw('COALESCE(tk.ticket_total, 0) + COALESCE(vt.vote_total, 0) > 0')
            ->orderByRaw('(COALESCE(tk.ticket_total, 0) + COALESCE(vt.vote_total, 0)) DESC')
            ->limit(10)
            ->get([
                'eventners.id',
                'eventners.nama_event',
                'eventners.plan',
                'users.name as owner_name',
                DB::raw('COALESCE(tk.ticket_total, 0) as ticket_total'),
                DB::raw('COALESCE(vt.vote_total, 0) as vote_total'),
                DB::raw('COALESCE(tk.ticket_total, 0) + COALESCE(vt.vote_total, 0) as grand_total'),
            ])
            ->toArray();
    }

    public function render()
    {
        return view('livewire.admin.revenue-dashboard');
    }
}
