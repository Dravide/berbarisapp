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
                ->get();
    }

    public function addCategory()
    {
        $this->validate(['newCategoryName' => 'required|string|max:255']);
        
        AssessmentCategory::create([
            'eventner_id' => $this->eventnerId,
            'name' => $this->newCategoryName,
        ]);
        
        $this->newCategoryName = '';
    }

    public function deleteCategory($id)
    {
        AssessmentCategory::where('eventner_id', $this->eventnerId)->findOrFail($id)->delete();
    }

    public function addSubCategory($categoryId)
    {
        $name = $this->newSubCategoryNames[$categoryId] ?? '';
        
        if(empty(trim($name))) {
            return;
        }

        AssessmentSubCategory::create([
            'assessment_category_id' => $categoryId,
            'name' => $name,
        ]);

        $this->newSubCategoryNames[$categoryId] = '';
    }

    public function deleteSubCategory($id)
    {
        AssessmentSubCategory::findOrFail($id)->delete();
    }

    public function addCriteria($subCategoryId)
    {
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

        AssessmentCriteria::create([
            'assessment_sub_category_id' => $subCategoryId,
            'name' => $name,
            'score_options' => array_values($scoreOptions),
        ]);

        $this->newCriteriaNames[$subCategoryId] = '';
        $this->newCriteriaScores[$subCategoryId] = ''; // default e.g. "50, 60, 70, 80, 90, 100" can be left at UI level if we want
    }

    public function deleteCriteria($id)
    {
        AssessmentCriteria::findOrFail($id)->delete();
    }

    public function render()
    {
        return view('livewire.eventner.format-nilai.builder');
    }
}
