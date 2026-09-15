<?php

namespace App\Livewire\Eventner\Scoring;

use App\Models\AssessmentCategory;
use App\Models\AssessmentScore;
use App\Models\CompetitionCategory;
use App\Models\DeductionCategory;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\ScoreDeduction;
use App\Services\ScoreFinalizationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Index extends Component
{
    public $eventner;
    public $view = 'categories'; // 'categories', 'participants', 'scoring'
    public $selectedCategoryId;
    public $search = '';
    public $selectedRegistrationId;
    public $selectedRegistration;
    public $scores = []; // [criteria_id => 'score_value']
    public $saveStatus = ''; // '', 'saved', 'error'
    public $isFinalized = false;

    // Sandbox: latihan input nilai tanpa menyimpan apa pun ke database
    public $simulateMode = false;

    // Judge support
    public $selectedJudgeId;
    public $judges = [];

    // Deduction support
    public $deductions = []; // [deduction_criteria_id => amount]
    public $deductionCategories = [];
    public $globalDeductionCategories = [];
    public $deductionSaveStatus = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedCategoryId' => ['except' => ''],
    ];

    public function mount()
    {
        $this->eventner = Auth::user()->eventner;

        if (!$this->eventner) {
            abort(403, 'Anda belum memiliki data Event terdaftar.');
        }

        // selectedCategoryId datang dari query string, jadi bisa berisi id
        // apa pun — termasuk id kategori tenant lain atau id yang sudah
        // dihapus. Dulu nilainya langsung dipercaya dan halaman peserta
        // dirender tanpa $selectedCategory, sehingga view meledak saat
        // membaca $selectedCategory->full_name.
        if ($this->selectedCategoryId && !$this->findOwnCategory($this->selectedCategoryId)) {
            $this->selectedCategoryId = null;
        }

        if ($this->selectedCategoryId) {
            $this->view = 'participants';
        }
    }

    /**
     * Cari kategori lomba milik eventner ini. Satu tempat untuk scoping
     * tenant — jangan pakai find() mentah di komponen ini.
     */
    private function findOwnCategory($id): ?CompetitionCategory
    {
        if (!$id) {
            return null;
        }

        return CompetitionCategory::where('eventner_id', $this->eventner->id)->find($id);
    }

    public function selectCategory($id)
    {
        $category = $this->findOwnCategory($id);

        if (!$category) {
            $this->dispatch('toast', type: 'error', message: 'Kategori lomba tidak ditemukan.');
            return;
        }

        $this->selectedCategoryId = $category->id;
        $this->view = 'participants';
    }

    public function toggleSimulateMode()
    {
        $this->simulateMode = !$this->simulateMode;

        // Bersihkan state penilaian agar tidak terbawa antar mode
        $this->scores = [];
        $this->deductions = [];
        $this->saveStatus = '';
        $this->deductionSaveStatus = '';
        $this->isFinalized = false;
    }

    public function backToCategories()
    {
        $this->view = 'categories';
        $this->selectedCategoryId = null;
        $this->search = '';
        $this->selectedRegistrationId = null;
        $this->selectedRegistration = null;
        $this->selectedJudgeId = null;
        $this->judges = [];
        $this->isFinalized = false;
    }

    public function selectParticipant($id)
    {
        $this->selectedRegistrationId = $id;
        // Scoping ke eventner sendiri — cegah IDOR ke registrasi tenant lain.
        $this->selectedRegistration = Registration::where('eventner_id', $this->eventner->id)
            ->with('competitionCategory')
            ->findOrFail($id);
        $this->view = 'scoring';

        // Load judges for this competition category
        $this->loadJudges();

        // Auto-select first judge if available
        if (count($this->judges) > 0) {
            $this->selectedJudgeId = $this->judges[0]->id;
        }

        $this->loadExistingScores();
        $this->loadDeductions();
    }

    public function updatedSelectedJudgeId()
    {
        $this->loadExistingScores();
        $this->saveStatus = '';
    }

    public function loadJudges()
    {
        // Hanya juri yang ditugaskan (Tugaskan Kategori) ke format penilaian
        // kategori kompetisi ini, via assessment_category_judge.
        $category = $this->selectedRegistration->competitionCategory;
        if ($category) {
            $this->judges = Judge::where('eventner_id', $this->eventner->id)
                ->whereHas('assessmentCategories', function ($q) use ($category) {
                    $q->where('assessment_categories.eventner_id', $this->eventner->id)
                        ->where(function ($sq) use ($category) {
                            $sq->where('assessment_categories.competition_category_id', $category->id)
                               ->orWhereNull('assessment_categories.competition_category_id');
                        });
                })
                ->get();
        } else {
            $this->judges = [];
        }
    }

    public function backToParticipants()
    {
        $this->view = 'participants';
        $this->scores = [];
        $this->selectedRegistrationId = null;
        $this->selectedRegistration = null;
        $this->saveStatus = '';
        $this->selectedJudgeId = null;
        $this->judges = [];
        $this->isFinalized = false;
        $this->deductions = [];
        $this->deductionCategories = [];
        $this->deductionSaveStatus = '';
    }

    public function loadExistingScores()
    {
        $this->scores = [];
        $this->isFinalized = false;

        if ($this->simulateMode || !$this->selectedJudgeId) {
            return;
        }

        $existingScores = AssessmentScore::where('registration_id', $this->selectedRegistrationId)
            ->where('eventner_id', $this->eventner->id)
            ->where('judge_id', $this->selectedJudgeId)
            ->get();

        foreach ($existingScores as $score) {
            $this->scores[$score->assessment_criteria_id] = $score->score;
            if ($score->is_finalized) {
                $this->isFinalized = true;
            }
        }
    }

    /**
     * Apakah nilai registrasi + juri ini sudah difinalisasi di database?
     *
     * Dulu keputusannya diambil dari properti $isFinalized, yang hanya dimuat
     * sekali saat peserta dibuka. Properti itu tidak ikut berubah kalau
     * finalisasi dilakukan di tempat lain — tombol "Finalisasi Semua",
     * halaman juri, atau tab lain — sehingga panel yang masih terbuka bisa
     * menimpa nilai yang sudah dikunci tanpa peringatan.
     */
    private function hasFinalizedScores(): bool
    {
        if (!$this->selectedRegistrationId || !$this->selectedJudgeId) {
            return false;
        }

        return AssessmentScore::where('registration_id', $this->selectedRegistrationId)
            ->where('eventner_id', $this->eventner->id)
            ->where('judge_id', $this->selectedJudgeId)
            ->where('is_finalized', true)
            ->exists();
    }

    public function saveScores()
    {
        if ($this->simulateMode) return;

        if (!$this->selectedJudgeId || $this->hasFinalizedScores()) {
            // Sinkronkan juga penanda di layar supaya tombolnya ikut terkunci.
            $this->isFinalized = $this->hasFinalizedScores();
            $this->saveStatus = 'error';
            session()->flash('scoring_error', 'Nilai sudah difinalisasi dan dikunci — tidak bisa diubah lagi.');
            return;
        }

        $eventnerId = $this->eventner->id;
        $registrationId = $this->selectedRegistrationId;
        $judgeId = $this->selectedJudgeId;

        foreach ($this->scores as $criteriaId => $scoreValue) {
            if ($scoreValue === '' || $scoreValue === null) {
                continue;
            }

            AssessmentScore::updateOrCreate(
                [
                    'registration_id' => $registrationId,
                    'assessment_criteria_id' => $criteriaId,
                    'judge_id' => $judgeId,
                ],
                [
                    'eventner_id' => $eventnerId,
                    'score' => $scoreValue,
                ]
            );
        }

        $this->saveStatus = 'saved';
    }

    public function finalizeScores()
    {
        if ($this->simulateMode || !$this->selectedJudgeId || $this->hasFinalizedScores()) {
            return;
        }

        // Simpan dulu nilai di layar supaya ikut tervalidasi & terkunci.
        $this->saveScores();

        // Pengurangan ikut disimpan. Dulu finalisasi hanya menyimpan nilai,
        // padahal layar menampilkan "NILAI AKHIR" yang sudah dikurangi
        // potongan dari form — potongan itu lalu hilang begitu halaman
        // dimuat ulang, dan angka yang dilihat operator tidak pernah ada.
        $this->saveDeductions();

        $result = app(ScoreFinalizationService::class)->finalize(
            $this->eventner->id,
            $this->selectedRegistrationId,
            $this->selectedJudgeId,
            array_filter($this->scores, fn ($v) => $v !== '' && $v !== null),
        );

        if ($result['missing']) {
            $this->saveStatus = 'error';
            session()->flash('scoring_error', 'Semua kriteria nilai harus diisi sebelum melakukan finalisasi.');
            return;
        }

        $this->isFinalized = true;
        $this->saveStatus = 'finalized';

        // Jika semua judge untuk registration ini sudah final → nilai selesai semua, kirim notif.
        if ($this->selectedRegistration) {
            app(ScoreFinalizationService::class)
                ->notifyIfComplete($this->eventner->id, $this->selectedRegistration);
        }

        session()->flash('success', 'Penilaian berhasil difinalisasi dan dikunci.');
    }

    private function notifyIfAllJudgesFinalized(): void
    {
        $registration = Registration::where('eventner_id', $this->eventner->id)
            ->find($this->selectedRegistrationId);

        if (!$registration) return;

        app(ScoreFinalizationService::class)
            ->notifyIfComplete($this->eventner->id, $registration);
    }

    /**
     * Finalisasi massal: kunci semua nilai yang sudah tersimpan untuk
     * seluruh peserta pada kategori lomba terpilih. Registrasi tanpa
     * nilai apa pun dilewati (tidak ada yang bisa dikunci).
     */
    public function finalizeAllForCategory()
    {
        if ($this->simulateMode) return;

        if (!$this->selectedCategoryId) {
            $this->dispatch('toast', type: 'error', message: 'Pilih kategori lomba terlebih dahulu.');
            return;
        }

        $registrationIds = Registration::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->selectedCategoryId)
            ->pluck('id');

        if ($registrationIds->isEmpty()) {
            $this->dispatch('toast', type: 'error', message: 'Tidak ada peserta pada kategori ini.');
            return;
        }

        $updated = 0;
        foreach ($registrationIds as $regId) {
            $affected = AssessmentScore::where('registration_id', $regId)
                ->where('eventner_id', $this->eventner->id)
                ->where('is_finalized', false)
                ->update(['is_finalized' => true]);
            $updated += $affected;

            if ($affected > 0) {
                $registration = Registration::where('eventner_id', $this->eventner->id)->find($regId);
                if ($registration) {
                    app(ScoreFinalizationService::class)
                        ->notifyIfComplete($this->eventner->id, $registration);
                }
            }
        }

        $this->dispatch('toast', type: 'success', message: $updated > 0
            ? "Finalisasi massal berhasil: {$updated} baris nilai dikunci untuk seluruh peserta kategori ini."
            : 'Semua nilai pada kategori ini sudah terfinalisasi sebelumnya.');
    }

    public function resetScores()
    {
        if ($this->simulateMode) {
            // Sandbox: cukup kosongkan state lokal, tidak perlu sentuh DB
            $this->scores = [];
            $this->saveStatus = '';
            return;
        }

        if (!$this->selectedJudgeId || !$this->selectedRegistrationId) {
            return;
        }

        // Nilai terkunci tidak boleh dihapus — reset berarti mengosongkan
        // kolom input, bukan membuka kembali penilaian yang sudah final.
        if ($this->hasFinalizedScores()) {
            $this->saveStatus = 'error';
            session()->flash('scoring_error', 'Nilai sudah difinalisasi dan dikunci — tidak bisa direset. Buka kunci lewat panitia terlebih dahulu.');
            return;
        }

        AssessmentScore::where('registration_id', $this->selectedRegistrationId)
            ->where('eventner_id', $this->eventner->id)
            ->where('judge_id', $this->selectedJudgeId)
            ->delete();

        $this->scores = [];
        $this->saveStatus = '';
        session()->flash('success', 'Nilai berhasil direset.');
    }

    public function loadDeductions()
    {
        $this->deductions = [];
        $this->deductionSaveStatus = '';

        $compCategoryId = $this->selectedRegistration->competition_category_id ?? null;

        $this->deductionCategories = DeductionCategory::with('criterias')
            ->where('eventner_id', $this->eventner->id)
            ->category()
            ->whereHas('assessmentCategory', function ($q) use ($compCategoryId) {
                $q->where(function ($sq) use ($compCategoryId) {
                    $sq->where('competition_category_id', $compCategoryId)
                       ->orWhereNull('competition_category_id');
                });
            })
            ->orderBy('sort_order')
            ->get();

        // Pengurangan tingkat: berlaku untuk semua kategori penilaian tetapi
        // hanya milik SATU tingkat lomba, jadi difilter competition_category_id
        // peserta ini — sanksi tingkat lain tidak boleh ikut memotong nilainya.
        // Nilainya tetap masuk peta $this->deductions yang sama, dijumlahkan ke
        // NILAI AKHIR, bukan ke kolom kategori mana pun.
        $this->globalDeductionCategories = DeductionCategory::with('criterias')
            ->where('eventner_id', $this->eventner->id)
            ->global()
            ->forLevel($compCategoryId)
            ->orderBy('sort_order')
            ->get();

        // Sandbox: mulai kosong, jangan muat pengurangan tersimpan
        if ($this->simulateMode) {
            return;
        }

        // Load existing deductions for this registration
        $existingDeductions = ScoreDeduction::where('registration_id', $this->selectedRegistrationId)
            ->where('eventner_id', $this->eventner->id)
            ->get();

        foreach ($existingDeductions as $deduction) {
            $this->deductions[$deduction->deduction_criteria_id] = $deduction->amount;
        }
    }

    public function saveDeductions()
    {
        if ($this->simulateMode) {
            // Sandbox: tombolnya sudah didisable di view; jangan set status "saved" palsu
            return;
        }

        if (!$this->selectedRegistrationId) {
            return;
        }

        // Pengurangan ikut terkunci bersama nilainya: ia dipakai sebagai
        // pemecah seri saat menentukan juara, jadi mengubahnya setelah
        // finalisasi sama saja mengubah hasil lomba.
        if ($this->hasFinalizedScores()) {
            $this->deductionSaveStatus = 'error';
            session()->flash('scoring_error', 'Nilai sudah difinalisasi dan dikunci — pengurangan tidak bisa diubah lagi.');
            return;
        }

        foreach ($this->deductions as $criteriaId => $amount) {
            if ($amount === '' || $amount === null || (float) $amount == 0) {
                // Remove if set to 0 or empty
                ScoreDeduction::where('registration_id', $this->selectedRegistrationId)
                    ->where('eventner_id', $this->eventner->id)
                    ->where('deduction_criteria_id', $criteriaId)
                    ->delete();
                continue;
            }

            ScoreDeduction::updateOrCreate(
                [
                    'registration_id' => $this->selectedRegistrationId,
                    'eventner_id' => $this->eventner->id,
                    'deduction_criteria_id' => $criteriaId,
                ],
                [
                    'amount' => (float) $amount,
                ]
            );
        }

        $this->deductionSaveStatus = 'saved';
    }

    public function render()
    {
        $participants = collect();
        $selectedCategory = null;
        $assessmentCategories = collect();

        if ($this->selectedCategoryId) {
            // Scoping ke eventner sendiri — cegah enumerasi kategori/registrasi tenant lain.
            $selectedCategory = $this->findOwnCategory($this->selectedCategoryId);

            // Kategori bisa hilang di antara mount dan render (dihapus di
            // tab lain, atau id dari query string). Kembali ke daftar
            // kategori daripada merender halaman peserta tanpa kategorinya.
            if (!$selectedCategory) {
                $this->selectedCategoryId = null;
                $this->view = 'categories';

                return view('livewire.eventner.scoring.index', [
                    'participants' => collect(),
                    'selectedCategory' => null,
                    'categories' => $this->eventner->competitionCategories()
                        ->whereNotNull('parent_id')->with('parent')->get()->loadCount('registrations'),
                    'assessmentCategories' => collect(),
                    'judgeTotals' => collect(),
                    'totalDeductions' => 0,
                    'totalDeductionsKategori' => 0,
                    'totalDeductionsGlobal' => 0,
                ])->title('Input Nilai - ' . $this->eventner->nama_event);
            }

            // Urut sesuai nomor undian — juri menilai mengikuti urutan tampil,
            // jadi daftarnya harus sama dengan yang dipanggil di lapangan.
            // Peserta tanpa nomor undian (belum diundi) ditaruh paling bawah,
            // lalu dirapikan per nama sekolah. Pola yang sama dipakai PDF
            // format nilai (FormatNilaiController) supaya semua daftar cetak
            // maupun layar menampilkan urutan yang identik.
            $query = Registration::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $this->selectedCategoryId)
                ->orderByRaw('COALESCE(urutan_tampil, 999999)')
                ->orderBy('nama_sekolah');

            if ($this->search) {
                $query->where(function ($q) {
                    $q->where('nama_sekolah', 'like', '%' . $this->search . '%')
                        ->orWhere('nama_pelatih', 'like', '%' . $this->search . '%');
                });
            }

            $participants = $query->get();
        }

        if ($this->view === 'scoring' && $this->selectedRegistration) {
            $compCategoryId = $this->selectedRegistration->competition_category_id ?? null;

            $baseQuery = AssessmentCategory::with(['subCategories.criterias'])
                ->where('eventner_id', $this->eventner->id)
                ->where(function ($q) use ($compCategoryId) {
                    $q->where('competition_category_id', $compCategoryId)
                      ->orWhereNull('competition_category_id');
                });

            if ($this->selectedJudgeId) {
                $assessmentCategories = (clone $baseQuery)
                    ->whereHas('judges', function ($q) {
                        $q->where('judges.id', $this->selectedJudgeId);
                    })
                    ->get();
            }

            if (!isset($assessmentCategories) || $assessmentCategories->isEmpty()) {
                $assessmentCategories = $baseQuery->get();
            }
        }

        // Bobot kriteria per id. Nilai harus dikalikan bobot saat dijumlah —
        // sama seperti rekap panitia dan papan skor publik. Dulu total per
        // juri di panel ini menjumlah mentah, jadi kriteria berbobot 2
        // tertulis separuh dari angka yang dipakai menentukan juara.
        $criteriaWeights = [];
        foreach ($assessmentCategories as $cat) {
            foreach ($cat->subCategories as $sub) {
                foreach ($sub->criterias as $crit) {
                    $criteriaWeights[$crit->id] = $crit->weight ?? 1;
                }
            }
        }

        // Calculate per-judge totals for the current registration
        $judgeTotals = collect();
        if ($this->view === 'scoring' && $this->selectedRegistration && count($this->judges) > 0) {
            if ($this->simulateMode) {
                // Sandbox: hanya juri aktif yang punya nilai (state lokal, bukan DB)
                foreach ($this->judges as $judge) {
                    $isMine = $judge->id == $this->selectedJudgeId;
                    $filled = collect($this->scores)->filter(fn($v) => $v !== '' && $v !== null)->count();
                    $judgeTotals->push([
                        'judge' => $judge,
                        'total' => $isMine ? collect($this->scores)->sum(fn($v, $k) => ($v === '' || $v === null) ? 0 : (float) $v * ($criteriaWeights[$k] ?? 1)) : 0,
                        'filled' => $isMine ? $filled : 0,
                    ]);
                }
            } else {
                $allJudgeScores = AssessmentScore::where('registration_id', $this->selectedRegistrationId)
                    ->where('eventner_id', $this->eventner->id)
                    ->whereIn('judge_id', collect($this->judges)->pluck('id'))
                    ->get()
                    ->groupBy('judge_id');

                foreach ($this->judges as $judge) {
                    $judgeScores = $allJudgeScores->get($judge->id, collect());
                    $total = $judgeScores->sum(fn($s) => (int) $s->score * ($criteriaWeights[$s->assessment_criteria_id] ?? 1));
                    $filled = $judgeScores->count();
                    $judgeTotals->push([
                        'judge' => $judge,
                        'total' => $total,
                        'filled' => $filled,
                    ]);
                }
            }
        }

        // Total pengurangan dipecah dua supaya operator melihat dari mana
        // angkanya datang: per kategori memotong kolom kategori itu, global
        // memotong NILAI AKHIR. Keduanya tetap dikurangkan dari nilai juri.
        $totalDeductionsKategori = 0;
        $totalDeductionsGlobal = 0;
        if ($this->view === 'scoring') {
            $globalCriteriaIds = $this->globalDeductionCategories
                ->flatMap->criterias
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->all();

            foreach ($this->deductions as $criteriaId => $amount) {
                if ($amount === '' || $amount === null) {
                    continue;
                }

                if (in_array((string) $criteriaId, $globalCriteriaIds, true)) {
                    $totalDeductionsGlobal += abs((float) $amount);
                } else {
                    $totalDeductionsKategori += abs((float) $amount);
                }
            }
        }

        $totalDeductions = $totalDeductionsKategori + $totalDeductionsGlobal;

        return view('livewire.eventner.scoring.index', [
            'participants' => $participants,
            'selectedCategory' => $selectedCategory,
            'categories' => $this->eventner->competitionCategories()->whereNotNull('parent_id')->with('parent')->get()->loadCount('registrations'),
            'assessmentCategories' => $assessmentCategories,
            'judgeTotals' => $judgeTotals,
            'totalDeductions' => $totalDeductions,
            'totalDeductionsKategori' => $totalDeductionsKategori,
            'totalDeductionsGlobal' => $totalDeductionsGlobal,
        ])->title('Input Nilai - ' . $this->eventner->nama_event);
    }
}
