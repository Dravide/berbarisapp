<?php

namespace App\Livewire\Eventner\ChampionCategory;

use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\ChampionCategory;
use App\Models\ChampionRankTitle;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\CompetitionRoundRegistration;
use App\Models\Registration;
use App\Models\ScoreDeduction;
use App\Notifications\JuaraDiumumkan;
use App\Services\ChampionCalculator;
use App\Services\FcmService;
use App\Traits\FeatureGatedComponent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Index extends Component
{
    use FeatureGatedComponent;

    protected string $requiredFeature = 'champion_categories';

    public $eventner;

    public $name = '';

    public $description = '';

    public $quantity = 1;

    public $isPublic = false;

    public $selectedSubCategories = [];

    public $selectedTiebreakSubCategories = [];

    /**
     * Kriteria terpilih (level ketiga). Kosong = "seluruh kriteria sub yang
     * dicentang" — lihat ChampionCategory::scoringCriteriaWeights().
     */
    public $selectedCriteria = [];

    public $selectedTiebreakCriteria = [];

    /** Sub-kategori yang daftar kriterianya sedang dibuka di modal. */
    public $expandedSubIds = [];

    public $expandedTiebreakSubIds = [];

    public $editingId = null;

    public $showForm = false;

    public $selectedCompetitionCategoryId;

    /**
     * Lingkup juara yang sedang dilihat. '' = seluruh ruang penilaian
     * (peringkat gabungan, perilaku sebelum fitur grup/babak).
     *
     * Nilainya kunci lingkup, bukan id babak: satu pilihan mewakili sepasang
     * (grup, babak) — "Grup A" dan "Grup B" memakai babak penyisihan yang sama,
     * jadi id babaknya tidak bisa dipakai sebagai nilai pilihan.
     */
    public $selectedScopeId = '';

    public $expandedChampionId = null;

    // Rank title management
    public $rankTitleChampionId = null;

    public $rankTitle = '';

    public $rankStart = '';

    public $rankEnd = '';

    public $showRankTitleForm = false;

    public $editingRankTitleId = null;

    public function mount()
    {
        $this->bootFeatureGate();
        $this->eventner = Auth::user()->eventner;

        if (! $this->eventner) {
            abort(403, 'Anda belum memiliki data Event terdaftar.');
        }

        $first = $this->eventner->competitionCategories->first();
        if ($first) {
            $this->selectedCompetitionCategoryId = $first->id;
        }
    }

    public function selectCompetitionCategory($id)
    {
        if ((string) $this->selectedCompetitionCategoryId !== (string) $id) {
            $this->selectedScopeId = '';
        }

        $this->selectedCompetitionCategoryId = $id;
        $this->expandedChampionId = null;
    }

    /**
     * Ganti lingkup juara.
     *
     * Nilainya kunci lingkup dari daftar, bukan id mentah dari DOM: yang
     * divalidasi adalah apakah kunci itu benar-benar ada di daftar lingkup
     * tingkat terpilih. Id babak/grup dari klien tidak pernah dipercaya
     * langsung — kalau tidak, peringkat bisa dihitung dari rubrik tingkat lain.
     */
    public function selectScope($id)
    {
        $this->selectedScopeId = '';

        if ($id !== '' && $id !== null) {
            $ada = $this->scopeOptions()->firstWhere('key', (string) $id);

            if (! $ada) {
                $this->addError('selectedScopeId', 'Lingkup juara tidak ditemukan pada tingkat lomba ini.');

                return;
            }

            $this->selectedScopeId = (string) $ada['key'];
        }

        $this->resetErrorBag('selectedScopeId');
    }

    public function updatedSelectedScopeId()
    {
        $this->selectScope($this->selectedScopeId);
    }

    public function toggleExpand($id)
    {
        $this->expandedChampionId = $this->expandedChampionId === $id ? null : $id;
    }

    public function create()
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit($id)
    {
        $champion = ChampionCategory::where('eventner_id', $this->eventner->id)->findOrFail($id);
        $this->editingId = $id;
        $this->name = $champion->name;
        $this->description = $champion->description ?? '';
        $this->quantity = $champion->quantity ?? 1;
        $this->isPublic = $champion->is_public ?? false;
        $this->selectedSubCategories = $champion->assessmentSubCategories()->pluck('assessment_sub_categories.id')->map(fn ($id) => (string) $id)->toArray();
        $this->selectedTiebreakSubCategories = $champion->tiebreakSubCategories()->pluck('assessment_sub_categories.id')->map(fn ($id) => (string) $id)->toArray();

        // Kategori juara lama belum punya kriteria terpilih. Isi dari seluruh
        // kriteria sub yang dicentang supaya kotaknya tampil tercentang — bukan
        // kosong, yang terbaca seperti rubriknya hilang.
        $this->selectedCriteria = $this->criteriaIdsFor($champion, 'criterias', $this->selectedSubCategories);
        $this->selectedTiebreakCriteria = $this->criteriaIdsFor($champion, 'tiebreakCriterias', $this->selectedTiebreakSubCategories);

        // Buka tiap sub yang punya kriteria tercentang, supaya centang parsial
        // tidak tersembunyi di balik sub yang terlipat.
        $this->expandedSubIds = $this->subIdsToExpand($this->selectedSubCategories, $this->selectedCriteria);
        $this->expandedTiebreakSubIds = $this->subIdsToExpand($this->selectedTiebreakSubCategories, $this->selectedTiebreakCriteria);

        $this->showForm = true;
    }

    /**
     * Kriteria milik kategori juara untuk satu pivot. Bila pivot-nya belum
     * terisi (data sebelum fitur checklist kriteria), pakai seluruh kriteria
     * dari sub-kategori yang dicentang.
     *
     * @param  array<int, string>  $selectedSubIds
     * @return array<int, string>
     */
    private function criteriaIdsFor(ChampionCategory $champion, string $relation, array $selectedSubIds): array
    {
        $explicit = $champion->{$relation}()->pluck('assessment_criterias.id')->map(fn ($id) => (string) $id)->toArray();
        if (! empty($explicit)) {
            return $explicit;
        }

        $subs = $relation === 'criterias' ? 'assessmentSubCategories' : 'tiebreakSubCategories';
        $champion->loadMissing("{$subs}.criterias");

        $ids = [];
        foreach ($champion->{$subs} as $sub) {
            if (! in_array((string) $sub->id, $selectedSubIds, true)) {
                continue;
            }
            foreach ($sub->criterias as $crit) {
                $ids[] = (string) $crit->id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Sub-kategori yang perlu dibuka: yang punya minimal satu kriteria
     * tercentang tapi tidak semua (centang parsial tersembunyi = terlihat bug).
     *
     * @param  array<int, string>  $selectedSubIds
     * @param  array<int, string>  $selectedCriteriaIds
     * @return array<int, string>
     */
    private function subIdsToExpand(array $selectedSubIds, array $selectedCriteriaIds): array
    {
        if (empty($selectedSubIds) || empty($selectedCriteriaIds)) {
            return [];
        }

        $criteriaBySub = AssessmentCriteria::whereIn('assessment_sub_category_id', $selectedSubIds)
            ->get(['id', 'assessment_sub_category_id'])
            ->groupBy('assessment_sub_category_id');

        $expanded = [];
        foreach ($criteriaBySub as $subId => $crits) {
            $subCritIds = $crits->pluck('id')->map(fn ($id) => (string) $id)->toArray();
            if (count(array_intersect($subCritIds, $selectedCriteriaIds)) !== count($subCritIds)) {
                $expanded[] = (string) $subId;
            }
        }

        return $expanded;
    }

    public function save()
    {
        // Sub-kategori diturunkan dari kriteria terpilih: satu kriteria
        // tercentang sudah cukup membuat sub-nya ikut (pivot sub dipakai
        // isVisibleFor untuk menentukan kategori juara relevan di tingkat mana).
        $this->selectedSubCategories = $this->deriveSubIds($this->selectedCriteria, $this->selectedSubCategories);
        $this->selectedTiebreakSubCategories = $this->deriveSubIds($this->selectedTiebreakCriteria, $this->selectedTiebreakSubCategories);

        $this->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'selectedSubCategories' => 'required|array|min:1',
        ], [
            'name.required' => 'Nama kategori juara wajib diisi.',
            'quantity.required' => 'Jumlah juara wajib diisi.',
            'selectedSubCategories.required' => 'Pilih minimal satu rubrik penilaian.',
            'selectedSubCategories.min' => 'Pilih minimal satu rubrik penilaian.',
        ]);

        $data = [
            'eventner_id' => $this->eventner->id,
            'name' => strip_tags($this->name),
            'description' => strip_tags($this->description) ?: null,
            'quantity' => $this->quantity,
            'is_public' => $this->isPublic,
        ];

        $wasPublic = false;
        if ($this->editingId) {
            $champion = ChampionCategory::where('eventner_id', $this->eventner->id)->findOrFail($this->editingId);
            $wasPublic = (bool) $champion->is_public;
            $champion->update($data);
        } else {
            $champion = ChampionCategory::create($data);
        }

        $champion->assessmentSubCategories()->sync($this->selectedSubCategories);
        $champion->tiebreakSubCategories()->sync($this->selectedTiebreakSubCategories);

        // Simpan pilihan kriteria apa adanya. Sub yang dicentang penuh ikut
        // disimpan sebagai kriteria eksplisit — hasil hitungnya sama, dan
        // daftarnya tetap benar bila kelak ada kriteria baru di sub itu.
        $champion->criterias()->sync($this->selectedCriteria);
        $champion->tiebreakCriterias()->sync($this->selectedTiebreakCriteria);

        // Juara baru diumumkan (transisi non-public → public): kirim notifikasi FCM ke semua pemenang.
        if ($this->isPublic && ! $wasPublic) {
            try {
                $this->notifyChampions($champion);
            } catch (\Throwable $e) {
                Log::warning('FCM champion notification failed', [
                    'champion_category_id' => $champion->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->resetForm();
        session()->flash('success', $this->editingId ? 'Kategori juara berhasil diperbarui.' : 'Kategori juara berhasil ditambahkan.');
    }

    private function notifyChampions(ChampionCategory $champion): void
    {
        // Notifikasi gelar juara per mata lomba — pool gabungan lintas mata
        // lomba membuat pasukan sekolah yang sama saling menyalip dan gelar
        // yang dikirim bisa tertukar antar pasukan.
        $winnersByCategory = \App\Models\Registration::where('eventner_id', $champion->eventner_id)
            ->whereNotNull('competition_category_id')
            ->distinct()
            ->pluck('competition_category_id')
            ->flatMap(fn ($catId) => app(ChampionCalculator::class)->winners($champion, $catId)[2]);

        $winners = $winnersByCategory->all();
        $eventner = $champion->eventner;
        $category = $champion;

        $fcm = app(FcmService::class);

        foreach ($winners as $winner) {
            $registration = $winner['registration'];
            $label = $winner['title'] ?? ('Juara '.$winner['rank']);
            app(JuaraDiumumkan::class)
                ->construct($registration, $label)
                ->send();
        }
    }

    public function delete($id)
    {
        $champion = ChampionCategory::where('eventner_id', $this->eventner->id)->findOrFail($id);
        $champion->assessmentSubCategories()->detach();
        $champion->delete();
        session()->flash('success', 'Kategori juara berhasil dihapus.');
    }

    public function cancel()
    {
        $this->resetForm();
    }

    /**
     * Sub-kategori yang menaungi kriteria terpilih. Dipakai untuk menurunkan
     * pivot sub dari pivot kriteria, supaya keduanya tidak bisa berbeda.
     *
     * @param  array<int, string>  $criteriaIds
     * @return array<int, string>
     */
    private function subIdsOf(array $criteriaIds): array
    {
        if (empty($criteriaIds)) {
            return [];
        }

        return AssessmentCriteria::whereIn('id', $criteriaIds)
            ->pluck('assessment_sub_category_id')
            ->unique()
            ->map(fn ($id) => (string) $id)
            ->values()
            ->toArray();
    }

    /**
     * Sub-kategori yang TIDAK punya kriteria sama sekali. Sub seperti ini tidak
     * bisa diwakili lewat daftar kriteria (centangnya tidak bisa dihapus), jadi
     * dipertahankan apa adanya supaya pilihan lama tidak hilang sendiri.
     *
     * @param  array<int, string>  $subIds
     * @return array<int, string>
     */
    private function criteriaLessSubIds(array $subIds): array
    {
        if (empty($subIds)) {
            return [];
        }

        return AssessmentSubCategory::whereIn('id', $subIds)
            ->whereDoesntHave('criterias')
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->toArray();
    }

    /**
     * @param  array<int, string>  $criteriaIds
     * @param  array<int, string>  $currentSubIds
     * @return array<int, string>
     */
    private function deriveSubIds(array $criteriaIds, array $currentSubIds = []): array
    {
        return array_values(array_unique(array_merge(
            $this->subIdsOf($criteriaIds),
            $this->criteriaLessSubIds($currentSubIds)
        )));
    }

    public function toggleCategory($categoryId)
    {
        // Scoping ke eventner sendiri — cegah baca struktur penilaian tenant lain.
        $category = AssessmentCategory::with('subCategories.criterias')
            ->where('eventner_id', $this->eventner->id)
            ->find($categoryId);
        if (! $category) {
            return;
        }

        $subIds = $category->subCategories->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        if (empty($subIds)) {
            return;
        }

        $this->toggleCriterionGroup($subIds, 'selectedCriteria', 'selectedSubCategories');
    }

    public function toggleTiebreakCategory($categoryId)
    {
        // Scoping ke eventner sendiri — cegah baca struktur penilaian tenant lain.
        $category = AssessmentCategory::with('subCategories.criterias')
            ->where('eventner_id', $this->eventner->id)
            ->find($categoryId);
        if (! $category) {
            return;
        }

        $subIds = $category->subCategories->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        if (empty($subIds)) {
            return;
        }

        $this->toggleCriterionGroup($subIds, 'selectedTiebreakCriteria', 'selectedTiebreakSubCategories');
    }

    /**
     * Centang/hapus satu sub-kategori beserta SELURUH kriterianya.
     */
    public function toggleCriteriaSub($subId)
    {
        // Scoping ke eventner sendiri lewat assessment_categories.
        $sub = AssessmentSubCategory::with('criterias')
            ->whereHas('category', fn ($q) => $q->where('eventner_id', $this->eventner->id))
            ->find($subId);
        if (! $sub) {
            return;
        }

        $this->toggleCriterionGroup([(string) $sub->id], 'selectedCriteria', 'selectedSubCategories');
        $this->toggleSubExpand($subId);
    }

    public function toggleTiebreakSub($subId)
    {
        // Scoping ke eventner sendiri lewat assessment_categories.
        $sub = AssessmentSubCategory::with('criterias')
            ->whereHas('category', fn ($q) => $q->where('eventner_id', $this->eventner->id))
            ->find($subId);
        if (! $sub) {
            return;
        }

        $this->toggleCriterionGroup([(string) $sub->id], 'selectedTiebreakCriteria', 'selectedTiebreakSubCategories');
        $this->toggleTiebreakSubExpand($subId);
    }

    /**
     * Centang/hapus SATU kriteria. Sub-kategori induknya mengikuti.
     */
    public function toggleCriteria($criteriaId)
    {
        $crit = $this->findOwnedCriteria($criteriaId);
        if (! $crit) {
            return;
        }

        $this->toggleCriterionIds([(string) $crit->id], 'selectedCriteria', 'selectedSubCategories');
    }

    public function toggleTiebreakCriteria($criteriaId)
    {
        $crit = $this->findOwnedCriteria($criteriaId);
        if (! $crit) {
            return;
        }

        $this->toggleCriterionIds([(string) $crit->id], 'selectedTiebreakCriteria', 'selectedTiebreakSubCategories');
    }

    /**
     * Batasi ke kriteria milik eventner ini — id dari klien tidak dipercaya.
     */
    private function findOwnedCriteria($criteriaId): ?AssessmentCriteria
    {
        return AssessmentCriteria::whereHas(
            'subCategory.category',
            fn ($q) => $q->where('eventner_id', $this->eventner->id)
        )->find($criteriaId);
    }

    /**
     * Toggle seluruh kriteria milik sekumpulan sub-kategori: kalau semuanya
     * sudah tercentang → hapus, kalau belum → tambahkan yang kurang. Berlaku
     * sama untuk baris kategori maupun baris sub-kategori.
     *
     * @param  array<int, string>  $subIds
     */
    private function toggleCriterionGroup(array $subIds, string $criteriaProp, string $subProp): void
    {
        $criteriaIds = AssessmentCriteria::whereIn('assessment_sub_category_id', $subIds)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->toArray();

        // Sub tanpa kriteria tidak bisa diwakili daftar kriteria (tidak ada
        // kotak untuk dihapus). Centang/hapus sub-nya langsung supaya klik
        // baris kategori tetap terasa.
        if (empty($criteriaIds)) {
            $selected = $this->{$subProp};
            $allSelected = count(array_intersect($subIds, $selected)) === count($subIds);

            $this->{$subProp} = $allSelected
                ? array_values(array_diff($selected, $subIds))
                : array_values(array_unique(array_merge($selected, $subIds)));

            return;
        }

        $this->toggleCriterionIds($criteriaIds, $criteriaProp, $subProp);
    }

    /**
     * @param  array<int, string>  $criteriaIds
     */
    private function toggleCriterionIds(array $criteriaIds, string $criteriaProp, string $subProp): void
    {
        $selected = $this->{$criteriaProp};
        $allSelected = count(array_intersect($criteriaIds, $selected)) === count($criteriaIds);

        $this->{$criteriaProp} = $allSelected
            ? array_values(array_diff($selected, $criteriaIds))
            : array_values(array_unique(array_merge($selected, $criteriaIds)));

        $this->{$subProp} = $this->deriveSubIds($this->{$criteriaProp}, $this->{$subProp});
    }

    public function toggleSubExpand($subId)
    {
        $this->expandedSubIds = $this->toggleInArray($this->expandedSubIds, (string) $subId);
    }

    public function toggleTiebreakSubExpand($subId)
    {
        $this->expandedTiebreakSubIds = $this->toggleInArray($this->expandedTiebreakSubIds, (string) $subId);
    }

    /**
     * @param  array<int, string>  $list
     * @return array<int, string>
     */
    private function toggleInArray(array $list, string $value): array
    {
        return in_array($value, $list, true)
            ? array_values(array_diff($list, [$value]))
            : array_values(array_merge($list, [$value]));
    }

    private function resetForm()
    {
        $this->name = '';
        $this->description = '';
        $this->quantity = 1;
        $this->isPublic = false;
        $this->selectedSubCategories = [];
        $this->selectedTiebreakSubCategories = [];
        $this->selectedCriteria = [];
        $this->selectedTiebreakCriteria = [];
        $this->expandedSubIds = [];
        $this->expandedTiebreakSubIds = [];
        $this->editingId = null;
        $this->showForm = false;
    }

    // ===== Rank Title Management =====

    public function showAddRankTitle($championId)
    {
        $this->rankTitleChampionId = $championId;
        $this->resetRankTitleForm();
        $this->showRankTitleForm = true;
    }

    public function editRankTitle($id)
    {
        $rankTitle = ChampionRankTitle::whereHas('championCategory', fn ($q) => $q->where('eventner_id', $this->eventner->id)
        )->findOrFail($id);

        $this->editingRankTitleId = $id;
        $this->rankTitleChampionId = $rankTitle->champion_category_id;
        $this->rankTitle = $rankTitle->title;
        $this->rankStart = $rankTitle->rank_start;
        $this->rankEnd = $rankTitle->rank_end;
        $this->showRankTitleForm = true;
    }

    public function saveRankTitle()
    {
        $this->validate([
            'rankTitle' => 'required|string|max:255',
            'rankStart' => 'required|integer|min:1',
            'rankEnd' => 'required|integer|min:1|gte:rankStart',
        ], [
            'rankTitle.required' => 'Nama gelar wajib diisi.',
            'rankStart.required' => 'Rank awal wajib diisi.',
            'rankEnd.required' => 'Rank akhir wajib diisi.',
            'rankEnd.gte' => 'Rank akhir harus >= rank awal.',
        ]);

        // Verify ownership
        ChampionCategory::where('eventner_id', $this->eventner->id)
            ->findOrFail($this->rankTitleChampionId);

        $maxSort = ChampionRankTitle::where('champion_category_id', $this->rankTitleChampionId)
            ->max('sort_order') ?? 0;

        if ($this->editingRankTitleId) {
            // Scoping ke eventner sendiri — cegah update rank title tenant lain via payload.
            $rankTitle = ChampionRankTitle::whereHas('championCategory', fn ($q) => $q->where('eventner_id', $this->eventner->id)
            )->findOrFail($this->editingRankTitleId);
            $rankTitle->update([
                'title' => strip_tags($this->rankTitle),
                'rank_start' => $this->rankStart,
                'rank_end' => $this->rankEnd,
            ]);
        } else {
            ChampionRankTitle::create([
                'champion_category_id' => $this->rankTitleChampionId,
                'title' => strip_tags($this->rankTitle),
                'rank_start' => $this->rankStart,
                'rank_end' => $this->rankEnd,
                'sort_order' => $maxSort + 1,
            ]);
        }

        $this->resetRankTitleForm();
        session()->flash('success', 'Gelar juara berhasil disimpan.');
    }

    public function deleteRankTitle($id)
    {
        ChampionRankTitle::whereHas('championCategory', fn ($q) => $q->where('eventner_id', $this->eventner->id)
        )->findOrFail($id)->delete();

        session()->flash('success', 'Gelar juara berhasil dihapus.');
    }

    /**
     * Tutup modal gelar juara. Livewire hanya bisa memanggil method public,
     * jadi view memanggil ini — bukan resetRankTitleForm yang private.
     */
    public function cancelRankTitle()
    {
        $this->resetRankTitleForm();
    }

    private function resetRankTitleForm()
    {
        $this->rankTitle = '';
        $this->rankStart = '';
        $this->rankEnd = '';
        $this->editingRankTitleId = null;
        $this->showRankTitleForm = false;
    }

    public function render()
    {
        $championCategories = ChampionCategory::with([
            'assessmentSubCategories.criterias',
            'assessmentSubCategories.category',
            'rankTitles',
            'tiebreakSubCategories.criterias',
            'criterias',
            'tiebreakCriterias',
        ])
            ->where('eventner_id', $this->eventner->id)
            ->get();

        // Grup & babak ikut dimuat: dipakai untuk melabeli rubrik di checklist,
        // karena satu tingkat kini boleh punya beberapa rubrik bernama sama
        // (PBB Grup A, PBB Grup B, PBB babak final) yang tanpa label terbaca
        // sebagai duplikat.
        $assessmentCategories = AssessmentCategory::with([
                'subCategories.criterias',
                'competitionCategory.parent',
                'competitionGroup',
                'competitionRound',
            ])
            ->where('eventner_id', $this->eventner->id)
            ->get();

        // Babak & grup milik tingkat terpilih. Keduanya dipakai untuk menyusun
        // daftar lingkup juara, dan babak terpilih dipakai menyaring nilai.
        $rounds = $this->selectedCompetitionCategoryId
            ? CompetitionRound::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $this->selectedCompetitionCategoryId)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
            : collect();

        $groups = $this->selectedCompetitionCategoryId
            ? CompetitionGroup::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $this->selectedCompetitionCategoryId)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
            : collect();

        $scopes = $this->buildScopes($groups, $rounds);

        // Lingkup terpilih diambil dari kuncinya, bukan dari id babaknya —
        // "Grup A" dan "Grup B" memakai babak penyisihan yang sama, jadi id
        // babak tidak unik per pilihan.
        $selectedScope = $this->selectedScopeId
            ? $scopes->firstWhere('key', (string) $this->selectedScopeId)
            : null;

        $selectedRound = $selectedScope && $selectedScope['round_id'] !== ''
            ? $rounds->firstWhere('id', (int) $selectedScope['round_id'])
            : null;

        $selectedGroup = $selectedScope && $selectedScope['group_id'] !== ''
            ? $groups->firstWhere('id', (int) $selectedScope['group_id'])
            : null;

        // Kategori juara hanya relevan di tingkat lomba yang rubriknya dipakai.
        // Sembunyikan kategori juara yang seluruh rubriknya milik tingkat lain —
        // nilainya tidak akan pernah terisi untuk peserta tingkat terpilih.
        // Saat babak dipilih, kategori juara yang rubriknya khusus babak lain
        // juga tidak ikut — kalau tidak, "Juara Final" ikut tampil di daftar
        // penyisihan.
        $visibleChampionCategories = $championCategories
            ->filter(fn($champion) => $champion->isVisibleFor($this->selectedCompetitionCategoryId, $selectedGroup?->id, $selectedRound?->id))
            ->values();

        // Filter rubrik mengikuti tingkat lomba yang dipilih di filter halaman.
        // Rubrik global (competition_category_id null) ikut tampil. Bila tingkat
        // terpilih belum punya rubrik, tampilkan semua (fallback agar tidak kosong).
        $filteredAssessmentCategories = $assessmentCategories->filter(function ($cat) use ($scopes) {
            if ($cat->competition_category_id === null) {
                return true; // rubrik global selalu tampil
            }

            if ((string) $cat->competition_category_id !== (string) $this->selectedCompetitionCategoryId) {
                return false;
            }

            // Lingkup juara ikut menyempitkan daftar rubrik. Saat lingkup "Grup A"
            // dipilih, rubrik Grup B dan rubrik babak Final disembunyikan: kategori
            // juara grup yang mencakup rubrik babak lain bukan sekadar tampak
            // berlebihan — nilainya benar-benar ikut terjumlah di peringkat.
            // Menampilkannya mengundang panitia mencentang rubrik yang salah.
            // Rubrik tanpa grup/babak (berlaku umum) selalu ikut.
            $scope = $this->selectedScopeId
                ? $scopes->firstWhere('key', (string) $this->selectedScopeId)
                : null;

            if ($scope) {
                if ($scope['group_id'] !== ''
                    && $cat->competition_group_id !== null
                    && (string) $cat->competition_group_id !== $scope['group_id']) {
                    return false;
                }

                if ($scope['round_id'] !== ''
                    && $cat->competition_round_id !== null
                    && (string) $cat->competition_round_id !== $scope['round_id']) {
                    return false;
                }
            }

            return true;
        });

        // Fallback "tampilkan semua" hanya berlaku tanpa lingkup terpilih —
        // kalau lingkup aktif, daftar kosong berarti tingkat itu memang belum
        // punya rubrik untuk lingkup tsb, dan menampilkan seluruh rubrik
        // justru mengembalikan lubang yang baru saja ditutup.
        if ($filteredAssessmentCategories->isEmpty() && ! $this->selectedScopeId) {
            $filteredAssessmentCategories = $assessmentCategories;
        }

        // Kelompokkan rubrik jadi dua lapis: tingkat lomba, lalu grup+babak.
        //
        // Satu tingkat boleh punya beberapa rubrik bernama sama — "PBB" untuk
        // Grup A, "PBB" untuk Grup B, "PBB FINAL" untuk babak final. Barisnya
        // memang berbeda, jadi bukan duplikat. Dulu semua rubrik satu tingkat
        // diratakan jadi satu daftar, dan itu berbahaya: "PBB" Grup A berdiri
        // sederajat dengan "PBB" babak final, padahal keduanya milik himpunan
        // peserta (dan babak) yang berbeda. Panitia bisa mencentang rubrik
        // final untuk kategori juara penyisihan tanpa sadar.
        //
        // Karena itu pengelompokan mengikuti grup dan babak, bukan nama: tiap
        // bagian berjudul "Grup A — Babak Fase Grup", sehingga isi satu
        // himpunan penilaian terlihat sebagai satu kesatuan. Rubrik tanpa grup
        // maupun babak (berlaku semua peserta) masuk bagian tersendiri tanpa
        // sub-judul, sama seperti tampilan sebelum grup/babak ada.
        $rubrikByLevel = collect();
        foreach ($filteredAssessmentCategories->groupBy('competition_category_id') as $ccId => $cats) {
            $level = null;
            if ($ccId) {
                $level = CompetitionCategory::where('eventner_id', $this->eventner->id)
                    ->with('parent')
                    ->find($ccId);
            }

            // Kunci bagian: grup dan babak sekaligus. Prefiks angka menitipkan
            // urutan supaya sortBy kunci teks ikut urutan grup/babak, bukan
            // abjad nama bagian.
            $grouped = collect();
            foreach ($cats->groupBy(fn ($cat) => $cat->competition_group_id ?? 0) as $groupId => $byGroup) {
                foreach ($byGroup->groupBy(fn ($cat) => $cat->competition_round_id ?? 0) as $roundId => $byRound) {
                    $group = $groupId ? $byGroup->first()->competitionGroup : null;
                    $round = $roundId ? $byRound->first()->competitionRound : null;

                    // Hanya bagian yang benar-benar menyempit yang diberi
                    // judul: tingkat tanpa grup/babak tampil seperti dulu,
                    // satu daftar tanpa sub-judul.
                    $parts = [];
                    if ($group) {
                        $parts[] = $group->name;
                    }
                    if ($round) {
                        $parts[] = 'Babak '.$round->name;
                    }

                    $grouped->push([
                        'key' => sprintf(
                            '%05d-%05d-%s',
                            $group?->sort_order ?? 99999,
                            $round?->sort_order ?? 99999,
                            ($group?->name ?? '').'|'.($round?->name ?? '')
                        ),
                        'section_name' => implode(' — ', $parts),
                        'is_final' => (bool) $round?->isFinal(),
                        'categories' => $byRound->sortBy('sort_order')->values(),
                    ]);
                }
            }

            $rubrikByLevel->push([
                'id' => $ccId,
                'level_name' => $level ? $level->full_name : 'Semua Tingkat (Global)',
                'sections' => $grouped->sortBy('key')->values(),
            ]);
        }
        $rubrikByLevel = $rubrikByLevel->sortBy('level_name')->values();

        // Status centang per baris untuk modal (kategori, sub, kriteria).
        $hooks = [
            'rubrik' => ['selectedCriteria', 'selectedSubCategories'],
            'tiebreak' => ['selectedTiebreakCriteria', 'selectedTiebreakSubCategories'],
        ];
        $checkStates = [];
        foreach ($hooks as $block => [$criteriaProp, $subProp]) {
            $checkStates[$block] = $this->buildCheckStates(
                $rubrikByLevel,
                $this->{$criteriaProp},
                $this->{$subProp}
            );
        }

        $competitionCategories = $this->eventner->competitionCategories()->whereNotNull('parent_id')->withCount('registrations')->get();

        // Calculate rankings for each champion category
        $rankings = collect();
        $rankTitleMap = collect();
        if ($this->selectedCompetitionCategoryId) {
            // Scoping ke eventner sendiri — cegah baca registrasi tenant lain.
            $participants = Registration::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $this->selectedCompetitionCategoryId)
                // Juara grup hanya memeringkat anggota grup itu.
                ->when($selectedGroup, fn ($q) => $q->where('competition_group_id', $selectedGroup->id))
                ->when($selectedRound && $selectedRound->isFinal(), function ($q) use ($selectedRound) {
                    // Babak final hanya menilai finalis. Tanpa batasan ini,
                    // sekolah yang tak pernah dinilai final muncul bernilai 0 di
                    // daftar juara final — terbaca sebagai juara bernilai nol.
                    $q->whereIn('id', CompetitionRoundRegistration::where('eventner_id', $this->eventner->id)
                        ->where('competition_round_id', $selectedRound->id)
                        ->pluck('registration_id'));
                })
                ->orderBy('nama_sekolah')
                ->get();

            $allScores = AssessmentScore::where('eventner_id', $this->eventner->id)
                ->whereIn('registration_id', $participants->pluck('id'))
                ->get()
                ->groupBy('registration_id');

            // Ambil data deduction. Pengurangan ber-scope 'global' hanya
            // berlaku di tingkat lombanya sendiri.
            $allDeductions = ScoreDeduction::where('eventner_id', $this->eventner->id)
                ->get()
                ->groupBy('registration_id');
            $deductionLevelMap = \App\Models\DeductionCategory::levelMapOfCriteria($this->eventner->id);

            // Ambil semua kriteria beserta bobotnya untuk menghitung other_total
            $allCriteriaWeightMap = AssessmentCriteria::whereIn(
                'assessment_sub_category_id',
                AssessmentSubCategory::whereIn(
                    'assessment_category_id',
                    AssessmentCategory::where('eventner_id', $this->eventner->id)->pluck('id')
                )->pluck('id')
            )->pluck('weight', 'id')->toArray();

            foreach ($visibleChampionCategories as $champion) {
                // Babak & grup ikut menyaring kriteria: kategori juara yang
                // mencakup rubrik Penyisihan DAN Final akan menjumlahkan
                // keduanya kalau tidak — padahal juara final ditentukan nilai
                // final saja, dan juara Grup A tidak boleh terisi nilai
                // Grup B.
                $criteriaMap = $champion->scoringCriteriaWeights($selectedRound?->id, $selectedGroup?->id);

                // Kriteria untuk tiebreak
                $tiebreakCriteriaMap = $champion->tiebreakCriteriaWeights($selectedRound?->id, $selectedGroup?->id);

                $participantScores = [];
                foreach ($participants as $participant) {
                    $scores = $allScores->get($participant->id, collect());

                    $total = 0;
                    $tiebreakTotal = 0;
                    $otherTotal = 0;

                    foreach ($scores as $score) {
                        $weight = $criteriaMap[$score->assessment_criteria_id] ?? null;
                        if ($weight !== null) {
                            $scoreVal = (int) $score->score * $weight;
                            $total += $scoreVal;
                        } else {
                            $weightOther = $allCriteriaWeightMap[$score->assessment_criteria_id] ?? 1;
                            $otherTotal += (int) $score->score * $weightOther;
                        }

                        // Tiebreak score (separate calculation)
                        $tbWeight = $tiebreakCriteriaMap[$score->assessment_criteria_id] ?? null;
                        if ($tbWeight !== null) {
                            $tiebreakTotal += (int) $score->score * $tbWeight;
                        }
                    }

                    $deductions = \App\Models\DeductionCategory::applicableToLevel(
                        $allDeductions->get($participant->id, collect()),
                        $participant->competition_category_id,
                        $deductionLevelMap
                    );
                    $totalDeduction = $deductions->sum(fn ($d) => $d->magnitude);

                    $participantScores[] = [
                        'participant' => $participant,
                        'total' => $total,
                        'tiebreak_total' => $tiebreakTotal,
                        'other_total' => $otherTotal,
                        'deduction' => $totalDeduction,
                        'urutan_tampil' => $participant->urutan_tampil ?? 999999,
                    ];
                }

                usort($participantScores, function ($a, $b) {
                    if ($b['total'] !== $a['total']) {
                        return $b['total'] <=> $a['total'];
                    }
                    if ($b['tiebreak_total'] !== $a['tiebreak_total']) {
                        return $b['tiebreak_total'] <=> $a['tiebreak_total'];
                    }
                    if ($b['other_total'] !== $a['other_total']) {
                        return $b['other_total'] <=> $a['other_total'];
                    }
                    if ($a['deduction'] !== $b['deduction']) {
                        return $a['deduction'] <=> $b['deduction'];
                    }

                    return $a['urutan_tampil'] <=> $b['urutan_tampil'];
                });

                // Peserta tanpa nilai (skor 0) bukan juara — dibuang SEBELUM
                // peringkat dihitung, sama seperti ChampionCalculator dan
                // /champions. Dulu halaman ini menampilkan mereka sebagai
                // "Juara N" padahal halaman lain menyebutnya PESERTA.
                $participantScores = array_values(array_filter(
                    $participantScores,
                    fn ($ps) => $ps['total'] > 0
                ));

                // Only take Top N based on quantity
                $participantScores = array_slice($participantScores, 0, $champion->quantity);

                foreach ($participantScores as $index => &$ps) {
                    $rank = $index + 1;
                    $ps['rank'] = $rank;

                    // Gelar + nomor posisi dalam grup, sama dengan /hasil,
                    // /champions, dan PDF rekap.
                    $ps['title'] = $champion->titleForRank($rank);
                }
                unset($ps);

                $rankings[$champion->id] = collect($participantScores);
                $rankTitleMap[$champion->id] = $champion->rankTitles;
            }
        }

        return view('livewire.eventner.champion-category.index', [
            'championCategories' => $visibleChampionCategories,
            'assessmentCategories' => $assessmentCategories,
            'rubrikByLevel' => $rubrikByLevel,
            'checkStates' => $checkStates,
            'competitionCategories' => $competitionCategories,
            'rankings' => $rankings,
            'rankTitleMap' => $rankTitleMap,
            'rounds' => $rounds,
            'scopes' => $scopes,
            'selectedRound' => $selectedRound,
            'selectedGroup' => $selectedGroup,
            'selectedScope' => $selectedScope,
        ])->title('Kategori Juara - '.$this->eventner->nama_event);
    }

    /**
     * Daftar lingkup juara tingkat terpilih: "Grup A", "Grup B", "Final".
     *
     * Sengaja bukan daftar babak. Yang dicari panitia di halaman ini adalah
     * "juara siapa" — juara Grup A, juara Grup B, juara final — bukan "babak
     * mana". Karena itu babak penyisihan tidak berdiri sebagai pilihan selama
     * tingkat itu punya grup: lingkupnya sudah terwakili grup, dan juara grup
     * memang dihitung dari nilai penyisihan ("Seluruh Ruang Penilaian" tetap
     * memberi peringkat penyisihan gabungan). Babak non-final tetap muncul
     * sendiri kalau tingkatnya tanpa grup — untuk data lama yang memakai babak
     * sebagai pemisah rubrik.
     *
     * Nilai tiap pilihan adalah `key` (bukan id babak): Grup A dan Grup B
     * memakai babak penyisihan yang sama, jadi id babak tidak unik per pilihan.
     *
     * @param  \Illuminate\Support\Collection  $groups
     * @param  \Illuminate\Support\Collection  $rounds
     * @return \Illuminate\Support\Collection<int, array>
     */
    private function buildScopes($groups, $rounds): \Illuminate\Support\Collection
    {
        $scopes = collect();

        // Babak default satu grup: babak penyisihan bila ada, kalau tidak babak
        // pertama — juara grup dihitung dari nilai babak itu.
        $preliminary = $rounds->first(fn ($r) => $r->type === CompetitionRound::TYPE_PRELIMINARY)
            ?? $rounds->first();

        foreach ($groups as $group) {
            $scopes->push([
                'key' => 'grup-'.$group->id,
                'label' => $group->name,
                'icon' => 'ti-users-group',
                'group_id' => (string) $group->id,
                'round_id' => $preliminary ? (string) $preliminary->id : '',
            ]);
        }

        foreach ($rounds as $round) {
            if (! $round->isFinal() && $groups->isNotEmpty()) {
                continue;
            }

            $scopes->push([
                'key' => 'babak-'.$round->id,
                'label' => $round->name,
                'icon' => $round->isFinal() ? 'ti-flag-check' : 'ti-flag',
                'group_id' => '',
                'round_id' => (string) $round->id,
            ]);
        }

        return $scopes;
    }

    /**
     * Daftar lingkup untuk validasi input dari DOM. Dipakai selectScope, jadi
     * pemanggilnya tidak perlu memuat grup/babak sendiri.
     *
     * @return \Illuminate\Support\Collection<int, array>
     */
    private function scopeOptions(): \Illuminate\Support\Collection
    {
        if (! $this->selectedCompetitionCategoryId) {
            return collect();
        }

        $groups = CompetitionGroup::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->selectedCompetitionCategoryId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $rounds = CompetitionRound::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->selectedCompetitionCategoryId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->buildScopes($groups, $rounds);
    }

    /**
     * Status centang tiap baris rubrik di modal: kategori, sub-kategori, dan
     * kriteria. Dihitung di sini, bukan inline di blade, supaya blok Rubrik
     * Penilaian dan Tie Break memakai logika yang sama persis.
     *
     * @param  \Illuminate\Support\Collection  $rubrikByLevel
     * @param  array<int, string>  $selectedCriteriaIds
     * @param  array<int, string>  $selectedSubIds
     * @return array<int|string, array>
     */
    private function buildCheckStates($rubrikByLevel, array $selectedCriteriaIds, array $selectedSubIds): array
    {
        $states = [];

        foreach ($rubrikByLevel as $levelGroup) {
            foreach ($levelGroup['sections'] as $section) {
                foreach ($section['categories'] as $cat) {
                    $catSubIds = [];
                    $catCritIds = [];

                    $subStates = [];
                    foreach ($cat->subCategories as $sub) {
                        $subId = (string) $sub->id;
                        $critIds = $sub->criterias->pluck('id')->map(fn ($id) => (string) $id)->toArray();

                        $catSubIds[] = $subId;
                        $catCritIds = array_merge($catCritIds, $critIds);

                        $subStates[$sub->id] = [
                            'criteria_ids' => $critIds,
                            'checked' => in_array($subId, $selectedSubIds, true),
                            'indeterminate' => count(array_intersect($critIds, $selectedCriteriaIds)) > 0
                                && count(array_intersect($critIds, $selectedCriteriaIds)) < count($critIds),
                        ];
                    }

                    $allSubsChecked = count($catSubIds) > 0 && count(array_intersect($catSubIds, $selectedSubIds)) === count($catSubIds);

                    $states[$cat->id] = [
                        'sub_ids' => $catSubIds,
                        'criteria_ids' => $catCritIds,
                        'checked' => $allSubsChecked,
                        'indeterminate' => count(array_intersect($catSubIds, $selectedSubIds)) > 0 && ! $allSubsChecked,
                        'subs' => $subStates,
                    ];
                }
            }
        }

        return $states;
    }
}
