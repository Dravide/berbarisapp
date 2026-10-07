<?php

namespace App\Livewire\Public\PanitiaScoring;

use App\Livewire\Concerns\MelaporKePengguna;
use App\Models\AssessmentCategory;
use App\Models\AssessmentScore;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\CompetitionRoundRegistration;
use App\Models\Eventner;
use App\Models\Registration;
use App\Services\ScoreFinalizationService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Entry nilai untuk PANITIA — halaman ringkas di host entry (entry.berbaris.*).
 *
 * Bukan pengganti tablet juri: `/juri/{token}` tetap jalan apa adanya. Halaman
 * ini untuk petugas yang duduk di meja dan mengetik nilai dari lembar kertas
 * juri. Karena itu:
 *
 *  - Panitia WAJIB memilih jurinya. `assessment_scores` unik pada
 *    (registration_id, assessment_criteria_id, judge_id) dan finalize()
 *    menuntut judge_id, jadi nilai tidak punya arti tanpa pemiliknya.
 *  - Peserta TIDAK disaring penugasan juri — panitia memasukkan nilai untuk
 *    juri mana pun. Yang disaring hanya babak final (hanya finalis).
 *  - Rubriknya tetap rubrik MILIK JURI terpilih, lewat satu-satunya pintu
 *    AssessmentCategory::rubrikUntukPeserta(). Kalau pintu itu dilewati,
 *    finalize() menolak lembar yang di layar kelihatan lengkap — tanpa satu
 *    pun pesan yang menjelaskan sebabnya.
 *
 * Gerbangnya dua lapis: token event di URL (identitas) + PIN event (izin).
 * Token saja tidak cukup — link ber-token bocor lewat grup WhatsApp dan
 * riwayat browser tablet bersama, sedangkan PIN tidak ikut tercetak di QR.
 *
 * Yang sengaja TIDAK ada di sini: deduksi, mode simulasi, finalisasi massal,
 * buka kunci, rekap PDF/CSV. Semuanya ada di dashboard panitia.
 */
#[Layout('layouts.entry')]
#[Title('Entry Nilai Panitia')]
class Index extends Component
{
    use MelaporKePengguna;

    #[Locked]
    public int $eventnerId;

    /** PIN sudah benar (sekali per sesi browser). */
    #[Locked]
    public bool $terbuka = false;

    public string $pinInput = '';

    public string $view = 'categories'; // pin | categories | participants | scoring

    public $selectedCategoryId = null;
    public $selectedRoundId = null;
    public $selectedRegistrationId = null;

    /** Juri yang lembarnya sedang diisi — nilai disimpan atas namanya. */
    public $selectedJudgeId = null;

    /** Kriteria yang boleh dinilai juri terpilih — guard IDOR untuk setScore(). */
    #[Locked]
    public array $allowedCriteriaIds = [];

    public array $scores = [];
    public bool $isFinalized = false;
    public string $saveStatus = ''; // '' | 'saved' | 'finalized' | 'error'

    /** Panitia mengetik di laptop, bukan mengetuk tablet: semua kriteria sekaligus. */
    public string $criteriaMode = 'semua';
    public int $currentCriteriaIndex = 0;

    /** Batas percobaan PIN sebelum sesi dikunci sementara. */
    private const MAX_PERCOBAAN_PIN = 5;
    private const LAMA_KUNCI_PIN_MENIT = 5;

    protected $queryString = [
        'selectedCategoryId' => ['except' => ''],
    ];

