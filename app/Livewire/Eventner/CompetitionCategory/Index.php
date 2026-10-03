<?php

namespace App\Livewire\Eventner\CompetitionCategory;

use Livewire\Component;
use App\Models\AssessmentCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\CompetitionRoundRegistration;
use App\Models\CompetitionSeries;
use App\Models\EventnerVenue;
use App\Models\Judge;
use App\Livewire\Concerns\MelaporKePengguna;
use App\Models\Registration;
use App\Services\ChampionCalculator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

#[Layout('layouts.admin')]
class Index extends Component
{
    use MelaporKePengguna;

    public $name = '';
    public $parentId = null;
    public $tanggal_pelaksanaan = '';
    public $kuota = '';
    public $max_registrations_per_school = 1;
    public $registration_fee = '';
    public $venueId = null;

    public $isEditMode = false;
    public $editingId = null;

    public $expandedParents = [];

    // ── Grup, Babak & Seri ─────────────────────────────────────────────
    /** Tingkat lomba yang modal grup/babak/serinya sedang dibuka. */
    public $groupPanelCategoryId = null;
    public $roundPanelCategoryId = null;
    public $seriesPanelCategoryId = null;

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

    /** Babak final yang sedang dibuka panel "Loloskan Top-N". */
    public $qualifyRoundId = null;
    public $qualifyTopN = 3;
    /** [registration_id => bool] centang dari pratinjau. */
    public $qualifySelection = [];

    protected $eventnerId;

    protected function getListeners()
    {
        return [
            'updateParentSort' => 'updateParentSort',
            'updateChildSort' => 'updateChildSort',
        ];
    }

