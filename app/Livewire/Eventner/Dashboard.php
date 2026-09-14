<?php

namespace App\Livewire\Eventner;

use Livewire\Component;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\VoteBooster;
use App\Models\VoteTransaction;
use App\Models\Ticket;
use App\Models\Judge;
use App\Models\AssessmentScore;
use App\Models\CompetitionCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
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

    // Stat card tambahan
    public $totalVotes = 0;
    public $ticketsSold = 0;
    public $ticketsCheckedIn = 0;
    public $pendingVerificationCount = 0;
    public $berkasMenungguCount = 0;

    // Voting
    public $voteStatus = 'nonaktif';
    public $voteTimeRemaining = null;
    public $votePaidCount = 0;
    public $votePendingCount = 0;
    public $activeBooster;

    // Jadwal & countdown
    public $daysUntilEvent;

    // Trial & Feature gating
    public $trialDaysLeft = 0;
    public $isTrialExpired = false;
    public $lockedFeatures = [];
    public $planInfo = [];

    // Chart data
    public $selectedChartCategory = null;
    public $scoringProgress = [];
    public $revenueData = [];
    public $topParticipants = [];

    // Command center
    public $readiness = [];
    public $readinessPercent = 0;
    public $alerts = [];
    public $checkinsToday = 0;

    // KPI row 2 — modul lain
    public $formatNilaiReady = false;
    public $kuotaFilled = 0;
    public $kuotaTotal = 0;
    public $drawnTotal = 0;
    public $drawnGrandTotal = 0;
    public $scoredTotal = 0;
    public $participantTotal = 0;

    public function mount()
    {
        $this->eventner = Auth::user()->eventner;

        if (!$this->eventner) {
            abort(403, 'Anda belum memiliki data Event terdaftar.');
        }

        // Trial & feature gate info — sebelum loadData (alerts butuh trialDaysLeft)
        $this->trialDaysLeft = $this->eventner->trialDaysLeft();
        $this->isTrialExpired = $this->eventner->isTrialExpired();
        $this->lockedFeatures = $this->eventner->lockedFeatures();
        $this->planInfo = $this->buildPlanInfo();

        $this->loadData();
    }

    /**
     * Ringkasan paket untuk header dashboard: nama paket, statusnya, dan
     * fitur apa saja yang boleh dipakai.
     *
     * Semua daftar diambil dari helper yang sama dengan penegakan akses
     * (canAccessFeature/lockedFeatures), jadi header tidak akan pernah
     * berbeda dari apa yang benar-benar bisa dibuka eventner ini.
     *
     * 'included' HANYA berisi fitur ber-locked_free (yakni yang tercatat di
     * config/eventner_features.php). Fitur di luar config — dashboard,
     * peserta, juri, input nilai, scoreboard — tersedia untuk semua paket,
     * jadi menampilkannya hanya akan menambah derau tanpa informasi.
     *
     * Paket gratis tidak menyimpan satu pun feature_key, jadi 'included'-nya
     * memang kosong: itu benar, bukan bug.
     */
    private function buildPlanInfo(): array
    {
        $e = $this->eventner;
        $gated = Config::get('eventner_features', []);

        // Kunci paket SaaS: satu-satunya sumber kebenaran soal fitur mana yang
        // termasuk paket. Dipakai hanya kalau paketnya benar-benar terpasang.
        // Paket bisa sudah dihapus admin (relasi nullOnDelete) — tanpa
        // pemeriksaan saasPlan, halaman dashboard melempar error.
        $planKeys = ($e->plan === 'paid' && $e->saasPlan)
            ? $e->saasPlan->features->pluck('feature_key')->all()
            : null;

        $included = [];
        foreach ($gated as $key => $config) {
            if (!$e->canAccessFeature($key)) {
                continue;
            }
            // Untuk paket berbayar, hanya tampilkan yang memang tercantum di
            // paket. Fitur ber-locked_free yang lolos tanpa tercantum (eventner
            // berbayar legacy) tidak dilaporkan sebagai "termasuk" — daftarnya
            // akan jadi seluruh isi config dan itu menyesatkan.
            if ($planKeys !== null && !in_array($key, $planKeys, true)) {
                continue;
            }
            $included[] = $config['label'];
        }

        return [
            'name' => $this->planName(),
            'status' => $this->planStatus(),
            'status_class' => $this->planStatusClass(),
            'trial_days_left' => $this->trialDaysLeft,
            'included' => $included,
        ];
    }

    private function planName(): string
    {
        $e = $this->eventner;

        // Paket terpasang selalu menang, termasuk paket gratis yang baru
        // dipasang admin — lihat catatan di admin/eventner/show.blade.php.
        if ($e->saasPlan) {
            return $e->saasPlan->is_free
                ? $e->saasPlan->name . ' (gratis)'
                : $e->saasPlan->name;
        }

        if ($e->plan === 'paid') {
            return 'Akses Penuh';
        }

        if ($this->isOnTrial()) {
            return 'Gratis (masa uji coba)';
        }

        return 'Gratis';
    }

    private function isOnTrial(): bool
    {
        return $this->trialDaysLeft > 0 && !$this->isTrialExpired;
    }

    private function planStatus(): string
    {
        $e = $this->eventner;

        if ($e->saasPlan) {
            return $e->hasActivePlan() ? 'Aktif' : 'Belum Bayar';
        }

        if ($e->plan === 'paid') {
            return 'Aktif';
        }

        if ($e->isOnTrial()) {
            return 'Trial ' . $this->trialDaysLeft . ' hari lagi';
        }

        return 'Trial Berakhir';
    }

    private function planStatusClass(): string
    {
        return match ($this->planStatus()) {
            'Aktif' => 'success',
            'Belum Bayar', 'Trial Berakhir' => 'danger',
            default => 'warning',
        };
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

        $this->totalRegistrations = Registration::where('eventner_id', $eventnerId)->count();
        $this->totalCategories = $this->eventner->competitionCategories()->whereNotNull('parent_id')->count();
        $this->totalJudges = Judge::where('eventner_id', $eventnerId)->count();

        // Recent registrations
        $this->recentRegistrations = Registration::with('competitionCategory')
            ->where('eventner_id', $eventnerId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Stat card tambahan
        $this->totalVotes = (int) VoteTransaction::where('eventner_id', $eventnerId)
            ->where('status', 'PAID')
            ->sum('votes_earned');

        $this->ticketsSold = (int) Ticket::where('eventner_id', $eventnerId)
            ->whereIn('status', ['PAID', 'CHECKED_IN'])
            ->sum('quantity');

        $this->ticketsCheckedIn = Ticket::where('eventner_id', $eventnerId)
            ->where('status', 'CHECKED_IN')
            ->count();

        $this->pendingVerificationCount = Registration::where('eventner_id', $eventnerId)
            ->where('payment_status', 'pending_verification')
            ->count();

        $this->berkasMenungguCount = Registration::where('eventner_id', $eventnerId)
            ->whereIn('status_berkas', ['Menunggu', 'booking'])
            ->count();

        // Jadwal voting
        $this->loadVoteSchedule();

        // Voting stats
        $this->votePaidCount = VoteTransaction::where('eventner_id', $eventnerId)
            ->where('status', 'PAID')
            ->count();
        $this->votePendingCount = VoteTransaction::where('eventner_id', $eventnerId)
            ->where('status', 'PENDING')
            ->count();
        $this->activeBooster = VoteBooster::where('eventner_id', $eventnerId)
            ->active()
            ->orderByDesc('vote_multiplier')
            ->first();

        // Countdown hari-H
        $this->daysUntilEvent = $this->eventner->tanggal
            ? (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($this->eventner->tanggal)->startOfDay(), false)
            : null;

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

        // Command center
        $this->loadKpiModules();
        $this->loadReadiness();
        $this->loadAlerts();

        $this->checkinsToday = Ticket::where('eventner_id', $eventnerId)
            ->where('status', 'CHECKED_IN')
            ->whereDate('checked_in_at', today())
            ->count();
    }

    /**
     * KPI row 2 — rekap modul lain: juri, kategori+kuota, undian, skoring.
     */
    public function loadKpiModules()
    {
        $eventnerId = $this->eventner->id;

        $this->formatNilaiReady = $this->eventner->assessmentCategories()->exists();

        // Kuota terisi vs total kuota (kategori child saja, yang punya kuota)
        $categories = $this->eventner->competitionCategories()
            ->whereNotNull('parent_id')
            ->withCount('registrations')
            ->get();
        $this->kuotaFilled = 0;
        $this->kuotaTotal = 0;
        foreach ($categories as $cat) {
            if ($cat->kuota) {
                $this->kuotaTotal += (int) $cat->kuota;
                $this->kuotaFilled += min((int) $cat->registrations_count, (int) $cat->kuota);
            }
        }

        // Undian
        $drawing = $this->drawingData;
        foreach ($drawing as $row) {
            $this->drawnTotal += $row['drawn'];
            $this->drawnGrandTotal += $row['total'];
        }

        // Skoring — agregat scoringProgress
        $this->scoredTotal = 0;
        $this->participantTotal = 0;
        foreach ($this->scoringProgress as $row) {
            $this->scoredTotal += $row['scored'];
            $this->participantTotal += $row['total'];
        }
    }

    /**
     * Checklist kesiapan event — tiap item: done / partial / todo + route aksi.
     */
    public function loadReadiness(): array
    {
        $eventner = $this->eventner;

        $categories = $eventner->competitionCategories()->whereNotNull('parent_id')->get();
        $totalCategories = $categories->count();
        $categoriesWithKuota = $categories->where('kuota', '>', 0)->count();

        $rundownCount = $eventner->eventRundowns()->count();
        $formatNilaiCount = $eventner->assessmentCategories()->count();
        $drawnCategories = $this->drawingData->where('drawn', '>', 0)->count();

        $items = [
            [
                'key' => 'profil',
                'label' => 'Profil & logo event',
                'status' => ($eventner->logo_event && $eventner->poster) ? 'done' : (($eventner->logo_event || $eventner->poster) ? 'partial' : 'todo'),
                'route' => route('eventner.profile.index'),
                'action' => 'Kelola Profil',
            ],
            [
                'key' => 'kategori',
                'label' => $totalCategories > 0
                    ? "Kategori lomba + kuota ({$totalCategories})"
                    : 'Kategori lomba belum ada',
                'status' => $totalCategories === 0 ? 'todo' : ($categoriesWithKuota === $totalCategories ? 'done' : 'partial'),
                'route' => route('eventner.competition-categories.index'),
                'action' => $totalCategories > 0 ? 'Kelola Kategori' : 'Tambah Kategori',
            ],
            [
                'key' => 'juri',
                'label' => $this->totalJudges > 0 ? "Juri ({$this->totalJudges})" : 'Juri belum ada',
                'status' => $this->totalJudges > 0 ? 'done' : 'todo',
                'route' => route('eventner.judges.index'),
                'action' => $this->totalJudges > 0 ? 'Kelola Juri' : 'Tambah Juri',
            ],
            [
                'key' => 'format_nilai',
                'label' => $formatNilaiCount > 0 ? 'Format nilai' : 'Format nilai belum dibuat',
                'status' => $formatNilaiCount > 0 ? 'done' : 'todo',
                'route' => route('eventner.format-nilai.builder'),
                'action' => $formatNilaiCount > 0 ? 'Builder' : 'Buat Format Nilai',
            ],
            [
                'key' => 'rundown',
                'label' => $rundownCount > 0 ? "Rundown ({$rundownCount} kegiatan)" : 'Rundown kosong',
                'status' => $rundownCount > 0 ? 'done' : 'todo',
                'route' => route('eventner.rundown.index'),
                'action' => $rundownCount > 0 ? 'Kelola Rundown' : 'Buat Rundown',
            ],
            [
                'key' => 'voting',
                'label' => $this->voteStatus === 'berjalan' ? 'Voting berjalan' : 'Voting belum dijadwalkan',
                'status' => ($eventner->vote_active && $eventner->vote_start && $eventner->vote_end) ? 'done' : 'partial',
                'route' => route('eventner.vote-settings.index'),
                'action' => 'Pengaturan Vote',
            ],
            [
                'key' => 'tiket',
                'label' => $eventner->ticket_active ? 'Tiket aktif' : 'Tiket tidak dipakai (opsional)',
                'status' => !$eventner->ticket_active || ($eventner->ticket_start && $eventner->ticket_end) ? 'done' : 'partial',
                'route' => route('eventner.tickets.settings'),
                'action' => 'Pengaturan Tiket',
            ],
            [
                'key' => 'undian',
                'label' => $drawnCategories > 0 ? "Undian ({$drawnCategories} kategori diundi)" : 'Undian belum diadakan',
                'status' => $drawnCategories > 0 ? 'done' : 'todo',
                'route' => route('eventner.drawing.index'),
                'action' => $drawnCategories > 0 ? 'Kelola Undian' : 'Mulai Undian',
            ],
        ];

        $score = 0;
        foreach ($items as $item) {
            $score += $item['status'] === 'done' ? 1.0 : ($item['status'] === 'partial' ? 0.5 : 0);
        }
        $this->readinessPercent = (int) round(($score / count($items)) * 100);

        return $this->readiness = $items;
    }

    /**
     * Alert & tugas — hal yang butuh perhatian user sekarang.
     */
    public function loadAlerts(): array
    {
        $eventnerId = $this->eventner->id;
        $alerts = [];

        if ($this->pendingVerificationCount > 0) {
            $alerts[] = [
                'severity' => 'warning',
                'message' => "{$this->pendingVerificationCount} pembayaran menunggu verifikasi",
                'route' => route('eventner.finance.index'),
                'action' => 'Verifikasi',
            ];
        }

        if ($this->berkasMenungguCount > 0) {
            $alerts[] = [
                'severity' => 'warning',
                'message' => "{$this->berkasMenungguCount} berkas menunggu",
                'route' => route('eventner.participants.index'),
                'action' => 'Cek Berkas',
            ];
        }

        // Kategori kuota terisi >= 80%
        $categories = $this->eventner->competitionCategories()
            ->whereNotNull('parent_id')
            ->where('kuota', '>', 0)
            ->withCount('registrations')
            ->get();
        foreach ($categories as $cat) {
            $ratio = $cat->registrations_count / $cat->kuota;
            if ($ratio >= 0.8) {
                $percent = (int) round($ratio * 100);
                $alerts[] = [
                    'severity' => $ratio >= 1 ? 'danger' : 'warning',
                    'message' => "Kuota \"{$cat->full_name}\" {$percent}% penuh",
                    'route' => route('eventner.competition-categories.index'),
                    'action' => 'Lihat',
                ];
            }
        }

        // Trial hampir habis
        if ($this->trialDaysLeft > 0 && $this->trialDaysLeft <= 7 && !$this->isTrialExpired) {
            $alerts[] = [
                'severity' => $this->trialDaysLeft <= 3 ? 'danger' : 'warning',
                'message' => "Trial berakhir {$this->trialDaysLeft} hari",
                'route' => route('eventner.billing.upgrade'),
                'action' => 'Upgrade',
            ];
        }

        return $this->alerts = $alerts;
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

    public function loadVoteSchedule()
    {
        $this->voteStatus = 'nonaktif';
        $this->voteTimeRemaining = null;

        if (!$this->eventner->vote_active) {
            return;
        }

        $now = now();

        if ($this->eventner->vote_start && $now->lt($this->eventner->vote_start)) {
            $this->voteStatus = 'belum';
            return;
        }

        if ($this->eventner->vote_end && $now->gt($this->eventner->vote_end)) {
            $this->voteStatus = 'selesai';
            return;
        }

        // Berjalan (atau tanpa jadwal sama sekali = selalu buka)
        $this->voteStatus = 'berjalan';

        if ($this->eventner->vote_end) {
            $diff = $now->diff($this->eventner->vote_end);
            $this->voteTimeRemaining = $diff->days > 0
                ? $diff->days . ' hari ' . $diff->h . ' jam'
                : $diff->h . ' jam ' . $diff->i . ' menit';
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
        // Hanya tingkat lomba (child) — peserta menempel di child, bukan di
        // jenis lomba (parent). Menampilkan parent membuat baris ganda kosong.
        $categories = $this->eventner->competitionCategories()
            ->whereNotNull('parent_id')
            ->withCount([
                'registrations',
                'registrations as drawn_count' => function ($q) {
                    $q->whereNotNull('urutan_tampil');
                },
            ])->get();

        return $categories->map(function ($cat) {
            return [
                'name' => $cat->name,
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
                'name' => $participant->display_name,
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