    public function mount(string $token)
    {
        // 404 untuk token ngawur maupun event yang belum disetujui — sama
        // seperti Checkin\Scan. Pesan berbeda hanya memberi tahu penebak bahwa
        // tokennya benar.
        $eventner = Eventner::where('panitia_token', $token)
            ->where('status', 'approved')
            ->first();

        if (! $eventner) {
            // Halaman ini 404 karena TIGA sebab berbeda, dan dari luar semuanya
            // tampak sama. Tanpa baris ini, "kenapa panitia tak bisa masuk"
            // hanya bisa dijawab dengan menebak. Yang dicatat cuma potongan
            // token — cukup untuk mencocokkan link yang dipakai, tak cukup
            // untuk dipakai masuk kalau log-nya bocor.
            Log::warning('Akses entry panitia ditolak: token tak cocok', [
                'token_awal' => substr($token, 0, 6),
                'ada_di_event_lain' => Eventner::where('panitia_token', $token)->exists(),
            ]);

            abort(404);
        }

        // Batas masa berlaku link, sama dengan tablet juri: setelah lomba usai
        // link lama tidak boleh hidup selamanya. Alasan sebenarnya dicatat di
        // log supaya panitia bisa menelusuri "kenapa tak bisa masuk".
        if (($batas = $eventner->judgeAccessExpiresAt()) && now()->gt($batas)) {
            Log::warning('Akses entry panitia ditolak: melewati batas tanggal event', [
                'eventner_id' => $eventner->id,
                'batas' => $batas->toDateTimeString(),
            ]);

            abort(404);
        }

        $this->eventnerId = $eventner->id;

        // Refresh tidak boleh meminta PIN ulang: itu inti "diminta sekali".
        $this->terbuka = (bool) session($this->kunciSesi());
        $this->view = $this->terbuka ? 'categories' : 'pin';

        if ($this->terbuka) {
            $this->lanjutDariQueryString();
        }
    }

    public function getEventnerProperty(): Eventner
    {
        return Eventner::findOrFail($this->eventnerId);
    }

    private function kunciSesi(): string
    {
        return 'panitia_entry.' . $this->eventnerId;
    }

    private function kunciGagal(): string
    {
        return 'panitia_entry_gagal.' . $this->eventnerId;
    }

    /** Sisa detik sampai percobaan PIN boleh dicoba lagi (0 = tidak terkunci). */
    private function sisaKunciPin(): int
    {
        return max(0, (int) session($this->kunciGagal() . '.sampai', 0) - now()->timestamp);
    }

    public function bukaPin()
    {
        if (($sisa = $this->sisaKunciPin()) > 0) {
            $this->gagal('Terlalu banyak percobaan. Coba lagi dalam ' . ceil($sisa / 60) . ' menit.');

            return;
        }

        $pin = (string) Eventner::whereKey($this->eventnerId)->value('panitia_pin');

        // PIN belum diatur = halaman tetap terkunci. Membukanya otomatis akan
        // membuat link ber-token jadi satu-satunya gerbang, padahal justru PIN
        // yang diminta ada.
        if ($pin === '') {
            $this->gagal('PIN entry belum diatur. Minta pemilik event membuatnya di halaman Daftar Juri.');

            return;
        }

        if (! hash_equals($pin, trim($this->pinInput))) {
            $this->catatGagalPin();
            $this->gagal('PIN salah.');

            return;
        }

        session()->forget($this->kunciGagal());
        session([$this->kunciSesi() => true]);

        $this->pinInput = '';
        $this->terbuka = true;
        $this->view = 'categories';
        $this->lanjutDariQueryString();
    }

    private function catatGagalPin(): void
    {
        $jumlah = (int) session($this->kunciGagal() . '.jumlah', 0) + 1;

        if ($jumlah >= self::MAX_PERCOBAAN_PIN) {
            session([
                $this->kunciGagal() . '.sampai' => now()->addMinutes(self::LAMA_KUNCI_PIN_MENIT)->timestamp,
                $this->kunciGagal() . '.jumlah' => 0,
            ]);

            return;
        }

        session([$this->kunciGagal() . '.jumlah' => $jumlah]);
    }

    /**
     * Lanjutkan ke tingkat yang diminta query string.
     *
     * Bentuk URL `/panitia/{token}?selectedCategoryId=` sengaja dijaga sama
     * dengan dashboard panitia: link yang sudah dibagikan ke petugas meja tetap
     * membuka tingkat yang sama.
     */
    private function lanjutDariQueryString(): void
    {
        if (! $this->selectedCategoryId) {
            return;
        }

        if (! $this->categories->pluck('id')->contains((int) $this->selectedCategoryId)) {
            $this->selectedCategoryId = null;

            return;
        }

        $this->view = 'participants';
        $this->pilihBabakDefault();
    }

    /**
     * Tingkat lomba yang bisa dinilai.
     *
     * Hanya anak (yang punya induk) — induk cuma payung, sama seperti dashboard
     * panitia dan halaman juri. Event lama yang tingkatnya datar tetap dilayani
     * lewat cabang cadangan.
     */
    public function getCategoriesProperty()
    {
        $anak = CompetitionCategory::where('eventner_id', $this->eventnerId)
            ->whereNotNull('parent_id')
            ->with('parent')
            ->withCount('registrations')
            ->orderBy('name')
            ->get();

        if ($anak->isNotEmpty()) {
            return $anak;
        }

        return CompetitionCategory::where('eventner_id', $this->eventnerId)
            ->with('parent')
            ->withCount('registrations')
            ->orderBy('name')
            ->get();
    }

