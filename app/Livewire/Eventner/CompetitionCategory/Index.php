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
use App\Services\ChampionCalculator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

#[Layout('layouts.admin')]
class Index extends Component
{
    public $name = '';
    public $parentId = null;
    public $tanggal_pelaksanaan = '';
    public $kuota = '';
    public $max_registrations_per_school = 1;
    public $registration_fee = '';
    public $venueId = null;
    public $selectedJudges = [];

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
     * Nama juri per grup — diturunkan dari rubrik grup (juri terikat rubrik,
     * bukan tingkat lomba). Untuk kartu "Juri:" di modal Kelola Grup.
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection>
     */
    #[Computed]
    public function groupJudgeNames()
    {
        return AssessmentCategory::where('eventner_id', $this->eventnerId)
            ->whereNotNull('competition_group_id')
            ->with('judges')
            ->get()
            ->groupBy('competition_group_id')
            ->map(fn ($cats) => $cats->flatMap(fn ($cat) => $cat->judges->pluck('name'))->unique()->values());
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
     * Nama rubrik & juri per seri, untuk kolom kartu di modal Kelola Seri.
     *
     * Diturunkan dari rubrik yang menempel ke seri — sama seperti
     * groupJudgeNames() untuk grup, dan itu memang pengikat juri yang
     * sebenarnya.
     */
    #[Computed]
    public function seriesRubrics()
    {
        return AssessmentCategory::where('eventner_id', $this->eventnerId)
            ->whereNotNull('competition_series_id')
            ->with('judges')
            ->get()
            ->groupBy('competition_series_id')
            ->map(fn ($cats) => [
                'rubrics' => $cats->pluck('name'),
                'judges' => $cats->flatMap(fn ($cat) => $cat->judges->pluck('name'))->unique()->values(),
            ]);
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
            session()->flash('success', 'Grup berhasil diperbarui.');
        } else {
            CompetitionGroup::create(array_merge($data, [
                'eventner_id' => $this->eventnerId,
                'competition_category_id' => $category->id,
            ]));
            session()->flash('success', 'Grup baru berhasil ditambahkan.');
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

        // FK-nya nullOnDelete: peserta dan rubrik tidak ikut terhapus, hanya
        // lepas dari grup. Tetap dicegah kalau rubriknya sudah dipakai menilai,
        // karena nilai yang tersimpan mengacu kriteria rubrik grup itu.
        $punyaNilai = AssessmentCategory::where('competition_group_id', $group->id)->exists();
        if ($punyaNilai) {
            session()->flash('error', 'Tidak bisa menghapus: rubrik grup ini masih terpasang di Format Nilai. Lepas dulu grupnya di sana.');
            return;
        }

        $group->delete();
        session()->flash('success', 'Grup dihapus. Pesertanya tetap ada, hanya kembali ke peringkat umum.');
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
            session()->flash('success', 'Seri berhasil diperbarui.');
        } else {
            CompetitionSeries::create(array_merge($data, [
                'eventner_id' => $this->eventnerId,
                'competition_category_id' => $category->id,
            ]));
            session()->flash('success', 'Seri baru berhasil ditambahkan.');
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
            session()->flash('error', 'Tidak bisa menghapus: rubrik seri ini masih terpasang di Format Nilai. Lepas dulu serinya di sana.');
            return;
        }

        $series->delete();
        session()->flash('success', 'Seri dihapus. Pesertanya tetap ada, hanya kehilangan lembar nilainya sampai seri baru dipilih.');
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
            session()->flash('success', 'Babak berhasil diperbarui.');
        } else {
            CompetitionRound::create(array_merge($data, [
                'eventner_id' => $this->eventnerId,
                'competition_category_id' => $category->id,
            ]));
            session()->flash('success', 'Babak baru berhasil ditambahkan.');
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
            session()->flash('error', 'Tidak bisa menghapus: rubrik babak ini masih terpasang di Format Nilai. Lepas dulu babaknya di sana.');
            return;
        }

        $round->delete();
        session()->flash('success', 'Babak dihapus. Nilai yang sudah tersimpan tidak ikut terhapus.');
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

        session()->flash('success', "Daftar finalis diperbarui: {$ditambah} peserta diloloskan, {$diperbarui} diperbarui, {$dihapus} dikeluarkan.");
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
            // parentId & selectedJudges datang dari klien: tanpa scope,
            // kategori bisa dipasang di bawah Jenis Lomba tenant lain, dan
            // juri tenant lain bisa ditempelkan ke kategori kita.
            'parentId' => [
                'nullable',
                Rule::exists('competition_categories', 'id')->where('eventner_id', $this->eventnerId),
            ],
            'selectedJudges' => 'array',
            'selectedJudges.*' => [
                Rule::exists('judges', 'id')->where('eventner_id', $this->eventnerId),
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

            if (!$isParent) {
                $cat->judges()->sync($this->selectedJudges);
            } else {
                $cat->judges()->detach();
            }

            session()->flash('success', 'Kategori Lomba berhasil diperbarui.');
        } else {
            $maxOrder = CompetitionCategory::where('eventner_id', $this->eventnerId)
                ->where('parent_id', $this->parentId)
                ->max('sort_order') ?? -1;

            $cat = CompetitionCategory::create(array_merge($data, [
                'eventner_id' => $this->eventnerId,
                'sort_order' => $maxOrder + 1,
            ]));

            if (!$isParent) {
                $cat->judges()->attach($this->selectedJudges);
            }

            session()->flash('success', ($isParent ? 'Jenis Lomba' : 'Tingkat Lomba') . ' baru berhasil ditambahkan.');
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
        $this->selectedJudges = $cat->judges->pluck('id')->toArray();
    }

    public function delete($id)
    {
        $cat = CompetitionCategory::where('eventner_id', $this->eventnerId)->findOrFail($id);

        if ($cat->isParent() && $cat->children()->exists()) {
            session()->flash('error', 'Tidak bisa menghapus: Jenis Lomba ini masih memiliki ' . $cat->children()->count() . ' Tingkat Lomba. Hapus tingkatnya terlebih dahulu.');
            return;
        }

        // Foreign key-nya cascade: menghapus tingkat lomba ikut menghapus
        // pendaftar, peserta, nilai juri, dan potongannya — tanpa SoftDeletes,
        // jadi tidak bisa dikembalikan. Undian/penilaian yang sudah jalan
        // tidak boleh hilang karena satu klik; peserta harus dipindah atau
        // dihapus dulu.
        if ($cat->registrations()->exists()) {
            session()->flash('error', 'Tidak bisa menghapus: masih ada ' . $cat->registrations()->count() . ' pendaftar di tingkat ini, beserta nilai dan potongannya. Hapus pendaftarnya dulu di halaman Peserta bila memang sudah tidak dipakai.');
            return;
        }

        $cat->delete();
        session()->flash('success', 'Kategori dihapus.');
    }

    public function resetForm()
    {
        $this->reset(['name', 'parentId', 'kuota', 'max_registrations_per_school', 'tanggal_pelaksanaan', 'registration_fee', 'venueId', 'selectedJudges', 'isEditMode', 'editingId']);
        $this->max_registrations_per_school = 1;
    }

    public function render()
    {
        return view('livewire.eventner.competition-category.index');
    }
}
