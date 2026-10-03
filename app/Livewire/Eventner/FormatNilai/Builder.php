<?php

namespace App\Livewire\Eventner\FormatNilai;

use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionRound;
use App\Models\CompetitionSeries;
use App\Models\DeductionCategory;
use App\Models\DeductionCriteria;
use App\Models\Judge;
use App\Models\ScoreDeduction;
use App\Traits\FeatureGatedComponent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use App\Livewire\Concerns\MelaporKePengguna;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
class Builder extends Component
{
    use MelaporKePengguna;

    use FeatureGatedComponent;

    protected string $requiredFeature = 'format_nilai';

    // Dikunci — diisi dari Auth di mount(). Semua query builder di-scope ke
    // id ini, jadi klien tidak boleh menulisnya ke tenant lain.
    #[Locked]
    public $eventnerId;

    public $activeTab = ''; // '' = global/semua tingkat, child_id = specific

    /**
     * Tingkat yang sedang dibuka — dipakai untuk mengisi pilihan grup & babak
     * pada kategori penilaian. '' = global, jadi tidak ada grup/babak khusus.
     */
    #[Computed]
    public function activeCompetitionCategoryId(): ?int
    {
        return $this->activeTab !== '' ? (int) $this->activeTab : null;
    }

    /**
     * Seri milik tingkat yang sedang dibuka.
     *
     * Seri — bukan grup — yang menentukan lembar nilai sebuah pasukan. Grup
     * tetap ada di halaman lain sebagai tabel peringkat & nomor undian, dan
     * sengaja tidak lagi muncul di sini: dua pasukan di grup yang sama boleh
     * dinilai dengan format yang berbeda.
     */
    #[Computed]
    public function series()
    {
        if (! $this->activeCompetitionCategoryId) {
            return collect();
        }

        return CompetitionSeries::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $this->activeCompetitionCategoryId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Babak milik tingkat yang sedang dibuka.
     */
    #[Computed]
    public function rounds()
    {
        if (! $this->activeCompetitionCategoryId) {
            return collect();
        }

        return CompetitionRound::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $this->activeCompetitionCategoryId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Simpan seri/babak sebuah kategori penilaian. Dipanggil dari tombol
     * simpan di kartu kategori (bukan langsung saat ganti select) supaya
     * perubahan tidak ikut terkirim saat panitia menelusuri dropdown.
     *
     * Kolom `competition_group_id` sengaja TIDAK ikut ditulis. Tandanya sudah
     * dipindah ke seri, dan menulisnya kembali dari dropdown yang tidak lagi
     * memuat grup akan mengosongkannya diam-diam — padahal ia masih berguna
     * sebagai catatan dari mana seri itu berasal.
     */
    public function saveRubricScope($categoryId)
    {
        $category = AssessmentCategory::where('eventner_id', $this->eventnerId)->findOrFail($categoryId);

        $seriesId = ($this->rubricSeriesId[$categoryId] ?? null) ?: null;
        $roundId = ($this->rubricRoundId[$categoryId] ?? null) ?: null;
        $seriesId = $seriesId !== null ? (int) $seriesId : null;
        $roundId = $roundId !== null ? (int) $roundId : null;

        // Seri/babak dari DOM wajib milik eventner ini DAN milik tingkat
        // kategori penilaiannya — kalau tidak, juri di acara lain bisa
        // tertarik masuk lewat parameter ini.
        $categoryLevel = $category->competition_category_id;

        if ($seriesId !== null && ! CompetitionSeries::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $categoryLevel)
            ->whereKey($seriesId)
            ->exists()) {
            $this->gagal('Seri yang dipilih bukan milik tingkat lomba ini.');

            return;
        }

        if ($roundId !== null && ! CompetitionRound::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $categoryLevel)
            ->whereKey($roundId)
            ->exists()) {
            $this->gagal('Babak yang dipilih bukan milik tingkat lomba ini.');

            return;
        }

        // Nilai yang sudah masuk terikat pada kriteria rubrik ini, bukan pada
        // serinya. Memindahkan rubrik ke seri lain setelah ada nilai membuat
        // angka lama tiba-tiba dibaca oleh pasukan yang tak pernah dinilai
        // dengan rubrik itu — dan sebaliknya, pasukan yang memang dinilai
        // kehilangan angkanya dari lembar. Kunci setelah nilai pertama masuk.
        $pindahSeri = (int) $category->competition_series_id !== (int) $seriesId;

        if ($pindahSeri && $this->anyCriteriaHasScores($this->criteriaIdsOf($category))) {
            $this->gagal('Tidak bisa memindahkan rubrik ke seri lain: sudah ada nilai yang masuk.');

            return;
        }

        $category->update([
            'competition_series_id' => $seriesId,
            'competition_round_id' => $roundId,
        ]);

        unset($this->categories);
    }

    /**
     * Id seluruh kriteria (dan pengurangan) milik sebuah kategori penilaian.
     */
    private function criteriaIdsOf(AssessmentCategory $category): \Illuminate\Support\Collection
    {
        return $category->subCategories()->with('criterias')->get()
            ->flatMap->criterias
            ->pluck('id');
    }

    // ── Pembagian rubrik antar juri ────────────────────────────────────

    /**
     * Juri yang ditawarkan untuk dicentang, per tingkat yang sedang dibuka.
     *
     * Diambil dari penugasan grup (competition_group_judge) tingkat itu, bukan
     * dari seluruh juri tenant: menawarkan juri yang tak bertugas di tingkat
     * ini hanya menghasilkan centang yang tak bisa dijangkau siapa pun —
     * rubriknya berhenti terisi tanpa satu pun pesan.
     *
     * Tingkat yang belum punya penugasan grup sama sekali (event lama, atau
     * tingkat yang grupnya belum diatur) jatuh ke seluruh juri tenant, supaya
     * layar ini tetap bisa dipakai lebih dulu tanpa memaksa panitia membuka
     * modal Kelola Grup.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Judge>
     */
    #[Computed]
    public function juriTingkat()
    {
        $penugasan = $this->juriPenugasanTingkat;

        // Penugasan kosong = tingkat ini belum diatur. Seluruh juri event
        // ditawarkan supaya layar ini tetap bisa dipakai lebih dulu, tanpa
        // memaksa panitia membuka modal Kelola Grup.
        return $penugasan->isNotEmpty()
            ? $penugasan
            : Judge::where('eventner_id', $this->eventnerId)->orderBy('name')->get();
    }

    /**
     * Juri yang PUNYA baris penugasan di tingkat yang sedang dibuka.
     *
     * Ini pembanding yang benar untuk "tak bisa dijangkau": rubrik yang
     * dicentang ke juri di luar himpunan ini tak akan pernah terisi, karena
     * tablet dan finalisasi hanya melihat juri dari baris penugasan.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Judge>
     */
    #[Computed]
    public function juriPenugasanTingkat()
    {
        $levelId = $this->activeCompetitionCategoryId;

        $ids = $levelId
            ? DB::table('competition_group_judge')
                ->where('competition_category_id', $levelId)
                ->pluck('judge_id')
                ->unique()
            : collect();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Judge::where('eventner_id', $this->eventnerId)
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get();
    }