    /** Jumlah peserta per tingkat — dari withCount, bukan satu query per kartu. */
    public function getJumlahPesertaProperty(): array
    {
        return $this->categories
            ->mapWithKeys(fn ($cat) => [$cat->id => (int) $cat->registrations_count])
            ->all();
    }

    public function getRoundsProperty()
    {
        if (! $this->selectedCategoryId) {
            return collect();
        }

        return CompetitionRound::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $this->selectedCategoryId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getActiveRoundProperty(): ?CompetitionRound
    {
        if (! $this->selectedRoundId) {
            return null;
        }

        return CompetitionRound::where('eventner_id', $this->eventnerId)
            ->find($this->selectedRoundId);
    }

    /**
     * Babak awal saat tingkat baru dibuka: penyisihan dulu, karena itulah yang
     * dinilai lebih dulu di lapangan. Tingkat tanpa babak tetap tanpa babak.
     */
    private function pilihBabakDefault(): void
    {
        $rounds = $this->rounds;

        $this->selectedRoundId = $rounds->count() === 1
            ? $rounds->first()->id
            : $rounds->firstWhere('type', CompetitionRound::TYPE_PRELIMINARY)?->id;
    }

    public function selectCategory($id)
    {
        abort_unless($this->terbuka, 403);
        abort_unless($this->categories->pluck('id')->contains((int) $id), 403);

        $this->selectedCategoryId = (int) $id;
        $this->selectedRoundId = null;
        $this->selectedRegistrationId = null;
        $this->selectedJudgeId = null;
        $this->resetLembar();

        $this->pilihBabakDefault();
        $this->view = 'participants';
    }

    public function switchRound($roundId)
    {
        abort_unless($this->terbuka, 403);

        $roundId = $roundId !== '' && $roundId !== null ? (int) $roundId : null;

        if ($roundId !== null && ! $this->rounds->pluck('id')->contains($roundId)) {
            abort(403);
        }

        $this->selectedRoundId = $roundId;
        $this->selectedRegistrationId = null;
        $this->selectedJudgeId = null;
        $this->resetLembar();

        $this->view = 'participants';
    }

    /**
     * Seluruh peserta tingkat terpilih.
     *
     * Tanpa saringan penugasan juri — panitia memasukkan nilai untuk juri mana
     * pun. Babak final tetap disaring finalis: menilai yang tak lolos
     * menghasilkan nilai yatim yang tak muncul di peringkat mana pun.
     */
    private function participantsQuery()
    {
        $query = Registration::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $this->selectedCategoryId);

        $round = $this->activeRound;

        if ($round && $round->isFinal()) {
            $query->whereIn('id', CompetitionRoundRegistration::where('eventner_id', $this->eventnerId)
                ->where('competition_round_id', $round->id)
                ->pluck('registration_id'));
        }

        return $query;
    }

    public function selectParticipant($id)
    {
        abort_unless($this->terbuka, 403);

        $registration = $this->participantsQuery()->findOrFail($id);

        $this->selectedRegistrationId = $registration->id;
        $this->view = 'scoring';

        $this->muatJuri();
        $this->muatKriteria();
    }

    /**
     * Juri yang lembarnya boleh diisi untuk peserta ini.
     *
     * Sumbernya baris penugasan (grup/babak), bukan rubrik — satu-satunya pintu
     * yang tahu "siapa menilai siapa" di tingkat ini.
     */
    private function muatJuri(): void
    {
        $this->selectedJudgeId = null;

        $judges = $this->judges;

        if ($judges->isNotEmpty()) {
            $this->selectedJudgeId = $judges->first()->id;
        }
    }

    public function getJudgesProperty()
    {
        if (! $this->selectedRegistrationId) {
            return collect();
        }

        return CompetitionGroup::judgesForRegistration(
            $this->registration,
            $this->selectedRoundId ? (int) $this->selectedRoundId : null,
        );
    }

    public function getRegistrationProperty(): Registration
    {
        return Registration::where('eventner_id', $this->eventnerId)
            ->with(['competitionCategory', 'competitionSeries', 'roundRegistrations'])
            ->findOrFail($this->selectedRegistrationId);
    }

