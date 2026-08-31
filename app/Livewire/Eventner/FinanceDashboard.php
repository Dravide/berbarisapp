<?php

namespace App\Livewire\Eventner;

use App\Models\CompetitionCategory;
use App\Models\Registration;
use App\Models\VoteTransaction;
use App\Models\Ticket;
use App\Models\EventnerBankAccount;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;

#[Layout('layouts.admin')]
#[Title('Dashboard Keuangan - BARIS APP')]
class FinanceDashboard extends Component
{
    public $eventner;
    public $totalRevenue = 0;
    public $ticketRevenue = 0;
    public $voteRevenue = 0;
    public $feeRevenue = 0;
    public $pendingVerificationCount = 0;

    public $selectedCategory = null;
    public $categoryBreakdown = [];
    public $pendingPayments = [];
    public $paymentDetails = [];
    public $selectedPaymentRegId = null;
    public $revenueData = [];

    // Filter modal detail pembayaran
    public string $detailStatus = 'all'; // all | paid | pending_verification | unpaid
    public string $detailCategoryId = 'all';
    public bool $showDetailModal = false;

    #[Computed]
    public function selectedPayment()
    {
        if (!$this->selectedPaymentRegId) return null;

        return Registration::with(['competitionCategory', 'paymentBankAccount'])
            ->where('eventner_id', $this->eventner->id)
            ->find($this->selectedPaymentRegId);
    }

    #[Computed]
    public function filteredPaymentDetails()
    {
        return collect($this->paymentDetails)
            ->when($this->detailStatus !== 'all', fn($c) => $c->where('payment_status', $this->detailStatus))
            ->when($this->detailCategoryId !== 'all', fn($c) => $c->where('competition_category_id', (int) $this->detailCategoryId));
    }

    public function mount()
    {
        $this->eventner = Auth::user()->eventner;
        if (!$this->eventner) abort(403);
        $this->loadData();
    }

    public function loadData()
    {
        $eventnerId = $this->eventner->id;

        // Fee revenue — paid registrations
        $this->feeRevenue = (float) Registration::where('eventner_id', $eventnerId)
            ->where('payment_status', 'paid')
            ->sum('total_fee');

        // Vote revenue
        $this->voteRevenue = (float) VoteTransaction::where('eventner_id', $eventnerId)
            ->where('status', 'PAID')
            ->sum('amount');

        // Ticket revenue
        $this->ticketRevenue = (float) Ticket::where('eventner_id', $eventnerId)
            ->whereIn('status', ['PAID', 'CHECKED_IN'])
            ->sum('total_amount');

        $this->totalRevenue = $this->feeRevenue + $this->voteRevenue + $this->ticketRevenue;

        // Pending verification count
        $this->pendingVerificationCount = Registration::where('eventner_id', $eventnerId)
            ->where('payment_status', 'pending_verification')
            ->count();

        // Category breakdown
        $this->loadCategoryBreakdown();

        // Pending payments
        $this->loadPendingPayments();

        // All registrations payment detail
        $this->loadPaymentDetails();

        // Revenue chart last 30 days
        $this->loadRevenueData();
    }

    public function loadCategoryBreakdown()
    {
        $categories = $this->eventner->competitionCategories()
            ->whereNotNull('parent_id')
            ->withCount(['registrations as total_paid' => fn($q) => $q->where('payment_status', 'paid')])
            ->withCount(['registrations as total_pending' => fn($q) => $q->where('payment_status', 'pending_verification')])
            ->withCount(['registrations as total_unpaid' => fn($q) => $q->where('payment_status', 'unpaid')])
            ->get();

        $this->categoryBreakdown = [];
        foreach ($categories as $cat) {
            $paidRevenue = (float) Registration::where('competition_category_id', $cat->id)
                ->where('payment_status', 'paid')
                ->sum('total_fee');

            $potentialRevenue = $cat->registration_fee
                ? (float) $cat->registration_fee * Registration::where('competition_category_id', $cat->id)
                    ->whereIn('payment_status', ['unpaid', 'pending_verification'])
                    ->count()
                : 0;

            $this->categoryBreakdown[] = [
                'id' => $cat->id,
                'name' => $cat->full_name,
                'fee' => $cat->registration_fee,
                'paid_count' => (int) $cat->total_paid,
                'pending_count' => (int) $cat->total_pending,
                'unpaid_count' => (int) $cat->total_unpaid,
                'paid_revenue' => $paidRevenue,
                'potential_revenue' => $potentialRevenue,
                'total_registrations' => (int) $cat->total_paid + (int) $cat->total_pending + (int) $cat->total_unpaid,
            ];
        }
    }

