<?php

namespace App\Livewire\Eventner;

use App\Models\Registration;
use App\Models\RegistrationPayment;
use App\Models\VoteTransaction;
use App\Models\Ticket;
use App\Services\PaymentRecorder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

#[Layout('layouts.admin')]
class FinanceDashboard extends Component
{
    public $eventner;

    /**
     * Uang pendaftaran yang SUDAH benar-benar masuk = SUM(amount_paid).
     *
     * Dulu angka ini menjumlah `total_fee` baris ber-status `paid`. Begitu DP
     * ada, cara itu berbohong dua kali: pasukan yang baru menyetor 30% dihitung
     * masuk 100%, dan pasukan yang menyetor 30% tidak dihitung sama sekali.
     */
    public $feeRevenue = 0;

    /** Sisa tagihan seluruh pendaftar berbayar — piutang panitia. */
    public $piutang = 0;

    /** Total tagihan yang pernah diterbitkan (snapshot harga saat booking). */
    public $totalTagihan = 0;

    public $ticketRevenue = 0;
    public $voteRevenue = 0;
    public $totalRevenue = 0;

    /** Jumlah BUKTI pembayaran yang menunggu verifikasi, bukan jumlah pendaftaran. */
    public $pendingVerificationCount = 0;

    public $selectedCategory = null;
    public $categoryBreakdown = [];
    public $pendingPayments = [];
    public $paymentDetails = [];
    public $revenueData = [];

    /** Baris registration_payments yang sedang dibuka di modal review. */
    public $selectedPaymentId = null;

    /**
     * Nominal final yang dibaca panitia dari mutasi rekening.
     *
     * Boleh berbeda dari nominal yang diklaim peserta — peserta sering salah
     * ketik, dan biaya transfer kadang dipotong bank. Yang dicatat adalah angka
     * ini, bukan angka klaim.
     */
    public $nominalDiterima;

    /** Alasan penolakan; kosong = ditolak tanpa alasan tertulis. */
    public $alasanTolak = '';

    // Filter modal detail pembayaran
    public string $detailStatus = 'all'; // all | paid | dp_paid | pending_verification | unpaid
    public string $detailCategoryId = 'all';
    public bool $showDetailModal = false;

    #[Computed]
    public function selectedPayment()
    {
        if (! $this->selectedPaymentId) {
            return null;
        }

        return RegistrationPayment::with(['registration.competitionCategory', 'bankAccount'])
            ->whereHas('registration', fn ($q) => $q->where('eventner_id', $this->eventner->id))
            ->find($this->selectedPaymentId);
    }

    /**
     * Nominal yang SEHARUSNYA dibayar untuk baris yang sedang di-review.
     *
     * Untuk DP: targetDp() pendaftarannya. Untuk pelunasan: sisa tagihannya.
     * Dipakai modal untuk menandai selisih — panitia yang melihat "klaim
     * Rp 150.000, seharusnya Rp 300.000" langsung tahu harus menelepon.
     */
    #[Computed]
    public function nominalSeharusnya()
    {
        $bayar = $this->selectedPayment;

        if (! $bayar || ! $bayar->registration) {
            return null;
        }

        $reg = $bayar->registration;

        return $bayar->isDp() ? $reg->targetDp() : $reg->sisaTagihan();
    }

    #[Computed]
    public function filteredPaymentDetails()
    {
        return collect($this->paymentDetails)
            ->when($this->detailStatus !== 'all', fn ($c) => $c->where('payment_status', $this->detailStatus))
            ->when($this->detailCategoryId !== 'all', fn ($c) => $c->where('competition_category_id', (int) $this->detailCategoryId));
    }

    public function mount()
    {
        $this->eventner = Auth::user()->eventner;
        if (! $this->eventner) abort(403);
        $this->loadData();
    }

