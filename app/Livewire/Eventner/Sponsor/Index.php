<?php

namespace App\Livewire\Eventner\Sponsor;

use App\Traits\FeatureGatedComponent;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Sponsor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithFileUploads;
    use FeatureGatedComponent;

    protected string $requiredFeature = 'sponsors';

    public $name = '';
    public $type = 'sponsor'; // sponsor, medpart, partner, supporting
    public $link = '';
    public $logo;
    public $currentLogoPath = null;
    public $sort_order = 0;

    public $isEditMode = false;
    public $editingId = null;

    protected $eventnerId;

    public function mount()
    {
        $this->bootFeatureGate();
    }

    public function boot()
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403);
        }
        $this->eventnerId = $eventner->id;
    }

    #[Computed]
    public function sponsors()
    {
        return Sponsor::where('eventner_id', $this->eventnerId)
            ->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'link' => 'nullable|url|max:500',
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
            $logoPath = $this->logo->store('sponsors', 'public');
        }

        if ($this->isEditMode && $this->editingId) {
            $sponsor = Sponsor::where('eventner_id', $this->eventnerId)->findOrFail($this->editingId);
            $sponsor->update([
                'name' => strip_tags($this->name),
                'type' => $this->type,
                'link' => $this->link ?: null,
                'logo' => $logoPath,
                'sort_order' => $this->sort_order,
            ]);
            session()->flash('success', 'Sponsor / Media Partner berhasil diperbarui.');
        } else {
            Sponsor::create([
                'eventner_id' => $this->eventnerId,
                'name' => strip_tags($this->name),
                'type' => $this->type,
                'link' => $this->link ?: null,
                'logo' => $logoPath,
                'sort_order' => $this->sort_order,
            ]);
            session()->flash('success', 'Sponsor / Media Partner baru berhasil ditambahkan.');
        }

        $this->resetForm();
    }

    public function edit($id)
    {
        $sponsor = Sponsor::where('eventner_id', $this->eventnerId)->findOrFail($id);
        $this->isEditMode = true;
        $this->editingId = $sponsor->id;
        $this->name = $sponsor->name;
        $this->type = $sponsor->type;
        $this->link = $sponsor->link ?? '';
        $this->currentLogoPath = $sponsor->logo;
        $this->sort_order = $sponsor->sort_order;
    }

    public function delete($id)
    {
        $sponsor = Sponsor::where('eventner_id', $this->eventnerId)->findOrFail($id);
        if ($sponsor->logo) {
            Storage::delete('public/' . $sponsor->logo);
        }
        $sponsor->delete();
        session()->flash('success', 'Sponsor berhasil dihapus.');
    }

    public function toggleActive($id)
    {
        $sponsor = Sponsor::where('eventner_id', $this->eventnerId)->findOrFail($id);
        $sponsor->is_active = !$sponsor->is_active;
        $sponsor->save();
        session()->flash('success', 'Status sponsor berhasil diubah.');
    }

    public function resetForm()
    {
        $this->reset(['name', 'type', 'link', 'logo', 'currentLogoPath', 'sort_order', 'isEditMode', 'editingId']);
    }

    public function render()
    {
        return view('livewire.eventner.sponsor.index')
            ->title('Sponsor & Media Partner - BARIS APP');
    }
}
