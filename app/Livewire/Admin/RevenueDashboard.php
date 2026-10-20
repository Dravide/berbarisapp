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
class RevenueDashboard extends Component
{
    public $saasRevenue = 0;      // fee registrasi eventner paid
    public $ticketRevenue = 0;
    public $voteRevenue = 0;
    public $totalRevenue = 0;

    public $paidEventners = 0;
    public $paidLangsung = 0;        // berbayar tanpa pernah lewat trial
    public $conversionRate = 0;      // trial → berbayar, bukan pangsa pasar

    /*
     * Corong paket. Setiap eventner masuk TEPAT SATU keranjang, dan
     * penggolongannya satu rantai match di loadSummary() — bukan lima
     * filter terpisah seperti versi sebelumnya.
     *
     * Versi lama memakai `plan` sebagai satu-satunya pemilah, dan `plan`
     * tak bisa memisahkan apa pun yang penting di sini: 'free' dipakai
     * bersama oleh trial dan paket gratis terpasang, sementara 'paid'
     * dipakai bersama oleh pembeli dan pendaftar yang QRIS-nya belum lunas.
     * Angka campuran itu terbaca seolah "tingkat konversi", padahal bukan.
     */
    public $trialAktif = 0;          // masa uji masih berjalan
    public $trialHabis = 0;          // masa uji habis tanpa membeli — prospek
    public $trialConverted = 0;      // masa uji berakhir dengan pembelian
    public $diberikanAdmin = 0;      // diaktifkan admin tanpa bayar
    public $percobaanBerbayar = 0;   // pilih paket berbayar, QRIS belum lunas
    public $belumPernahTrial = 0;    // sisa: legacy tanpa jejak apa pun

    public $monthlyData = [];
    public $topEventners = [];

    public function mount()
    {
        $this->loadSummary();
        $this->loadMonthlyData();
        $this->loadTopEventners();
    }

    private function loadSummary(): void
    {
        $planPrice = (int) \App\Models\Setting::get('eventner_plan_price', 150000);

        // Semua eventner dimuat sekali lalu digolongkan SATU rantai match,
        // bukan sekumpulan where() terpisah per kartu seperti sebelumnya.
        // Versi lama begitu, tiap kartu memakai potongan syarat sendiri, dan
        // itulah tepatnya bagaimana trial bisa tercampur dengan paket gratis
        // terpasang sampai terbaca sebagai satu angka "konversi". Satu rantai
        // menjamin tiap eventner masuk tepat satu keranjang, jadi kartunya
        // bisa dijumlahkan pembaca tanpa saling menutupi.
        $keranjang = [
            'trialAktif' => 0, 'trialHabis' => 0, 'trialConverted' => 0,
            'paidLangsung' => 0, 'diberikanAdmin' => 0,
            'percobaanBerbayar' => 0, 'belumPernahTrial' => 0,
        ];

        foreach (Eventner::all() as $e) {
            $keranjang[match (true) {
                // Pembelian sungguhan: paket berbayar + pembayaran tercatat +
                // daftar mandiri. Sumbernya wajib 'self' karena hanya pendaftar
                // mandiri yang melewati QRIS; paket berbayar yang diberikan
                // admin lewat assignPlan() juga menstempel registration_paid_at,
                // jadi tanpa syarat itu hibah admin terhitung sebagai kas.
                $e->isPembeli() => $e->pernahTrial() ? 'trialConverted' : 'paidLangsung',
                // Diaktifkan tanpa bayar. Diletakkan SEBELUM cabang `plan`
                // di bawah: paket berbayar yang diberikan admin juga
                // registration_paid_at terisi, dan kalau `plan === 'paid'`
                // lebih dulu, ia salah masuk keranjang "belum bayar".
                $e->registration_paid_at !== null => 'diberikanAdmin',
                // Memilih paket berbayar, QRIS belum lunas — bukan pendapatan.
                $e->plan === 'paid' => 'percobaanBerbayar',
                $e->isOnTrial() => 'trialAktif',
                $e->isTrialExpired() => 'trialHabis',
                default => 'belumPernahTrial',
            }]++;
        }

        foreach ($keranjang as $nama => $jumlah) {
            $this->{$nama} = $jumlah;
        }

        // Pendapatan SaaS: hanya pembelian sungguhan, yang tersebar di dua
        // keranjang (pembeli langsung dan trial yang berujung beli).
        $this->paidEventners = $this->trialConverted + $this->paidLangsung;
        $this->saasRevenue = (float) $this->paidEventners * $planPrice;

        $this->ticketRevenue = (float) Ticket::whereIn('status', ['PAID', 'CHECKED_IN'])->sum('total_amount');
        $this->voteRevenue = (float) VoteTransaction::where('status', 'PAID')->sum('amount');

        $this->totalRevenue = $this->saasRevenue + $this->ticketRevenue + $this->voteRevenue;

        // Konversi = pangsa kohort trial yang berakhir dengan pembelian.
        // Pembaginya sengaja hanya trial yang SUDAH diputuskan: masa uji yang
        // masih berjalan belum menjawab apa pun, jadi memasukkannya membuat
        // angka ini turun hanya karena ada pendaftar baru — persis kebalikan
        // dari yang ingin dibaca panel ini.
        $diputuskan = $this->trialConverted + $this->trialHabis;
        $this->conversionRate = $diputuskan > 0
            ? round($this->trialConverted / $diputuskan * 100, 1)
            : 0;
    }