    public function boot()
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403);
        }
        $this->eventnerId = $eventner->id;
    }

    public function updateSortOrder($orderedIds)
    {
        foreach ($orderedIds as $index => $id) {
            CompetitionCategory::where('eventner_id', $this->eventnerId)
                ->where('id', $id)
                ->update(['sort_order' => $index]);
        }
    }

    public function updateParentSort($orderedIds)
    {
        $this->updateSortOrder($orderedIds);
    }

    public function updateChildSort($orderedIds)
    {
        $this->updateSortOrder($orderedIds);
    }

    #[Computed]
    public function parentCategories()
    {
        return CompetitionCategory::whereNull('parent_id')
            ->where('eventner_id', $this->eventnerId)
            ->with(['children' => fn($q) => $q->with('judges', 'registrations', 'venue')->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function orphanChildren()
    {
        return CompetitionCategory::whereNotNull('parent_id')
            ->where('eventner_id', $this->eventnerId)
            ->whereDoesntHave('parent', fn($q) => $q->where('eventner_id', $this->eventnerId))
            ->with('judges', 'registrations', 'venue')
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function allParents()
    {
        return CompetitionCategory::whereNull('parent_id')
            ->where('eventner_id', $this->eventnerId)
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function availableJudges()
    {
        return Judge::where('eventner_id', $this->eventnerId)->get();
    }

    #[Computed]
    public function availableVenues()
    {
        return EventnerVenue::where('eventner_id', $this->eventnerId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Semua grup & babak event ini, dikelompokkan per tingkat lomba, dengan
     * jumlah peserta. Satu query per tabel — bukan per kartu tingkat.
     */
    #[Computed]
    public function groupsByCategory()
    {
        return CompetitionGroup::where('eventner_id', $this->eventnerId)
            ->withCount('registrations')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('competition_category_id');
    }

    #[Computed]
    public function roundsByCategory()
    {
        return CompetitionRound::where('eventner_id', $this->eventnerId)
            ->withCount('roundRegistrations')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('competition_category_id');
    }

    #[Computed]
    public function seriesByCategory()
    {
        return CompetitionSeries::where('eventner_id', $this->eventnerId)
            ->withCount('registrations')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('competition_category_id');
    }

    /** Tingkat lomba yang modalnya sedang terbuka (di-scope tenant). */
    #[Computed]
    public function panelCategory()
    {
        $id = $this->groupPanelCategoryId ?: $this->seriesPanelCategoryId ?: $this->roundPanelCategoryId;

        return $id
            ? CompetitionCategory::where('eventner_id', $this->eventnerId)->find($id)
            : null;
    }

    /**
     * ID juri per baris penugasan, untuk centang di modal Kelola Grup.
     *
     * Kuncinya `group:{id}` untuk grup, dan nama scope-nya sendiri
     * (`ungrouped`/`final`/`level`) untuk tiga baris tanpa grup. Dibaca dari
     * competition_group_judge — sumber yang sama dengan yang dipakai tablet
     * juri, jadi modal ini tak bisa lagi menjanjikan regu yang berbeda dari
     * yang ditemukan juri di daftarnya.
     *
     * WAJIB disaring ke TINGKAT yang sedang dibuka. Tiga scope tanpa grup
     * memakai kunci yang sama di seluruh event, jadi tanpa saringan ini centang
     * tingkat lain menyalakan centang tingkat ini: modal "SD / MI - U12"
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
        $levelId = $this->panelCategory?->id;

        if (! $levelId) {
            return collect();
        }

        return DB::table('competition_group_judge as cgj')
            ->join('judges as j', 'j.id', '=', 'cgj.judge_id')
            ->where('j.eventner_id', $this->eventnerId)
            ->where('cgj.competition_category_id', $levelId)
            ->orderBy('j.name')
            ->get(['cgj.competition_group_id', 'cgj.scope', 'cgj.judge_id'])
            ->groupBy(fn ($b) => $b->scope === CompetitionGroup::SCOPE_GROUP
                ? 'group:' . $b->competition_group_id
                : $b->scope)
            ->map(fn ($baris) => $baris->pluck('judge_id')->map(fn ($id) => (int) $id)->unique()->values());
    }

    /**
     * Baris penugasan yang relevan untuk satu tingkat, berurutan seperti di
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
        $category = $this->panelCategory;

        if (! $category) {
            return [];
        }

        // Grup & babak menempel pada tingkat yang tombol "Atur"nya ditekan —
        // kategori 34 "Tingkat Kelas 9" yang beranak di bawah "LOBB" punya
        // grupnya SENDIRI, bukan grup induknya. Barisnya harus dibaca dari
        // kategori yang sedang dibuka, sama seperti kartu tingkat di daftar
        // (yang juga membaca groupsByCategory->get($child->id)).
        $indukId = $category->id;

        // Peserta dihitung sampai ke anak-anaknya: satu tingkat bisa dibelah
        // jadi sub-kategori, dan semuanya masuk pool grup yang sama.
        $levelIds = collect([$indukId])
            ->merge($category->children->pluck('id'))
            ->all();

        $panelGroups = $this->groupsByCategory->get($indukId, collect());

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
            $peserta = $this->pesertaTingkat($levelIds);

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

        $tanpaGrup = $this->pesertaTingkat($levelIds)
            - Registration::whereIn('competition_category_id', $levelIds)
                ->whereNotNull('competition_group_id')
                ->count();

        if ($tanpaGrup > 0) {
            $baris[] = [
                'scope' => CompetitionGroup::SCOPE_UNGROUPED,
                'group_id' => null,
                'label' => CompetitionGroup::SCOPE_LABELS[CompetitionGroup::SCOPE_UNGROUPED],
                'key' => CompetitionGroup::SCOPE_UNGROUPED,
                'count' => $tanpaGrup,
            ];
        }

        foreach ($this->roundsByCategory->get($indukId, collect()) as $round) {
            if (! $round->isFinal()) {
                continue;
            }

            $baris[] = [
                'scope' => CompetitionGroup::SCOPE_FINAL,
                'group_id' => null,
                'label' => CompetitionGroup::SCOPE_LABELS[CompetitionGroup::SCOPE_FINAL],
                'key' => CompetitionGroup::SCOPE_FINAL,
                'count' => CompetitionRoundRegistration::where('competition_round_id', $round->id)->count(),
            ];

            break; // Satu tingkat hanya punya satu babak final.
        }

        return $baris;
    }

    /**
     * Hitung seluruh peserta tingkat (dan anak-anaknya), apa pun kondisinya.
     *
     * Dipakai baris `level` — tingkat tanpa grup sama sekali. Pesertanya justru
     * yang membuat baris itu perlu ada: tanpa itu panel juri tingkat lama
     * kosong begitu layar centang rubrik dihapus.
     */
    private function pesertaTingkat(array $levelIds): int
    {
        return Registration::whereIn('competition_category_id', $levelIds)->count();
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
        $category = $this->panelCategory;

        if (! $category) {
            return;
        }

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

    /** Nama rubrik per babak, untuk kolom "Rubrik" di modal Kelola Babak. */
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
     * Nama rubrik per seri, untuk kolom kartu di modal Kelola Seri.
     *
     * Hanya rubriknya. Juri tak lagi diturunkan dari seri: penugasan juri ada
     * di modal Kelola Grup, dan seri tinggal menentukan lembar nilai mana yang
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

    private function findOwnCategory($id): CompetitionCategory
    {
        return CompetitionCategory::where('eventner_id', $this->eventnerId)->findOrFail($id);
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

    // ── Atur Babak & Grup ──────────────────────────────────────────────

    public function saveGroup()
    {
        $category = $this->findOwnCategory($this->groupPanelCategoryId);

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
        $this->dispatch('$refresh');
    }

    public function editGroup($id)
    {
        $group = $this->findOwnGroup($id);
        $this->groupPanelCategoryId = $group->competition_category_id;
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
    }

    public function resetGroupForm()
    {
        $this->reset(['groupName', 'groupSortOrder', 'editingGroupId']);
    }

    // ── Kelola Seri ────────────────────────────────────────────────────

    /**
     * Seri menempel pada TINGKAT, sama seperti grup dan babak. Bedanya, seri
     * yang menentukan lembar nilai & cakupan juri, sedangkan grup menentukan
     * tabel peringkat dan nomor undian.
     */
    public function saveSeries()
    {
        $category = $this->findOwnCategory($this->seriesPanelCategoryId);

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
        $this->dispatch('$refresh');
    }

    public function editSeries($id)
    {
        $series = $this->findOwnSeries($id);
        $this->seriesPanelCategoryId = $series->competition_category_id;
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
    }

    public function resetSeriesForm()
    {
        $this->reset(['seriesName', 'seriesSortOrder', 'editingSeriesId']);
    }

    // ── Kelola Babak ───────────────────────────────────────────────────

    public function openRoundPanel($categoryId)
    {
        $this->findOwnCategory($categoryId);

        $this->roundPanelCategoryId = $categoryId;
        // Grup dan seri dibuka bersama babak: ketiganya diatur pada satu layar,
        // dan panel grup/seri butuh tingkat yang sama supaya "panelCategory"
        // menunjuk kategori yang benar saat semuanya terbuka.
        $this->groupPanelCategoryId = $categoryId;
        $this->seriesPanelCategoryId = $categoryId;
        $this->qualifyRoundId = null;
        $this->resetRoundForm();
    }

    public function closeRoundPanel()
    {
        $this->roundPanelCategoryId = null;
        $this->groupPanelCategoryId = null;
        $this->seriesPanelCategoryId = null;
        $this->qualifyRoundId = null;
        $this->resetRoundForm();
    }

    public function saveRound()
    {
        $category = $this->findOwnCategory($this->roundPanelCategoryId);

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
        $this->dispatch('$refresh');
    }

    public function editRound($id)
    {
        $round = $this->findOwnRound($id);
        $this->roundPanelCategoryId = $round->competition_category_id;
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
     * Angka totalnya datang dari ChampionCalculator::rankOrdered() — sort yang
     * sama dengan penentu juara, supaya "peringkat 1 grup" di sini identik
     * dengan yang diumumkan.
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

            $groups[] = ['group' => $group, 'rows' => $rows];
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
            $groups[] = ['group' => null, 'rows' => $rows];
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
        $this->roundPanelCategoryId = $round->competition_category_id;
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
                    'preliminary_total' => $row['total'],
                ]);
                $diperbarui++;
            } else {
                CompetitionRoundRegistration::create([
                    'eventner_id' => $this->eventnerId,
                    'competition_round_id' => $round->id,
                    'registration_id' => $registrationId,
                    'competition_group_id' => $registration->competition_group_id,
                    'seed' => $row['rank'],
                    'preliminary_total' => $row['total'],
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

    public function toggleExpand($id)
    {
        if (in_array($id, $this->expandedParents)) {
            $this->expandedParents = array_diff($this->expandedParents, [$id]);
        } else {
            $this->expandedParents[] = $id;
        }
    }

    public function save()
    {
        $isParent = is_null($this->parentId);

        $rules = [
            'name' => 'required|string|max:255',
            // parentId datang dari klien: tanpa scope, kategori bisa dipasang di
            // bawah Jenis Lomba tenant lain.
            'parentId' => [
                'nullable',
                Rule::exists('competition_categories', 'id')->where('eventner_id', $this->eventnerId),
            ],
        ];

        if (!$isParent) {
            $rules['kuota'] = 'nullable|integer|min:1';
            $rules['max_registrations_per_school'] = 'required|integer|min:1';
            $rules['tanggal_pelaksanaan'] = 'nullable|date';
            $rules['registration_fee'] = 'nullable|numeric|min:0';
            // exists saja tidak cukup — venueId datang dari klien, jadi harus
            // dipastikan tempatnya memang milik eventner ini (cegah IDOR).
            $rules['venueId'] = [
                'nullable',
                Rule::exists('eventner_venues', 'id')->where('eventner_id', $this->eventnerId),
            ];
        }

        $this->validate($rules);

        $data = [
            'name' => strip_tags($this->name),
            'parent_id' => $this->parentId,
        ];

        if ($isParent) {
            $data['kuota'] = null;
            $data['max_registrations_per_school'] = 1;
            $data['tanggal_pelaksanaan'] = null;
            $data['venue_id'] = null;
        } else {
            $data['kuota'] = $this->kuota ?: null;
            $data['max_registrations_per_school'] = $this->max_registrations_per_school;
            $data['tanggal_pelaksanaan'] = $this->tanggal_pelaksanaan ?: null;
            $data['registration_fee'] = $this->registration_fee !== '' ? $this->registration_fee : null;
            $data['venue_id'] = $this->venueId ?: null;
        }

        if ($this->isEditMode && $this->editingId) {
            $cat = CompetitionCategory::where('eventner_id', $this->eventnerId)->findOrFail($this->editingId);
            $cat->update($data);

            $this->toast('Kategori Lomba berhasil diperbarui.');
        } else {
            $maxOrder = CompetitionCategory::where('eventner_id', $this->eventnerId)
                ->where('parent_id', $this->parentId)
                ->max('sort_order') ?? -1;

            CompetitionCategory::create(array_merge($data, [
                'eventner_id' => $this->eventnerId,
                'sort_order' => $maxOrder + 1,
            ]));

            $this->toast(($isParent ? 'Jenis Lomba' : 'Tingkat Lomba') . ' baru berhasil ditambahkan.');
        }

        $this->resetForm();
        $this->dispatch('$refresh');
    }

    public function edit($id)
    {
        $cat = CompetitionCategory::where('eventner_id', $this->eventnerId)->findOrFail($id);
        $this->isEditMode = true;
        $this->editingId = $cat->id;
        $this->name = $cat->name;
        $this->parentId = $cat->parent_id;
        $this->kuota = $cat->kuota ?? '';
        $this->max_registrations_per_school = $cat->max_registrations_per_school ?? 1;
        $this->tanggal_pelaksanaan = $cat->tanggal_pelaksanaan ?? '';
        $this->registration_fee = $cat->registration_fee ?? '';
        $this->venueId = $cat->venue_id;
    }

    public function delete($id)
    {
        $cat = CompetitionCategory::where('eventner_id', $this->eventnerId)->findOrFail($id);

        if ($cat->isParent() && $cat->children()->exists()) {
            $this->gagal('Tidak bisa menghapus: Jenis Lomba ini masih memiliki ' . $cat->children()->count() . ' Tingkat Lomba. Hapus tingkatnya terlebih dahulu.');
            return;
        }

        // Foreign key-nya cascade: menghapus tingkat lomba ikut menghapus
        // pendaftar, peserta, nilai juri, dan potongannya — tanpa SoftDeletes,
        // jadi tidak bisa dikembalikan. Undian/penilaian yang sudah jalan
        // tidak boleh hilang karena satu klik; peserta harus dipindah atau
        // dihapus dulu.
        if ($cat->registrations()->exists()) {
            $this->gagal(
                'Tidak bisa menghapus: masih ada ' . $cat->registrations()->count() . ' pendaftar di tingkat ini, beserta nilai dan potongannya. Hapus pendaftarnya dulu di halaman Peserta bila memang sudah tidak dipakai.',
                route('eventner.participants.index'),
                'Buka Daftar Peserta'
            );
            return;
        }

        $cat->delete();
        $this->toast('Kategori dihapus.');
    }

    public function resetForm()
    {
        $this->reset(['name', 'parentId', 'kuota', 'max_registrations_per_school', 'tanggal_pelaksanaan', 'registration_fee', 'venueId', 'isEditMode', 'editingId']);
        $this->max_registrations_per_school = 1;
    }

    public function render()
    {
        return view('livewire.eventner.competition-category.index');
    }
}
