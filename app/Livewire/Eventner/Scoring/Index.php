<?php

namespace App\Livewire\Eventner\Scoring;

use App\Models\AssessmentCategory;
use App\Models\AssessmentScore;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\CompetitionRoundRegistration;
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

    /** Modal Buka Kunci — alasan wajib diisi sebelum kunci dilepas. */
    public $showUnlockModal = false;
    public $unlockReason = '';

    // Sandbox: latihan input nilai tanpa menyimpan apa pun ke database
    public $simulateMode = false;

    // Judge support
    public $selectedJudgeId;
    public $judges = [];

    /**
     * Babak yang sedang dinilai (id CompetitionRound) — null = tanpa babak.
     *
     * TIDAK dipilih langsung dari DOM. Nilainya mengikuti chip di daftar
     * peserta: chip grup membuka babak penyisihan, chip "Final" membuka babak
     * final. Kartu pemilih babak di form input sudah dihapus justru karena
     * tugasnya kini dipegang chip itu — satu tingkat punya babak yang sama
     * untuk semua sekolah, jadi menanyakan babak lagi per peserta hanya
     * mengulang pertanyaan yang sama.
     */
    public $selectedRoundId;

    /** Grup yang sedang disaring di daftar peserta (id CompetitionGroup) — null = semua. */
    public $selectedGroupId;

    /**
     * Hanya peserta yang belum dibagi grup.
     *
     * Peserta yang belum masuk grup mana pun tidak muncul di kartu grup mana
     * pun, jadi tanpa jalur ini mereka lenyap dari jangkauan panitia begitu
     * tingkatnya mulai dibagi.
     */
    public $ungroupedOnly = false;

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
            $this->applyDefaultScope();
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
        $this->selectedGroupId = null;
        $this->selectedRoundId = null;
        $this->ungroupedOnly = false;

        // Tingkat berfase grup (atau berbabak final) mampir dulu ke layar
        // pemilih grup — "SMPN 1 masuk grup mana" adalah pertanyaan pertama
        // panitia, dan daftar gabungan seluruh sekolah tidak menjawabnya.
        // Tingkat tanpa keduanya langsung ke daftar sekolah seperti biasa.
        if ($this->perluPilihScope()) {
            $this->view = 'groups';
            return;
        }

        $this->view = 'participants';
        $this->applyDefaultScope();
    }

    /** Tingkat ini perlu layar pemilih grup/babak sebelum daftar sekolah? */
    private function perluPilihScope(): bool
    {
        return $this->groups()->isNotEmpty() || $this->finalRoundId() !== null;
    }

    /** Pilih grup/babak dari layar perantara, lalu masuk ke daftar sekolahnya. */
    public function selectGroupScope($scope)
    {
        $this->selectScope($scope);
        $this->view = 'participants';
    }

    /**
     * Kembali dari daftar peserta: ke layar pemilih grup bila tingkat ini
     * punya grup, kalau tidak langsung ke daftar kategori.
     */
    public function backFromParticipants()
    {
        $this->selectedRegistrationId = null;
        $this->selectedRegistration = null;
        $this->selectedJudgeId = null;
        $this->judges = [];
        $this->scores = [];
        $this->isFinalized = false;

        if ($this->perluPilihScope()) {
            $this->view = 'groups';
            $this->selectedGroupId = null;
            $this->selectedRoundId = null;
            $this->ungroupedOnly = false;
            return;
        }

        $this->backToCategories();
    }

    /**
     * Scope awal saat tingkat baru dibuka: tanpa grup, dan babak penyisihan.
     *
     * Tanpa ini form input memuat rubrik penyisihan DAN final sekaligus pada
     * tingkat berbabak — persis kebingungan yang mau dihapus oleh pemilih grup.
     * Tingkat tanpa babak tetap tanpa babak (selectedRoundId null).
     */
    private function applyDefaultScope(): void
    {
        $this->selectedGroupId = null;
        $this->ungroupedOnly = false;
        $this->selectedRoundId = $this->preliminaryRoundId() ?? $this->rounds()->first()?->id;
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
        $this->selectedGroupId = null;
        $this->selectedRoundId = null;
        $this->ungroupedOnly = false;
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

    /**
     * Babak milik tingkat peserta yang sedang dibuka.
     *
     * Kosong = tingkat ini memang satu-babak; pemilih babak disembunyikan dan
     * perilakunya persis seperti sebelum fitur babak ada.
     */
    private function rounds()
    {
        if (! $this->selectedCategoryId) {
            return collect();
        }

        return CompetitionRound::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->selectedCategoryId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /** Grup milik tingkat terpilih, untuk chip di daftar peserta. */
    private function groups()
    {
        if (! $this->selectedCategoryId) {
            return collect();
        }

        return CompetitionGroup::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->selectedCategoryId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /** Babak datang dari DOM pada jalur lain — pastikan milik eventner ini sebelum dipakai. */
    public function updatedSelectedRoundId()
    {
        $this->guardSelectedRound();
    }

    /**
     * Pemilih satu baris di daftar peserta: "Semua", satu grup, atau "Final".
     *
     * Grup dan Final saling eksklusif, bukan dua dimensi terpisah. Babak
     * penyisihan berlaku untuk semua sekolah sekaligus, jadi memilih Grup A
     * tidak menambah pertanyaan babak apa pun — ia sekaligus menetapkan bahwa
     * yang dinilai adalah babak penyisihan tingkat ini (bila ada).
     */
    public function selectScope($scope)
    {
        $scope = (string) $scope;
        $this->ungroupedOnly = false;

        if ($scope === 'final') {
            $this->selectedGroupId = null;
            $this->selectedRoundId = $this->finalRoundId();
        } elseif ($scope === 'ungrouped') {
            // Peserta yang belum dibagi grup tidak muncul di kartu grup mana
            // pun. Tanpa jalur ini, begitu tingkatnya mulai dibagi, sebagian
            // peserta lenyap dari jangkauan panitia padahal justru merekalah
            // yang belum dibagi.
            $this->ungroupedOnly = true;
            $this->selectedGroupId = null;
            $this->selectedRoundId = $this->preliminaryRoundId();
        } else {
            $this->selectedGroupId = $scope === 'all' ? null : $this->ownGroupId($scope);
            $this->selectedRoundId = $this->preliminaryRoundId();
        }

        $this->guardSelectedRound();
        $this->syncSelectionToScope();
    }

    /** Grup milik tingkat terpilih; null bila bukan miliknya. */
    private function ownGroupId($id): ?int
    {
        if (! $id || ! $this->selectedCategoryId) {
            return null;
        }

        $grup = CompetitionGroup::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->selectedCategoryId)
            ->find($id);

        return $grup ? (int) $grup->id : null;
    }

    /** Babak penyisihan tingkat terpilih, atau null bila tingkatnya tanpa babak. */
    private function preliminaryRoundId(): ?int
    {
        return $this->rounds()->firstWhere('type', CompetitionRound::TYPE_PRELIMINARY)?->id;
    }

    /** Babak final tingkat terpilih, atau null bila tidak ada babak final. */
    private function finalRoundId(): ?int
    {
        return $this->rounds()->firstWhere('type', CompetitionRound::TYPE_FINAL)?->id;
    }

    /**
     * Setelah scope berganti, peserta yang sedang dibuka dan nilai di layar
     * bisa jadi milik grup/babak lain. Membiarkannya membuat operasi simpan
     * mendarat di peserta di luar daftar yang sedang dilihat.
     */
    private function syncSelectionToScope()
    {
        if ($this->selectedRegistrationId) {
            $masihMasuk = $this->inScope($this->selectedRegistrationId);

            if (! $masihMasuk) {
                $this->backToParticipants();
                return;
            }

            $this->loadJudges();
            $this->loadExistingScores();
        }

        $this->saveStatus = '';
    }

    /** Peserta ini termasuk daftar yang sedang disaring? */
    private function inScope($registrationId): bool
    {
        $reg = Registration::where('eventner_id', $this->eventner->id)->find($registrationId);

        if (! $reg || (int) $reg->competition_category_id !== (int) $this->selectedCategoryId) {
            return false;
        }

        // Peserta yang tidak masuk daftar lolos tidak bisa dibuka di babak final.
        if ($this->isFinalScope() && ! $this->finalistIds()->contains((int) $reg->id)) {
            return false;
        }

        if ($this->selectedGroupId && (int) $reg->competition_group_id !== (int) $this->selectedGroupId) {
            return false;
        }

        if ($this->ungroupedOnly && $reg->competition_group_id !== null) {
            return false;
        }

        return true;
    }

    private function isFinalScope(): bool
    {
        $final = $this->finalRoundId();

        return $final && (int) $this->selectedRoundId === $final;
    }

    /** Id registrasi yang tercatat lolos ke babak final tingkat terpilih. */
    private function finalistIds(): \Illuminate\Support\Collection
    {
        $final = $this->finalRoundId();

        if (! $final) {
            return collect();
        }

        return CompetitionRoundRegistration::where('eventner_id', $this->eventner->id)
            ->where('competition_round_id', $final)
            ->pluck('registration_id')
            ->map(fn ($id) => (int) $id);
    }

    /** Babak terpilih wajib milik eventner ini. */
    private function guardSelectedRound(): void
    {
        if ($this->selectedRoundId && !CompetitionRound::where('eventner_id', $this->eventner->id)->find($this->selectedRoundId)) {
            $this->selectedRoundId = null;
        }
    }

    public function loadJudges()
    {
        // Hanya juri yang ditugaskan (Tugaskan Kategori) ke format penilaian
        // pada tingkat + grup + BABAK yang sedang dibuka. Ketiganya wajib:
        //  - grup: juri yang cuma memegang rubrik Grup B tidak boleh muncul di
        //    panel peserta Grup A — inilah "juri berbeda per grup";
        //  - babak: juri final tidak boleh muncul saat menilai penyisihan grup,
        //    dan sebaliknya — rubriknya memang baris terpisah, jadi tanpa
        //    saringan babak kedua regu juri tercampur di satu panel.
        $category = $this->selectedRegistration->competitionCategory;
        if ($category) {
            $groupId = $this->selectedRegistration->competition_group_id;
            $roundId = $this->selectedRoundId ? (int) $this->selectedRoundId : null;

            $this->judges = Judge::where('eventner_id', $this->eventner->id)
                ->whereHas('assessmentCategories', function ($q) use ($category, $groupId, $roundId) {
                    $q->where('assessment_categories.eventner_id', $this->eventner->id)
                        ->forEntry($category->id, $groupId, $roundId);
                })
                ->get();

            // Tidak ada cabang cadangan "panel kosong → semua juri": rubrik
            // tanpa babak sudah ikut lewat forLevel() (klausa orWhereNull), jadi
            // panel kosong berarti memang tak ada yang berhak menilai babak ini.
            // Cabang cadangan justru menghidupkan lagi juri final di penyisihan.
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
            ->when($this->roundCriteriaIds() !== null, fn ($q) => $q->whereIn('assessment_criteria_id', $this->roundCriteriaIds()))
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

        // Dibatasi ke kriteria babak terpilih: kalau tidak, nilai penyisihan
        // yang sudah dikunci membekukan input babak final juga (dan sebaliknya,
        // finalisasi babak final membuat operator tidak bisa memperbaiki
        // penyisihan).
        $criteriaIds = $this->roundCriteriaIds();

        return AssessmentScore::where('registration_id', $this->selectedRegistrationId)
            ->where('eventner_id', $this->eventner->id)
            ->where('judge_id', $this->selectedJudgeId)
            ->where('is_finalized', true)
            ->when($criteriaIds !== null, fn ($q) => $q->whereIn('assessment_criteria_id', $criteriaIds))
            ->exists();
    }

    /**
     * Id kriteria milik babak terpilih, atau null bila tanpa babak.
     *
     * Sengaja membaca rubrik babak itu apa adanya (tanpa grup), karena
     * pertanyaannya "nilai babak ini sudah dikunci atau belum" — bukan
     * "kriteria mana yang boleh dinilai juri ini".
     */
    private function roundCriteriaIds(): ?array
    {
        if (!$this->selectedRoundId) {
            return null;
        }

        return AssessmentCategory::where('eventner_id', $this->eventner->id)
            ->where('competition_round_id', $this->selectedRoundId)
            ->with('subCategories.criterias')
            ->get()
            ->flatMap(fn ($cat) => $cat->subCategories->flatMap(fn ($sub) => $sub->criterias->pluck('id')))
            ->map(fn ($id) => (int) $id)
            ->all();
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
            $this->selectedRoundId ? (int) $this->selectedRoundId : null,
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
                ->notifyIfComplete(
                    $this->eventner->id,
                    $this->selectedRegistration,
                    $this->selectedRoundId ? (int) $this->selectedRoundId : null,
                );
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
            // Mengunci "semua" saat chip grup aktif akan mengunci grup lain
            // diam-diam — peserta yang tidak terlihat di layar.
            ->when($this->selectedGroupId, fn ($q) => $q->where('competition_group_id', $this->selectedGroupId))
            ->pluck('id');

        if ($registrationIds->isEmpty()) {
            $this->dispatch('toast', type: 'error', message: 'Tidak ada peserta pada kategori ini.');
            return;
        }

        $updated = 0;
        $criteriaIds = $this->roundCriteriaIds();

        foreach ($registrationIds as $regId) {
            $affected = AssessmentScore::where('registration_id', $regId)
                ->where('eventner_id', $this->eventner->id)
                ->where('is_finalized', false)
                ->when($criteriaIds !== null, fn ($q) => $q->whereIn('assessment_criteria_id', $criteriaIds))
                ->update(['is_finalized' => true]);
            $updated += $affected;

            if ($affected > 0) {
                $registration = Registration::where('eventner_id', $this->eventner->id)->find($regId);
                if ($registration) {
                    app(ScoreFinalizationService::class)
                        ->notifyIfComplete(
                            $this->eventner->id,
                            $registration,
                            $this->selectedRoundId ? (int) $this->selectedRoundId : null,
                        );
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
            ->when($this->roundCriteriaIds() !== null, fn ($q) => $q->whereIn('assessment_criteria_id', $this->roundCriteriaIds()))
            ->delete();

        $this->scores = [];
        $this->saveStatus = '';
        session()->flash('success', 'Nilai berhasil direset.');
    }

    // ── Buka Kunci Nilai ──────────────────────────────────────────────

    /**
     * Buka modal Buka Kunci untuk juri yang sedang dipilih.
     *
     * Hanya jalan bila nilai juri ini memang terkunci — kalau tidak, modalnya
     * cuma melaporkan "tidak ada yang terkunci" setelah alasan diisi, dan itu
     * membuang waktu panitia.
     */
    public function openUnlockModal()
    {
        if ($this->simulateMode) {
            return;
        }

        if (!$this->selectedJudgeId || !$this->selectedRegistrationId) {
            return;
        }

        if (!$this->hasFinalizedScores()) {
            $this->dispatch('toast', type: 'error', message: 'Nilai juri ini tidak sedang terkunci.');
            return;
        }

        $this->unlockReason = '';
        $this->resetErrorBag('unlockReason');
        $this->showUnlockModal = true;
    }

    public function closeUnlockModal()
    {
        $this->showUnlockModal = false;
        $this->unlockReason = '';
        $this->resetErrorBag('unlockReason');
    }

    /**
     * Lepas kunci nilai satu juri pada peserta yang sedang dibuka.
     *
     * Satu aksi = satu peserta × satu juri. Tidak ada jalur massal: salah
     * tekan "Finalisasi Semua" tetap harus diperbaiki satu per satu, supaya
     * membuka kunci tak pernah jadi satu ketukan yang melepas puluhan nilai.
     *
     * Pembukaan didelegasikan ke ScoreFinalizationService, bukan dihitung di
     * sini dengan roundCriteriaIds(): daftar kriteria service itu memuat rubrik
     * tanpa babak, sedangkan roundCriteriaIds() tidak — dan rubrik tanpa babak
     * ikut terkunci. Lihat catatan di unfinalize().
     */
    public function unlockScores()
    {
        if ($this->simulateMode) {
            return;
        }

        if (!$this->selectedJudgeId || !$this->selectedRegistrationId) {
            $this->closeUnlockModal();
            return;
        }

        $this->validate([
            'unlockReason' => 'required|string|min:5|max:500',
        ], [
            'unlockReason.required' => 'Alasan wajib diisi — inilah yang tercatat sebagai jejak audit.',
            'unlockReason.min' => 'Alasan terlalu pendek. Tulis minimal 5 karakter.',
            'unlockReason.max' => 'Alasan terlalu panjang. Maksimal 500 karakter.',
        ]);

        $judge = Judge::where('eventner_id', $this->eventner->id)->find($this->selectedJudgeId);
        $namaJuri = $judge?->name ?? 'Juri #' . $this->selectedJudgeId;

        $updated = app(ScoreFinalizationService::class)->unfinalize(
            $this->eventner->id,
            $this->selectedRegistrationId,
            $this->selectedJudgeId,
            $this->selectedRoundId ? (int) $this->selectedRoundId : null,
        );

        if ($updated === 0) {
            $this->dispatch('toast', type: 'error', message: 'Tidak ada nilai terkunci untuk juri ini pada babak ini.');
            $this->closeUnlockModal();
            return;
        }

        // Jejak audit. Subject wajib Registration: halaman Activity Log
        // menyaring subject_type ke tujuh model, dan AssessmentScore bukan
        // salah satunya — dicatat pada skor, barisnya tak akan pernah terlihat.
        activity('penilaian')
            ->performedOn($this->selectedRegistration)
            ->withProperties([
                'registration_id' => $this->selectedRegistrationId,
                'judge_id' => $this->selectedJudgeId,
                'juri' => $namaJuri,
                'round_id' => $this->selectedRoundId ? (int) $this->selectedRoundId : null,
                'babak' => $this->selectedRoundId
                    ? CompetitionRound::where('eventner_id', $this->eventner->id)->find($this->selectedRoundId)?->name
                    : null,
                'jumlah_kriteria' => $updated,
                'alasan' => $this->unlockReason,
            ])
            ->log('Buka kunci nilai: ' . $this->selectedRegistration->display_name . ' — juri ' . $namaJuri);

        $this->isFinalized = false;
        $this->saveStatus = '';

        $this->dispatch('toast', type: 'success', message: "Kunci nilai dibuka: {$updated} baris untuk juri {$namaJuri}.");
        $this->closeUnlockModal();
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
                    'rounds' => collect(),
                    'groups' => collect(),
                    'groupCounts' => collect(),
                    'ungroupedCount' => 0,
                    'totalCount' => 0,
                    'finalistCount' => 0,
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

            // Chip grup: daftar menyempit ke peserta grup itu. "Semua" tidak
            // menyaring, jadi tingkat polos berperilaku persis seperti dulu.
            if ($this->selectedGroupId) {
                $query->where('competition_group_id', $this->selectedGroupId);
            }

            if ($this->ungroupedOnly) {
                $query->whereNull('competition_group_id');
            }

            // Chip Final: yang dinilai hanya peserta yang tercatat lolos.
            if ($this->isFinalScope()) {
                $query->whereIn('id', $this->finalistIds());
            }

            if ($this->search) {
                $query->where(function ($q) {
                    $q->where('nama_sekolah', 'like', '%' . $this->search . '%')
                        ->orWhere('nama_pelatih', 'like', '%' . $this->search . '%');
                });
            }

            $participants = $query->get();
        }

        if ($this->view === 'scoring' && $this->selectedRegistration) {
            // Rubrik yang boleh dinilai juri terpilih = tingkat peserta ini,
            // grupnya, dan babak yang sedang dibuka. Babak wajib ikut: rubrik
            // penyisihan dan final sengaja dibuat terpisah (kuncinya per
            // kriteria), jadi tanpa penyaring ini form menampilkan keduanya
            // sekaligus dan operator tak tahu mana yang sedang dinilai.
            $baseQuery = AssessmentCategory::with(['subCategories.criterias'])
                ->where('eventner_id', $this->eventner->id)
                ->forEntry(
                    $this->selectedRegistration->competition_category_id ?? null,
                    $this->selectedRegistration->competition_group_id,
                    $this->selectedRoundId ? (int) $this->selectedRoundId : null,
                );

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
                    // Total per juri mengikuti babak terpilih. Tanpa ini angka
                    // "sudah dinilai" menjumlah penyisihan + final sekaligus dan
                    // tak cocok dengan kolom nilai yang sedang tampil.
                    ->when($this->roundCriteriaIds() !== null, fn ($q) => $q->whereIn('assessment_criteria_id', $this->roundCriteriaIds()))
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

        // Jumlah peserta per grup untuk kartu pemilih grup. Satu query
        // dikelompokkan, bukan hitungan per kartu.
        $groupCounts = collect();
        $ungroupedCount = 0;
        if ($this->selectedCategoryId) {
            $groupCounts = Registration::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $this->selectedCategoryId)
                ->whereNotNull('competition_group_id')
                ->groupBy('competition_group_id')
                ->selectRaw('competition_group_id, COUNT(*) as total')
                ->pluck('total', 'competition_group_id');

            $ungroupedCount = Registration::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $this->selectedCategoryId)
                ->whereNull('competition_group_id')
                ->count();
        }

        return view('livewire.eventner.scoring.index', [
            'participants' => $participants,
            'selectedCategory' => $selectedCategory,
            'rounds' => $this->rounds(),
            'groups' => $this->groups(),
            'groupCounts' => $groupCounts,
            'ungroupedCount' => $ungroupedCount,
            'totalCount' => (int) $groupCounts->sum() + $ungroupedCount,
            'finalistCount' => $this->finalistIds()->count(),
            'categories' => $this->eventner->competitionCategories()->whereNotNull('parent_id')->with('parent')->get()->loadCount('registrations'),
            'assessmentCategories' => $assessmentCategories,
            'judgeTotals' => $judgeTotals,
            'totalDeductions' => $totalDeductions,
            'totalDeductionsKategori' => $totalDeductionsKategori,
            'totalDeductionsGlobal' => $totalDeductionsGlobal,
        ])->title('Input Nilai - ' . $this->eventner->nama_event);
    }
}