    /**
     * Centang juri per kategori penilaian: [categoryId => [judgeId]].
     *
     * Kosong = rubrik belum dibagi, dan itu berarti terbuka untuk semua juri
     * dari penugasan yang berlaku. Dibaca sebagai satu query untuk seluruh tab,
     * bukan per baris rubrik.
     *
     * @return array<int, array<int>>
     */
    #[Computed]
    public function rubricJudgeIds(): array
    {
        $categoryIds = $this->categories->pluck('id')->all();

        if ($categoryIds === []) {
            return [];
        }

        return DB::table('assessment_category_judge')
            ->whereIn('assessment_category_id', $categoryIds)
            ->get(['assessment_category_id', 'judge_id'])
            ->groupBy('assessment_category_id')
            ->map(fn ($baris) => $baris->pluck('judge_id')->map(fn ($id) => (int) $id)->values()->all())
            // Kunci dijadikan int: blade mencarinya dengan $category->id, dan
            // kunci string dari driver basis data akan gagal cocok di situ.
            ->mapWithKeys(fn ($ids, $categoryId) => [(int) $categoryId => $ids])
            ->all();
    }

    /**
     * Rubrik yang centangannya tak bisa dijangkau juri mana pun.
     *
     * Diperiksa di aras TINGKAT, bukan per peserta: cukup tahu apakah ada satu
     * pun juri tercentang yang juga bertugas di tingkat ini. Kalau tidak, tak
     * ada peserta mana pun yang rubriknya terisi — dan karena total juara
     * menjumlah apa adanya, angkanya diam-diam kehilangan rubrik itu.
     *
     * Dua hal yang sengaja TIDAK ditandai:
     *
     *  - Rubrik tanpa centang sama sekali. Kosong berarti "semua juri boleh
     *    mengisi", jadi tak ada yang perlu dijangkau; menandainya akan
     *    memerahkan setiap rubrik di acara yang belum dibagi — persis keadaan
     *    yang fitur ini jaga supaya tidak berubah.
     *  - Tingkat yang belum punya baris penugasan sama sekali. Di situ tiap
     *    centang memang tak terjangkau, tapi penyebabnya bukan centangnya —
     *    panel di atasnya sudah menyatakan "Belum ada juri di tingkat ini".
     *
     * Pembandingnya `juriPenugasanTingkat`, BUKAN `juriTingkat`: yang kedua
     * jatuh ke seluruh juri event saat penugasan belum ada, dan di situ setiap
     * centang akan tampak terjangkau padahal tak ada baris penugasan yang
     * menjalankannya.
     *
     * @return array<int> id kategori penilaian yang bermasalah
     */
    #[Computed]
    public function rubrikTanpaJuriReachable(): array
    {
        $juriPenugasan = $this->juriPenugasanTingkat->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($juriPenugasan === []) {
            return [];
        }

        return collect($this->rubricJudgeIds)
            ->filter(fn ($ids) => $ids !== [] && array_intersect($ids, $juriPenugasan) === [])
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Nyalakan/matikan satu centang juri pada satu rubrik.
     *
     * Ditulis per perubahan, bukan menunggu tombol Simpan — meniru
     * CompetitionCategory\Index::toggleGroupJudge(). Satu baris = satu
     * syncRubricJudges(), jadi tabelnya tak pernah menyimpan keadaan setengah
     * jadi yang membuat rubrik hilang dari tablet juri.
     */
    public function toggleRubricJudge(int $categoryId, int $judgeId, bool $checked): void
    {
        $category = AssessmentCategory::where('eventner_id', $this->eventnerId)->find($categoryId);

        if (! $category) {
            return;
        }

        // Ditarik ulang dari tenant sendiri: id yang datang dari klien tidak
        // boleh dipercaya begitu saja.
        $sah = Judge::where('eventner_id', $this->eventnerId)->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (! in_array($judgeId, $sah, true)) {
            return;
        }

        $sekarang = $category->rubricJudgeIds();
        $baru = $checked
            ? array_values(array_unique([...$sekarang, $judgeId]))
            : array_values(array_diff($sekarang, [$judgeId]));

        $category->syncRubricJudges($baru);

        unset($this->rubricJudgeIds, $this->rubrikTanpaJuriReachable);
    }

    public $errorMessage = '';

    // Salin Ke (baru) — dari kategori sumber ke tingkat target
    public $showCopyToModal = false;

    public $copyToSourceCategoryId = null;

    public $copyToTargetCompetitionCategoryId = null;

    // Duplikat — nama diminta lebih dulu; grup/babak tidak diwarisi
    public $duplicatingCategoryId = null;

    public $duplicateCategoryName = '';

    // State for inputs
    public $newCategoryName = '';

    // Arrays to hold independent input states per item to avoid interfering with each other
    public $newSubCategoryNames = [];

    /**
     * Penanda seri & babak per kategori penilaian, indeks = id kategori.
     * Kosong = rubrik berlaku untuk semua seri / semua babak tingkat itu.
     */
    public $rubricSeriesId = [];

    public $rubricRoundId = [];

    /**
     * Babak per kelompok pengurangan TINGKAT, indeks = id kelompok.
     * Kosong = berlaku di semua babak.
     *
     * Pengurangan per kategori tidak butuh array ini: babaknya sudah ikut
     * lewat rubrik yang ditempelinya.
     */
    public $deductionRoundId = [];

    public function mount()
    {
        // Get the active eventner ID from the authenticated user
        $this->bootFeatureGate();
        $eventner = Auth::user()->eventner;
        if (! $eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }
        $this->eventnerId = $eventner->id;

        // Auto-select tingkat pertama yang memiliki format penilaian agar konten & tombol
        // "Tambah Kriteria" langsung tampil. Fallback ke tingkat pertama jika tak ada.
        $idsWithFormat = AssessmentCategory::where('eventner_id', $this->eventnerId)
            ->whereNotNull('competition_category_id')
            ->pluck('competition_category_id')
            ->all();

        $first = $this->competitionCategories->first(
            fn ($cc) => in_array($cc->id, $idsWithFormat)
        ) ?? $this->competitionCategories->first();

        if ($first) {
            $this->activeTab = (string) $first->id;
        }

        $this->refreshRubricScopeInputs();
    }

    /**
     * Isi ulang penanda seri/babak dari data tersimpan. Dipanggil setelah tab
     * tingkat berganti dan setelah kategori penilaian berubah, supaya tiap
     * dropdown menampilkan pilihan yang benar-benar tersimpan (bukan sisa
     * input tingkat sebelumnya).
     */
    public function refreshRubricScopeInputs()
    {
        $this->rubricSeriesId = $this->categories
            ->mapWithKeys(fn ($c) => [$c->id => $c->competition_series_id ? (string) $c->competition_series_id : ''])
            ->all();

        $this->rubricRoundId = $this->categories
            ->mapWithKeys(fn ($c) => [$c->id => $c->competition_round_id ? (string) $c->competition_round_id : ''])
            ->all();

        $this->deductionRoundId = $this->globalDeductionCategories
            ->mapWithKeys(fn ($c) => [$c->id => $c->competition_round_id ? (string) $c->competition_round_id : ''])
            ->all();
    }

    /**
     * Simpan babak sebuah kelompok pengurangan TINGKAT. Dipanggil dari tombol
     * simpan di kartu kelompok (bukan saat ganti select), sama seperti
     * saveRubricScope().
     *
     * Tanpa batas ini sanksi fase grup ikut memotong NILAI AKHIR di fase final
     * — pengurangan tingkat tidak menempel ke rubrik mana pun, jadi babaknya
     * tidak bisa dibaca dari mana pun selain kolom ini.
     */
    public function saveDeductionScope($categoryId)
    {
        $category = DeductionCategory::where('eventner_id', $this->eventnerId)
            ->global()
            ->findOrFail($categoryId);

        $roundId = ($this->deductionRoundId[$categoryId] ?? null) ?: null;
        $roundId = $roundId !== null ? (int) $roundId : null;

        // Babak dari DOM wajib milik eventner ini DAN milik tingkat kelompoknya.
        if ($roundId !== null && ! CompetitionRound::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $category->competition_category_id)
            ->whereKey($roundId)
            ->exists()) {
            $this->gagal('Babak yang dipilih bukan milik tingkat lomba ini.');

            return;
        }

        $category->update(['competition_round_id' => $roundId]);

        unset($this->globalDeductionCategories);
        $this->toast($roundId
            ? 'Pengurangan tingkat dibatasi ke babak yang dipilih.'
            : 'Pengurangan tingkat berlaku di semua babak.');
    }

