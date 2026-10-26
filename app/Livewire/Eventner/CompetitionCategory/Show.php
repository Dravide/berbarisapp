<?php

namespace App\Livewire\Eventner\CompetitionCategory;

use Livewire\Component;
use App\Models\AssessmentCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionFeeWave;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\CompetitionRoundRegistration;
use App\Models\CompetitionSeries;
use App\Models\DeductionCategory;
use App\Models\Judge;
use App\Livewire\Concerns\MelaporKePengguna;
use App\Models\Registration;
use App\Services\ChampionCalculator;
use App\Models\ScoreDeduction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;

/**
 * Halaman atur satu tingkat lomba: Grup & Juri, Seri, Babak, dan Gelombang
 * harga.
 *
 * Menggantikan modal "Atur Babak, Grup & Seri" yang menumpuk tiga bagian
 * dalam satu gulungan — kini tiap bagian jadi tab di halamannya sendiri,
 * dan kategorinya tidak lagi dilacak lewat tiga ID panel sekaligus.
 *
 * Gelombang harga ikut ke sini, bukan ke modal Tambah/Edit Kategori: modal
 * itu harus bisa menyimpan tingkat yang belum punya id, sedangkan gelombang
 * menempel pada id tingkat. Halaman ini juga sudah jadi tempat kerja
 * per-tingkat, jadi tidak ada modal ketiga yang perlu diingat panitia.
 */
#[Layout('layouts.admin')]
class Show extends Component
{
    use MelaporKePengguna;

    /** Tingkat lomba yang diatur halaman ini. Hanya id-nya yang beredar di klien. */
    #[Locked]
    public $categoryId = null;

    /** Tab yang terbuka. Di-URL-kan supaya bisa di-bookmark (?tab=babak). */
    #[Url(history: true)]
    public string $tab = 'grup'; // grup | seri | babak | gelombang

    public $groupName = '';
    public $groupSortOrder = '';
    public $editingGroupId = null;

    public $seriesName = '';
    public $seriesSortOrder = '';
    public $editingSeriesId = null;

    public $roundName = '';
    public $roundType = CompetitionRound::TYPE_PRELIMINARY;
    public $roundSortOrder = '';
    public $editingRoundId = null;

    public $waveName = '';
    public $waveStartsOn = '';
    public $waveEndsOn = '';
    public $waveAmount = '';
    public $editingWaveId = null;

    // ── Uang muka (DP) ─────────────────────────────────────────────────
    /** Panitia menyalakan DP untuk tingkat ini? Default mati. */
    public bool $dpAktif = false;
    /** 'nominal' | 'persen' — hanya satu yang boleh terisi. */
    public string $dpMode = 'nominal';
    public $dpAmount = '';
    public $dpPercent = '';
    public $dpDays = '';

    /** Babak final yang sedang dibuka panel "Loloskan Top-N". */
    public $qualifyRoundId = null;
    public $qualifyTopN = 3;
    /** [registration_id => bool] centang dari pratinjau. */
    public $qualifySelection = [];

    protected $eventnerId;

