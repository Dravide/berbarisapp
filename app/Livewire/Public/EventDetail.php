<?php

namespace App\Livewire\Public;

use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use App\Services\AutoGoPay;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;

#[Layout('layouts.frontend')]
class EventDetail extends Component
{
    use WithFileUploads;

    public $eventner;

    // Tab state
    public $tab = 'info';
    public $registration_status;

    // Vote state
    public $voteView = 'categories';
    public $selectedCategoryId;
    public $search = '';
    public $selectedRegistrationId;
    public $voterName;
    public $voterEmail;
    public $voteCount = 10;

    // Payment state
    public $qrImageUrl;
    public $expiryTime;
    public $currentTransactionId;
    public $autoGoPayTransactionId;
    public $paymentAmount;
    public $paymentConfirmed = false;

    protected $queryString = [
        'tab' => ['except' => 'info'],
        'selectedCategoryId' => ['except' => ''],
        'search' => ['except' => ''],
    ];

    protected $rules = [
        'selectedRegistrationId' => 'required|exists:registrations,id',
        'voterName' => 'required|string|max:255',
        'voterEmail' => 'required|email|max:255',
        'voteCount' => 'required|integer|min:1',
    ];

    public function mount($slug)
    {
        $this->eventner = Eventner::with([
            'competitionCategories.registrations' => function ($query) {
                $query->withSum(['voteTransactions as total_votes' => function ($q) {
                    $q->where('status', 'PAID');
                }], 'votes_earned')
                    ->where('status_berkas', '!=', 'dibatalkan')
                    ->orderBy('urutan_tampil', 'asc');
            },
            'competitionCategories.registrations.participants',
            'judges.assessmentCategories',
            'sponsors' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order')->latest();
            },
            'tenants' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order')->latest();
            },
        ])->where('slug', $slug)->firstOrFail();

        $this->registration_status = $this->eventner->registration_status ?? 'open';

        if ($this->selectedCategoryId) {
            $this->voteView = 'participants';
        }

        if (!in_array($this->tab, ['info', 'participants', 'vote'])) {
            $this->tab = 'info';
        }
    }

    // Tab switching
    public function setTab($tab)
    {
        $this->tab = $tab;
        if ($tab === 'vote') {
            $this->voteView = 'categories';
            $this->selectedCategoryId = null;
            $this->search = '';
        }
    }

    // Vote methods
    public function selectCategory($id)
    {
        $this->selectedCategoryId = $id;
        $this->voteView = 'participants';
    }

    public function backToCategories()
    {
        $this->voteView = 'categories';
        $this->selectedCategoryId = null;
        $this->search = '';
    }

    public function selectTeam($id)
    {
        $this->selectedRegistrationId = $id;
    }

    public function submitVote()
    {
        if (RateLimiter::tooManyAttempts('vote-submit:' . request()->ip(), 5)) {
            session()->flash('error', 'Terlalu banyak permintaan. Silakan coba lagi dalam satu menit.');
            return;
        }

        RateLimiter::hit('vote-submit:' . request()->ip(), 60);

        if (!$this->eventner->vote_active) {
            session()->flash('error', 'Fitur Vote Online sudah ditutup.');
            return;
        }

        $this->validate();

        $amount = $this->voteCount * ($this->eventner->vote_price ?? 1000);

        try {
            $service = new AutoGoPay();
            $result = $service->generateQris($amount);

            if (!($result['success'] ?? false)) {
                session()->flash('error', 'Gagal membuat QRIS. Silakan coba lagi.');
                return;
            }

            $data = $result['data'];

            $transaction = \App\Models\VoteTransaction::create([
                'eventner_id' => $this->eventner->id,
                'registration_id' => $this->selectedRegistrationId,
                'autogopay_transaction_id' => $data['transaction_id'],
                'qr_url' => $data['qr_url'],
                'amount' => $amount,
                'votes_earned' => $this->voteCount,
                'voter_name' => $this->voterName,
                'voter_email' => $this->voterEmail,
                'status' => 'PENDING',
            ]);

            $this->qrImageUrl = $data['qr_url'];
            $this->expiryTime = $data['expiry_time'];
            $this->currentTransactionId = $transaction->id;
            $this->autoGoPayTransactionId = $data['transaction_id'];
            $this->paymentAmount = $amount;
            $this->paymentConfirmed = false;
            $this->voteView = 'payment';
        } catch (\Exception $e) {
            Log::error('AutoGoPay QRIS generation failed (vote)', [
                'registration_id' => $this->selectedRegistrationId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Gagal membuat QRIS: ' . $e->getMessage());
        }
    }

    public function checkPaymentStatus()
    {
        if (!$this->autoGoPayTransactionId || $this->paymentConfirmed) {
            return;
        }

        $tx = \App\Models\VoteTransaction::find($this->currentTransactionId);
        if ($tx && $tx->status === 'PAID') {
            $this->paymentConfirmed = true;
            $this->voteView = 'success';
            return;
        }

        if ($tx && $tx->status === 'EXPIRED') {
            $this->voteView = 'participants';
            session()->flash('error', 'Pembayaran kedaluwarsa. Silakan coba lagi.');
            return;
        }

        try {
            $service = new AutoGoPay();
            $result = $service->checkStatus($this->autoGoPayTransactionId);
            $status = $result['data']['transaction_status'] ?? 'pending';

            if ($status === 'settlement') {
                if ($tx && $tx->status !== 'PAID') {
                    $tx->update(['status' => 'PAID', 'paid_at' => now()]);
                }
                $this->paymentConfirmed = true;
                $this->voteView = 'success';
            } elseif ($status === 'expire') {
                if ($tx && $tx->status !== 'EXPIRED') {
                    $tx->update(['status' => 'EXPIRED']);
                }
                $this->voteView = 'participants';
                session()->flash('error', 'Pembayaran kedaluwarsa. Silakan coba lagi.');
            }
        } catch (\Exception $e) {
            Log::warning('AutoGoPay status check failed', ['error' => $e->getMessage()]);
        }
    }

    public function resetPayment()
    {
        $this->qrImageUrl = null;
        $this->expiryTime = null;
        $this->currentTransactionId = null;
        $this->autoGoPayTransactionId = null;
        $this->paymentAmount = null;
        $this->paymentConfirmed = false;
        $this->voteView = 'participants';
    }

    #[Computed]
    public function voteCategories()
    {
        return $this->eventner->competitionCategories->loadCount('registrations');
    }

    #[Computed]
    public function voteParticipants()
    {
        if (!$this->selectedCategoryId) {
            return collect();
        }

        $query = Registration::where('competition_category_id', $this->selectedCategoryId)
            ->withSum(['voteTransactions as total_votes' => function ($q) {
                $q->where('status', 'PAID');
            }], 'votes_earned')
            ->orderByDesc('total_votes');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nama_sekolah', 'like', '%' . $this->search . '%')
                    ->orWhere('nama_pelatih', 'like', '%' . $this->search . '%');
            });
        }

        return $query->get();
    }

    #[Computed]
    public function relatedEvents()
    {
        return Eventner::where('id', '!=', $this->eventner->id)
            ->whereNotNull('slug')
            ->latest()
            ->take(3)
            ->get();
    }

    #[Title('Detail Event')]
    public function render()
    {
        return view('livewire.public.event-detail', [
            'eventner' => $this->eventner,
        ])->title($this->eventner->nama_event . ' - BARIS APP')
            ->layoutData(['eventner' => $this->eventner]);
    }
}
