<?php

namespace App\Livewire\Eventner\FormatNilai;

use App\Traits\FeatureGatedComponent;
use Livewire\Component;
use App\Models\AssessmentCategory;
use App\Models\AssessmentSubCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\CompetitionCategory;
use App\Models\DeductionCategory;
use App\Models\DeductionCriteria;
use App\Models\ScoreDeduction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
#[Title('Format Penilaian - BARIS APP')]
class Builder extends Component
{
    use FeatureGatedComponent;

    protected string $requiredFeature = 'format_nilai';

    public $eventnerId;
    public $activeTab = ''; // '' = global/semua tingkat, child_id = specific
    public $errorMessage = '';

    // Copy format (lama)
    public $showCopyModal = false;
    public $copySourceId = null;
    public $copyPreviewData = [];

    // Salin Ke (baru) — dari kategori sumber ke tingkat target
    public $showCopyToModal = false;
    public $copyToSourceCategoryId = null;
    public $copyToTargetCompetitionCategoryId = null;

    // State for inputs
    public $newCategoryName = '';

    // Arrays to hold independent input states per item to avoid interfering with each other
    public $newSubCategoryNames = [];

    public function mount()
    {
        // Get the active eventner ID from the authenticated user
        $this->bootFeatureGate();
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }
        $this->eventnerId = $eventner->id;

        // Auto-select tingkat pertama agar toolbar (Salin dari Tingkat Lain) langsung tampil.
        $first = $this->competitionCategories->first();
        if ($first) {
            $this->activeTab = (string) $first->id;
        }
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

    #[\Livewire\Attributes\Computed]
    public function competitionCategories()
    {
        return CompetitionCategory::where('eventner_id', $this->eventnerId)
            ->where(function ($q) {
                // Child categories (hierarchy)
                $q->whereNotNull('parent_id');
                // OR: parent categories with no children (old flat data, backward compat)
                $q->orWhere(function ($sq) {
                    $sq->whereNull('parent_id')
                       ->whereDoesntHave('children');
                });
            })
            ->with('parent')
            ->orderBy('name')
            ->get();
    }

    #[\Livewire\Attributes\Computed]
    public function categories()
    {
        $query = AssessmentCategory::with(['subCategories.criterias', 'deductionCategories.criterias', 'competitionCategory'])
                ->where('eventner_id', $this->eventnerId)
                ->orderBy('sort_order');

        if ($this->activeTab !== '') {
            $query->where('competition_category_id', $this->activeTab);
        }

        return $query->get();
    }

