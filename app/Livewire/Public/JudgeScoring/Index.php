<?php

namespace App\Livewire\Public\JudgeScoring;

use App\Models\AssessmentCategory;
use App\Models\AssessmentScore;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\CompetitionRoundRegistration;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Registration;
use App\Services\ScoreFinalizationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Penilaian juri via tablet selama lomba berlangsung.
 *
 * Token di URL adalah satu-satunya gerbang (pola sama dengan
 * event.checkin.scan / magic.link) — tidak ada PIN maupun session login.
 * Identitas juri diikat server-side dari token; klien tidak pernah
 * mengirim judge_id.
 *
 * Token tidak punya masa berlaku sendiri; aksesnya ditutup setelah tanggal
 * event lewat (lihat Eventner::judgeAccessExpiresAt()). Yang ditutup itu
 * kebocoran jangka panjang — QR lomba lama — bukan penyebaran token selama
 * lomba berlangsung: sampai acara usai, siapa pun yang memegang token masih
 * bisa membuka halaman ini.
 */
#[Layout('layouts.judge')]
#[Title('Penilaian Juri')]
class Index extends Component
{
    #[Locked]
    public int $judgeId;

    #[Locked]
    public int $eventnerId;

    /** Kriteria yang boleh dinilai juri ini — dipakai sebagai guard IDOR. */
    #[Locked]
    public array $allowedCriteriaIds = [];

    /** Registrasi yang sedang dinilai — hanya boleh di-set via selectParticipant(). */
    #[Locked]
    public $selectedRegistrationId = null;

    public string $view = 'categories'; // categories | participants | scoring
    public $selectedCategoryId = null;

    /**
     * Babak yang sedang dinilai (id CompetitionRound). Null = tingkat ini
     * memang satu babak, atau juri belum memilih.
     */
    public $selectedRoundId = null;

    public array $scores = [];
    public bool $isFinalized = false;
    public string $saveStatus = ''; // '' | 'saved' | 'error'

    /**
     * Babak penuh pada tingkat terpilih, sebelum ada peserta dipilih.
     * Diisi sekali saat memilih tingkat supaya tak dihitung ulang tiap render.
     */
    private ?\Illuminate\Support\Collection $roundIdsCache = null;

    /** Mode penilaian: 'satu-satu' (satu kriteria per layar, maju otomatis) | 'semua'. */
    public string $criteriaMode = 'satu-satu';

    /** Kriteria yang sedang ditampilkan pada mode satu-per-satu. */
    public int $currentCriteriaIndex = 0;

    public function mount(string $token)
    {
        $judge = Judge::where('access_token', $token)->firstOrFail();

        // Batas masa berlaku link tablet juri: setelah lomba usai, token lama
        // tidak boleh lagi hidup — tanpa ini ia berlaku selamanya kecuali
        // panitia ingat menekan "Ganti Token".
        //
        // 404, bukan 403: halaman ini juga menjawab 404 untuk token ngawur, dan
        // pesan berbeda akan memberi tahu penebak bahwa tokennya benar — yang
        // justru informasi paling berguna bagi mereka. Alasan sebenarnya
        // dicatat di log supaya panitia bisa menelusuri "kenapa juri tak bisa
        // masuk" (kasus nyata: event diundur, tanggal tidak ikut diperbarui).
        $eventner = Eventner::find($judge->eventner_id);

        if ($eventner && ($batas = $eventner->judgeAccessExpiresAt()) && now()->gt($batas)) {
            Log::warning('Akses tablet juri ditolak: melewati batas tanggal event', [
                'judge_id' => $judge->id,
                'eventner_id' => $eventner->id,
                'batas' => $batas->toDateTimeString(),
            ]);

            abort(404);
        }

        $this->judgeId = $judge->id;
        $this->eventnerId = $judge->eventner_id;
    }

    public function getJudgeProperty(): Judge
    {
        return Judge::where('eventner_id', $this->eventnerId)->findOrFail($this->judgeId);
    }

    public function getEventnerProperty()
    {
        return \App\Models\Eventner::findOrFail($this->eventnerId);
    }