    private function loadMonthlyData(): void
    {
        $start = now()->subMonths(11)->startOfMonth();

        // DATE_FORMAT = MySQL; test suite pakai sqlite → ganti strftime
        $ymExpr = config('database.default') === 'sqlite'
            ? "strftime('%Y-%m', %col)"
            : "DATE_FORMAT(%col, '%Y-%m')";

        // Aktivasi paket per bulan (dari registration_paid_at).
        //
        // Disaring `plan='paid'` + `registration_source='self'`: assignPlan()
        // menstempel registration_paid_at untuk paket GRATIS hibah admin juga,
        // jadi tanpa saringan ini bulan-bulan yang cuma berisi pemberian admin
        // menampilkan lonjakan pendapatan yang tak pernah masuk kas.
        $activations = Eventner::select(
                DB::raw(str_replace('%col', 'registration_paid_at', $ymExpr) . ' as ym'),
                DB::raw('COUNT(*) as total')
            )
            ->where('plan', 'paid')
            ->where('registration_source', 'self')
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
                'eventners.registration_source',
                'eventners.registration_paid_at',
                'users.name as owner_name',
                DB::raw('COALESCE(tk.ticket_total, 0) as ticket_total'),
                DB::raw('COALESCE(vt.vote_total, 0) as vote_total'),
                DB::raw('COALESCE(tk.ticket_total, 0) + COALESCE(vt.vote_total, 0) as grand_total'),
            ])
            ->map(function (Eventner $row) {
                // Badge dari fakta pembayaran, bukan dari `plan`: pendaftar
                // paket berbayar ber-plan 'paid' sejak mendaftar, jadi badge
                // dari `plan` saja menandai hijau untuk yang QRIS-nya belum
                // dibayar — dan abu-abu untuk paket yang justru sudah
                // diaktifkan admin. Predikatnya sama dengan penggolongan
                // corong di loadSummary(), lewat model.
                $row->setAttribute('paket_label', match (true) {
                    $row->isPembeli() => 'Berbayar',
                    $row->registration_paid_at !== null => 'Diberikan admin',
                    $row->plan === 'paid' => 'Belum bayar',
                    default => 'Gratis',
                });

                $row->setAttribute('paket_class', match ($row->paket_label) {
                    'Berbayar' => 'bg-success',
                    'Belum bayar' => 'bg-warning',
                    default => 'bg-secondary',
                });

                return $row;
            })
            ->toArray();
    }

    public function render()
    {
        return view('livewire.admin.revenue-dashboard')->title('Pendapatan Platform - ' . app_name());
    }
}