    public function loadPendingPayments()
    {
        $this->pendingPayments = Registration::with(['competitionCategory', 'paymentBankAccount'])
            ->where('eventner_id', $this->eventner->id)
            ->where('payment_status', 'pending_verification')
            ->orderBy('updated_at', 'asc')
            ->get();
    }

    public function loadPaymentDetails()
    {
        $this->paymentDetails = Registration::with('competitionCategory')
            ->where('eventner_id', $this->eventner->id)
            ->whereIn('payment_status', ['paid', 'unpaid', 'pending_verification'])
            ->orderByRaw("CASE payment_status WHEN 'pending_verification' THEN 0 WHEN 'unpaid' THEN 1 ELSE 2 END")
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function loadRevenueData()
    {
        $eventnerId = $this->eventner->id;
        $this->revenueData = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');

            $dayFee = (float) Registration::where('eventner_id', $eventnerId)
                ->where('payment_status', 'paid')
                ->whereDate('payment_verified_at', $date)
                ->sum('total_fee');

            $dayVote = (float) VoteTransaction::where('eventner_id', $eventnerId)
                ->where('status', 'PAID')
                ->whereDate('paid_at', $date)
                ->sum('amount');

            $dayTicket = (float) Ticket::where('eventner_id', $eventnerId)
                ->whereIn('status', ['PAID', 'CHECKED_IN'])
                ->whereDate('paid_at', $date)
                ->sum('total_amount');

            $this->revenueData[] = [
                'date' => now()->subDays($i)->format('d M'),
                'fee' => (int) $dayFee,
                'vote' => (int) $dayVote,
                'ticket' => (int) $dayTicket,
                'total' => (int) ($dayFee + $dayVote + $dayTicket),
            ];
        }
    }

    public function verifyPayment($regId)
    {
        $reg = Registration::where('eventner_id', $this->eventner->id)->findOrFail($regId);
        if ($reg->payment_status !== 'pending_verification') return;

        $reg->payment_status = 'paid';
        $reg->payment_verified_at = now();
        $reg->payment_verified_by = Auth::id();
        $reg->save();

        session()->flash('success', 'Pembayaran ' . $reg->nama_sekolah . ' berhasil diverifikasi.');
        $this->closePaymentModal();
        $this->loadData();
    }

    public function rejectPayment($regId)
    {
        $reg = Registration::where('eventner_id', $this->eventner->id)->findOrFail($regId);
        if ($reg->payment_status !== 'pending_verification') return;

        $reg->payment_status = 'unpaid';
        $reg->payment_proof = null;
        $reg->payment_verified_at = null;
        $reg->payment_verified_by = null;
        $reg->save();

        session()->flash('success', 'Bukti pembayaran ' . $reg->nama_sekolah . ' ditolak. Peserta dapat upload ulang.');
        $this->closePaymentModal();
        $this->loadData();
    }

    public function openDetailModal(?int $categoryId = null)
    {
        $this->detailCategoryId = $categoryId !== null ? (string) $categoryId : 'all';
        $this->showDetailModal = true;
        $this->dispatch('open-detail-modal');
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->detailStatus = 'all';
        $this->detailCategoryId = 'all';
        $this->dispatch('close-detail-modal');
    }

    public function getDetailCategoriesProperty()
    {
        // Kategori yang punya registrasi berbayar (child categories saja)
        return $this->eventner->competitionCategories()
            ->whereNotNull('parent_id')
            ->orderBy('name')
            ->get();
    }

    public function openPaymentModal($regId)
    {
        $this->selectedPaymentRegId = $regId;
        $this->dispatch('open-payment-modal');
    }

    public function closePaymentModal()
    {
        $this->selectedPaymentRegId = null;
        $this->dispatch('close-payment-modal');
    }

    public function render()
    {
        return view('livewire.eventner.finance-dashboard');
    }
}