    /** Kategori lomba yang ditugaskan ke juri ini (inversi loadJudges di dashboard). */
    public function getCategoriesProperty()
    {
        return \App\Models\CompetitionCategory::where('eventner_id', $this->eventnerId)
            ->whereNotNull('parent_id')
            ->where(function ($q) {
                // Tingkat dari baris penugasan juri ini, plus rubrik global yang
                // berlaku di semua tingkat. Bukan lagi "tingkat dari rubrik yang
                // dipegang" — di bawah model grup, juri ditugaskan ke grup, dan
                // rubriknya menyusul dari seri peserta.
                $q->whereIn('id', $this->assignedLevelIds())
                  ->orWhereIn('id', $this->globalRubricCategoryIds());
            })
            ->with('parent')
            ->withCount('registrations')
            ->get();
    }

    /** Kategori lomba yang punya rubrik global (competition_category_id NULL). */
    private function globalRubricCategoryIds(): array
    {
        if (! $this->hasGlobalRubric()) {
            return [];
        }

        return \App\Models\CompetitionCategory::where('eventner_id', $this->eventnerId)
            ->whereNotNull('parent_id')
            ->pluck('id')
            ->all();
    }

    /**
     * Jumlah peserta yang BENAR-BENAR akan dilihat juri ini di tingkat itu.
     *
     * withCount('registrations') menghitung seluruh pendaftar tingkat — tak
     * peduli grup maupun babak. Akibatnya kartu menjanjikan angka yang berbeda
     * dari isi daftarnya: juri final melihat "5 peserta" lalu menemukan 2, dan
     * juri Grup B melihat 5 padahal pesertanya 3. Angka yang salah lebih buruk
     * daripada tidak ada angka: juri mengira ada peserta yang hilang.
     *
     * Karena itu hitungannya memakai aturan yang sama dengan daftarnya
     * (participantsQuery), bukan salinan kedua yang harus dijaga tetap sejalan.
     *
     * Dihitung sekali untuk seluruh kartu yang tampil, bukan per kartu per
     * render. Hasilnya diteruskan ke view lewat render(), jadi tidak ada method
     * publik baru yang terekspos sebagai aksi Livewire.
     *
     * @return array<int,int> id tingkat => jumlah peserta
     */
    private function jumlahPesertaPerTingkat(): array
    {
        // Babak seluruh tingkat dimuat sekali untuk semua kartu. Kalau dicari
        // per kartu, satu layar berisi sepuluh tingkat berarti sepuluh query.
        $babakTingkat = CompetitionRound::where('eventner_id', $this->eventnerId)
            ->whereIn('competition_category_id', $this->categories->pluck('id'))
            ->get()
            ->groupBy('competition_category_id');

        return $this->categories
            ->mapWithKeys(fn ($cat) => [
                $cat->id => $this->hitungPeserta($cat->id, $babakTingkat->get($cat->id, collect())),
            ])
            ->all();
    }

    /**
     * Jumlah peserta satu tingkat, memakai aturan yang sama dengan daftarnya.
     *
     * Dihitung dari satu tempat, bukan disalin — angka yang salah lebih buruk
     * daripada tidak ada angka: juri mengira ada peserta yang hilang.
     *
     * @param  \Illuminate\Support\Collection<int, CompetitionRound>  $rounds  babak tingkat ini
     */
    private function hitungPeserta(int $competitionCategoryId, $rounds): int
    {
        $query = Registration::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $competitionCategoryId);

        // Babak untuk kartu diambil dengan aturan yang sama seperti saat peserta
        // dibuka (resolveRoundForParticipant): satu babak dipilih otomatis.
        //
        // Tanpa itu kartu dan daftar memakai babak berbeda: layar kategori belum
        // punya babak terpilih, jadi kartu tingkat berbabak-final tunggal (dan
        // kartu juri final di dalamnya) menghitung seluruh pendaftar sementara
        // daftarnya cuma finalis.
        $round = $this->selectedRoundId
            ? $rounds->firstWhere('id', (int) $this->selectedRoundId)
            : ($rounds->count() === 1 ? $rounds->first() : null);

        // Babak final hanya menghitung finalis — kalau tidak, kartu menjanjikan
        // seluruh pendaftar sementara daftarnya cuma yang lolos. Sama seperti
        // participantsQuery(), penyaring ini dipasang SEBELUM cabang rubrik
        // global, supaya keduanya menyaring urutan yang sama.
        if ($round && $round->isFinal()) {
            $query->whereIn('id', CompetitionRoundRegistration::where('eventner_id', $this->eventnerId)
                ->where('competition_round_id', $round->id)
                ->pluck('registration_id'));
        }

