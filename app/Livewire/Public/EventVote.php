<?php

namespace App\Livewire\Public;

use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\VoteBooster;
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
    public $voterComment = '';

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

    public function mount($slug = null)
    {
        $resolved = app()->bound('current_eventner') ? app('current_eventner') : null;
        if ($resolved) {
            $this->eventner = $resolved;
        } else {
            $this->eventner = Eventner::with(['competitionCategories' => function ($q) {
                $q->whereNotNull('parent_id')->withCount('registrations');
            }])->approved()->where('slug', $slug)->firstOrFail();
        }

        // Cek voting ditutup
        if (!$this->eventner->vote_active) {
            if (in_array($this->view, ['payment', 'success'])) {
                $this->view = $this->selectedCategoryId ? 'participants' : 'categories';
            }
        }

        // Cek jadwal
        if ($this->eventner->vote_active && $this->eventner->vote_end && now()->gt($this->eventner->vote_end)) {
            $this->view = 'closed';
        }
        if ($this->eventner->vote_active && $this->eventner->vote_start && now()->lt($this->eventner->vote_start)) {
            $this->view = 'scheduled';
        }

        // Only allow participant view if voting is active and not scheduled/closed
        if ($this->selectedCategoryId && !in_array($this->view, ['scheduled', 'closed'])) {
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
        // Peserta yang boleh divote HANYA milik event ini. Tampaknya id-nya
        // memang cuma dirender dari daftar event ini, tapi $id datang dari
        // DOM dan bisa diganti klien — tanpa cek ini suara (dan uang) bisa
        // dibukukan ke peserta event lain.
        $milikEventIni = Registration::where('eventner_id', $this->eventner->id)->find($id);

        if (!$milikEventIni) {
            $this->selectedRegistrationId = null;
            session()->flash('error', 'Peserta tidak ditemukan pada event ini.');
            return;
        }

        $this->selectedRegistrationId = $milikEventIni->id;
    }

    public function incrementVote()
    {
        $this->voteCount = (int)$this->voteCount + 1;
    }

    public function decrementVote()
    {
        $this->voteCount = max(1, (int)$this->voteCount - 1);
    }

    /**
     * Apakah voting sedang terbuka — satu-satunya sumber aturan ini.
     *
     * mount() memeriksa jadwal (vote_start/vote_end) untuk memilih tampilan,
     * tapi submitVote() hanya memeriksa vote_active. Karena view == 'payment'
     * TIDAK pernah dikembalikan ke 'closed'/'scheduled' oleh mount(), pembeli
     * yang membuka halaman sebelum vote_start masih memegang form lama: ia
     * bisa menekan bayar setelah voting ditutup (atau sebelum dibuka) dan
     * QRIS-nya tetap dibuat — uangnya masuk, suaranya tidak pernah sah.
     *
     * @return array{0:bool,1:string} [terbuka, pesan alasan bila tertutup]
     */
    public function votingState(): array
    {
        if (! $this->eventner->vote_active) {
            return [false, 'Fitur Vote Online sudah ditutup.'];
        }

        if ($this->eventner->vote_start && now()->lt($this->eventner->vote_start)) {
            return [false, 'Voting belum dibuka.'];
        }

        if ($this->eventner->vote_end && now()->gt($this->eventner->vote_end)) {
            return [false, 'Voting sudah ditutup.'];
        }

        return [true, ''];
    }

    public function submitVote()
    {
        if (RateLimiter::tooManyAttempts('vote-submit:' . request()->ip(), $maxAttempts = 5)) {
            session()->flash('error', 'Terlalu banyak permintaan. Silakan coba lagi dalam satu menit.');
            return;
        }

        RateLimiter::hit('vote-submit:' . request()->ip(), $decaySeconds = 60);

        [$terbuka, $pesan] = $this->votingState();

        if (! $terbuka) {
            session()->flash('error', $pesan);
            $this->view = $terbuka ? $this->view : 'participants';

            return;
        }

        $this->validate();

        $basePrice = $this->eventner->vote_price ?? 1000;
        $multiplier = 1;

        // Cek vote booster aktif
        $activeBooster = VoteBooster::where('eventner_id', $this->eventner->id)
            ->active()
            ->orderByDesc('vote_multiplier')
            ->first();

        if ($activeBooster) {
            $multiplier = $activeBooster->vote_multiplier;
        }

        $totalVotes = $this->voteCount * $multiplier;
        $amount = $this->voteCount * $basePrice; // harga tetap per transaksi

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
                'votes_earned' => $totalVotes,
                'voter_name' => $this->voterName,
                'voter_email' => $this->voterEmail,
                'comment' => strip_tags($this->voterComment),
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

        // Status EXPIRED tidak langsung dipercaya sebagai akhir: pembeli bisa
        // menyelesaikan pembayaran setelah QR dinyatakan kedaluwarsa. Karena
        // itu tetap dicek ke gateway — kalau ternyata settlement, vote-nya
        // dikreditkan (claimPaid menerima PENDING maupun EXPIRED).
        $expired = $tx && $tx->status === 'EXPIRED';

        // Fallback: cek langsung ke AutoGoPay API
        try {
            $service = new AutoGoPay();
            $result = $service->checkStatus($this->autoGoPayTransactionId);

            $status = $result['data']['transaction_status'] ?? 'pending';

            if ($status === 'settlement') {
                // Atomic claim — siapa yang duluan klaim yang menang.
                //
                // claimPaid() mengembalikan false bila barisnya sudah PAID
                // atau sudah tidak bisa diklaim. Dulu hasilnya diabaikan dan
                // halaman selalu bilang "berhasil", padahal bisa saja klaim
                // itu kalah dari webhook/polling lain — pembeli melihat sukses
                // untuk suara yang tidak pernah bertambah.
                $terklaim = $tx ? $tx->claimPaid() : false;

                if ($terklaim || ($tx && $tx->fresh()?->status === 'PAID')) {
                    $this->paymentConfirmed = true;
                    $this->view = 'success';
                } else {
                    session()->flash('error', 'Pembayaran belum bisa dikonfirmasi. Tunggu sebentar, lalu buka lagi halaman ini.');
                }
            } elseif ($status === 'expire') {
                if ($expired) {
                    $this->view = 'participants';
                    session()->flash('error', 'Pembayaran kedaluwarsa. Silakan coba lagi.');
                    return;
                }

                if ($tx) {
                    VoteTransaction::where('id', $tx->id)
                        ->where('status', 'PENDING')
                        ->update(['status' => 'EXPIRED']);
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

    /**
     * Jumlah vote yang BENAR-BENAR tercatat untuk transaksi yang baru dibayar.
     *
     * voteCount cuma jumlah yang diketik pembeli; yang tersimpan di
     * votes_earned sudah dikali vote booster aktif. Halaman sukses dulu
     * mencetak voteCount apa adanya, jadi saat booster jalan pembeli melihat
     * angka yang lebih kecil daripada yang benar-benar masuk ke peringkat.
     */
    public function getCreditedVotesProperty(): int
    {
        if ($this->currentTransactionId) {
            $tx = VoteTransaction::find($this->currentTransactionId);

            if ($tx) {
                return (int) $tx->votes_earned;
            }
        }

        return (int) $this->voteCount;
    }

    public function getActiveBoosterProperty()
    {
        return VoteBooster::where('eventner_id', $this->eventner->id)
            ->active()
            ->orderByDesc('vote_multiplier')
            ->first();
    }

    public function getAllBoostersProperty()
    {
        return VoteBooster::where('eventner_id', $this->eventner->id)
            ->where('is_active', true)
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * Top 10 pendukung seluruh kategori — agregasi per email voter.
     */
    public function getTopSupportersProperty()
    {
        return VoteTransaction::where('eventner_id', $this->eventner->id)
            ->where('status', 'PAID')
            ->whereNotNull('voter_email')
            ->selectRaw('voter_email, MAX(voter_name) as voter_name, SUM(votes_earned) as total_votes, COUNT(*) as total_transactions')
            ->groupBy('voter_email')
            ->orderByDesc('total_votes')
            ->limit(10)
            ->get();
    }

    public function getClosedVoteResultsProperty()
    {
        return $this->eventner->competitionCategories()
            ->whereNotNull('parent_id')
            ->with(['registrations' => function ($q) {
                $q->withSum(['voteTransactions as total_votes' => function ($q) {
                    $q->where('status', 'PAID');
                }], 'votes_earned')->orderByDesc('total_votes');
            }])
            ->get();
    }

    public function render()
    {
        $participants = collect();
        $selectedCategory = null;
        $recentComments = collect();

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

            // Load recent comments per participant
            $regIds = $participants->pluck('id');
            $recentComments = \App\Models\VoteTransaction::whereIn('registration_id', $regIds)
                ->where('status', 'PAID')
                ->whereNotNull('comment')
                ->where('comment', '!=', '')
                ->orderByDesc('paid_at')
                ->get()
                ->groupBy('registration_id');

        }

        // All comments for floating widget (from all participants in event)
        $allComments = \App\Models\VoteTransaction::with('registration')
            ->where('eventner_id', $this->eventner->id)
            ->where('status', 'PAID')
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->orderByDesc('paid_at')
            ->limit(50)
            ->get();

        // Total vote count across all participants
        $totalEventVotes = \App\Models\VoteTransaction::where('eventner_id', $this->eventner->id)
            ->where('status', 'PAID')
            ->sum('votes_earned');

        return view('livewire.public.event-vote', [
            'participants' => $participants,
            'selectedCategory' => $selectedCategory,
            'categories' => $this->eventner->competitionCategories()->whereNotNull('parent_id')->with('parent')->orderBy('sort_order')->get(),
            'recentComments' => $recentComments,
            'allComments' => $allComments ?? collect(),
            'totalEventVotes' => $totalEventVotes ?? 0,
        ])->title('Vote Peserta - ' . $this->eventner->nama_event)
         ->layoutData(['eventner' => $this->eventner]);
    }
}