    public function boot()
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403);
        }
        $this->eventnerId = $eventner->id;

        // categoryId datang dari klien tiap request: kalau diganti milik tenant
        // lain, halamannya 404 — bukan diam-diam menampilkan data orang lain.
        if ($this->categoryId !== null && !$this->category) {
            abort(404, 'Tingkat lomba tidak ditemukan.');
        }
    }

    public function mount($category)
    {
        $cat = CompetitionCategory::where('eventner_id', Auth::user()->eventner->id)->find($category);
        abort_unless($cat, 404, 'Tingkat lomba tidak ditemukan.');

        $this->categoryId = $cat->id;
        $this->muatFormDp();
    }

    /**
     * Isi form DP dari keadaan tersimpan.
     *
     * `dpMode` ditentukan dari kolom mana yang terisi, bukan dari preferensi
     * terakhir panitia: kalau DB menyimpan persentase, form harus terbuka di
     * mode persen — kalau tidak, menekan Simpan tanpa menyentuh apa pun akan
     * memindahkan nominal DP dari persen ke rupiah.
     */
    public function muatFormDp(): void
    {
        $cat = $this->category;
        if (! $cat) {
            return;
        }

        $this->dpAktif = $cat->punyaDp();
        $this->dpMode = $cat->dp_percent !== null && $cat->dp_amount === null ? 'persen' : 'nominal';
        $this->dpAmount = $cat->dp_amount !== null ? (string) (float) $cat->dp_amount : '';
        $this->dpPercent = $cat->dp_percent !== null ? (string) $cat->dp_percent : '';
        $this->dpDays = $cat->dp_days !== null ? (string) $cat->dp_days : '';
    }

    /**
     * Simpan pengaturan DP tingkat ini.
     *
     * Nominal/persentase dihitung ulang di server lewat alasanDpTidakSah():
     * DP yang sama dengan atau lebih besar dari tagihan akan membuat portal
     * menawarkan "bayar DP" yang sebenarnya bayar penuh, dan peserta tidak
     * punya cara tahu bedanya.
     *
     * Mematikan DP TIDAK menyentuh pendaftaran yang sudah terlanjur DP — uang
     * yang sudah masuk tetap tercatat di registration_payments dan sisa
     * tagihannya tetap ditagih. Yang berubah hanya penawaran untuk pendaftar
     * berikutnya.
     */
    public function simpanDp()
    {
        $cat = $this->category;
        if (! $cat) {
            return;
        }

        $this->validate([
            'dpAmount' => 'nullable|numeric|min:0',
            'dpPercent' => 'nullable|integer|min:1|max:99',
            'dpDays' => 'nullable|integer|min:1|max:365',
        ], [], [
            'dpAmount' => 'Nominal DP',
            'dpPercent' => 'Persentase DP',
            'dpDays' => 'Tenggat Pelunasan',
        ]);

        $data = [
            'dp_amount' => null,
            'dp_percent' => null,
            'dp_days' => $this->dpDays !== '' && $this->dpDays !== null ? (int) $this->dpDays : null,
        ];

        if ($this->dpAktif) {
            if ($this->dpMode === 'persen') {
                $data['dp_percent'] = $this->dpPercent !== '' && $this->dpPercent !== null
                    ? (int) $this->dpPercent
                    : null;
            } else {
                $data['dp_amount'] = $this->dpAmount !== '' && $this->dpAmount !== null
                    ? (float) $this->dpAmount
                    : null;
            }

            if ($data['dp_amount'] === null && $data['dp_percent'] === null) {
                $this->gagal('Isi nominal atau persentase DP bila DP dinyalakan.');
                return;
            }
        }

        // Validasi memakai keadaan sesudah perubahan, bukan sesudah save:
        // menyalakan DP lalu menyimpannya dengan angka yang salah harus
        // ditolak sebelum apa pun tertulis.
        $cat->fill($data);
        $alasan = $cat->alasanDpTidakSah();

        if ($alasan) {
            $this->gagal($alasan);
            return;
        }

        $cat->save();
        unset($this->category);

        $this->toast($this->dpAktif
            ? 'Pengaturan DP disimpan. Portal pendaftar berikutnya akan menawarkan pilihan DP.'
            : 'DP dimatikan untuk tingkat ini. Pendaftar berikutnya membayar penuh.');
    }

    /** Tingkat yang diatur halaman ini, di-scope tenant setiap kali diambil. */
    #[Computed]
    public function category()
    {
        return CompetitionCategory::where('eventner_id', $this->eventnerId)
            ->find($this->categoryId);
    }

    public function pilihTab(string $tab)
    {
        if (in_array($tab, ['grup', 'seri', 'babak', 'gelombang'], true)) {
            $this->tab = $tab;
        }
    }

    #[Computed]
    public function availableJudges()
    {
        return Judge::where('eventner_id', $this->eventnerId)->get();
    }

    /** Grup tingkat ini, berurutan, dengan jumlah peserta. */
    #[Computed]
    public function groupsByCategory()
    {
        return CompetitionGroup::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $this->category->id)
            ->withCount('registrations')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /** Babak tingkat ini, berurutan, dengan jumlah finalis. */
    #[Computed]
    public function roundsByCategory()
    {
        return CompetitionRound::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $this->category->id)
            ->withCount('roundRegistrations')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /** Seri tingkat ini, berurutan, dengan jumlah peserta. */
    #[Computed]
    public function seriesByCategory()
    {
        return CompetitionSeries::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $this->category->id)
            ->withCount('registrations')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /** Gelombang harga tingkat ini, terurut tanggal, dengan penanda "berlaku hari ini". */
    #[Computed]
    public function feeWavesByCategory()
    {
        return $this->category->gelombang()->get();
    }

    /**
     * ID juri per baris penugasan, untuk centang di tab Grup & Juri.
     *
     * Kuncinya `group:{id}` untuk grup, dan nama scope-nya sendiri
     * (`ungrouped`/`final`/`level`) untuk tiga baris tanpa grup. Dibaca dari
     * competition_group_judge — sumber yang sama dengan yang dipakai tablet
     * juri, jadi layar ini tak bisa lagi menjanjikan regu yang berbeda dari
     * yang ditemukan juri di daftarnya.
     *
     * WAJIB disaring ke TINGKAT yang sedang dibuka. Tiga scope tanpa grup
     * memakai kunci yang sama di seluruh event, jadi tanpa saringan ini centang
     * tingkat lain menyalakan centang tingkat ini: halaman "SD / MI - U12"
     * menampilkan juri `final` milik "SMP - U15" seolah sudah dicentang di sana,
     * dan mengkliknya menulis penugasan yang salah. Kunci `group:{id}` sendiri
     * aman karena id grup unik lintas tingkat — tapi saringannya tetap berlaku
     * untuk ketiganya.
     *
     * Yang disimpan idnya, bukan namanya: dua juri boleh bernama sama, dan
     * mencocokkan centang lewat nama akan menyalakan centang yang salah.
     *
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int,int>>
     */
    #[Computed]
    public function groupJudgeIds()
    {
        return DB::table('competition_group_judge as cgj')
            ->join('judges as j', 'j.id', '=', 'cgj.judge_id')
            ->where('j.eventner_id', $this->eventnerId)
            ->where('cgj.competition_category_id', $this->category->id)
            ->orderBy('j.name')
            ->get(['cgj.competition_group_id', 'cgj.scope', 'cgj.judge_id'])
            ->groupBy(fn ($b) => $b->scope === CompetitionGroup::SCOPE_GROUP
                ? 'group:' . $b->competition_group_id
                : $b->scope)
            ->map(fn ($baris) => $baris->pluck('judge_id')->map(fn ($id) => (int) $id)->unique()->values());
    }

    /**
     * Baris penugasan yang relevan untuk tingkat ini, berurutan seperti di
     * layar: tiap grup, lalu Belum Bergrup, Final, dan Seluruh Tingkat.
     *
     * Tiga baris tanpa grup dirender hanya saat ada gunanya — tingkat tanpa
     * grup tak perlu baris "Seluruh Tingkat" kalau tak punya peserta pun.
     * Tingkat polos (tanpa grup, babak, maupun seri) tetap tak menampilkan
     * pemilih apa pun.
     *
     * @return array<int, array{scope: string, group_id: ?int, label: string, key: string, count: int}>
     */
    #[Computed]
    public function assignmentRows()
    {
        $category = $this->category;

        // Grup & babak menempel pada tingkat ini — kategori 34 "Tingkat Kelas 9"
        // yang beranak di bawah "LOBB" punya grupnya SENDIRI, bukan grup induknya.
        $indukId = $category->id;

        // Peserta dihitung sampai ke anak-anaknya: satu tingkat bisa dibelah
        // jadi sub-kategori, dan semuanya masuk pool grup yang sama.
        $levelIds = collect([$indukId])
            ->merge($category->children->pluck('id'))
            ->all();

        $panelGroups = $this->groupsByCategory;

        // Satu query untuk dua angka: total peserta tingkat, dan berapa di
        // antaranya yang sudah bergrup. Selisihnya baris "Belum Bergrup".
        $hitung = Registration::whereIn('competition_category_id', $levelIds)
            ->selectRaw('COUNT(*) as total, COUNT(competition_group_id) as dengan_grup')
            ->first();
        $peserta = (int) $hitung->total;
        $tanpaGrup = $peserta - (int) $hitung->dengan_grup;

        $baris = [];

        foreach ($panelGroups as $group) {
            $baris[] = [
                'scope' => CompetitionGroup::SCOPE_GROUP,
                'group_id' => $group->id,
                'label' => $group->name,
                'key' => 'group:' . $group->id,
                'count' => $group->registrations_count,
            ];
        }

        if ($panelGroups->isEmpty()) {
            // Tingkat tanpa grup sama sekali (tiga belas event lama): satu baris
            // seluruh tingkat, supaya panel jurinya tidak kosong begitu layar
            // centang rubrik dihapus. Baris ini satu-satunya isi daftar, jadi
            // peserta yang belum bergrup tetap harus terhitung di sini.
            //
            // Tingkat yang pesertanya memang belum ada tak mendapat baris ini:
            // centangnya tak akan mengubah siapa pun, dan tabel berisi satu
            // baris kosong lebih membingungkan daripada pesan "Belum ada grup".
            if ($peserta > 0) {
                $baris[] = [
                    'scope' => CompetitionGroup::SCOPE_LEVEL,
                    'group_id' => null,
                    'label' => CompetitionGroup::SCOPE_LABELS[CompetitionGroup::SCOPE_LEVEL],
                    'key' => CompetitionGroup::SCOPE_LEVEL,
                    'count' => $peserta,
                ];
            }

            return $baris;
        }

        if ($tanpaGrup > 0) {
            $baris[] = [
                'scope' => CompetitionGroup::SCOPE_UNGROUPED,
                'group_id' => null,
                'label' => CompetitionGroup::SCOPE_LABELS[CompetitionGroup::SCOPE_UNGROUPED],
                'key' => CompetitionGroup::SCOPE_UNGROUPED,
                'count' => $tanpaGrup,
            ];
        }

        foreach ($this->roundsByCategory as $round) {
            if (! $round->isFinal()) {
                continue;
            }

            $baris[] = [
                'scope' => CompetitionGroup::SCOPE_FINAL,
                'group_id' => null,
                'label' => CompetitionGroup::SCOPE_LABELS[CompetitionGroup::SCOPE_FINAL],
                'key' => CompetitionGroup::SCOPE_FINAL,
                // Jumlah finalis sudah ikut withCount roundsByCategory — tanpa
                // query hitung tambahan per baris.
                'count' => $round->round_registrations_count,
            ];

            break; // Satu tingkat hanya punya satu babak final.
        }

        return $baris;
    }

    /**
     * Nyalakan/matikan satu centang juri pada satu baris penugasan.
     *
     * Ditulis per perubahan, bukan menunggu tombol Simpan: satu baris = satu
     * syncJudges(), jadi tabelnya tak pernah menyimpan keadaan setengah jadi
     * yang membuat panel juri berbeda dari centangannya.
     */
    public function toggleGroupJudge(string $scope, ?int $groupId, int $judgeId, bool $checked): void
    {
        $category = $this->category;

        if ($groupId !== null) {
            $this->findOwnGroup($groupId);
        }

        // Ditarik ulang dari tenant sendiri: id yang datang dari klien tidak
        // boleh dipercaya begitu saja.
        $sah = Judge::where('eventner_id', $this->eventnerId)->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (! in_array($judgeId, $sah, true)) {
            return;
        }

        $sekarang = CompetitionGroup::judgeIdsUntuk($category->id, $scope, $groupId);
        $baru = $checked
            ? array_values(array_unique([...$sekarang, $judgeId]))
            : array_values(array_diff($sekarang, [$judgeId]));

        CompetitionGroup::syncJudges($category->id, $scope, $groupId, $baru);

        unset($this->groupJudgeIds, $this->assignmentRows);
    }

    /** Nama rubrik per babak, untuk kolom "Rubrik" di daftar babak. */
    #[Computed]
    public function roundRubricNames()
    {
        return AssessmentCategory::where('eventner_id', $this->eventnerId)
            ->whereNotNull('competition_round_id')
            ->get()
            ->groupBy('competition_round_id')
            ->map(fn ($cats) => $cats->pluck('name'));
    }

    /**
     * Nama rubrik per seri, untuk kolom tabel di tab Seri.
     *
     * Hanya rubriknya. Juri tak lagi diturunkan dari seri: penugasan juri ada
     * di tab Grup & Juri, dan seri tinggal menentukan lembar nilai mana yang
     * terbuka untuk peserta itu.
     */
    #[Computed]
    public function seriesRubrics()
    {
        return AssessmentCategory::where('eventner_id', $this->eventnerId)
            ->whereNotNull('competition_series_id')
            ->get()
            ->groupBy('competition_series_id')
            ->map(fn ($cats) => ['rubrics' => $cats->pluck('name')]);
    }

    private function findOwnGroup($id): CompetitionGroup
    {
        return CompetitionGroup::where('eventner_id', $this->eventnerId)->findOrFail($id);
    }

    private function findOwnRound($id): CompetitionRound
    {
        return CompetitionRound::where('eventner_id', $this->eventnerId)->findOrFail($id);
    }

    private function findOwnSeries($id): CompetitionSeries
    {
        return CompetitionSeries::where('eventner_id', $this->eventnerId)->findOrFail($id);
    }

    private function findOwnWave($id): CompetitionFeeWave
    {
        return CompetitionFeeWave::where('eventner_id', $this->eventnerId)->findOrFail($id);
    }

    // ── Grup ───────────────────────────────────────────────────────────

    public function saveGroup()
    {
        $category = $this->category;

        $this->validate([
            'groupName' => [
                'required', 'string', 'max:255',
                // Nama grup unik per tingkat (juga dijaga unique index DB).
                Rule::unique('competition_groups', 'name')
                    ->where('competition_category_id', $category->id)
                    ->where('eventner_id', $this->eventnerId)
                    ->ignore($this->editingGroupId),
            ],
            'groupSortOrder' => 'nullable|integer|min:0',
        ], [], ['groupName' => 'Nama Grup']);

        $data = [
            'name' => strip_tags($this->groupName),
            'sort_order' => $this->groupSortOrder !== '' ? (int) $this->groupSortOrder : 0,
        ];

        if ($this->editingGroupId) {
            $this->findOwnGroup($this->editingGroupId)->update($data);
            $this->toast('Grup berhasil diperbarui.');
        } else {
            CompetitionGroup::create(array_merge($data, [
                'eventner_id' => $this->eventnerId,
                'competition_category_id' => $category->id,
            ]));
            $this->toast('Grup baru berhasil ditambahkan.');
        }

        $this->resetGroupForm();
        unset($this->groupsByCategory, $this->assignmentRows, $this->groupJudgeIds);
    }

    public function editGroup($id)
    {
        $group = $this->findOwnGroup($id);
        $this->editingGroupId = $group->id;
        $this->groupName = $group->name;
        $this->groupSortOrder = $group->sort_order;
    }

    public function deleteGroup($id)
    {
        $group = $this->findOwnGroup($id);

        // Tanpa penjagaan rubrik lagi: penugasan juri menempel di pivot grup
        // (cascadeOnDelete), jadi menghapus grup melepas penugasannya sendiri —
        // bukan meninggalkan nilai yatim yang mengacu kriteria rubriknya.
        $group->delete();
        $this->toast('Grup dihapus. Pesertanya tetap ada, hanya kembali ke peringkat umum. Penugasan jurinya ikut dilepas.');

        unset($this->groupsByCategory, $this->assignmentRows, $this->groupJudgeIds);
    }

    public function resetGroupForm()
    {
        $this->reset(['groupName', 'groupSortOrder', 'editingGroupId']);
    }

    // ── Seri ───────────────────────────────────────────────────────────

    /**
     * Seri menempel pada TINGKAT, sama seperti grup dan babak. Bedanya, seri
     * yang menentukan lembar nilai & cakupan juri, sedangkan grup menentukan
     * tabel peringkat dan nomor undian.
     */
    public function saveSeries()
    {
        $category = $this->category;

        $this->validate([
            'seriesName' => [
                'required', 'string', 'max:255',
                // Nama seri unik per tingkat (juga dijaga unique index DB).
                Rule::unique('competition_series', 'name')
                    ->where('competition_category_id', $category->id)
                    ->where('eventner_id', $this->eventnerId)
                    ->ignore($this->editingSeriesId),
            ],
            'seriesSortOrder' => 'nullable|integer|min:0',
        ], [], ['seriesName' => 'Nama Seri']);

        $data = [
            'name' => strip_tags($this->seriesName),
            'sort_order' => $this->seriesSortOrder !== '' ? (int) $this->seriesSortOrder : 0,
        ];

        if ($this->editingSeriesId) {
            $this->findOwnSeries($this->editingSeriesId)->update($data);
            $this->toast('Seri berhasil diperbarui.');
        } else {
            CompetitionSeries::create(array_merge($data, [
                'eventner_id' => $this->eventnerId,
                'competition_category_id' => $category->id,
            ]));
            $this->toast('Seri baru berhasil ditambahkan.');
        }

        $this->resetSeriesForm();
        unset($this->seriesByCategory, $this->seriesRubrics);
    }

    public function editSeries($id)
    {
        $series = $this->findOwnSeries($id);
        $this->editingSeriesId = $series->id;
        $this->seriesName = $series->name;
        $this->seriesSortOrder = $series->sort_order;
    }

    public function deleteSeries($id)
    {
        $series = $this->findOwnSeries($id);

        // FK-nya nullOnDelete: peserta dan rubrik tidak ikut terhapus, hanya
        // lepas dari serinya. Tetap dicegah kalau rubriknya masih terpasang,
        // karena dari situlah juri tahu apa yang boleh dinilai.
        $terpasang = AssessmentCategory::where('competition_series_id', $series->id)->exists();
        if ($terpasang) {
            $this->gagal(
                'Tidak bisa menghapus: rubrik seri ini masih terpasang di Format Nilai. Lepas dulu serinya di sana.',
                route('eventner.format-nilai.builder'),
                'Buka Format Penilaian'
            );
            return;
        }

        $series->delete();
        $this->toast('Seri dihapus. Pesertanya tetap ada, hanya kehilangan lembar nilainya sampai seri baru dipilih.');

        unset($this->seriesByCategory, $this->seriesRubrics);
    }

    public function resetSeriesForm()
    {
        $this->reset(['seriesName', 'seriesSortOrder', 'editingSeriesId']);
    }

    // ── Gelombang harga ────────────────────────────────────────────────

    public function saveWave()
    {
        $category = $this->category;

        $this->validate([
            'waveName' => [
                'required', 'string', 'max:255',
                // Nama gelombang hanya perlu unik per tingkat — dua tingkat
                // boleh sama-sama punya "Gelombang 1" dengan harga berbeda.
                Rule::unique('competition_fee_waves', 'name')
                    ->where('competition_category_id', $category->id)
                    ->where('eventner_id', $this->eventnerId)
                    ->ignore($this->editingWaveId),
            ],
            'waveStartsOn' => 'required|date',
            'waveEndsOn' => 'required|date|after_or_equal:waveStartsOn',
            'waveAmount' => 'required|numeric|min:0',
        ], [], [
            'waveName' => 'Nama Gelombang',
            'waveStartsOn' => 'Tanggal Mulai',
            'waveEndsOn' => 'Tanggal Selesai',
            'waveAmount' => 'Harga',
        ]);

        // Tumpang tindih dicegah di sini, bukan ditengahi dengan "yang terbaru
        // menang": dua gelombang yang beririsan berarti harga yang ditagih
        // bergantung urutan baris, dan panitia tidak punya cara melihatnya.
        $alasan = $category->alasanGelombangBentrok(
            $this->waveStartsOn,
            $this->waveEndsOn,
            $this->editingWaveId
        );

        if ($alasan) {
            $this->gagal($alasan);
            return;
        }

        $data = [
            'name' => strip_tags($this->waveName),
            'starts_on' => $this->waveStartsOn,
            'ends_on' => $this->waveEndsOn,
            'amount' => $this->waveAmount,
        ];

        if ($this->editingWaveId) {
            $this->findOwnWave($this->editingWaveId)->update($data);
            $this->toast('Gelombang berhasil diperbarui.');
        } else {
            CompetitionFeeWave::create(array_merge($data, [
                'eventner_id' => $this->eventnerId,
                'competition_category_id' => $category->id,
            ]));
            $this->toast('Gelombang baru berhasil ditambahkan.');
        }

        $this->resetWaveForm();
        unset($this->feeWavesByCategory, $this->category);
    }

    public function editWave($id)
    {
        $wave = $this->findOwnWave($id);
        $this->editingWaveId = $wave->id;
        $this->waveName = $wave->name;
        $this->waveStartsOn = $wave->starts_on->toDateString();
        $this->waveEndsOn = $wave->ends_on->toDateString();
        $this->waveAmount = (string) (float) $wave->amount;
    }

    public function deleteWave($id)
    {
        $wave = $this->findOwnWave($id);

        // Pendaftar yang sudah masuk TIDAK tersentuh: biaya yang mereka bayar
        // tersimpan di registrations.total_fee saat booking, dan tidak ada
        // satu pun tempat yang membaca ulang harga kategori setelah itu.
        // Menghapus gelombang hanya mengubah harga bagi yang mendaftar sesudahnya.
        $wave->delete();
        $this->toast('Gelombang dihapus. Tagihan pendaftar yang sudah masuk tidak ikut berubah.');

        unset($this->feeWavesByCategory, $this->category);
    }

    public function resetWaveForm()
    {
        $this->reset(['waveName', 'waveStartsOn', 'waveEndsOn', 'waveAmount', 'editingWaveId']);
    }

    // ── Babak ──────────────────────────────────────────────────────────

    public function saveRound()
    {
        $category = $this->category;

        $this->validate([
            'roundName' => [
                'required', 'string', 'max:255',
                Rule::unique('competition_rounds', 'name')
                    ->where('competition_category_id', $category->id)
                    ->where('eventner_id', $this->eventnerId)
                    ->ignore($this->editingRoundId),
            ],
            'roundType' => ['required', Rule::in([CompetitionRound::TYPE_PRELIMINARY, CompetitionRound::TYPE_FINAL])],
            'roundSortOrder' => 'nullable|integer|min:0',
        ], [], ['roundName' => 'Nama Babak', 'roundType' => 'Jenis Babak']);

        $data = [
            'name' => strip_tags($this->roundName),
            'type' => $this->roundType,
            'sort_order' => $this->roundSortOrder !== '' ? (int) $this->roundSortOrder : 0,
        ];

        if ($this->editingRoundId) {
            $this->findOwnRound($this->editingRoundId)->update($data);
            $this->toast('Babak berhasil diperbarui.');
        } else {
            CompetitionRound::create(array_merge($data, [
                'eventner_id' => $this->eventnerId,
                'competition_category_id' => $category->id,
            ]));
            $this->toast('Babak baru berhasil ditambahkan.');
        }

        $this->resetRoundForm();
        unset($this->roundsByCategory, $this->roundRubricNames, $this->assignmentRows);
    }

    public function editRound($id)
    {
        $round = $this->findOwnRound($id);
        $this->editingRoundId = $round->id;
        $this->roundName = $round->name;
        $this->roundType = $round->type;
        $this->roundSortOrder = $round->sort_order;
    }

    public function deleteRound($id)
    {
        $round = $this->findOwnRound($id);

        $punyaNilai = AssessmentCategory::where('competition_round_id', $round->id)->exists();
        if ($punyaNilai) {
            $this->gagal(
                'Tidak bisa menghapus: rubrik babak ini masih terpasang di Format Nilai. Lepas dulu babaknya di sana.',
                route('eventner.format-nilai.builder'),
                'Buka Format Penilaian'
            );
            return;
        }

        $round->delete();
        $this->toast('Babak dihapus. Nilai yang sudah tersimpan tidak ikut terhapus.');

        unset($this->roundsByCategory, $this->roundRubricNames, $this->assignmentRows);
    }

    public function resetRoundForm()
    {
        $this->reset(['roundName', 'roundType', 'roundSortOrder', 'editingRoundId', 'qualifySelection']);
        $this->roundType = CompetitionRound::TYPE_PRELIMINARY;
    }

    // ── Loloskan Top-N ke babak final ──────────────────────────────────

    /**
     * Pratinjau N terbaik tiap grup dari nilai babak penyisihan.
     *
     * Nilai yang TAMPIL dan URUTANNYA memakai nilai akhir (total minus
     * pengurangan yang berlaku di tingkat & babak penyisihan), sama seperti
     * papan skor dan Rekap Nilai. Peringkat dihitung ulang di sini per grup
     * dari nilai akhir itu — nilai sama berperingkat sama.
     */
    #[Computed]
    public function qualifyPreview()
    {
        if (!$this->qualifyRoundId) {
            return ['round' => null, 'groups' => [], 'sudahFinalis' => []];
        }

        $round = CompetitionRound::where('eventner_id', $this->eventnerId)->find($this->qualifyRoundId);
        if (!$round) {
            return ['round' => null, 'groups' => [], 'sudahFinalis' => []];
        }

        $eventner = Auth::user()->eventner;
        $calculator = app(ChampionCalculator::class);

        // Bobot rubrik babak penyisihan tingkat ini (kriteria babak penyisihan
        // + kriteria tanpa babak), dipakai apa adanya sebagai peta bobot.
        $weights = $this->roundCriteriaWeights($eventner->id, $round->competition_category_id, CompetitionRound::TYPE_PRELIMINARY);

        // Saringan pengurangan yang sama dengan kalkulator: berlaku di tingkat
        // pesertanya sendiri, dan di babak penyisihan ini saja — bukan babak
        // final yang sedang dibuka panelnya. Sanksi fase final tidak boleh
        // memotong usulan lolos, sebagaimana tidak memotong peringkat
        // penyisihan.
        $deductionLevelMap = DeductionCategory::levelMapOfCriteria($eventner->id);
        $deductionRoundMap = DeductionCategory::roundMapOfCriteria($eventner->id);
        $babakPenyisihanIds = CompetitionRound::where('eventner_id', $eventner->id)
            ->where('competition_category_id', $round->competition_category_id)
            ->where('type', CompetitionRound::TYPE_PRELIMINARY)
            ->pluck('id')
            ->all();

        $allDeductions = ScoreDeduction::where('eventner_id', $eventner->id)
            ->get()
            ->groupBy('registration_id');

        $kurangi = function ($rows) use ($allDeductions, $deductionLevelMap, $deductionRoundMap, $babakPenyisihanIds) {
            foreach ($rows as &$row) {
                $participant = $row['registration'];
                $deductions = DeductionCategory::applicableToLevel(
                    $allDeductions->get($participant->id, collect()),
                    $participant->competition_category_id,
                    $deductionLevelMap
                );
                // Tanpa babak = berlaku di semua babak; dengan babak = hanya
                // bila babaknya termasuk penyisihan tingkat ini.
                $deductions = $deductions->reject(fn ($d) => ($deductionRoundMap[$d->deduction_criteria_id] ?? null) !== null
                    && ! in_array((int) $deductionRoundMap[$d->deduction_criteria_id], $babakPenyisihanIds, true));
                $row['final_total'] = $row['total'] - $deductions->sum(fn ($d) => $d->magnitude);
            }
            unset($row);

            // Urut & peringkatkan ulang dari nilai akhir: peserta bersanksi
            // benar-benar turun di daftar, bukan hanya angkanya dipotong.
            usort($rows, fn ($a, $b) => $b['final_total'] <=> $a['final_total']);

            $rank = 0;
            $sebelumnya = null;
            foreach ($rows as $index => &$row) {
                if ($sebelumnya === null || $row['final_total'] !== $sebelumnya) {
                    $rank = $index + 1;
                }
                $sebelumnya = $row['final_total'];
                $row['rank'] = $rank;
            }
            unset($row);

            return $rows;
        };

        $groups = [];
        $groupModels = CompetitionGroup::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $round->competition_category_id)
            ->orderBy('sort_order')->orderBy('id')
            ->get();

        foreach ($groupModels as $group) {
            $rows = $calculator->rankOrdered(
                $eventner,
                $weights['scoring'],
                $weights['tiebreak'],
                $round->competition_category_id,
                $group->id,
                null,
                $round->id,
            );

            $groups[] = ['group' => $group, 'rows' => $kurangi($rows)];
        }

        // Peserta yang belum bergrup: satu pool umum, supaya panitia juga bisa
        // meloloskan dari sana (mis. tingkat tanpa grup sama sekali).
        $rows = $calculator->rankOrdered(
            $eventner,
            $weights['scoring'],
            $weights['tiebreak'],
            $round->competition_category_id,
            null,
            null,
            $round->id,
        );
        $rows = array_values(array_filter($rows, fn ($r) => !$r['registration']->competition_group_id));
        if ($rows) {
            $groups[] = ['group' => null, 'rows' => $kurangi($rows)];
        }

        $sudahFinalis = CompetitionRoundRegistration::where('competition_round_id', $round->id)
            ->pluck('registration_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return ['round' => $round, 'groups' => $groups, 'sudahFinalis' => $sudahFinalis];
    }

    /**
     * Peta bobot kriteria untuk satu babak, plus kriteria tanpa babak
     * (perilaku lama).
     *
     * Pemecah nilai sama sengaja tidak diisi: kategori juara punya daftar
     * tiebreak sendiri, sedangkan tombol Loloskan Top-N hanya butuh urutan yang
     * masuk akal sebagai USULAN — panitia masih mencentang ulang sebelum
     * menyimpan. Urutan akhir saat nilainya sama jatuh ke urutan undian lewat
     * kunci terakhir.
     *
     * @return array{scoring: array, tiebreak: array}
     */
    private function roundCriteriaWeights(int $eventnerId, int $categoryId, string $roundType): array
    {
        $criteria = \App\Models\AssessmentCriteria::whereHas(
            'subCategory.category',
            function ($q) use ($eventnerId, $categoryId, $roundType) {
                $q->where('eventner_id', $eventnerId)
                    // forLevel, bukan forEntry: peta bobot ini dipakai untuk
                    // memeringkat SEMUA grup sekaligus, jadi rubrik tiap grup
                    // harus ikut. forEntry akan membuang rubrik grup lain dan
                    // peringkat antar grup jadi tak sebanding.
                    ->forLevel($categoryId)
                    ->where(function ($sq) use ($roundType) {
                        $sq->whereHas('competitionRound', fn ($r) => $r->where('type', $roundType))
                            ->orWhereNull('competition_round_id');
                    });
            }
        )->get(['id', 'weight']);

        $map = $criteria->pluck('weight', 'id')->map(fn ($w) => $w ?? 1)->all();

        return ['scoring' => $map, 'tiebreak' => []];
    }

    public function openQualifyPanel($roundId)
    {
        $round = $this->findOwnRound($roundId);
        $this->qualifyRoundId = $round->id;

        $this->recomputeQualifySelection();
    }

    public function closeQualifyPanel()
    {
        $this->qualifyRoundId = null;
        $this->qualifySelection = [];
    }

    public function recomputeQualifySelection()
    {
        $preview = $this->qualifyPreview;
        $topN = max(1, (int) $this->qualifyTopN);
        $selection = [];

        foreach ($preview['groups'] as $bucket) {
            foreach ($bucket['rows'] as $row) {
                $selection[(string) $row['registration']->id] = $row['rank'] <= $topN;
            }
        }

        $this->qualifySelection = $selection;
    }

    /** Hitung ulang usulan Top-N dari angka di kotak N (tombol Terapkan). */
    public function terapkanTopN()
    {
        $this->recomputeQualifySelection();
    }

    public function updatedQualifyTopN()
    {
        $this->recomputeQualifySelection();
    }

    public function toggleQualifySelection($registrationId)
    {
        $key = (string) $registrationId;
        $this->qualifySelection[$key] = !($this->qualifySelection[$key] ?? false);
    }

    public function toggleQualifyGroup($groupId = null)
    {
        $preview = $this->qualifyPreview;
        $key = $groupId === null || $groupId === '' ? null : (int) $groupId;

        foreach ($preview['groups'] as $bucket) {
            if (($bucket['group']?->id) !== $key) {
                continue;
            }

            $ids = array_map(fn ($r) => (string) $r['registration']->id, $bucket['rows']);
            $semuaTercentang = collect($ids)->every(fn ($id) => $this->qualifySelection[$id] ?? false);

            foreach ($ids as $id) {
                $this->qualifySelection[$id] = !$semuaTercentang;
            }
        }
    }

    /**
     * Tulis daftar finalis. Idempoten lewat UNIQUE (round, registration):
     * menekan dua kali tidak menggandakan, hanya memperbarui seed/total.
     */
    public function saveQualify()
    {
        $round = $this->findOwnRound($this->qualifyRoundId);
        $preview = $this->qualifyPreview;

        // Peta peringkat & total per registrasi dari pratinjau (angka penentu).
        $rowsByRegistration = [];
        foreach ($preview['groups'] as $bucket) {
            foreach ($bucket['rows'] as $row) {
                $rowsByRegistration[(int) $row['registration']->id] = $row;
            }
        }

        $dipilih = collect($this->qualifySelection)->filter(fn ($v) => (bool) $v)->keys()->map(fn ($id) => (int) $id);

        // Buang yang tidak sah: id dari DOM wajib peserta eventner ini dan
        // benar-benar ada di pratinjau babak ini.
        $dipilih = $dipilih->filter(fn ($id) => isset($rowsByRegistration[$id]));

        $ditambah = 0;
        $diperbarui = 0;

        foreach ($dipilih as $registrationId) {
            $row = $rowsByRegistration[$registrationId];
            $registration = $row['registration'];

            $existing = CompetitionRoundRegistration::where('competition_round_id', $round->id)
                ->where('registration_id', $registrationId)
                ->first();

            if ($existing) {
                $existing->update([
                    'competition_group_id' => $registration->competition_group_id,
                    'seed' => $row['rank'],
                    // Yang disimpan nilai akhir (sudah dipotong pengurangan),
                    // sama dengan angka yang ditampilkan di pratinjau.
                    'preliminary_total' => $row['final_total'] ?? $row['total'],
                ]);
                $diperbarui++;
            } else {
                CompetitionRoundRegistration::create([
                    'eventner_id' => $this->eventnerId,
                    'competition_round_id' => $round->id,
                    'registration_id' => $registrationId,
                    'competition_group_id' => $registration->competition_group_id,
                    'seed' => $row['rank'],
                    'preliminary_total' => $row['final_total'] ?? $row['total'],
                ]);
                $ditambah++;
            }
        }

        // Yang tidak dicentang dikeluarkan dari babak ini.
        $dihapus = CompetitionRoundRegistration::where('competition_round_id', $round->id)
            ->when($dipilih->isNotEmpty(), fn ($q) => $q->whereNotIn('registration_id', $dipilih->all()))
            ->delete();

        $this->toast("Daftar finalis diperbarui: {$ditambah} peserta diloloskan, {$diperbarui} diperbarui, {$dihapus} dikeluarkan.");
        $this->qualifyRoundId = null;
        $this->qualifySelection = [];
    }

    public function render()
    {
        abort_unless($this->category, 404, 'Tingkat lomba tidak ditemukan.');

        $category = $this->category->load(['parent', 'venue', 'judges', 'children']);

        return view('livewire.eventner.competition-category.show', [
            'category' => $category,
            'pesertaCount' => $category->registrations()->count(),
        ]);
    }
}
