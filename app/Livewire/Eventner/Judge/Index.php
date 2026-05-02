<?php

namespace App\Livewire\Eventner\Judge;

use Livewire\Component;
use App\Models\Judge;
use App\Models\AssessmentCategory;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

#[Layout('layouts.admin')]
class Index extends Component
{
    public $name = '';
    public $phone_number = '';
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
        return AssessmentCategory::where('eventner_id', $this->eventnerId)->get();
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'selectedCategories' => 'array',
            'selectedCategories.*' => 'exists:assessment_categories,id'
        ]);

        if ($this->isEditMode && $this->editingId) {
            $judge = Judge::where('eventner_id', $this->eventnerId)->findOrFail($this->editingId);
            $judge->update([
                'name' => strip_tags($this->name),
                'phone_number' => strip_tags($this->phone_number),
            ]);
            $judge->assessmentCategories()->sync($this->selectedCategories);
            session()->flash('success', 'Data juri berhasil diperbarui.');
        } else {
            $judge = Judge::create([
                'eventner_id' => $this->eventnerId,
                'name' => strip_tags($this->name),
                'phone_number' => strip_tags($this->phone_number),
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
        $this->phone_number = $judge->phone_number;
        $this->selectedCategories = $judge->assessmentCategories->pluck('id')->toArray();
    }

    public function delete($id)
    {
        $judge = Judge::where('eventner_id', $this->eventnerId)->findOrFail($id);
        $judge->delete();
        session()->flash('success', 'Juri berhasil dihapus.');
    }

    public function resetForm()
    {
        $this->reset(['name', 'phone_number', 'selectedCategories', 'isEditMode', 'editingId']);
    }

    public function render()
    {
        return view('livewire.eventner.judge.index');
    }
}
