<?php

namespace App\Livewire\Public;

use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\VoteTransaction;
use App\Services\AutoGoPay;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.frontend')]
class EventVote extends Component
{
    public $eventner;
    public $view = 'categories'; // 'categories', 'participants', 'payment', 'success'
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
        'search' => ['except' => ''],
        'selectedCategoryId' => ['except' => ''],
    ];

    protected $rules = [
        'selectedRegistrationId' => 'required|exists:registrations,id',
        'voterName' => 'required|string|max:255',
        'voterEmail' => 'required|email|max:255',
        'voteCount' => 'required|integer|min:1',
    ];

    public function mount($slug)
    {
        $this->eventner = Eventner::where('slug', $slug)->firstOrFail();

        if (!$this->eventner->vote_active) {
            abort(403, 'Fitur Vote Online untuk event ini tidak aktif.');
        }

        if ($this->selectedCategoryId) {
            $this->view = 'participants';
        }
    }

    public function selectCategory($id)
    {
        $this->selectedCategoryId = $id;
        $this->view = 'participants';
    }

    public function backToCategories()
    {
        $this->view = 'categories';
        $this->selectedCategoryId = null;
        $this->search = '';
    }

    public function selectTeam($id)
    {
        $this->selectedRegistrationId = $id;
    }

    public function incrementVote()
    {
        $this->voteCount = (int)$this->voteCount + 1;
    }

    public function decrementVote()
    {
        $this->voteCount = max(1, (int)$this->voteCount - 1);
    }

    public function submitVote()
    {
        if (RateLimiter::tooManyAttempts('vote-submit:' . request()->ip(), $maxAttempts = 5)) {
            session()->flash('error', 'Terlalu banyak permintaan. Silakan coba lagi dalam satu menit.');
            return;
        }

        RateLimiter::hit('vote-submit:' . request()->ip(), $decaySeconds = 60);

        if (!$this->eventner->vote_active) {
            session()->flash('error', 'Fitur Vote Online sudah ditutup.');
            return;
        }

        $this->validate();

        $amount = $this->voteCount * ($this->eventner->vote_price ?? 1000);

        try {
            // Generate QRIS via AutoGoPay
            $service = new AutoGoPay();
            $result = $service->generateQris($amount);

            if (!($result['success'] ?? false)) {
                session()->flash('error', 'Gagal membuat QRIS. Silakan coba lagi.');
                return;
            }

            $data = $result['data'];

            // Simpan transaksi PENDING
            $transaction = VoteTransaction::create([
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

            // Tampilkan QR code
            $this->qrImageUrl = $data['qr_url'];
            $this->expiryTime = $data['expiry_time'];
            $this->currentTransactionId = $transaction->id;
            $this->autoGoPayTransactionId = $data['transaction_id'];
            $this->paymentAmount = $amount;
            $this->paymentConfirmed = false;
            $this->view = 'payment';

        } catch (\Exception $e) {
            Log::error('AutoGoPay QRIS generation failed (vote)', [
                'registration_id' => $this->selectedRegistrationId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Gagal membuat QRIS: ' . $e->getMessage());
        }
    }

    /**
     * Polling untuk cek status pembayaran (fallback jika webhook delay).
     */
    public function checkPaymentStatus()
    {
        if (!$this->autoGoPayTransactionId || $this->paymentConfirmed) {
            return;
        }

        // Cek dari database dulu (lebih cepat jika webhook sudah masuk)
        $tx = VoteTransaction::find($this->currentTransactionId);
        if ($tx && $tx->status === 'PAID') {
            $this->paymentConfirmed = true;
            $this->view = 'success';
            return;
        }

        if ($tx && $tx->status === 'EXPIRED') {
            $this->view = 'participants';
            session()->flash('error', 'Pembayaran kedaluwarsa. Silakan coba lagi.');
            return;
        }

        // Fallback: cek langsung ke AutoGoPay API
        try {
            $service = new AutoGoPay();
            $result = $service->checkStatus($this->autoGoPayTransactionId);

            $status = $result['data']['transaction_status'] ?? 'pending';

            if ($status === 'settlement') {
                // Update di database
                if ($tx && $tx->status !== 'PAID') {
                    $tx->update(['status' => 'PAID', 'paid_at' => now()]);
                }
                $this->paymentConfirmed = true;
                $this->view = 'success';
            } elseif ($status === 'expire') {
                if ($tx && $tx->status !== 'EXPIRED') {
                    $tx->update(['status' => 'EXPIRED']);
                }
                $this->view = 'participants';
                session()->flash('error', 'Pembayaran kedaluwarsa. Silakan coba lagi.');
            }
        } catch (\Exception $e) {
            // Silently fail — akan retry di polling berikutnya
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
        $this->view = 'participants';
    }

    public function render()
    {
        $participants = collect();
        $selectedCategory = null;

        if ($this->selectedCategoryId) {
            $selectedCategory = CompetitionCategory::find($this->selectedCategoryId);

            $query = Registration::where('competition_category_id', $this->selectedCategoryId)
                ->withSum(['voteTransactions as total_votes' => function($q) {
                    $q->where('status', 'PAID');
                }], 'votes_earned')
                ->orderByDesc('total_votes');

            if ($this->search) {
                $query->where(function ($q) {
                    $q->where('nama_sekolah', 'like', '%' . $this->search . '%')
                      ->orWhere('nama_pelatih', 'like', '%' . $this->search . '%');
                });
            }

            $participants = $query->get();
        }

        return view('livewire.public.event-vote', [
            'participants' => $participants,
            'selectedCategory' => $selectedCategory,
            'categories' => $this->eventner->competitionCategories->loadCount('registrations')
        ])->title('Vote Peserta - ' . $this->eventner->nama_event)
         ->layoutData(['eventner' => $this->eventner]);
    }
}
