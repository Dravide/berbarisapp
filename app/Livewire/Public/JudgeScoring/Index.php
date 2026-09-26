<?php

namespace App\Livewire\Public\JudgeScoring;

use App\Models\AssessmentCategory;
use App\Models\AssessmentScore;
use App\Models\CompetitionRound;
use App\Models\CompetitionRoundRegistration;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Registration;
use App\Services\ScoreFinalizationService;
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
        $rubricCategoryIds = AssessmentCategory::where('eventner_id', $this->eventnerId)
            ->whereHas('judges', fn ($q) => $q->where('judges.id', $this->judgeId))
            ->pluck('competition_category_id');

        return \App\Models\CompetitionCategory::where('eventner_id', $this->eventnerId)
            ->whereNotNull('parent_id')
            ->where(function ($q) use ($rubricCategoryIds) {
                $q->whereIn('id', $rubricCategoryIds->filter()->all())
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
        return $this->categories
            ->mapWithKeys(fn ($cat) => [$cat->id => $this->hitungPeserta($cat->id)])
            ->all();
    }

    private function hitungPeserta(int $competitionCategoryId): int
    {
        $query = Registration::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $competitionCategoryId);

        // Rubrik global = juri menilai seluruh tingkat, tanpa saringan grup.
        if ($this->hasGlobalRubric()) {
            return $query->count();
        }

        // Rubrik yang menempel ke babak final saja berarti pesertanya finalis.
        // Tanpa ini, juri final melihat jumlah seluruh pendaftar.
        $roundIds = $this->roundIdsFor($competitionCategoryId, null, semuaGrup: true);
        $semuaFinal = $roundIds->isNotEmpty() && $roundIds->every(
            fn ($id) => CompetitionRound::where('eventner_id', $this->eventnerId)->find($id)?->isFinal()
        );

        if ($semuaFinal) {
            $finalis = CompetitionRoundRegistration::where('eventner_id', $this->eventnerId)
                ->whereIn('competition_round_id', $roundIds)
                ->pluck('registration_id');

            return $query->whereIn('id', $finalis)->count();
        }

        // Saringan grup & babak dihitung dengan babak yang sama seperti daftar
        // peserta, kalau tidak angka di kartu membandingkan dua daftar berbeda.
        $roundId = $this->selectedRoundId ? (int) $this->selectedRoundId : null;

        if (! $this->hasLevelWideRubric($competitionCategoryId, $roundId)) {
            $query->whereIn('competition_group_id', $this->allowedGroupIds($competitionCategoryId, $roundId));
        }

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
     * Babak datang dari rubrik juri sendiri, bukan dari peserta: juri yang
     * memegang rubrik "Penyisihan" memang hanya menilai penyisihan. Satu babak
     * saja = dipilih otomatis tanpa bertanya; lebih dari satu = juri memilih.
     *
     * Saat BELUM ada peserta terpilih, grup belum diketahui, jadi babak dihitung
     * dari rubrik yang tidak menempel ke grup mana pun. Kalau itu menghasilkan
     * daftar kosong — justru kasus normal untuk juri grup — babak diambil dari
     * seluruh rubrik juri di tingkat ini. Tanpa cadangan itu, juri Grup A tak
     * melihat pemilih babak sama sekali sebelum memilih peserta, padahal
     * rubriknya menempel ke babak.
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

        if ($this->selectedRegistrationId) {
            $compCategoryId = $this->registration->competition_category_id ?? null;
            $groupId = $this->registration->competition_group_id;
            $roundIds = $this->roundIdsFor($compCategoryId, $groupId);
        } else {
            $this->roundIdsCache ??= $this->roundIdsFor($this->selectedCategoryId, null);

            $roundIds = $this->roundIdsCache;

            if ($roundIds->isEmpty()) {
                $roundIds = $this->roundIdsFor($this->selectedCategoryId, null, semuaGrup: true);
            }
        }

        if ($roundIds->isEmpty()) {
            return collect();
        }

        return CompetitionRound::where('eventner_id', $this->eventnerId)
            ->whereIn('id', $roundIds)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Id babak dari rubrik milik juri ini.
     *
     * `$semuaGrup` melepas saringan grup — dipakai hanya untuk pemilih babak
     * sebelum peserta dipilih. Saringan babaknya sendiri (forLevel) tidak
     * pernah dilepas: babak dari tingkat lain tetap tidak boleh muncul.
     */
    private function roundIdsFor(?int $compCategoryId, ?int $groupId, bool $semuaGrup = false): \Illuminate\Support\Collection
    {
        if (! $compCategoryId) {
            return collect();
        }

        return AssessmentCategory::where('eventner_id', $this->eventnerId)
            ->whereNotNull('competition_round_id')
            ->forLevel($compCategoryId, null)
            ->when(! $semuaGrup, function ($q) use ($groupId) {
                $q->where(function ($sq) use ($groupId) {
                    if (! $groupId) {
                        $sq->whereNull('competition_group_id');

                        return;
                    }

                    $sq->where('competition_group_id', $groupId)->orWhereNull('competition_group_id');
                });
            })
            ->whereHas('judges', fn ($q) => $q->where('judges.id', $this->judgeId))
            ->pluck('competition_round_id')
            ->unique()
            ->values();
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
     * ID grup yang punya rubrik milik juri ini pada tingkat terpilih.
     *
     * Inilah grasi "juri berbeda per grup": juri Grup A tak pernah melihat
     * peserta Grup B di daftar, sekalipun satu tingkat.
     *
     * Babak wajib ikut disaring: rubrik Grup B pada babak lain tidak boleh
     * membuat juri terlihat berhak atas Grup B pada babak yang sedang dibuka.
     *
     * Babak diminta lewat parameter, bukan dibaca dari selectedRoundId: kartu
     * tingkat dirender tanpa babak terpilih, sedangkan halaman daftar/nilai
     * memakai babak yang sedang dibuka. Dua pemanggil itu butuh jawaban yang
     * berbeda dari data yang sama, dan selectedRoundId cuma benar untuk yang
     * kedua.
     */
    private function allowedGroupIds(?int $competitionCategoryId, ?int $roundId): array
    {
        if (! $competitionCategoryId) {
            return [];
        }

        return AssessmentCategory::where('eventner_id', $this->eventnerId)
            ->whereNotNull('competition_group_id')
            // forLevel, bukan forEntry: forEntry(null grup) justru mengunci ke
            // rubrik TANPA grup — kebalikan dari yang dicari di sini.
            ->forLevel($competitionCategoryId, $roundId)
            ->whereHas('judges', fn ($q) => $q->where('judges.id', $this->judgeId))
            ->pluck('competition_group_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Rubrik tanpa grup di tingkat ini = juri menilai seluruh peserta tingkat.
     *
     * Babak ikut disaring, dan itu yang menutup kebocoran lintas-babak: rubrik
     * Final yang tanpa grup dulu membuat juri dianggap selebar tingkat juga saat
     * menilai penyisihan, sehingga peserta grup lain muncul di daftarnya.
     *
     * Seperti allowedGroupIds(), babak diminta lewat parameter — lihat catatan
     * di sana.
     */
    private function hasLevelWideRubric(?int $competitionCategoryId, ?int $roundId): bool
    {
        if (! $competitionCategoryId) {
            return false;
        }

        return AssessmentCategory::where('eventner_id', $this->eventnerId)
            ->forEntry($competitionCategoryId, null, $roundId)
            ->whereHas('judges', fn ($q) => $q->where('judges.id', $this->judgeId))
            ->exists();
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

        // Tanpa rubrik selebar tingkat, juri terkurung ke grupnya sendiri.
        if (! $this->hasLevelWideRubric($this->selectedCategoryId, $this->selectedRoundId ? (int) $this->selectedRoundId : null)) {
            $query->whereIn('competition_group_id', $this->allowedGroupIds($this->selectedCategoryId, $this->selectedRoundId ? (int) $this->selectedRoundId : null));
        }

        return $query;
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
        $groupId = $registration->competition_group_id;
        $roundId = $this->selectedRoundId ? (int) $this->selectedRoundId : null;

        // forEntry() memuat klausa tingkat + grup + babak sekaligus, jadi cabang
        // fallback di bawah tidak mungkin membocorkan rubrik grup atau babak lain.
        $base = fn () => AssessmentCategory::with(['subCategories.criterias'])
            ->where('eventner_id', $this->eventnerId)
            ->forEntry($compCategoryId, $groupId, $roundId);

        $categories = $base()
            ->whereHas('judges', fn ($q) => $q->where('judges.id', $this->judgeId))
            ->get();

        if ($categories->isEmpty()) {
            $categories = $base()->get();
        }

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
            ->with('competitionCategory')
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
                $registration->competition_group_id,
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
                ->with('competitionCategory')
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
