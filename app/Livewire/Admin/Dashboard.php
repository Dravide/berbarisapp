<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\School;
use App\Models\Ticket;
use App\Models\User;
use App\Models\VoteTransaction;
use App\Services\MailyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Dashboard extends Component
{
    public $totalEventners = 0;
    public $totalRegistrations = 0;
    public $totalRevenue = 0;

    // Antrean persetujuan
    public $pendingQueue;
    public $pendingCount = 0;

    // Modal tolak
    public $selectedEventnerId = null;
    public $rejectionReason = '';
    public $showRejectModal = false;

    // Paket & trial
    public $trialExpiredCount = 0;
    public $trialSoonCount = 0;
    public $expiredEvents;

    // Kesehatan pembayaran
    public $stuckVoteCount = 0;
    public $stuckTicketCount = 0;

    // Tren 6 bulan
    public array $registrationsTrend = [];
    public array $revenueTrend = [];

    // Event mendekati hari-H
    public $upcomingEvents;

    // Global search
    public $globalSearch = '';
    public $searchResults = null;
    public $showSearchResults = false;

    public function mount()
    {
        $this->loadStats();
    }

    /**
     * Pencarian lintas-entitas dari dashboard: event, user, sekolah,
     * pendaftar. Dipicu live di blade; hasilnya panel terpisah.
     */
    public function updatedGlobalSearch($value): void
    {
        $q = trim($value);

        if (mb_strlen($q) < 2) {
            $this->searchResults = null;
            $this->showSearchResults = false;
            return;
        }

        $needle = '%' . $q . '%';

        $events = Eventner::query()
            ->where('nama_event', 'like', $needle)
            ->orWhere('diselenggarakan_oleh', 'like', $needle)
            ->orWhere('lokasi', 'like', $needle)
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'nama_event', 'diselenggarakan_oleh', 'lokasi', 'status']);

        $users = User::query()
            ->where('name', 'like', $needle)
            ->orWhere('username', 'like', $needle)
            ->orWhere('email', 'like', $needle)
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'name', 'username', 'email', 'role', 'is_active']);

        $schools = School::query()
            ->where('nama_sekolah', 'like', $needle)
            ->orWhere('npsn', 'like', $needle)
            ->orderBy('nama_sekolah')
            ->limit(5)
            ->get(['npsn', 'nama_sekolah']);

        $registrations = Registration::query()
            ->with('eventner:id,nama_event')
            ->where('nama_sekolah', 'like', $needle)
            ->orWhere('nama_pelatih', 'like', $needle)
            ->orWhere('npsn', 'like', $needle)
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'eventner_id', 'nama_sekolah', 'npsn', 'nama_pelatih']);

        $this->searchResults = [
            'events' => $events,
            'users' => $users,
            'schools' => $schools,
            'registrations' => $registrations,
        ];
        $this->showSearchResults = true;
    }

    public function closeSearch(): void
    {
        $this->showSearchResults = false;
    }

    public function loadStats(): void
    {
        $this->totalEventners = Eventner::count();
        $this->totalRegistrations = Registration::count();
        $this->totalRevenue = (float) VoteTransaction::where('status', 'PAID')->sum('amount');

        $this->pendingQueue = Eventner::with('user')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->limit(5)
            ->get();
        $this->pendingCount = Eventner::where('status', 'pending')->count();

        $this->trialExpiredCount = Eventner::where('plan', 'free')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now())
            ->count();

        $this->trialSoonCount = Eventner::where('plan', 'free')
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [now(), now()->addDays(7)])
            ->count();

        $this->expiredEvents = Eventner::with('user')
            ->where('plan', 'free')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now())
            ->orderBy('trial_ends_at')
            ->limit(5)
            ->get();

        $this->stuckVoteCount = VoteTransaction::whereIn('status', ['PENDING', 'EXPIRED'])
            ->where('created_at', '<', now()->subHours(2))
            ->count();
        $this->stuckTicketCount = Ticket::where('status', 'PENDING')
            ->where('created_at', '<', now()->subHours(2))
            ->count();

        $this->loadTrends();
        $this->loadUpcoming();
    }

    /**
     * Tren 6 bulan: pendaftar per bulan, dan revenue gabungan
     * (SaaS aktivasi + tiket + voting) — angkanya konsisten dengan
     * RevenueDashboard, cuma jendela lebih pendek.
     */
    private function loadTrends(): void
    {
        $start = now()->subMonths(5)->startOfMonth();

        // DATE_FORMAT = MySQL; test suite pakai sqlite → ganti strftime
        $ymExpr = config('database.default') === 'sqlite'
            ? "strftime('%Y-%m', %col)"
            : "DATE_FORMAT(%col, '%Y-%m')";

        $regs = Registration::select(
                DB::raw(str_replace('%col', 'created_at', $ymExpr) . ' as ym'),
                DB::raw('COUNT(*) as total')
            )
            ->where('created_at', '>=', $start)
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

        $saas = Eventner::select(
                DB::raw(str_replace('%col', 'registration_paid_at', $ymExpr) . ' as ym'),
                DB::raw('COUNT(*) as total')
            )
            ->whereNotNull('registration_paid_at')
            ->where('registration_paid_at', '>=', $start)
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $planPrice = (int) \App\Models\Setting::get('eventner_plan_price', 150000);

        $this->registrationsTrend = [];
        $this->revenueTrend = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $ym = $month->format('Y-m');

            $this->registrationsTrend[] = [
                'month' => $month->translatedFormat('M y'),
                'total' => (int) ($regs[$ym] ?? 0),
            ];

            $revenue = (int) ($saas[$ym] ?? 0) * $planPrice
                + (float) ($tickets[$ym] ?? 0)
                + (float) ($votes[$ym] ?? 0);

            $this->revenueTrend[] = [
                'month' => $month->translatedFormat('M y'),
                'total' => (int) round($revenue),
            ];
        }
    }

    /**
     * Event approved yang digelar ≤ 7 hari ke depan, dengan tanda
     * persiapan: rubrik dan juri sudah diisi atau belum.
     */
    private function loadUpcoming(): void
    {
        $this->upcomingEvents = Eventner::query()
            ->withCount([
                'assessmentCategories as rubrik_count',
                'judges as juri_count',
            ])
            ->where('status', 'approved')
            ->whereBetween('tanggal', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->orderBy('tanggal')
            ->get()
            ->each(function ($e) {
                // Kolom tanggal event berupa string Y-m-d tanpa cast — format di sini
                // sekali, di PHP, supaya blade tetap polos.
                $e->tanggal_pendek = $e->tanggal ? substr($e->tanggal, 0, 10) : null;
            });
    }

    public function approve($eventnerId)
    {
        $eventner = Eventner::with('user')->findOrFail($eventnerId);

        if ($eventner->status !== 'pending') {
            session()->flash('error', 'Eventner ini sudah diproses.');
            $this->loadStats();
            return;
        }

        $eventner->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $eventner->user->update([
            'is_active' => true,
        ]);

        try {
            $mailService = app(MailyService::class);
            $mailService->sendEventnerApproved(
                $eventner->user->email,
                $eventner->user->name,
                $eventner->nama_event
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to send approval email', [
                'eventner_id' => $eventnerId,
                'error' => $e->getMessage(),
            ]);
        }

        session()->flash('success', "Eventner \"{$eventner->nama_event}\" berhasil disetujui.");
        $this->loadStats();
    }

    public function openRejectModal($eventnerId)
    {
        $this->selectedEventnerId = $eventnerId;
        $this->rejectionReason = '';
        $this->showRejectModal = true;
    }

    public function reject()
    {
        $this->validate([
            'rejectionReason' => 'nullable|string|max:1000',
        ]);

        $eventner = Eventner::with('user')->findOrFail($this->selectedEventnerId);

        if ($eventner->status !== 'pending') {
            session()->flash('error', 'Eventner ini sudah diproses.');
            $this->loadStats();
            $this->showRejectModal = false;
            return;
        }

        $eventner->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $this->rejectionReason ?: null,
        ]);

        try {
            $mailService = app(MailyService::class);
            $mailService->sendEventnerRejected(
                $eventner->user->email,
                $eventner->user->name,
                $eventner->nama_event,
                $this->rejectionReason ?: 'Tidak memenuhi persyaratan'
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to send rejection email', [
                'eventner_id' => $this->selectedEventnerId,
                'error' => $e->getMessage(),
            ]);
        }

        session()->flash('success', "Eventner \"{$eventner->nama_event}\" ditolak.");
        $this->showRejectModal = false;
        $this->loadStats();
    }

    public function render()
    {
        return view('livewire.admin.dashboard')->title('Admin Dashboard - ' . app_name());
    }
}