    public function selectJudge($judgeId)
    {
        abort_unless($this->terbuka, 403);
        abort_unless($this->judges->pluck('id')->contains((int) $judgeId), 403);

        $this->selectedJudgeId = (int) $judgeId;
        $this->muatKriteria();
    }

    /**
     * Muat kriteria + nilai tersimpan untuk (peserta, juri, babak) yang aktif.
     *
     * Rubriknya lewat AssessmentCategory::rubrikUntukPeserta() — pintu yang sama
     * dengan tablet juri dan ScoreFinalizationService, jadi layar ini tidak
     * pernah meminta kriteria yang tidak dituntut finalize().
     */
    private function muatKriteria(): void
    {
        $this->resetLembar();

        if (! $this->selectedJudgeId) {
            return;
        }

        $registration = $this->registration;

        $this->allowedCriteriaIds = AssessmentCategory::rubrikUntukPeserta(
            $this->eventnerId,
            $registration->competition_category_id,
            $registration->competition_series_id,
            $this->selectedRoundId ? (int) $this->selectedRoundId : null,
            (int) $this->selectedJudgeId,
        )->get()
            ->flatMap(fn ($cat) => $cat->subCategories->flatMap(
                fn ($sub) => $sub->criterias->pluck('id')
            ))
            ->map(fn ($id) => (int) $id)
            ->all();

        $existing = AssessmentScore::where('registration_id', $registration->id)
            ->where('eventner_id', $this->eventnerId)
            ->where('judge_id', $this->selectedJudgeId)
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

        $this->jumpToFirstUnfilled();
    }

    /**
     * Rubrik yang dirender — daftar yang SAMA dengan allowedCriteriaIds, karena
     * keduanya memanggil pintu yang sama. Jangan tulis query kedua di sini.
     */
    public function getAssessmentCategoriesProperty()
    {
        if (! $this->selectedRegistrationId || ! $this->selectedJudgeId) {
            return collect();
        }

        $registration = $this->registration;

        return AssessmentCategory::rubrikUntukPeserta(
            $this->eventnerId,
            $registration->competition_category_id,
            $registration->competition_series_id,
            $this->selectedRoundId ? (int) $this->selectedRoundId : null,
            (int) $this->selectedJudgeId,
        )->get();
    }

    /** Daftar kriteria rata (kategori → sub → kriteria) untuk mode satu-per-satu. */
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

    public function getCurrentCriteriaProperty(): ?array
    {
        return $this->flatCriteria[$this->currentCriteriaIndex] ?? null;
    }