        // Rubrik global = juri menilai seluruh tingkat, tanpa saringan grup.
        if ($this->hasGlobalRubric()) {
            return $query->count();
        }

        // Saringan grup memakai aturan yang sama dengan daftarnya. Juri yang
        // ditugaskan ke baris `final` melihat seluruh finalis; juri grup yang
        // tidak ditugaskan ke sana melihat nol begitu babak final dibuka.
        $query = CompetitionGroup::saringDinilaiOleh(
            $query,
            $this->judgeId,
            $competitionCategoryId,
            $round?->id,
        );

        return $query->count();
    }

    public function selectCategory($id)
    {
        abort_unless($this->categories->pluck('id')->contains((int) $id), 403);

        $this->selectedCategoryId = $id;
        $this->selectedRegistrationId = null;
        $this->roundIdsCache = null;
        $this->resolveRoundForParticipant();
        $this->view = 'participants';
    }

    public function backToCategories()
    {
        $this->view = 'categories';
        $this->selectedCategoryId = null;
        $this->roundIdsCache = null;
        // Babak ikut dikosongkan, bukan cuma tingkatnya. Kalau tidak, kartu
        // tingkat berikutnya mewarisi babak peserta terakhir yang dinilai dan
        // menyaring peserta dengan babak milik tingkat lain.
        $this->selectedRoundId = null;
        $this->resetScoringState();
    }

    /**
     * Babak yang boleh dinilai juri ini untuk peserta terpilih.
     *
     * Babak datang dari tingkatnya, bukan dari rubrik juri: grup tak punya babak
     * sendiri, dan juri grup memang menilai babak apa pun yang digelar tingkat
     * itu. Satu babak = dipilih otomatis; lebih dari satu = juri memilih.
     *
     * Dihitung ulang hanya saat pindah peserta, bukan tiap render: properti ini
     * dipanggil `participantsQuery()` yang juga dipanggil render(), dan query
     * tambahan di jalur itu berjalan puluhan kali per render.
     */
    public function getAvailableRoundsProperty()
    {
        if (! $this->selectedCategoryId) {
            return collect();
        }

        $this->roundIdsCache ??= CompetitionRound::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $this->selectedCategoryId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('id');

        if ($this->roundIdsCache->isEmpty()) {
            return collect();
        }

        return CompetitionRound::where('eventner_id', $this->eventnerId)
            ->whereIn('id', $this->roundIdsCache)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /** Babak terpilih, atau null kalau tingkat ini memang tanpa babak. */
    public function getActiveRoundProperty(): ?CompetitionRound
    {
        if (! $this->selectedRoundId) {
            return null;
        }

        return \App\Models\CompetitionRound::where('eventner_id', $this->eventnerId)
            ->find($this->selectedRoundId);
    }

    /** Juri memilih babak sendiri (hanya muncul bila rubriknya lebih dari satu babak). */
    public function switchRound($roundId)
    {
        $roundId = $roundId !== '' && $roundId !== null ? (int) $roundId : null;

        if ($roundId !== null && ! $this->availableRounds->pluck('id')->contains($roundId)) {
            abort(403);
        }

        $this->selectedRoundId = $roundId;
        $this->loadCriteria();
        $this->jumpToFirstUnfilled();
    }

    /**
     * Babak untuk peserta terpilih: satu babak → otomatis, banyak → pilihan
     * juri dipertahankan, tanpa rubrik berbasis babak → kosong (perilaku lama).
     */
    private function resolveRoundForParticipant(): void
    {
        $rounds = $this->availableRounds;

        if ($rounds->isEmpty()) {
            $this->selectedRoundId = null;
            return;
        }

        if ($rounds->count() === 1) {
            $this->selectedRoundId = $rounds->first()->id;
            return;
        }

        if (! $rounds->pluck('id')->contains((int) $this->selectedRoundId)) {
            $this->selectedRoundId = $rounds->first()->id;
        }
    }

    /**
     * ID tingkat lomba yang ditugaskan ke juri ini.
     *
     * Sumbernya baris penugasan, bukan rubrik yang dipegang: juri terikat ke
     * grup (atau baris final/levelnya), dan tingkatnya datang dari grup itu.
     * Baris tak-bergrup membawa tingkatnya langsung di competition_category_id.
     */
    private function assignedLevelIds(): array
    {
        $dariGrup = CompetitionGroup::where('eventner_id', $this->eventnerId)
            ->whereIn('id', DB::table('competition_group_judge')
                ->where('judge_id', $this->judgeId)
                ->where('scope', CompetitionGroup::SCOPE_GROUP)
                ->pluck('competition_group_id'))
            ->pluck('competition_category_id');

        $dariTingkat = DB::table('competition_group_judge')
            ->where('judge_id', $this->judgeId)
            ->whereIn('scope', [
                CompetitionGroup::SCOPE_FINAL,
                CompetitionGroup::SCOPE_UNGROUPED,
                CompetitionGroup::SCOPE_LEVEL,
            ])
            ->pluck('competition_category_id');

        return $dariGrup->merge($dariTingkat)->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    /** Peserta tingkat terpilih yang memang boleh dinilai juri ini. */
    private function participantsQuery()
    {
        $query = Registration::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $this->selectedCategoryId);

        // Babak final hanya menilai peserta yang tercatat lolos: menilai yang
        // tak lolos menghasilkan nilai yatim yang tak muncul di peringkat mana
        // pun.
        $round = $this->activeRound;
        if ($round && $round->isFinal()) {
            $query->whereIn('id', CompetitionRoundRegistration::where('eventner_id', $this->eventnerId)
                ->where('competition_round_id', $round->id)
                ->pluck('registration_id'));
        }

        // Rubrik global berlaku untuk semua tingkat — juri seperti ini menilai
        // seluruh peserta, tak peduli grupnya.
        if ($this->hasGlobalRubric()) {
            return $query;
        }

        // Selebihnya: peserta baris penugasan juri ini. Inilah keputusan "grup
        // menggantikan seri" — juri Grup A menilai SELURUH peserta Grup A, apa
        // pun serinya, dan lembarnya menyusul dari seri masing-masing peserta.
        return CompetitionGroup::saringDinilaiOleh(
            $query,
            $this->judgeId,
            (int) $this->selectedCategoryId,
            $this->selectedRoundId ? (int) $this->selectedRoundId : null,
        );
    }

    /** Apakah juri ini punya rubrik global (competition_category_id NULL). */
    private function hasGlobalRubric(): bool
    {
        return AssessmentCategory::where('eventner_id', $this->eventnerId)
            ->whereNull('competition_category_id')
            ->whereHas('judges', fn ($q) => $q->where('judges.id', $this->judgeId))
            ->exists();
    }

    public function selectParticipant($id)
    {
        // Peserta terpilih harus dikosongkan dulu: daftar babak & grup yang
        // boleh dibuka dihitung dari tingkat + peserta yang sedang aktif.
        $this->selectedRegistrationId = null;
        $this->resolveRoundForParticipant();

        $registration = $this->participantsQuery()->findOrFail($id);

        $this->selectedRegistrationId = $registration->id;
        $this->view = 'scoring';
        $this->loadCriteria();
        $this->jumpToFirstUnfilled();
    }

    /** Muat kriteria yang boleh dinilai + nilai yang sudah tersimpan. */
    private function loadCriteria(): void
    {
        $registration = $this->registration;
        $compCategoryId = $registration->competition_category_id;
        $seriesId = $registration->competition_series_id;
        $roundId = $this->selectedRoundId ? (int) $this->selectedRoundId : null;

        // Rubrik ditentukan SERI peserta, bukan juri yang membukanya: juri Grup A
        // menilai lembar Seri A untuk peserta Seri A dan lembar Seri B untuk
        // peserta Seri B — keduanya di grup yang sama.
        //
        // TIDAK ADA cabang "kalau kosong, ambil semua rubrik seri ini". Cabang
        // itu dulu membocorkan rubrik seri lain; di bawah model grup ia lebih
        // buruk lagi — juri Grup A yang kebetulan tak memegang rubrik apa pun
        // akan menerima seluruh lembar, dan itulah nilai yang tersimpan.
        // Panel kosong berarti seri peserta ini memang belum punya rubrik.
        $categories = AssessmentCategory::with(['subCategories.criterias'])
            ->where('eventner_id', $this->eventnerId)
            ->forEntry($compCategoryId, $seriesId, $roundId)
            ->get();

        $this->allowedCriteriaIds = $categories
            ->flatMap(fn ($cat) => $cat->subCategories->flatMap(
                fn ($sub) => $sub->criterias->pluck('id')
            ))
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->scores = [];
        $this->isFinalized = false;
        $this->saveStatus = '';

        $existing = AssessmentScore::where('registration_id', $registration->id)
            ->where('eventner_id', $this->eventnerId)
            ->where('judge_id', $this->judgeId)
            // Babak lain punya barisnya sendiri — memuat semuanya membuat
            // penanda "terkunci" dan daftar nilai bercampur antar babak.
            ->whereIn('assessment_criteria_id', $this->allowedCriteriaIds)
            ->get();

        foreach ($existing as $score) {
            $this->scores[$score->assessment_criteria_id] = $score->score;
            if ($score->is_finalized) {
                $this->isFinalized = true;
            }
        }
    }

    public function getRegistrationProperty(): Registration
    {
        return Registration::where('eventner_id', $this->eventnerId)
            ->with(['competitionCategory', 'competitionSeries'])
            ->findOrFail($this->selectedRegistrationId);
    }

    /**
     * Daftar kriteria rata (kategori → sub → kriteria) untuk mode satu-per-satu.
     *
     * Mode "semua" tetap memakai struktur bertingkat $assessmentCategories;
     * mode ini butuh urutan datar supaya bisa maju satu kriteria per layar.
     */
    public function getFlatCriteriaProperty(): array
    {
        return $this->assessmentCategories
            ->flatMap(fn ($cat) => $cat->subCategories->flatMap(
                fn ($sub) => $sub->criterias->map(fn ($c) => [
                    'id' => (int) $c->id,
                    'name' => $c->name,
                    'category' => $cat->name,
                    'sub' => $sub->name,
                    'score_options' => $c->score_options,
                ])
            ))
            ->values()
            ->all();
    }

    /** Kriteria yang sedang tampil di mode satu-per-satu (null bila daftar kosong). */
    public function getCurrentCriteriaProperty(): ?array
    {
        return $this->flatCriteria[$this->currentCriteriaIndex] ?? null;
    }

    /** Ganti mode penilaian tanpa mengubah nilai yang sudah tersimpan. */
    public function setCriteriaMode(string $mode): void
    {
        $this->criteriaMode = $mode === 'semua' ? 'semua' : 'satu-satu';

        // Hanya bermakna saat peserta sudah dipilih (kriteria sudah dimuat).
        if ($this->selectedRegistrationId) {
            $this->jumpToFirstUnfilled();
        }
    }

    public function goToCriteria(int $index): void
    {
        $this->currentCriteriaIndex = max(0, min($index, count($this->flatCriteria) - 1));
    }

    public function nextCriteria(): void
    {
        $this->goToCriteria($this->currentCriteriaIndex + 1);
    }

    public function prevCriteria(): void
    {
        $this->goToCriteria($this->currentCriteriaIndex - 1);
    }

    /**
     * Arahkan ke kriteria kosong pertama; kalau semua sudah terisi, tahan di
     * kriteria terakhir supaya tombol finalisasi tetap di jangkauan.
     */
    private function jumpToFirstUnfilled(): void
    {
        foreach ($this->flatCriteria as $i => $criteria) {
            $value = $this->scores[$criteria['id']] ?? null;
            if ($value === null || $value === '') {
                $this->currentCriteriaIndex = $i;
                return;
            }
        }

        $this->currentCriteriaIndex = max(0, count($this->flatCriteria) - 1);
    }

    public function getAssessmentCategoriesProperty()
    {
        $registration = $this->registration;

        $base = fn () => AssessmentCategory::with(['subCategories.criterias'])
            ->where('eventner_id', $this->eventnerId)
            ->forEntry(
                $registration->competition_category_id,
                $registration->competition_series_id,
                $this->selectedRoundId ? (int) $this->selectedRoundId : null,
            );

        $categories = $base()
            ->whereHas('judges', fn ($q) => $q->where('judges.id', $this->judgeId))
            ->get();

        return $categories->isEmpty() ? $base()->get() : $categories;
    }

    /**
     * Simpan satu kriteria begitu tombol nilai diketuk — satu roundtrip per
     * ketukan, jadi koneksi putus paling banyak menghilangkan satu ketukan
     * (bukan seluruh formulir).
     */
    public function setScore($criteriaId, $value)
    {
        if ($this->isFinalized) {
            return;
        }

        if (!in_array((int) $criteriaId, $this->allowedCriteriaIds, true)) {
            abort(403);
        }

        AssessmentScore::updateOrCreate(
            [
                'registration_id' => $this->selectedRegistrationId,
                'assessment_criteria_id' => $criteriaId,
                'judge_id' => $this->judgeId,
            ],
            [
                'eventner_id' => $this->eventnerId,
                'score' => $value,
            ]
        );

        $this->scores[$criteriaId] = $value;
        $this->saveStatus = 'saved';

        // Mode satu-per-satu: begitu nilai diketuk, langsung ke kriteria
        // berikutnya supaya juri tidak perlu menyentuh layar dua kali.
        if ($this->criteriaMode === 'satu-satu' && !$this->isFinalized) {
            $this->nextCriteria();
        }
    }

    public function finalize()
    {
        if ($this->isFinalized) {
            return;
        }

        $result = app(ScoreFinalizationService::class)->finalize(
            $this->eventnerId,
            $this->selectedRegistrationId,
            $this->judgeId,
            [],
            $this->selectedRoundId ? (int) $this->selectedRoundId : null,
        );

        if ($result['missing']) {
            $this->saveStatus = 'error';
            $this->dispatch('toast', message: 'Masih ada kriteria yang kosong.', type: 'error');

            return;
        }

        $this->isFinalized = true;
        $this->saveStatus = 'finalized';
        $this->dispatch('toast', message: 'Nilai peserta terkunci.', type: 'success');

        app(ScoreFinalizationService::class)->notifyIfComplete(
            $this->eventnerId,
            $this->registration,
            $this->selectedRoundId ? (int) $this->selectedRoundId : null,
        );
    }

    /** Kembali ke daftar peserta setelah finalisasi. */
    public function backToParticipants()
    {
        $this->view = 'participants';
        $this->resetScoringState();
    }

    private function resetScoringState(): void
    {
        $this->selectedRegistrationId = null;
        $this->allowedCriteriaIds = [];
        $this->scores = [];
        $this->isFinalized = false;
        $this->saveStatus = '';
        $this->currentCriteriaIndex = 0;
    }

    public function render()
    {
        $participants = collect();

        if ($this->selectedCategoryId) {
            $participants = $this->participantsQuery()
                ->with(['competitionCategory', 'competitionSeries'])
                ->get()
                // Urutan panggung (hasil undian) dulu; yang belum diundian di bawah.
                ->sortBy([
                    fn ($a, $b) => ($a->urutan_tampil ?? PHP_INT_MAX) <=> ($b->urutan_tampil ?? PHP_INT_MAX),
                    fn ($a, $b) => strcmp($a->nama_sekolah ?? '', $b->nama_sekolah ?? ''),
                ])
                ->values();

            // Status penilaian juri ini per peserta.
            $status = AssessmentScore::where('eventner_id', $this->eventnerId)
                ->where('judge_id', $this->judgeId)
                ->whereIn('registration_id', $participants->pluck('id'))
                ->get()
                ->groupBy('registration_id');

            $participants = $participants->map(function ($reg) use ($status) {
                $rows = $status->get($reg->id, collect());
                $reg->judge_status = $rows->isEmpty()
                    ? 'belum'
                    : ($rows->every(fn ($r) => (bool) $r->is_finalized) ? 'final' : 'dinilai');
                return $reg;
            });
        }

        return view('livewire.public.judge-scoring.index', [
            'eventner' => $this->eventner,
            'judge' => $this->judge,
            'categories' => $this->categories,
            'jumlahPeserta' => $this->jumlahPesertaPerTingkat(),
            'participants' => $participants,
            'rounds' => $this->availableRounds,
            'selectedRoundId' => $this->selectedRoundId,
        ])->layoutData([
            'eventner' => $this->eventner,
            'judge' => $this->judge,
        ])->title('Penilaian Juri - ' . $this->eventner->nama_event);
    }
}
