<?php

namespace App\Livewire\Eventner\CompetitionCategory;

use Livewire\Component;
use App\Models\CompetitionCategory;
use App\Models\Judge;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

#[Layout('layouts.admin')]
class Index extends Component
{
    public $name = '';
    public $tanggal_pelaksanaan = '';
    public $kuota = '';
    public $selectedJudges = [];
    
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

    #[\Livewire\Attributes\Computed]
    public function categories()
    {
        return CompetitionCategory::with('judges')
            ->where('eventner_id', $this->eventnerId)
            ->latest()
            ->get();
    }

    #[\Livewire\Attributes\Computed]
    public function availableJudges()
    {
        return Judge::where('eventner_id', $this->eventnerId)->get();
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'kuota' => 'nullable|integer|min:1',
            'tanggal_pelaksanaan' => 'nullable|date',
            'selectedJudges' => 'array',
            'selectedJudges.*' => 'exists:judges,id'
        ]);

        if ($this->isEditMode && $this->editingId) {
            $cat = CompetitionCategory::where('eventner_id', $this->eventnerId)->findOrFail($this->editingId);
            $cat->update([
                'name' => $this->name, 
                'kuota' => $this->kuota ?: null,
                'tanggal_pelaksanaan' => $this->tanggal_pelaksanaan ?: null
            ]);
            $cat->judges()->sync($this->selectedJudges);
            session()->flash('success', 'Tingkat/Kategori Lomba berhasil diperbarui.');
        } else {
            $cat = CompetitionCategory::create([
                'eventner_id' => $this->eventnerId,
                'name' => $this->name,
                'kuota' => $this->kuota ?: null,
                'tanggal_pelaksanaan' => $this->tanggal_pelaksanaan ?: null,
            ]);
            $cat->judges()->attach($this->selectedJudges);
            session()->flash('success', 'Tingkat/Kategori Lomba baru berhasil ditambahkan.');
        }

        $this->resetForm();
    }

    public function edit($id)
    {
        $cat = CompetitionCategory::where('eventner_id', $this->eventnerId)->findOrFail($id);
        $this->isEditMode = true;
        $this->editingId = $cat->id;
        $this->name = $cat->name;
        $this->kuota = $cat->kuota ?? '';
        $this->tanggal_pelaksanaan = $cat->tanggal_pelaksanaan ?? '';
        $this->selectedJudges = $cat->judges->pluck('id')->toArray();
    }

    public function delete($id)
    {
        $cat = CompetitionCategory::where('eventner_id', $this->eventnerId)->findOrFail($id);
        $cat->delete();
        session()->flash('success', 'Tingkat/Kategori Lomba dihapus.');
    }

    public function resetForm()
    {
        $this->reset(['name', 'kuota', 'tanggal_pelaksanaan', 'selectedJudges', 'isEditMode', 'editingId']);
    }

    public function render()
    {
        return view('livewire.eventner.competition-category.index');
    }
}