    public function loadData()
    {
        $eventnerId = $this->eventner->id;

        // Uang pendaftaran yang benar-benar masuk. SUM(amount_paid) adalah
        // cache dari baris registration_payments ber-status verified, jadi
        // angka ini tidak pernah menghitung bukti yang belum diverifikasi.
        // sqlDibayar() menambahkan cadangan untuk pendaftaran lama yang sudah
        // ditandai lunas sebelum ledger ini ada — pasangan SQL dari
        // Registration::jumlahDibayar().
        $this->feeRevenue = (float) (Registration::where('eventner_id', $eventnerId)
            ->selectRaw('COALESCE(SUM(' . Registration::sqlDibayar() . '), 0) as total')
            ->first()?->total ?? 0);

        // Tagihan yang pernah terbit: snapshot harga saat booking, bukan harga
        // hari ini. Pendaftar gelombang pertama tidak boleh menaikkan piutang
        // hanya karena gelombangnya sudah berganti.
        $this->totalTagihan = (float) Registration::where('eventner_id', $eventnerId)
            ->where('total_fee', '>', 0)
            ->sum('total_fee');

        $this->piutang = max(0, $this->totalTagihan - $this->feeRevenue);

        $this->voteRevenue = (float) VoteTransaction::where('eventner_id', $eventnerId)
            ->where('status', 'PAID')
            ->sum('amount');

        $this->ticketRevenue = (float) Ticket::where('eventner_id', $eventnerId)
            ->whereIn('status', ['PAID', 'CHECKED_IN'])
            ->sum('total_amount');

        $this->totalRevenue = $this->feeRevenue + $this->voteRevenue + $this->ticketRevenue;

        // Antrean verifikasi sekarang per BUKTI, bukan per pendaftaran: satu
        // pasukan bisa mengirim DP lalu pelunasan, dan keduanya harus
        // diverifikasi terpisah.
        $this->pendingVerificationCount = RegistrationPayment::where('status', RegistrationPayment::STATUS_PENDING)
            ->whereHas('registration', fn ($q) => $q->where('eventner_id', $eventnerId))
            ->count();

        $this->loadCategoryBreakdown();
        $this->loadPendingPayments();
        $this->loadPaymentDetails();
        $this->loadRevenueData();
    }

    public function loadCategoryBreakdown()
    {
        $categories = $this->eventner->competitionCategories()
            ->whereNotNull('parent_id')
            ->with('gelombang')
            ->withCount(['registrations as total_paid' => fn ($q) => $q->where('payment_status', 'paid')])
            ->withCount(['registrations as total_dp' => fn ($q) => $q->where('payment_status', 'dp_paid')])
            ->withCount(['registrations as total_pending' => fn ($q) => $q->where('payment_status', 'pending_verification')])
            ->withCount(['registrations as total_unpaid' => fn ($q) => $q->where('payment_status', 'unpaid')])
            ->get();

        $this->categoryBreakdown = [];
        foreach ($categories as $cat) {
            $terkumpul = (float) (Registration::where('competition_category_id', $cat->id)
                ->selectRaw('COALESCE(SUM(' . Registration::sqlDibayar() . '), 0) as total')
                ->first()?->total ?? 0);

            $sisaKategori = (float) (Registration::where('competition_category_id', $cat->id)
                ->where('total_fee', '>', 0)
                ->selectRaw('COALESCE(SUM(total_fee - ' . Registration::sqlDibayar() . '), 0) as sisa')
                ->first()?->sisa ?? 0);

            // Potensi = harga yang berlaku hari ini × pendaftar yang belum
            // lunas. Memakai resolver, bukan kolom mentah: kalau penagih
            // memakai gelombang dan layar ini memakai kolom, angka potensi
            // yang ditampilkan bukan angka yang akan ditagih.
            $biayaKini = $cat->biayaPada();

            $potentialRevenue = $biayaKini
                ? (float) $biayaKini * Registration::where('competition_category_id', $cat->id)
                    ->whereIn('payment_status', ['unpaid', 'pending_verification'])
                    ->count()
                : 0;

            $this->categoryBreakdown[] = [
                'id' => $cat->id,
                'name' => $cat->full_name,
                'fee' => $biayaKini,
                'paid_count' => (int) $cat->total_paid,
                'dp_count' => (int) $cat->total_dp,
                'pending_count' => (int) $cat->total_pending,
                'unpaid_count' => (int) $cat->total_unpaid,
                'paid_revenue' => $terkumpul,
                'sisa_tagihan' => max(0, $sisaKategori),
                'potential_revenue' => $potentialRevenue,
                'total_registrations' => (int) $cat->total_paid + (int) $cat->total_dp
                    + (int) $cat->total_pending + (int) $cat->total_unpaid,
            ];
        }
    }

