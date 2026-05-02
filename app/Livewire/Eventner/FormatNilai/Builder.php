<?php

namespace App\Livewire\Eventner\FormatNilai;

use Livewire\Component;
use App\Models\AssessmentCategory;
use App\Models\AssessmentSubCategory;
use App\Models\AssessmentCriteria;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
#[Title('Format Penilaian - BARIS APP')]
class Builder extends Component
{
    public $eventnerId;

    // State for inputs
    public $newCategoryName = '';
    
    // Arrays to hold independent input states per item to avoid interfering with each other
    public $newSubCategoryNames = [];
    
    public $newCriteriaNames = [];
    public $newCriteriaScores = []; // e.g. "50,60,70,80,90,100"
    public $newCriteriaWeights = []; // weight per criteria, default 1

    public function mount()
    {
        // Get the active eventner ID from the authenticated user
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }
        $this->eventnerId = $eventner->id;
    }

    #[\Livewire\Attributes\Computed]
    public function categories()
    {
        return AssessmentCategory::with(['subCategories.criterias'])
                ->where('eventner_id', $this->eventnerId)
                ->orderBy('sort_order')
                ->get();
    }

    public function addCategory()
    {
        $this->validate(['newCategoryName' => 'required|string|max:255']);

        $maxOrder = AssessmentCategory::where('eventner_id', $this->eventnerId)->max('sort_order') ?? 0;

        AssessmentCategory::create([
            'eventner_id' => $this->eventnerId,
            'name' => strip_tags($this->newCategoryName),
            'sort_order' => $maxOrder + 1,
        ]);

        $this->newCategoryName = '';
    }

    public function deleteCategory($id)
    {
        AssessmentCategory::where('eventner_id', $this->eventnerId)->findOrFail($id)->delete();
    }

    public function duplicateCategory($id)
    {
        $original = AssessmentCategory::with(['subCategories.criterias'])
            ->where('eventner_id', $this->eventnerId)
            ->findOrFail($id);

        $maxOrder = AssessmentCategory::where('eventner_id', $this->eventnerId)->max('sort_order') ?? 0;

        // Clone the category
        $newCategory = AssessmentCategory::create([
            'eventner_id' => $this->eventnerId,
            'name' => $original->name . ' (Salinan)',
            'sort_order' => $maxOrder + 1,
        ]);

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

        session()->flash('success', 'Kategori berhasil diduplikat.');
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
        })->findOrFail($id);
        $sub->delete();
    }

    public function addCriteria($subCategoryId)
    {
        // Verify the sub-category belongs to this eventner
        $subCat = AssessmentSubCategory::whereHas('category', function ($q) {
            $q->where('eventner_id', $this->eventnerId);
        })->findOrFail($subCategoryId);

        $name = $this->newCriteriaNames[$subCategoryId] ?? '';
        $scoresStr = $this->newCriteriaScores[$subCategoryId] ?? '';

        if (empty(trim($name)) || empty(trim($scoresStr))) {
            session()->flash("error_{$subCategoryId}", "Nama Kriteria dan Opsi Nilai wajib diisi.");
            return;
        }

        // Parse comma separated values
        $scoreOptions = array_map('trim', explode(',', $scoresStr));

        // Remove empty values
        $scoreOptions = array_filter($scoreOptions, function($val) { return $val !== ''; });

        if (empty($scoreOptions)) {
            session()->flash("error_{$subCategoryId}", "Opsi Nilai tidak valid.");
            return;
        }

        // Validate all score options are numeric
        foreach ($scoreOptions as $opt) {
            if (!is_numeric($opt)) {
                session()->flash("error_{$subCategoryId}", "Semua opsi nilai harus berupa angka.");
                return;
            }
        }

        $maxOrder = AssessmentCriteria::where('assessment_sub_category_id', $subCategoryId)->max('sort_order') ?? 0;

        $weight = $this->newCriteriaWeights[$subCategoryId] ?? 1;
        if (!is_numeric($weight) || $weight < 0) {
            $weight = 1;
        }

        AssessmentCriteria::create([
            'assessment_sub_category_id' => $subCategoryId,
            'name' => strip_tags($name),
            'score_options' => array_values($scoreOptions),
            'weight' => $weight,
            'sort_order' => $maxOrder + 1,
        ]);

        $this->newCriteriaNames[$subCategoryId] = '';
        $this->newCriteriaScores[$subCategoryId] = '';
        $this->newCriteriaWeights[$subCategoryId] = '';
    }

    public function deleteCriteria($id)
    {
        // Verify ownership through parent chain: criteria -> subCategory -> category -> eventner
        AssessmentCriteria::whereHas('subCategory.category', function ($q) {
            $q->where('eventner_id', $this->eventnerId);
        })->findOrFail($id)->delete();
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
    // EDIT: Criteria
    // ============================================================
    public $editingCriteriaId = null;
    public $editCriteriaName = '';
    public $editCriteriaScores = '';
    public $editCriteriaWeight = 1;

    public function startEditCriteria($id)
    {
        $crit = AssessmentCriteria::whereHas('subCategory.category', function ($q) {
            $q->where('eventner_id', $this->eventnerId);
        })->findOrFail($id);
        $this->editingCriteriaId = $id;
        $this->editCriteriaName = $crit->name;
        $this->editCriteriaScores = implode(',', $crit->score_options ?? []);
        $this->editCriteriaWeight = $crit->weight ?? 1;
    }

    public function saveEditCriteria()
    {
        $this->validate([
            'editCriteriaName' => 'required|string|max:255',
            'editCriteriaScores' => 'required|string',
        ]);

        $scoreOptions = array_filter(array_map('trim', explode(',', $this->editCriteriaScores)), fn($v) => $v !== '');

        if (empty($scoreOptions)) {
            session()->flash('error_criteria', 'Opsi Nilai tidak valid.');
            return;
        }

        // Validate all score options are numeric
        foreach ($scoreOptions as $opt) {
            if (!is_numeric($opt)) {
                session()->flash('error_criteria', 'Semua opsi nilai harus berupa angka.');
                return;
            }
        }

        $weight = is_numeric($this->editCriteriaWeight) && $this->editCriteriaWeight >= 0 ? $this->editCriteriaWeight : 1;

        AssessmentCriteria::whereHas('subCategory.category', function ($q) {
            $q->where('eventner_id', $this->eventnerId);
        })->findOrFail($this->editingCriteriaId)
            ->update([
                'name' => strip_tags($this->editCriteriaName),
                'score_options' => array_values($scoreOptions),
                'weight' => $weight,
            ]);

        $this->reset('editingCriteriaId', 'editCriteriaName', 'editCriteriaScores', 'editCriteriaWeight');
    }

    public function cancelEditCriteria()
    {
        $this->reset('editingCriteriaId', 'editCriteriaName', 'editCriteriaScores', 'editCriteriaWeight');
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
        foreach ($categories as $order => $catId) {
            AssessmentCategory::where('id', $catId)->update(['sort_order' => $order + 1]);
        }
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

        foreach ($criterias as $order => $critId) {
            AssessmentCriteria::where('id', $critId)->update(['sort_order' => $order + 1]);
        }
    }

    // ============================================================
    // RENDER
    // ============================================================

    public function render()
    {
        return view('livewire.eventner.format-nilai.builder');
    }
}