    public function setCriteriaMode(string $mode): void
    {
        $this->criteriaMode = $mode === 'satu-satu' ? 'satu-satu' : 'semua';

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

    /**
     * Simpan satu kriteria begitu tombol nilai diketuk.
     *
     * Satu roundtrip per ketukan, jadi koneksi meja yang putus paling banyak
     * menghilangkan satu ketukan — bukan seluruh lembar.
     */
    public function setScore($criteriaId, $value)
    {
        abort_unless($this->terbuka, 403);

        if ($this->isFinalized) {
            return;
        }

        if (! in_array((int) $criteriaId, $this->allowedCriteriaIds, true)) {
            abort(403);
        }

        AssessmentScore::updateOrCreate(
            [
                'registration_id' => $this->selectedRegistrationId,
                'assessment_criteria_id' => $criteriaId,
                'judge_id' => $this->selectedJudgeId,
            ],
            [
                'eventner_id' => $this->eventnerId,
                'score' => $value,
            ]
        );

        $this->scores[$criteriaId] = $value;
        $this->saveStatus = 'saved';

        // Mode satu-per-satu: begitu nilai diketuk, langsung ke kriteria
        // berikutnya supaya panitia tidak menyentuh layar dua kali.
        if ($this->criteriaMode === 'satu-satu' && ! $this->isFinalized) {
            $this->nextCriteria();
        }
    }

    /**
     * Hapus nilai satu kriteria.
     *
     * Halaman ini menyimpan tiap ketukan (tanpa tombol Simpan), jadi × di sini
     * menghapus barisnya saat itu juga.
     *
     * Sengaja TIDAK memanggil nextCriteria(): menghapus bukan mengisi, jadi
     * melompat ke kriteria berikutnya hanya melempar panitia tanpa sebab.
     */
    public function clearScore($criteriaId)
    {
        abort_unless($this->terbuka, 403);

        if ($this->isFinalized) {
            return;
        }

        if (! in_array((int) $criteriaId, $this->allowedCriteriaIds, true)) {
            abort(403);
        }

        AssessmentScore::where('registration_id', $this->selectedRegistrationId)
            ->where('eventner_id', $this->eventnerId)
            ->where('assessment_criteria_id', $criteriaId)
            ->where('judge_id', $this->selectedJudgeId)
            ->delete();

        unset($this->scores[$criteriaId]);
        $this->saveStatus = 'saved';
    }

    public function finalize()
    {
        abort_unless($this->terbuka, 403);

        if ($this->isFinalized || ! $this->selectedJudgeId) {
            return;
        }

        $result = app(ScoreFinalizationService::class)->finalize(
            $this->eventnerId,
            $this->selectedRegistrationId,
            (int) $this->selectedJudgeId,
            [],
            $this->selectedRoundId ? (int) $this->selectedRoundId : null,
        );

        if ($result['missing']) {
            $this->saveStatus = 'error';
            $this->gagal('Masih ada kriteria yang kosong.');

            return;
        }

        $this->isFinalized = true;
        $this->saveStatus = 'finalized';
        $this->toast('Nilai ' . $this->registration->nama_sekolah . ' terkunci.');

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
        $this->selectedRegistrationId = null;
        $this->selectedJudgeId = null;
        $this->resetLembar();
    }

    public function backToCategories()
    {
        $this->view = 'categories';
        $this->selectedCategoryId = null;
        $this->selectedRoundId = null;
        $this->selectedRegistrationId = null;
        $this->selectedJudgeId = null;
        $this->resetLembar();
    }

    /** Kosongkan lembar yang sedang terbuka. Peserta terpilih tak disentuh. */
    private function resetLembar(): void
    {
        $this->allowedCriteriaIds = [];
        $this->scores = [];
        $this->isFinalized = false;
        $this->saveStatus = '';
        $this->currentCriteriaIndex = 0;
    }

    public function render()
    {
        $participants = collect();

        if ($this->terbuka && $this->view === 'participants' && $this->selectedCategoryId) {
            $participants = $this->participantsQuery()
                ->with(['competitionSeries', 'roundRegistrations'])
                ->get()
                // Urutan panggung dulu; yang belum diundian di bawah.
                ->sortBy([
                    fn ($a, $b) => ($a->nomorUndian($this->activeRound) ?? PHP_INT_MAX)
                        <=> ($b->nomorUndian($this->activeRound) ?? PHP_INT_MAX),
                    fn ($a, $b) => strcmp($a->nama_sekolah ?? '', $b->nama_sekolah ?? ''),
                ])
                ->values();

            // Status per peserta untuk juri yang sedang dipegang. Satu query
            // untuk seluruh daftar; selama juri belum dipilih, tanpa badge.
            if ($this->selectedJudgeId) {
                $status = AssessmentScore::where('eventner_id', $this->eventnerId)
                    ->where('judge_id', $this->selectedJudgeId)
                    ->whereIn('registration_id', $participants->pluck('id'))
                    ->get()
                    ->groupBy('registration_id');

                $participants = $participants->map(function ($reg) use ($status) {
                    $rows = $status->get($reg->id, collect());
                    $reg->panitia_status = $rows->isEmpty()
                        ? 'belum'
                        : ($rows->every(fn ($r) => (bool) $r->is_finalized) ? 'final' : 'dinilai');

                    return $reg;
                });
            }
        }

        return view('livewire.public.panitia-scoring.index', [
            'eventner' => $this->eventner,
            'categories' => $this->categories,
            'jumlahPeserta' => $this->jumlahPeserta,
            'rounds' => $this->rounds,
            'selectedRound' => $this->activeRound,
            'participants' => $participants,
            'judges' => $this->judges,
            'adaJuri' => \App\Models\Judge::where('eventner_id', $this->eventnerId)->exists(),
            'assessmentCategories' => $this->assessmentCategories,
            'flatCriteria' => $this->flatCriteria,
            'currentCriteria' => $this->currentCriteria,
        ])
            ->layoutData(['eventner' => $this->eventner])
            ->title('Entry Nilai Panitia - ' . $this->eventner->nama_event);
    }
}