    public function loadPendingPayments()
    {
        $this->pendingPayments = RegistrationPayment::with(['registration.competitionCategory', 'bankAccount'])
            ->where('status', RegistrationPayment::STATUS_PENDING)
            ->whereHas('registration', fn ($q) => $q->where('eventner_id', $this->eventner->id))
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function loadPaymentDetails()
    {
        $this->paymentDetails = Registration::with('competitionCategory')
            ->where('eventner_id', $this->eventner->id)
            ->whereIn('payment_status', ['paid', 'dp_paid', 'unpaid', 'pending_verification'])
            ->orderByRaw("CASE payment_status WHEN 'pending_verification' THEN 0 WHEN 'unpaid' THEN 1 WHEN 'dp_paid' THEN 2 ELSE 3 END")
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function loadRevenueData()
    {
        $eventnerId = $this->eventner->id;
        $this->revenueData = [];

        // Uang masuk harian dibaca dari tanggal VERIFIKASI baris pembayaran,
        // bukan dari `registrations.payment_verified_at`: kolom itu hanya
        // terisi saat lunas, sehingga DP yang diterima hari ini tidak akan
        // pernah muncul di grafik.
        $perHari = RegistrationPayment::where('status', RegistrationPayment::STATUS_VERIFIED)
            ->whereHas('registration', fn ($q) => $q->where('eventner_id', $eventnerId))
            ->where('verified_at', '>=', now()->subDays(30)->startOfDay())
            ->selectRaw('DATE(verified_at) as hari, SUM(amount) as total')
            ->groupBy(DB::raw('DATE(verified_at)'))
            ->pluck('total', 'hari');

        for ($i = 29; $i >= 0; $i--) {
            $tanggal = now()->subDays($i);
            $kunci = $tanggal->format('Y-m-d');

            $dayFee = (float) ($perHari[$kunci] ?? 0);

            $dayVote = (float) VoteTransaction::where('eventner_id', $eventnerId)
                ->where('status', 'PAID')
                ->whereDate('paid_at', $kunci)
                ->sum('amount');

            $dayTicket = (float) Ticket::where('eventner_id', $eventnerId)
                ->whereIn('status', ['PAID', 'CHECKED_IN'])
                ->whereDate('paid_at', $kunci)
                ->sum('total_amount');

            $this->revenueData[] = [
                'date' => $tanggal->format('d M'),
                'fee' => (int) $dayFee,
                'vote' => (int) $dayVote,
                'ticket' => (int) $dayTicket,
                'total' => (int) ($dayFee + $dayVote + $dayTicket),
            ];
        }
    }

    /**
     * Terima satu bukti pembayaran.
     *
     * Nominal yang dipakai adalah yang dibaca panitia dari mutasi rekening
     * (`$nominalDiterima`), bukan yang diklaim peserta. Semua penulisan status
     * lewat PaymentRecorder — komponen ini tidak menyentuh `payment_status`
     * sendiri.
     */
    public function verifyPayment($paymentId)
    {
        $bayar = $this->cariBukti($paymentId);
        if (! $bayar || ! $bayar->isPending()) {
            return;
        }

        $this->validate([
            'nominalDiterima' => 'nullable|numeric|min:0',
        ], [], [
            'nominalDiterima' => 'Nominal diterima',
        ]);

        $nominal = $this->nominalDiterima !== null && $this->nominalDiterima !== ''
            ? (float) $this->nominalDiterima
            : null;

        app(PaymentRecorder::class)->terima($bayar, Auth::user(), $nominal);

        $reg = $bayar->registration->fresh();

        session()->flash('success', 'Pembayaran ' . $reg->display_name . ' diterima. Status: '
            . ($reg->isPaid() ? 'LUNAS' : 'DP — sisa Rp ' . number_format($reg->sisaTagihan(), 0, ',', '.') . '.'));

        $this->closePaymentModal();
        $this->loadData();
    }

    public function rejectPayment($paymentId)
    {
        $bayar = $this->cariBukti($paymentId);
        if (! $bayar || ! $bayar->isPending()) {
            return;
        }

        app(PaymentRecorder::class)->tolak($bayar, Auth::user(), $this->alasanTolak ?: null);

        session()->flash('success', 'Bukti pembayaran ' . $bayar->registration->display_name . ' ditolak. Peserta dapat upload ulang.');
        $this->closePaymentModal();
        $this->loadData();
    }

    /** Bukti pembayaran milik eventner ini; null bila bukan haknya. */
    private function cariBukti($paymentId): ?RegistrationPayment
    {
        return RegistrationPayment::with('registration')
            ->whereHas('registration', fn ($q) => $q->where('eventner_id', $this->eventner->id))
            ->find($paymentId);
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
        return $this->eventner->competitionCategories()
            ->whereNotNull('parent_id')
            ->orderBy('name')
            ->get();
    }

    /**
     * Buka modal review untuk satu baris pembayaran.
     *
     * Nominal final diisi awal dari nominal klaim peserta supaya panitia hanya
     * perlu mengubahnya bila mutasi rekening berkata lain.
     */
    public function openPaymentModal($paymentId)
    {
        $bayar = $this->cariBukti($paymentId);
        if (! $bayar) {
            return;
        }

        $this->selectedPaymentId = $bayar->id;
        $this->nominalDiterima = (float) $bayar->amount;
        $this->alasanTolak = '';
        $this->dispatch('open-payment-modal');
    }

    public function closePaymentModal()
    {
        $this->selectedPaymentId = null;
        $this->nominalDiterima = null;
        $this->alasanTolak = '';
        $this->dispatch('close-payment-modal');
    }

    public function render()
    {
        return view('livewire.eventner.finance-dashboard')->title('Dashboard Keuangan - ' . app_name());
    }
}
