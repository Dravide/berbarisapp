<?php

namespace App\Livewire\Eventner\Judge;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Judge;
use App\Models\AssessmentCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithFileUploads;

    public $name = '';
    public $phone_number = '';
    public $photo;
    public $currentPhotoPath = null;
    public $selectedCategories = [];

    public $isEditMode = false;
    public $editingId = null;

    protected $eventnerId;

    public function boot()
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403);
        }
        $this->eventnerId = $eventner->id;
    }

    #[Computed]
    public function judges()
    {
        return Judge::with('assessmentCategories')
            ->where('eventner_id', $this->eventnerId)
            ->latest()
            ->get();
    }

    #[Computed]
    public function availableCategories()
    {
        return AssessmentCategory::with('competitionCategory.parent')
            ->where('eventner_id', $this->eventnerId)
            ->get();
    }

    /**
     * Kategori dikelompokkan per jenjang/tingkat (parent competition_category,
     * mis. SD vs SMP). Kunci grup = id parent, atau id kategori itu sendiri bila
     * kategori menunjuk langsung ke tingkat induk (parent).
     */
    #[Computed]
    public function availableCategoriesGrouped()
    {
        $grouped = [];
        foreach ($this->availableCategories as $cat) {
            $cc = $cat->competitionCategory;
            if (!$cc) {
                $grouped['Lainnya'][] = $cat;
                continue;
            }
            $parent = $cc->parent ?: $cc;
            $grouped[$parent->id] = [
                'name' => $parent->name,
                'items' => ($grouped[$parent->id]['items'] ?? []) + [$cat->id => $cat],
            ];
        }
        return collect($grouped)->sortBy('name');
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'selectedCategories' => 'array',
            'selectedCategories.*' => 'exists:assessment_categories,id',
        ];

        if ($this->photo) {
            $rules['photo'] = 'image|max:2048';
        }

        $this->validate($rules);

        $photoPath = $this->currentPhotoPath;

        if ($this->photo) {
            if ($this->currentPhotoPath) {
                Storage::delete('public/' . $this->currentPhotoPath);
            }
            $photoPath = $this->photo->store('judges', 'public');
        }

        if ($this->isEditMode && $this->editingId) {
            $judge = Judge::where('eventner_id', $this->eventnerId)->findOrFail($this->editingId);
            $judge->update([
                'name' => strip_tags($this->name),
                'phone_number' => strip_tags($this->phone_number),
                'photo' => $photoPath,
            ]);
            $judge->assessmentCategories()->sync($this->selectedCategories);
            session()->flash('success', 'Data juri berhasil diperbarui.');
        } else {
            $judge = Judge::create([
                'eventner_id' => $this->eventnerId,
                'name' => strip_tags($this->name),
                'phone_number' => strip_tags($this->phone_number),
                'photo' => $photoPath,
            ]);
            $judge->assessmentCategories()->attach($this->selectedCategories);
            session()->flash('success', 'Juri baru berhasil ditambahkan.');
        }

        $this->resetForm();
    }

    public function edit($id)
    {
        $judge = Judge::where('eventner_id', $this->eventnerId)->findOrFail($id);
        $this->isEditMode = true;
        $this->editingId = $judge->id;
        $this->name = $judge->name;
        $this->phone_number = $judge->phone_number ?? '';
        $this->currentPhotoPath = $judge->photo;
        $this->selectedCategories = $judge->assessmentCategories->pluck('id')->toArray();
    }

    public function delete($id)
    {
        $judge = Judge::where('eventner_id', $this->eventnerId)->findOrFail($id);
        if ($judge->photo) {
            Storage::delete('public/' . $judge->photo);
        }
        $judge->delete();
        session()->flash('success', 'Juri berhasil dihapus.');
    }

    public function resetForm()
    {
        $this->reset(['name', 'phone_number', 'photo', 'currentPhotoPath', 'selectedCategories', 'isEditMode', 'editingId']);
    }

    public function render()
    {
        return view('livewire.eventner.judge.index');
    }
}
