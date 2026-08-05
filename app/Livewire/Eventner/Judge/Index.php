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
     * Kategori dikelompokkan per tingkat/kelas kompetisi (competition_category),
     * contoh parent "LOBB" dengan child "U13", "U16". Kunci grup = id
     * competition_category (child), label = full_name ("LOBB — U13") sehingga
     * U13 dan U16 tampil terpisah meski induknya sama. Kategori yang menunjuk
     * langsung ke induk (parent, tanpa child) tetap jadi grup sendiri.
     */
    #[Computed]
    public function availableCategoriesGrouped()
    {
        $grouped = [];
        foreach ($this->availableCategories as $cat) {
            $cc = $cat->competitionCategory;
            if (!$cc) {
                $grouped['Lainnya'] = [
                    'name' => 'Lainnya',
                    'items' => ($grouped['Lainnya']['items'] ?? []) + [$cat->id => $cat],
                ];
                continue;
            }
            // grup per competition_category itu sendiri (child jika ada, atau induk)
            $grouped[$cc->id] = [
                'name' => $cc->full_name,
                'items' => ($grouped[$cc->id]['items'] ?? []) + [$cat->id => $cat],
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