    public function addCategory()
    {
        $this->validate(['newCategoryName' => 'required|string|max:255']);

        $maxOrder = AssessmentCategory::where('eventner_id', $this->eventnerId)->max('sort_order') ?? 0;

        AssessmentCategory::create([
            'eventner_id' => $this->eventnerId,
            'competition_category_id' => $this->activeTab !== '' ? $this->activeTab : null,
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
            session()->flash('error', 'Tidak bisa menghapus kategori: sudah ada nilai yang masuk. Hapus/lck format sebelum penilaian dimulai.');
            return;
        }

        $deductionCriteriaIds = $category->deductionCategories->flatMap->criterias->pluck('id');
        if ($this->anyDeductionHasScores($deductionCriteriaIds)) {
            session()->flash('error', 'Tidak bisa menghapus kategori: sudah ada nilai pengurangan yang masuk.');
            return;
        }

        $category->delete();
    }

    public function duplicateCategory($id)
    {
        $original = AssessmentCategory::with(['subCategories.criterias', 'judges', 'deductionCategories.criterias'])
            ->where('eventner_id', $this->eventnerId)
            ->findOrFail($id);

        $maxOrder = AssessmentCategory::where('eventner_id', $this->eventnerId)->max('sort_order') ?? 0;

        // Clone the category
        $newCategory = AssessmentCategory::create([
            'eventner_id' => $this->eventnerId,
            'competition_category_id' => $original->competition_category_id,
            'name' => $original->name . ' (Salinan)',
            'sort_order' => $maxOrder + 1,
        ]);

        // Clone pivot juri agar kategori hasil duplikat tetap punya juri yang sama
        if ($original->judges->isNotEmpty()) {
            $newCategory->judges()->sync($original->judges->pluck('id'));
        }

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

        session()->flash('success', 'Kategori berhasil diduplikat.');
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
    public function executeCopyTo($sourceId = null)
    {
        $sourceId = $sourceId ?: $this->copyToSourceCategoryId;
        $targetCompetitionCategoryId = $this->copyToTargetCompetitionCategoryId;

        if (!$sourceId || !$targetCompetitionCategoryId) {
            session()->flash('error', 'Pilih tingkat tujuan terlebih dahulu.');
            return;
        }

        $original = AssessmentCategory::with(['subCategories.criterias', 'judges', 'deductionCategories.criterias'])
            ->where('eventner_id', $this->eventnerId)
            ->findOrFail($sourceId);

        // Validasi target tingkat milik eventner ini
        CompetitionCategory::where('eventner_id', $this->eventnerId)
            ->findOrFail($targetCompetitionCategoryId);

        $maxOrder = AssessmentCategory::where('eventner_id', $this->eventnerId)->max('sort_order') ?? 0;

        $newCategory = AssessmentCategory::create([
            'eventner_id' => $this->eventnerId,
            'competition_category_id' => $targetCompetitionCategoryId,
            'name' => $original->name,
            'sort_order' => $maxOrder + 1,
        ]);

        // Copy by rubrik: hanya sub-kategori + kriteria (bobot, skor). Tanpa juri & kelompok pengurangan.
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
        session()->flash('success', "Rubrik '{$original->name}' berhasil disalin ke {$targetName}.");
    }

    public function addSubCategory($categoryId)
    {
        // Verify the category belongs to this eventner
        AssessmentCategory::where('eventner_id', $this->eventnerId)->findOrFail($categoryId);

        $name = $this->newSubCategoryNames[$categoryId] ?? '';

        if(empty(trim($name))) {
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
            session()->flash('error', 'Tidak bisa menghapus sub-kategori: sudah ada nilai yang masuk.');
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
            session()->flash('error', 'Tidak bisa menghapus kriteria: sudah ada nilai yang masuk.');
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
    public $showCriteriaModal = false;
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
                if (is_array($opt) && !empty($opt['label'])) {
                    $label = $opt['label'];
                    if (!isset($grouped[$label])) {
                        $grouped[$label] = ['label' => $label, 'scores' => []];
                    }
                    $grouped[$label]['scores'][] = $opt['score'];
                } else {
                    $score = is_array($opt) ? ($opt['score'] ?? '') : $opt;
                    if (!isset($grouped[''])) {
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

        $this->showCriteriaModal = true;
    }

    public function closeCriteriaModal()
    {
        $this->reset('showCriteriaModal', 'criteriaModalSubCategoryId', 'criteriaModalTargetId',
            'criteriaModalName', 'criteriaModalWeight', 'labelGroups');
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

            $scoresStr = str_replace([' – ', ' - ', '–', ';'], ',', $scoresRaw);
            $parts = array_map('trim', explode(',', $scoresStr));
            $parts = array_filter($parts, fn($v) => $v !== '');

            if (!empty($label)) $hasLabels = true;

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
                fn($s) => is_array($s) ? $s : ['score' => $s, 'label' => ''],
                $allScores
            ));
        } else {
            $scoreOptions = array_values(array_map(
                fn($s) => is_array($s) ? $s['score'] : $s,
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
            session()->flash('error', 'Tidak bisa menghapus kategori pengurangan: sudah ada nilai pengurangan yang masuk.');
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
        $options = array_filter($options, fn($v) => $v !== '');

        if (empty($options)) {
            return;
        }

        // Ensure all values are numeric (allow negatives)
        foreach ($options as $opt) {
            if (!is_numeric($opt)) {
                session()->flash('error', 'Semua opsi pengurangan harus berupa angka.');
                return;
            }
        }

        if (count($options) > self::MAX_OPTIONS) {
            session()->flash('error', 'Maksimal ' . self::MAX_OPTIONS . ' opsi pengurangan per kriteria.');
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
            session()->flash('error', 'Tidak bisa menghapus kriteria pengurangan: sudah ada nilai pengurangan yang masuk.');
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

        $options = array_filter(array_map('trim', explode(',', $this->editDeductionCriteriaOptions)), fn($v) => $v !== '');

        if (empty($options)) {
            return;
        }

        foreach ($options as $opt) {
            if (!is_numeric($opt)) {
                session()->flash('error', 'Semua opsi pengurangan harus berupa angka.');
                return;
            }
        }

        if (count($options) > self::MAX_OPTIONS) {
            session()->flash('error', 'Maksimal ' . self::MAX_OPTIONS . ' opsi pengurangan per kriteria.');
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
        $categories = AssessmentCategory::where('eventner_id', $this->eventnerId)
            ->orderBy('sort_order')
            ->pluck('id')
            ->toArray();

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
        $this->activeTab = $id !== '' && $id !== null ? (string) $id : '';
    }

    public function previewCopy($sourceId)
    {
        if (!$sourceId) {
            $this->copyPreviewData = [];
            return;
        }

        $sourceCategories = AssessmentCategory::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $sourceId)
            ->with(['subCategories.criterias', 'deductionCategories.criterias'])
            ->get();

        $this->copyPreviewData = $sourceCategories->map(fn($cat) => [
            'name' => $cat->name,
            'sub_count' => $cat->subCategories->count(),
            'criteria_count' => $cat->subCategories->sum(fn($s) => $s->criterias->count()),
            'deduction_count' => $cat->deductionCategories->count(),
            'deduction_criteria_count' => $cat->deductionCategories->sum(fn($d) => $d->criterias->count()),
        ])->toArray();
    }

    public function executeCopy()
    {
        if (!$this->copySourceId) {
            session()->flash('error', 'Pilih tingkat sumber terlebih dahulu.');
            return;
        }

        $sourceCategories = AssessmentCategory::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $this->copySourceId)
            ->with(['subCategories.criterias', 'deductionCategories.criterias'])
            ->get();

        if ($sourceCategories->isEmpty()) {
            session()->flash('error', 'Tingkat sumber tidak memiliki format penilaian.');
            $this->closeCopyModal();
            return;
        }

        $targetId = $this->activeTab !== '' ? $this->activeTab : null;

        foreach ($sourceCategories as $cat) {
            $maxOrder = AssessmentCategory::where('eventner_id', $this->eventnerId)->max('sort_order') ?? 0;

            $newCat = AssessmentCategory::create([
                'eventner_id' => $this->eventnerId,
                'competition_category_id' => $targetId,
                'name' => $cat->name,
                'sort_order' => $maxOrder + 1,
            ]);

            foreach ($cat->subCategories as $subIndex => $sub) {
                $newSub = AssessmentSubCategory::create([
                    'assessment_category_id' => $newCat->id,
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

            foreach ($cat->deductionCategories as $dedIndex => $dedCat) {
                $newDed = DeductionCategory::create([
                    'eventner_id' => $this->eventnerId,
                    'assessment_category_id' => $newCat->id,
                    'name' => $dedCat->name,
                    'sort_order' => $dedIndex + 1,
                ]);

                foreach ($dedCat->criterias as $dedCrit) {
                    DeductionCriteria::create([
                        'deduction_category_id' => $newDed->id,
                        'name' => $dedCrit->name,
                        'deduction_options' => $dedCrit->deduction_options,
                        'sort_order' => $dedCrit->sort_order,
                    ]);
                }
            }
        }

        session()->flash('success', 'Format penilaian berhasil disalin (' . $sourceCategories->count() . ' kategori).');
        $this->closeCopyModal();
    }

    public function openCopyModal()
    {
        $this->showCopyModal = true;
        $this->copySourceId = null;
        $this->copyPreviewData = [];
    }

    public function closeCopyModal()
    {
        $this->showCopyModal = false;
        $this->copySourceId = null;
        $this->copyPreviewData = [];
    }

    // ============================================================
    // RENDER
    // ============================================================

    public function render()
    {
        return view('livewire.eventner.format-nilai.builder');
    }
}
