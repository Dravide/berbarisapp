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
    public $parentId = null;
    public $tanggal_pelaksanaan = '';
    public $kuota = '';
    public $max_registrations_per_school = 1;
    public $selectedJudges = [];

    public $isEditMode = false;
    public $editingId = null;

    public $expandedParents = [];

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
    public function parentCategories()
    {
        return CompetitionCategory::whereNull('parent_id')
            ->where('eventner_id', $this->eventnerId)
            ->with(['children' => fn($q) => $q->with('judges', 'registrations')])
            ->latest()
            ->get();
    }

    #[Computed]
    public function orphanChildren()
    {
        return CompetitionCategory::whereNotNull('parent_id')
            ->where('eventner_id', $this->eventnerId)
            ->whereDoesntHave('parent', fn($q) => $q->where('eventner_id', $this->eventnerId))
            ->with('judges', 'registrations')
            ->latest()
            ->get();
    }

    #[Computed]
    public function allParents()
    {
        return CompetitionCategory::whereNull('parent_id')
            ->where('eventner_id', $this->eventnerId)
            ->get();
    }

    #[Computed]
    public function availableJudges()
    {
        return Judge::where('eventner_id', $this->eventnerId)->get();
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
            'parentId' => 'nullable|exists:competition_categories,id',
            'selectedJudges' => 'array',
            'selectedJudges.*' => 'exists:judges,id',
        ];

        if (!$isParent) {
            $rules['kuota'] = 'nullable|integer|min:1';
            $rules['max_registrations_per_school'] = 'required|integer|min:1';
            $rules['tanggal_pelaksanaan'] = 'nullable|date';
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
        } else {
            $data['kuota'] = $this->kuota ?: null;
            $data['max_registrations_per_school'] = $this->max_registrations_per_school;
            $data['tanggal_pelaksanaan'] = $this->tanggal_pelaksanaan ?: null;
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
            $cat = CompetitionCategory::create(array_merge($data, ['eventner_id' => $this->eventnerId]));

            if (!$isParent) {
                $cat->judges()->attach($this->selectedJudges);
            }

            session()->flash('success', ($isParent ? 'Jenis Lomba' : 'Tingkat Lomba') . ' baru berhasil ditambahkan.');
        }

        $this->resetForm();
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
        $this->selectedJudges = $cat->judges->pluck('id')->toArray();
    }

    public function delete($id)
    {
        $cat = CompetitionCategory::where('eventner_id', $this->eventnerId)->findOrFail($id);

        if ($cat->isParent() && $cat->children()->exists()) {
            session()->flash('error', 'Tidak bisa menghapus: Jenis Lomba ini masih memiliki ' . $cat->children()->count() . ' Tingkat Lomba. Hapus tingkatnya terlebih dahulu.');
            return;
        }

        $cat->delete();
        session()->flash('success', 'Kategori dihapus.');
    }

    public function resetForm()
    {
        $this->reset(['name', 'parentId', 'kuota', 'max_registrations_per_school', 'tanggal_pelaksanaan', 'selectedJudges', 'isEditMode', 'editingId']);
        $this->max_registrations_per_school = 1;
    }

    public function render()
    {
        return view('livewire.eventner.competition-category.index');
    }
}
