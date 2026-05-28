<?php

namespace App\Livewire\Eventner\Tenant;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithFileUploads;

    public $name = '';
    public $type = 'culinary'; // food, beverage, bazaar, souvenir, other
    public $description = '';
    public $logo;
    public $currentLogoPath = null;
    public $sort_order = 0;

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
    public function tenants()
    {
        return Tenant::where('eventner_id', $this->eventnerId)
            ->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'required|integer|min:0',
        ];

        if ($this->logo) {
            $rules['logo'] = 'image|max:2048'; // Max 2MB image
        }

        $this->validate($rules);

        $logoPath = $this->currentLogoPath;

        if ($this->logo) {
            // Delete old logo
            if ($this->currentLogoPath) {
                Storage::delete('public/' . $this->currentLogoPath);
            }
            $logoPath = $this->logo->store('tenants', 'public');
        }

        if ($this->isEditMode && $this->editingId) {
            $tenant = Tenant::where('eventner_id', $this->eventnerId)->findOrFail($this->editingId);
            $tenant->update([
                'name' => strip_tags($this->name),
                'type' => $this->type,
                'description' => $this->description ?: null,
                'logo' => $logoPath,
                'sort_order' => $this->sort_order,
            ]);
            session()->flash('success', 'Tenant berhasil diperbarui.');
        } else {
            Tenant::create([
                'eventner_id' => $this->eventnerId,
                'name' => strip_tags($this->name),
                'type' => $this->type,
                'description' => $this->description ?: null,
                'logo' => $logoPath,
                'sort_order' => $this->sort_order,
            ]);
            session()->flash('success', 'Tenant baru berhasil ditambahkan.');
        }

        $this->resetForm();
    }

    public function edit($id)
    {
        $tenant = Tenant::where('eventner_id', $this->eventnerId)->findOrFail($id);
        $this->isEditMode = true;
        $this->editingId = $tenant->id;
        $this->name = $tenant->name;
        $this->type = $tenant->type;
        $this->description = $tenant->description ?? '';
        $this->currentLogoPath = $tenant->logo;
        $this->sort_order = $tenant->sort_order;
    }

    public function delete($id)
    {
        $tenant = Tenant::where('eventner_id', $this->eventnerId)->findOrFail($id);
        if ($tenant->logo) {
            Storage::delete('public/' . $tenant->logo);
        }
        $tenant->delete();
        session()->flash('success', 'Tenant berhasil dihapus.');
    }

    public function toggleActive($id)
    {
        $tenant = Tenant::where('eventner_id', $this->eventnerId)->findOrFail($id);
        $tenant->is_active = !$tenant->is_active;
        $tenant->save();
        session()->flash('success', 'Status tenant berhasil diubah.');
    }

    public function resetForm()
    {
        $this->reset(['name', 'type', 'description', 'logo', 'currentLogoPath', 'sort_order', 'isEditMode', 'editingId']);
    }

    public function render()
    {
        return view('livewire.eventner.tenant.index')
            ->title('Daftar Tenant - BARIS APP');
    }
}