    public function updatedActiveTab()
    {
        $this->refreshRubricScopeInputs();
    }

    /**
     * Batas jumlah opsi nilai/pengurangan per kriteria.
     */
    public const MAX_OPTIONS = 20;

    /**
     * Cek apakah sebuah kriteria penilaian sudah punya nilai masuk.
     */
    private function criteriaHasScores(int $criteriaId): bool
    {
        return AssessmentScore::where('assessment_criteria_id', $criteriaId)->exists();
    }

    /**
     * Cek apakah kumpulan kriteria penilaian sudah punya nilai masuk.
     */
    private function anyCriteriaHasScores(iterable $criteriaIds): bool
    {
        $ids = collect($criteriaIds)->flatten()->filter();

        return $ids->isNotEmpty() && AssessmentScore::whereIn('assessment_criteria_id', $ids)->exists();
    }

    /**
     * Cek apakah kumpulan kriteria pengurangan sudah dipakai di skor.
     */
    private function anyDeductionHasScores(iterable $deductionCriteriaIds): bool
    {
        $ids = collect($deductionCriteriaIds)->flatten()->filter();

        return $ids->isNotEmpty() && ScoreDeduction::whereIn('deduction_criteria_id', $ids)->exists();
    }

    #[Computed]
    public function competitionCategories()
    {
        return CompetitionCategory::where('eventner_id', $this->eventnerId)
            ->selectable()
            ->with('parent')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function categories()
    {
        $query = AssessmentCategory::with([
            'subCategories.criterias',
            'deductionCategories.criterias',
            'competitionCategory',
            'competitionSeries',
            'competitionRound',
        ])
            ->where('eventner_id', $this->eventnerId)
            ->orderBy('sort_order');

        if ($this->activeTab !== '') {
            $query->where('competition_category_id', $this->activeTab);
        }

        return $query->get();
    }

    /**
     * Rubrik pengurangan tingkat — berlaku untuk semua kategori penilaian di
     * dalam satu tingkat lomba, tidak menempel pada kategori penilaian mana
     * pun. Difilter activeTab: tiap tingkat punya daftar sanksinya sendiri,
     * sama seperti rubrik penilaiannya.
     */
    #[Computed]
    public function globalDeductionCategories()
    {
        return DeductionCategory::with('criterias')
            ->where('eventner_id', $this->eventnerId)
            ->global()
            ->forLevel($this->activeTab !== '' ? $this->activeTab : null)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Setelah import Excel sukses (dari komponen Import), reset cache computed
     * agar kategori hasil import langsung tampil tanpa refresh halaman.
     */
    #[On('import:done')]
    public function refreshAfterImport()
    {
        unset($this->categories);
    }

    public function addCategory()
    {
        $this->validate(['newCategoryName' => 'required|string|max:255']);

        $maxOrder = AssessmentCategory::where('eventner_id', $this->eventnerId)->max('sort_order') ?? 0;

        $activeTab = $this->normalizeActiveTab($this->activeTab);

        AssessmentCategory::create([
            'eventner_id' => $this->eventnerId,
            'competition_category_id' => $activeTab !== '' ? $activeTab : null,
            'name' => strip_tags($this->newCategoryName),
            'sort_order' => $maxOrder + 1,
        ]);

        $this->newCategoryName = '';
    }

    public function deleteCategory($id)
    {
        $category = AssessmentCategory::where('eventner_id', $this->eventnerId)
            ->with(['subCategories.criterias', 'deductionCategories.criterias'])
            ->findOrFail($id);

        $criteriaIds = $category->subCategories->flatMap->criterias->pluck('id');

        if ($this->anyCriteriaHasScores($criteriaIds)) {
            $this->gagal('Tidak bisa menghapus kategori: sudah ada nilai yang masuk. Hapus/lck format sebelum penilaian dimulai.', route('eventner.scoring.index'), 'Buka Input Nilai');

            return;
        }

        $deductionCriteriaIds = $category->deductionCategories->flatMap->criterias->pluck('id');
        if ($this->anyDeductionHasScores($deductionCriteriaIds)) {
            $this->gagal('Tidak bisa menghapus kategori: sudah ada nilai pengurangan yang masuk.', route('eventner.scoring.index'), 'Buka Input Nilai');

            return;
        }

        // Rubrik kategori ini bisa dipakai kategori juara (champion_assessment
        // menunjuk ke assessment_SUB_categories). Pivot itu cascade, jadi
        // menghapus kategori diam-diam mencabut rubrik dari kategori juara —
        // juara yang tadinya dihitung dari kategori ini langsung kehilangan
        // kriterianya tanpa peringatan. Tampilkan dulu kategori juara mana
        // yang akan kehilangan rubriknya.
        $championNames = ChampionCategory::where('eventner_id', $this->eventnerId)
            ->whereHas('assessmentSubCategories', function ($q) use ($category) {
                $q->whereIn('assessment_sub_categories.assessment_category_id', [$category->id]);
            })
            ->pluck('name');

        if ($championNames->isNotEmpty()) {
            $this->gagal(
                'Tidak bisa menghapus kategori: rubriknya dipakai kategori juara '
                . $championNames->implode(', ')
                . '. Lepaskan rubrik itu dari kategori juara dulu.',
                route('eventner.champion-categories.index'),
                'Buka Kategori Juara'
            );

            return;
        }

        // Kategori pengurangan menunjuk ke assessment_categories dengan
        // nullOnDelete, jadi barisnya TIDAK ikut terhapus — ia jadi yatim dan
        // hilang dari halaman Input Nilai (yang menyaring NOT NULL). Nilainya
        // ikut tak terhitung, padahal masih tersimpan. Hapus sekalian.
        $category->deductionCategories()->delete();

        $category->delete();
    }

    /**
     * Duplikat rubrik.
     *
     * Alur yang dimaksud: panitia menyalin format penilaian yang sudah ada
     * untuk dipakai babak lain (mis. Penyisihan → Final dengan isi kriteria
     * yang sama). Nama diminta lebih dulu supaya hasil salinannya tidak perlu
     * di-rename manual, dan seri/babak sengaja DIKOSONGKAN — menyalinnya
     * menghasilkan rubrik yang salah tandanya diam-diam (salinan rubrik Seri A
     * lahir bertanda Seri A juga, padahal maksudnya dipakai di babak Final).
     */
    public function startDuplicateCategory($id)
    {
        $cat = AssessmentCategory::where('eventner_id', $this->eventnerId)->findOrFail($id);

        $this->duplicatingCategoryId = $id;
        $this->duplicateCategoryName = $cat->name . ' (Salinan)';
    }

    public function cancelDuplicateCategory()
    {
        $this->reset('duplicatingCategoryId', 'duplicateCategoryName');
    }

    public function confirmDuplicateCategory()
    {
        $this->validate(['duplicateCategoryName' => 'required|string|max:255']);

        $original = AssessmentCategory::with(['subCategories.criterias', 'judges', 'deductionCategories.criterias'])
            ->where('eventner_id', $this->eventnerId)
            ->findOrFail($this->duplicatingCategoryId);

        $maxOrder = AssessmentCategory::where('eventner_id', $this->eventnerId)->max('sort_order') ?? 0;

        // Clone the category — seri/babak dikosongkan, lihat docblock di atas.
        $newCategory = AssessmentCategory::create([
            'eventner_id' => $this->eventnerId,
            'competition_category_id' => $original->competition_category_id,
            'competition_group_id' => null,
            'competition_series_id' => null,
            'competition_round_id' => null,
            'name' => strip_tags($this->duplicateCategoryName),
            'sort_order' => $maxOrder + 1,
        ]);

        // Clone pivot juri agar kategori hasil duplikat tetap punya juri yang sama.
        // Salinan mewakili rubrik yang sama untuk babak lain, jadi pembagiannya
        // ikut — kalau tidak, panitia harus mencentang ulang tiap babak.
        $newCategory->syncRubricJudges($original->rubricJudgeIds());

        // Clone sub-categories and their criteria
        foreach ($original->subCategories as $subIndex => $sub) {
            $newSub = AssessmentSubCategory::create([
                'assessment_category_id' => $newCategory->id,
                'name' => $sub->name,
                'sort_order' => $subIndex + 1,
            ]);

            foreach ($sub->criterias as $crit) {
                AssessmentCriteria::create([
                    'assessment_sub_category_id' => $newSub->id,
                    'name' => $crit->name,
                    'score_options' => $crit->score_options,
                    'weight' => $crit->weight ?? 1,
                    'sort_order' => $crit->sort_order,
                ]);
            }
        }

        // Clone deduction categories and their criterias, menempel ke kategori hasil duplikat
        foreach ($original->deductionCategories as $dedIndex => $dedCat) {
            $newDedCat = DeductionCategory::create([
                'eventner_id' => $this->eventnerId,
                'assessment_category_id' => $newCategory->id,
                'name' => $dedCat->name,
                'sort_order' => $dedIndex + 1,
            ]);

            foreach ($dedCat->criterias as $dedCrit) {
                DeductionCriteria::create([
                    'deduction_category_id' => $newDedCat->id,
                    'name' => $dedCrit->name,
                    'deduction_options' => $dedCrit->deduction_options,
                    'sort_order' => $dedCrit->sort_order,
                ]);
            }
        }

        $this->toast('Kategori berhasil diduplikat. Atur Babak/Grup-nya bila perlu — salinan sengaja tidak mewarisi keduanya.');
        $this->reset('duplicatingCategoryId', 'duplicateCategoryName');
        $this->refreshRubricScopeInputs();
    }

    public function openCopyToModal($sourceCategoryId)
    {
        $this->copyToSourceCategoryId = (int) $sourceCategoryId;
        $this->copyToTargetCompetitionCategoryId = null;
        $this->showCopyToModal = true;
    }

    public function closeCopyToModal()
    {
        $this->showCopyToModal = false;
        $this->copyToSourceCategoryId = null;
        $this->copyToTargetCompetitionCategoryId = null;
    }

    /**
     * Salin seluruh struktur kategori sumber ke tingkat (competition_category) target.
     * Buat kategori baru di tingkat target + clone sub-kategori, kriteria, pengurangan, juri.
     */
    public function confirmCopyTo()
    {
        $targetCompetitionCategoryId = $this->copyToTargetCompetitionCategoryId;

        if (! $this->copyToSourceCategoryId || ! $targetCompetitionCategoryId) {
            $this->gagal('Pilih tingkat tujuan terlebih dahulu.');
            $this->dispatch('copy:done', success: false, message: 'Pilih tingkat tujuan terlebih dahulu.');

            return;
        }

        $target = CompetitionCategory::where('eventner_id', $this->eventnerId)
            ->find($targetCompetitionCategoryId);
        $source = AssessmentCategory::where('eventner_id', $this->eventnerId)
            ->find($this->copyToSourceCategoryId);

        // Param bernama, bukan posisional: Livewire menaruh param di event.detail
        // apa adanya, jadi bentuk array hanya menambah satu lapis yang harus
        // ditebak pembacanya.
        $this->dispatch('copy:confirm',
            source_name: $source?->name ?? '',
            target_name: $target?->full_name ?? '',
        );
    }

    public function executeCopyTo($sourceId = null)
    {
        $sourceId = $sourceId ?: $this->copyToSourceCategoryId;
        $targetCompetitionCategoryId = $this->copyToTargetCompetitionCategoryId;

        if (! $sourceId || ! $targetCompetitionCategoryId) {
            $this->gagal('Pilih tingkat tujuan terlebih dahulu.');
            $this->dispatch('copy:done', success: false, message: 'Pilih tingkat tujuan terlebih dahulu.');

            return;
        }

        try {
            $original = AssessmentCategory::with(['subCategories.criterias', 'judges', 'deductionCategories.criterias'])
                ->where('eventner_id', $this->eventnerId)
                ->findOrFail($sourceId);

            // Tingkat tujuan harus milik eventner ini DAN tingkat lomba —
            // induk ber-anak bukan tujuan sah (rubriknya jadi yatim).
            CompetitionCategory::where('eventner_id', $this->eventnerId)
                ->selectable()
                ->findOrFail($targetCompetitionCategoryId);
        } catch (\Throwable $e) {
            $this->dispatch('copy:done', success: false, message: 'Kategori atau tingkat tujuan tidak ditemukan.');

            return;
        }

        $maxOrder = AssessmentCategory::where('eventner_id', $this->eventnerId)->max('sort_order') ?? 0;

        $newCategory = AssessmentCategory::create([
            'eventner_id' => $this->eventnerId,
            'competition_category_id' => $targetCompetitionCategoryId,
            'name' => $original->name,
            'sort_order' => $maxOrder + 1,
        ]);
        // Copy by rubrik: hanya sub-kategori + kriteria (bobot, skor). Tanpa juri & kelompok pengurangan.
        // Babak & grup sengaja TIDAK ikut disalin: keduanya milik tingkat asal,
        // jadi menyalinnya ke tingkat tujuan cuma menghasilkan penunjuk yatim
        // (rubriknya tak akan pernah muncul di juri tingkat tujuan).
        foreach ($original->subCategories as $subIndex => $sub) {
            $newSub = AssessmentSubCategory::create([
                'assessment_category_id' => $newCategory->id,
                'name' => $sub->name,
                'sort_order' => $subIndex + 1,
            ]);

            foreach ($sub->criterias as $crit) {
                AssessmentCriteria::create([
                    'assessment_sub_category_id' => $newSub->id,
                    'name' => $crit->name,
                    'score_options' => $crit->score_options,
                    'weight' => $crit->weight ?? 1,
                    'sort_order' => $crit->sort_order,
                ]);
            }
        }

        $targetName = CompetitionCategory::find($targetCompetitionCategoryId)?->full_name ?? 'Tingkat tujuan';
        $this->closeCopyToModal();
        $this->dispatch('copy:done', success: true, message: "Rubrik '{$original->name}' berhasil disalin ke {$targetName}.");
    }

    public function addSubCategory($categoryId)
    {
        // Verify the category belongs to this eventner
        AssessmentCategory::where('eventner_id', $this->eventnerId)->findOrFail($categoryId);

        $name = $this->newSubCategoryNames[$categoryId] ?? '';

        if (empty(trim($name))) {
            return;
        }

        $maxOrder = AssessmentSubCategory::where('assessment_category_id', $categoryId)->max('sort_order') ?? 0;

        AssessmentSubCategory::create([
            'assessment_category_id' => $categoryId,
            'name' => strip_tags($name),
            'sort_order' => $maxOrder + 1,
        ]);

        $this->newSubCategoryNames[$categoryId] = '';
    }

    public function deleteSubCategory($id)
    {
        // Verify ownership through parent category
        $sub = AssessmentSubCategory::whereHas('category', function ($q) {
            $q->where('eventner_id', $this->eventnerId);
        })->with('criterias')->findOrFail($id);

        if ($this->anyCriteriaHasScores($sub->criterias->pluck('id'))) {
            $this->gagal('Tidak bisa menghapus sub-kategori: sudah ada nilai yang masuk.', route('eventner.scoring.index'), 'Buka Input Nilai');

            return;
        }

        // Sama seperti deleteCategory(): champion_assessment menunjuk ke sub
        // kategori dengan cascade, jadi menghapusnya mencabut rubrik dari
        // kategori juara tanpa peringatan.
        $championNames = ChampionCategory::where('eventner_id', $this->eventnerId)
            ->whereHas('assessmentSubCategories', fn ($q) => $q->where('assessment_sub_categories.id', $sub->id))
            ->pluck('name');

        if ($championNames->isNotEmpty()) {
            $this->gagal(
                'Tidak bisa menghapus sub-kategori: rubriknya dipakai kategori juara '
                . $championNames->implode(', ')
                . '. Lepaskan rubrik itu dari kategori juara dulu.',
                route('eventner.champion-categories.index'),
                'Buka Kategori Juara'
            );

            return;
        }

        $sub->delete();
    }

    public function deleteCriteria($id)
    {
        // Verify ownership through parent chain: criteria -> subCategory -> category -> eventner
        $crit = AssessmentCriteria::whereHas('subCategory.category', function ($q) {
            $q->where('eventner_id', $this->eventnerId);
        })->findOrFail($id);

        if ($this->criteriaHasScores($id)) {
            $this->gagal('Tidak bisa menghapus kriteria: sudah ada nilai yang masuk.', route('eventner.scoring.index'), 'Buka Input Nilai');

            return;
        }

        // Kriteria bisa dicentang langsung di kategori juara (pivot
        // champion_criteria / champion_tiebreak_criteria), juga ikut lewat
        // pivot sub-kategori. Keduanya cascade, jadi menghapus kriteria
        // diam-diam mengubah peringkat juara yang sudah dihitung. Sama seperti
        // deleteCategory()/deleteSubCategory(): sebutkan kategori juaranya dulu.
        $championNames = ChampionCategory::where('eventner_id', $this->eventnerId)
            ->where(function ($q) use ($id) {
                $q->whereHas('criterias', fn ($c) => $c->where('assessment_criterias.id', $id))
                    ->orWhereHas('tiebreakCriterias', fn ($c) => $c->where('assessment_criterias.id', $id))
                    ->orWhereHas('assessmentSubCategories.criterias', fn ($c) => $c->where('assessment_criterias.id', $id));
            })
            ->pluck('name');

        if ($championNames->isNotEmpty()) {
            $this->gagal(
                'Tidak bisa menghapus kriteria: kriteria ini dipakai kategori juara '
                . $championNames->implode(', ')
                . '. Lepaskan kriteria itu dari kategori juara dulu.',
                route('eventner.champion-categories.index'),
                'Buka Kategori Juara'
            );

            return;
        }

        $crit->delete();
    }

    // ============================================================
    // EDIT: Category
    // ============================================================
    public $editingCategoryId = null;

    public $editCategoryName = '';

    public function startEditCategory($id)
    {
        $cat = AssessmentCategory::where('eventner_id', $this->eventnerId)->findOrFail($id);
        $this->editingCategoryId = $id;
        $this->editCategoryName = $cat->name;
    }

    public function saveEditCategory()
    {
        $this->validate(['editCategoryName' => 'required|string|max:255']);
        AssessmentCategory::where('eventner_id', $this->eventnerId)
            ->findOrFail($this->editingCategoryId)
            ->update(['name' => strip_tags($this->editCategoryName)]);
        $this->reset('editingCategoryId', 'editCategoryName');
    }

    public function cancelEditCategory()
    {
        $this->reset('editingCategoryId', 'editCategoryName');
    }

    // ============================================================
    // EDIT: Sub Category
    // ============================================================
    public $editingSubCategoryId = null;

    public $editSubCategoryName = '';

    public function startEditSubCategory($id)
    {
        $sub = AssessmentSubCategory::whereHas('category', function ($q) {
            $q->where('eventner_id', $this->eventnerId);
        })->findOrFail($id);
        $this->editingSubCategoryId = $id;
        $this->editSubCategoryName = $sub->name;
    }

    public function saveEditSubCategory()
    {
        $this->validate(['editSubCategoryName' => 'required|string|max:255']);
        AssessmentSubCategory::whereHas('category', function ($q) {
            $q->where('eventner_id', $this->eventnerId);
        })->findOrFail($this->editingSubCategoryId)
            ->update(['name' => strip_tags($this->editSubCategoryName)]);
        $this->reset('editingSubCategoryId', 'editSubCategoryName');
    }

    public function cancelEditSubCategory()
    {
        $this->reset('editingSubCategoryId', 'editSubCategoryName');
    }

    // ============================================================
    // KRITERIA MODAL (Tambah & Edit — Nama, Bobot, Label Groups)
    // ============================================================
    public $criteriaModalSubCategoryId = null;

    public $criteriaModalTargetId = null; // null = new, int = editing

    public $criteriaModalName = '';

    public $criteriaModalWeight = 1;

    public $labelGroups = []; // [ ['label' => 'Kurang', 'scores' => '23,30'], ... ]

    public function openCriteriaModal($subCategoryId, $criteriaId = null)
    {
        $this->criteriaModalSubCategoryId = $subCategoryId;
        $this->criteriaModalTargetId = $criteriaId;
        $this->labelGroups = [];

        if ($criteriaId) {
            // Editing existing criteria
            $crit = AssessmentCriteria::whereHas('subCategory.category', function ($q) {
                $q->where('eventner_id', $this->eventnerId);
            })->findOrFail($criteriaId);

            $this->criteriaModalName = $crit->name;
            $this->criteriaModalWeight = $crit->weight ?? 1;

            // Load existing label groups from score_options
            $grouped = [];
            foreach ($crit->score_options ?? [] as $opt) {
                if (is_array($opt) && ! empty($opt['label'])) {
                    $label = $opt['label'];
                    if (! isset($grouped[$label])) {
                        $grouped[$label] = ['label' => $label, 'scores' => []];
                    }
                    $grouped[$label]['scores'][] = $opt['score'];
                } else {
                    $score = is_array($opt) ? ($opt['score'] ?? '') : $opt;
                    if (! isset($grouped[''])) {
                        $grouped[''] = ['label' => '', 'scores' => []];
                    }
                    $grouped['']['scores'][] = $score;
                }
            }
            foreach ($grouped as $g) {
                $this->labelGroups[] = ['label' => $g['label'], 'scores' => implode(', ', $g['scores'])];
            }
        } else {
            // New criteria
            $this->criteriaModalName = '';
            $this->criteriaModalWeight = 1;
        }

        if (empty($this->labelGroups)) {
            $this->labelGroups[] = ['label' => '', 'scores' => ''];
        }

        // Modal kini selalu dirender di DOM (Bootstrap). Buka lewat JS agar tampil
        // tanpa bergantung pada render ulang Livewire untuk kondisi @if.
        $this->dispatch('open-criteria-modal');
    }

    public function closeCriteriaModal()
    {
        $this->reset('criteriaModalSubCategoryId', 'criteriaModalTargetId',
            'criteriaModalName', 'criteriaModalWeight', 'labelGroups');
        $this->dispatch('close-criteria-modal');
    }

    /**
     * Siapkan modal untuk mode "Tambah Kriteria": simpan sub-kategori target dan
     * reset form. Modal dibuka langsung via Bootstrap (data-bs-toggle), bukan lewat
     * render ulang @if — sehingga tampil bahkan jika request Livewire bermasalah.
     */
    public function prepareAddCriteria($subCategoryId)
    {
        $this->reset('criteriaModalTargetId', 'criteriaModalName', 'criteriaModalWeight', 'labelGroups');
        $this->criteriaModalSubCategoryId = (int) $subCategoryId;
        $this->labelGroups = [['label' => '', 'scores' => '']];

        // Buka modal setelah re-render selesai (dispatch diproses pasca-morph),
        // sehingga class Bootstrap .show tidak hilang oleh Livewire re-render.
        $this->dispatch('open-criteria-modal');
    }

    public function addLabelRow()
    {
        $this->labelGroups[] = ['label' => '', 'scores' => ''];
    }

    public function removeLabelRow($index)
    {
        if (count($this->labelGroups) > 1) {
            unset($this->labelGroups[$index]);
            $this->labelGroups = array_values($this->labelGroups);
        }
    }

    public function fillLabelPreset()
    {
        $this->labelGroups = [
            ['label' => 'Kurang', 'scores' => '0 – 25'],
            ['label' => 'Cukup', 'scores' => '26 – 50'],
            ['label' => 'Baik', 'scores' => '51 – 75'],
            ['label' => 'Sangat Baik', 'scores' => '76 – 100'],
        ];
    }

    public function saveCriteriaModal()
    {
        $this->validate(['criteriaModalName' => 'required|string|max:255']);

        // Generate score_options from label groups
        $allScores = [];
        $hasLabels = false;

        foreach ($this->labelGroups as $group) {
            $label = trim($group['label'] ?? '');
            $scoresRaw = $group['scores'] ?? '';

            // Parser yang sama dengan preview di blade — lihat ScoreOptions.
            // Dulu keduanya punya daftar pemisah berbeda, jadi yang dilihat
            // operator di preview bukan yang tersimpan.
            $parts = \App\Support\ScoreOptions::split($scoresRaw);

            if (! empty($label)) {
                $hasLabels = true;
            }

            foreach ($parts as $part) {
                if (empty($label)) {
                    $allScores[] = $part;
                } else {
                    $allScores[] = ['score' => $part, 'label' => $label];
                }
            }
        }

        if (empty($allScores)) {
            session()->flash('error_criteria_modal', 'Minimal satu skor harus diisi.');

            return;
        }

        // Normalize
        if ($hasLabels) {
            $scoreOptions = array_values(array_map(
                fn ($s) => is_array($s) ? $s : ['score' => $s, 'label' => ''],
                $allScores
            ));
        } else {
            $scoreOptions = array_values(array_map(
                fn ($s) => is_array($s) ? $s['score'] : $s,
                $allScores
            ));
        }

        if ($this->criteriaModalTargetId) {
            // Edit existing
            AssessmentCriteria::whereHas('subCategory.category', function ($q) {
                $q->where('eventner_id', $this->eventnerId);
            })->findOrFail($this->criteriaModalTargetId)
                ->update([
                    'name' => strip_tags($this->criteriaModalName),
                    'score_options' => $scoreOptions,
                    'weight' => $this->criteriaModalWeight >= 0 ? $this->criteriaModalWeight : 1,
                ]);
        } else {
            // Create new
            $subCat = AssessmentSubCategory::whereHas('category', function ($q) {
                $q->where('eventner_id', $this->eventnerId);
            })->findOrFail($this->criteriaModalSubCategoryId);

            $maxOrder = AssessmentCriteria::where('assessment_sub_category_id', $subCat->id)->max('sort_order') ?? 0;

            AssessmentCriteria::create([
                'assessment_sub_category_id' => $subCat->id,
                'name' => strip_tags($this->criteriaModalName),
                'score_options' => $scoreOptions,
                'weight' => $this->criteriaModalWeight >= 0 ? $this->criteriaModalWeight : 1,
                'sort_order' => $maxOrder + 1,
            ]);
        }

        $this->closeCriteriaModal();
    }

    /**
     * Teks preview badge dari kelompok label yang sedang diketik.
     *
     * Dipakai blade supaya preview dan hasil simpan tidak bisa lagi berbeda:
     * keduanya memanggil parser yang sama.
     *
     * @return array<int, string>
     */
    public function getScoreOptionPreviewProperty(): array
    {
        $preview = [];

        foreach ($this->labelGroups as $group) {
            $label = trim($group['label'] ?? '');

            foreach (\App\Support\ScoreOptions::split($group['scores'] ?? '') as $score) {
                $preview[] = $label !== '' ? "{$score} ({$label})" : $score;
            }
        }

        return $preview;
    }

    // ============================================================
    // DEDUCTION CATEGORIES & CRITERIA
    // ============================================================

    public $newDeductionCategoryNames = [];

    public $newDeductionCriteriaNames = [];

    public $newDeductionCriteriaOptions = [];

    public function addDeductionCategory($assessmentCategoryId)
    {
        // Pastikan assessment category milik eventner ini
        AssessmentCategory::where('eventner_id', $this->eventnerId)->findOrFail($assessmentCategoryId);

        $name = $this->newDeductionCategoryNames[$assessmentCategoryId] ?? '';

        if (trim($name) === '') {
            session()->flash("error_dedcat_{$assessmentCategoryId}", 'Nama kategori pengurangan wajib diisi.');

            return;
        }

        $maxOrder = DeductionCategory::where('assessment_category_id', $assessmentCategoryId)->max('sort_order') ?? 0;

        DeductionCategory::create([
            'eventner_id' => $this->eventnerId,
            'assessment_category_id' => $assessmentCategoryId,
            'name' => strip_tags($name),
            'sort_order' => $maxOrder + 1,
        ]);

        $this->newDeductionCategoryNames[$assessmentCategoryId] = '';
    }

    public function deleteDeductionCategory($id)
    {
        $category = DeductionCategory::where('eventner_id', $this->eventnerId)
            ->with('criterias')
            ->findOrFail($id);

        if ($this->anyDeductionHasScores($category->criterias->pluck('id'))) {
            $this->gagal('Tidak bisa menghapus kategori pengurangan: sudah ada nilai pengurangan yang masuk.');

            return;
        }

        $category->delete();
    }

    public function addDeductionCriteria($categoryId)
    {
        DeductionCategory::where('eventner_id', $this->eventnerId)->findOrFail($categoryId);

        $name = $this->newDeductionCriteriaNames[$categoryId] ?? '';
        $optionsStr = $this->newDeductionCriteriaOptions[$categoryId] ?? '';

        if (empty(trim($name)) || empty(trim($optionsStr))) {
            return;
        }

        $options = array_map('trim', explode(',', $optionsStr));
        $options = array_filter($options, fn ($v) => $v !== '');

        if (empty($options)) {
            return;
        }

        // Ensure all values are numeric (allow negatives)
        foreach ($options as $opt) {
            if (! is_numeric($opt)) {
                $this->gagal('Semua opsi pengurangan harus berupa angka.');

                return;
            }
        }

        if (count($options) > self::MAX_OPTIONS) {
            $this->gagal('Maksimal '.self::MAX_OPTIONS.' opsi pengurangan per kriteria.');

            return;
        }

        $maxOrder = DeductionCriteria::where('deduction_category_id', $categoryId)->max('sort_order') ?? 0;

        DeductionCriteria::create([
            'deduction_category_id' => $categoryId,
            'name' => strip_tags($name),
            'deduction_options' => array_values($options),
            'sort_order' => $maxOrder + 1,
        ]);

        $this->newDeductionCriteriaNames[$categoryId] = '';
        $this->newDeductionCriteriaOptions[$categoryId] = '';
    }

    public function deleteDeductionCriteria($id)
    {
        DeductionCriteria::whereHas('category', function ($q) {
            $q->where('eventner_id', $this->eventnerId);
        })->findOrFail($id);

        if (ScoreDeduction::where('deduction_criteria_id', $id)->exists()) {
            $this->gagal('Tidak bisa menghapus kriteria pengurangan: sudah ada nilai pengurangan yang masuk.');

            return;
        }

        DeductionCriteria::where('id', $id)->delete();
    }

    // Edit Deduction Category
    public $editingDeductionCategoryId = null;

    public $editDeductionCategoryName = '';

    public function startEditDeductionCategory($id)
    {
        $cat = DeductionCategory::where('eventner_id', $this->eventnerId)->findOrFail($id);
        $this->editingDeductionCategoryId = $id;
        $this->editDeductionCategoryName = $cat->name;
    }

    public function saveEditDeductionCategory()
    {
        $this->validate(['editDeductionCategoryName' => 'required|string|max:255']);
        DeductionCategory::where('eventner_id', $this->eventnerId)
            ->findOrFail($this->editingDeductionCategoryId)
            ->update(['name' => strip_tags($this->editDeductionCategoryName)]);
        $this->reset('editingDeductionCategoryId', 'editDeductionCategoryName');
    }

    public function cancelEditDeductionCategory()
    {
        $this->reset('editingDeductionCategoryId', 'editDeductionCategoryName');
    }

    // ============================================================
    // PENGURANGAN TINGKAT — berlaku seluruh kategori penilaian satu tingkat
    //
    // assessment_category_id sengaja NULL: kelompok ini tidak memotong kolom
    // kategori mana pun, melainkan NILAI AKHIR. Yang diikat justru tingkat
    // lombanya (competition_category_id = activeTab), karena sanksi satu
    // tingkat tidak boleh ikut memotong nilai peserta tingkat lain.
    // Pembedaannya lewat kolom `scope`, bukan lewat NULL, karena NULL juga
    // dipakai data lama yang belum ditentukan targetnya.
    // ============================================================

    public $newGlobalDeductionCategoryName = '';

    /**
     * Tingkat lomba yang jadi pemilik kelompok baru.
     *
     * activeTab '' berarti tab "Semua Tingkat": tidak ada tingkat yang bisa
     * dipakai sebagai pemilik, jadi jatuh ke tingkat pertama yang ada. Bila
     * eventner belum punya tingkat sama sekali, kembalikan null supaya
     * pemanggil bisa menolak dengan pesan yang jelas — kelompok tanpa tingkat
     * tidak akan pernah muncul di panel Input Nilai.
     */
    private function ownerCompetitionCategoryId()
    {
        if ($this->activeTab !== '') {
            return $this->normalizeActiveTab($this->activeTab);
        }

        $first = $this->competitionCategories->first();

        return $first ? (string) $first->id : null;
    }

    public function addGlobalDeductionCategory()
    {
        $name = $this->newGlobalDeductionCategoryName;

        if (trim($name) === '') {
            session()->flash('error_dedcat_global', 'Nama kelompok pengurangan wajib diisi.');

            return;
        }

        $ownerId = $this->ownerCompetitionCategoryId();

        if (! $ownerId) {
            $this->gagal(
                'Buat tingkat lomba terlebih dahulu sebelum menambah pengurangan.',
                route('eventner.competition-categories.index'),
                'Buka Kategori Lomba'
            );

            return;
        }

        $maxOrder = DeductionCategory::where('eventner_id', $this->eventnerId)->global()->max('sort_order') ?? 0;

        DeductionCategory::create([
            'eventner_id' => $this->eventnerId,
            'assessment_category_id' => null,
            'competition_category_id' => $ownerId,
            'scope' => DeductionCategory::SCOPE_GLOBAL,
            'name' => strip_tags($name),
            'sort_order' => $maxOrder + 1,
        ]);

        $this->newGlobalDeductionCategoryName = '';
    }

    public function startEditGlobalDeductionCategory($id)
    {
        $cat = DeductionCategory::where('eventner_id', $this->eventnerId)->findOrFail($id);
        $this->editingDeductionCategoryId = $id;
        $this->editDeductionCategoryName = $cat->name;
    }

    public function saveEditGlobalDeductionCategory()
    {
        $this->validate(['editDeductionCategoryName' => 'required|string|max:255']);

        DeductionCategory::where('eventner_id', $this->eventnerId)
            ->global()
            ->findOrFail($this->editingDeductionCategoryId)
            ->update(['name' => strip_tags($this->editDeductionCategoryName)]);

        $this->reset('editingDeductionCategoryId', 'editDeductionCategoryName');
    }

    public function deleteGlobalDeductionCategory($id)
    {
        $category = DeductionCategory::where('eventner_id', $this->eventnerId)
            ->global()
            ->with('criterias')
            ->findOrFail($id);

        if ($this->anyDeductionHasScores($category->criterias->pluck('id'))) {
            $this->gagal('Tidak bisa menghapus kelompok pengurangan: sudah ada nilai pengurangan yang masuk.');

            return;
        }

        $category->delete();
    }

    // Edit Deduction Criteria
    public $editingDeductionCriteriaId = null;

    public $editDeductionCriteriaName = '';

    public $editDeductionCriteriaOptions = '';

    public function startEditDeductionCriteria($id)
    {
        $crit = DeductionCriteria::whereHas('category', function ($q) {
            $q->where('eventner_id', $this->eventnerId);
        })->findOrFail($id);
        $this->editingDeductionCriteriaId = $id;
        $this->editDeductionCriteriaName = $crit->name;
        $this->editDeductionCriteriaOptions = implode(',', $crit->deduction_options ?? []);
    }

    public function saveEditDeductionCriteria()
    {
        $this->validate([
            'editDeductionCriteriaName' => 'required|string|max:255',
            'editDeductionCriteriaOptions' => 'required|string',
        ]);

        $options = array_filter(array_map('trim', explode(',', $this->editDeductionCriteriaOptions)), fn ($v) => $v !== '');

        if (empty($options)) {
            return;
        }

        foreach ($options as $opt) {
            if (! is_numeric($opt)) {
                $this->gagal('Semua opsi pengurangan harus berupa angka.');

                return;
            }
        }

        if (count($options) > self::MAX_OPTIONS) {
            $this->gagal('Maksimal '.self::MAX_OPTIONS.' opsi pengurangan per kriteria.');

            return;
        }

        DeductionCriteria::whereHas('category', function ($q) {
            $q->where('eventner_id', $this->eventnerId);
        })->findOrFail($this->editingDeductionCriteriaId)
            ->update([
                'name' => strip_tags($this->editDeductionCriteriaName),
                'deduction_options' => array_values($options),
            ]);

        $this->reset('editingDeductionCriteriaId', 'editDeductionCriteriaName', 'editDeductionCriteriaOptions');
    }

    public function cancelEditDeductionCriteria()
    {
        $this->reset('editingDeductionCriteriaId', 'editDeductionCriteriaName', 'editDeductionCriteriaOptions');
    }

    // ============================================================
    // REORDER: Drag & Drop Sorting
    // ============================================================

    public function reorderCategories($id, $position)
    {
        // Daftar yang diurutkan HARUS sama dengan yang dirender — yaitu hanya
        // kategori milik tab aktif. Dulu di sini di-pluck seluruh kategori
        // eventner, padahal $position datang dari DOM yang cuma memuat satu
        // tab. Di tab mana pun selain yang pertama, posisinya jadi menunjuk
        // kategori milik tingkat lain, sehingga urutan yang tersimpan meleset
        // dan kategori bisa berpindah tingkat tanpa disadari.
        $query = AssessmentCategory::where('eventner_id', $this->eventnerId)
            ->orderBy('sort_order');

        if ($this->activeTab !== '') {
            $query->where('competition_category_id', $this->activeTab);
        }

        $categories = $query->pluck('id')->toArray();

        // Remove the dragged item from its current position
        $key = array_search($id, $categories);
        if ($key !== false) {
            array_splice($categories, $key, 1);
        }

        // Insert at the new position
        array_splice($categories, $position, 0, $id);

        // Update all sort_order values
        DB::transaction(function () use ($categories) {
            foreach ($categories as $order => $catId) {
                AssessmentCategory::where('id', $catId)->update(['sort_order' => $order + 1]);
            }
        });
    }

    public function reorderSubCategories($id, $position, $groupId)
    {
        // Verify both source and destination categories belong to this eventner
        AssessmentCategory::where('eventner_id', $this->eventnerId)->findOrFail($groupId);

        // Find the sub-category being moved
        $movedSub = AssessmentSubCategory::whereHas('category', function ($q) {
            $q->where('eventner_id', $this->eventnerId);
        })->findOrFail($id);

        $oldCategoryId = $movedSub->assessment_category_id;
        $newCategoryId = $groupId; // destination category from wire:sort:group-id

        DB::transaction(function () use ($movedSub, $oldCategoryId, $newCategoryId, $id, $position) {
            // If moving to a different category, update the foreign key
            if ($oldCategoryId != $newCategoryId) {
                $movedSub->update(['assessment_category_id' => $newCategoryId]);

                // Re-index old category's remaining sub-categories
                $oldSiblings = AssessmentSubCategory::where('assessment_category_id', $oldCategoryId)
                    ->orderBy('sort_order')
                    ->pluck('id')
                    ->toArray();
                foreach ($oldSiblings as $order => $sibId) {
                    AssessmentSubCategory::where('id', $sibId)->update(['sort_order' => $order + 1]);
                }
            }

            // Re-index the destination category's sub-categories
            $siblings = AssessmentSubCategory::where('assessment_category_id', $newCategoryId)
                ->orderBy('sort_order')
                ->pluck('id')
                ->toArray();

            // Remove the moved item if it's already in the list
            $key = array_search($id, $siblings);
            if ($key !== false) {
                array_splice($siblings, $key, 1);
            }

            // Insert at the new position
            array_splice($siblings, $position, 0, $id);

            foreach ($siblings as $order => $sibId) {
                AssessmentSubCategory::where('id', $sibId)->update(['sort_order' => $order + 1]);
            }
        });
    }

    public function reorderCriterias($id, $position)
    {
        // Verify the criteria belongs to this eventner
        $movedCrit = AssessmentCriteria::whereHas('subCategory.category', function ($q) {
            $q->where('eventner_id', $this->eventnerId);
        })->findOrFail($id);

        $criterias = AssessmentCriteria::where('assessment_sub_category_id', $movedCrit->assessment_sub_category_id)
            ->orderBy('sort_order')
            ->pluck('id')
            ->toArray();

        $key = array_search($id, $criterias);
        if ($key !== false) {
            array_splice($criterias, $key, 1);
        }

        array_splice($criterias, $position, 0, $id);

        DB::transaction(function () use ($criterias) {
            foreach ($criterias as $order => $critId) {
                AssessmentCriteria::where('id', $critId)->update(['sort_order' => $order + 1]);
            }
        });
    }

    // ============================================================
    // TABS & COPY FORMAT
    // ============================================================

    public function selectTab($id)
    {
        $this->activeTab = $this->normalizeActiveTab($id);
        $this->refreshRubricScopeInputs();
    }

    /**
     * activeTab ditulis langsung sebagai competition_category_id ke
     * assessment_categories, dan nilainya berasal dari klien. Tanpa
     * pemeriksaan ini, kategori penilaian baru bisa ditempelkan ke tingkat
     * milik event lain — barisnya tersimpan tapi tidak pernah tampil di
     * halaman mana pun, karena semua query sudah di-scope per eventner.
     *
     * selectable(): id induk ber-anak juga ditolak — induk bukan tingkat lomba,
     * dan rubrik yang menempel di sana sama-sama tidak pernah tampil karena
     * pemilih tingkat di halaman ini hanya menawarkan tingkat lomba.
     */
    private function normalizeActiveTab($id): string
    {
        if ($id === '' || $id === null) {
            return '';
        }

        $ada = CompetitionCategory::where('eventner_id', $this->eventnerId)
            ->selectable()
            ->find($id);

        return $ada ? (string) $ada->id : '';
    }

    // ============================================================
    // RENDER
    // ============================================================

    public function render()
    {
        return view('livewire.eventner.format-nilai.builder')->title('Format Penilaian - ' . app_name());
    }
}
