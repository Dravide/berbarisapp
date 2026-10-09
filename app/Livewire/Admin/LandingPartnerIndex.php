<?php

namespace App\Livewire\Admin;

use App\Models\LandingPartner;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Kelola sponsor & media partner yang tampil di laman depan platform.
 * Pola PricingSettings: tabel + modal form, logo disimpan ke disk public.
 */
#[Layout('layouts.admin')]
class LandingPartnerIndex extends Component
{
    use WithFileUploads;

    public bool $showModal = false;

    public ?int $partnerId = null;

    // Form
    public string $name = '';

    public $logo;

    public string $logo_current = '';

    public string $link = '';

    public string $type = 'sponsor';

    public bool $is_active = true;

    public $sort_order = 0;

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            // 2 MB cukup untuk logo; PNG/SVG transparan paling cocok di laman depan
            'logo' => 'nullable|image|max:2048',
            'link' => 'nullable|url|max:255',
            'type' => 'required|in:sponsor,medpart',
            'sort_order' => 'required|integer|min:0',
        ];
    }

    public function createPartner()
    {
        $this->resetForm();
        $this->sort_order = LandingPartner::count() + 1;
        $this->showModal = true;
    }

    public function editPartner(int $id)
    {
        $partner = LandingPartner::findOrFail($id);

        $this->partnerId = $partner->id;
        $this->name = $partner->name;
        $this->logo_current = $partner->logo ?? '';
        $this->link = $partner->link ?? '';
        $this->type = $partner->type;
        $this->is_active = $partner->is_active;
        $this->sort_order = $partner->sort_order;
        $this->showModal = true;
    }

    public function savePartner()
    {
        $this->validate($this->rules());

        $logo = $this->logo_current;
        if ($this->logo) {
            if ($logo) {
                Storage::disk('public')->delete($logo);
            }
            $logo = $this->logo->store('landing-partners', 'public');
            // Path baru jadi "current" supaya simpan kedua kali tidak
            // menghapus gambar yang baru diunggah.
            $this->logo_current = $logo;
        }

        LandingPartner::updateOrCreate(['id' => $this->partnerId], [
            'name' => $this->name,
            'logo' => $logo ?: null,
            'link' => $this->link ?: null,
            'type' => $this->type,
            'is_active' => $this->is_active,
            'sort_order' => (int) $this->sort_order,
        ]);

        $this->showModal = false;
        $this->resetForm();
        session()->flash('success', 'Partner berhasil disimpan.');
    }

    public function toggleActive(int $id)
    {
        $partner = LandingPartner::findOrFail($id);
        $partner->update(['is_active' => ! $partner->is_active]);
    }

    public function deletePartner(int $id)
    {
        $partner = LandingPartner::findOrFail($id);

        if ($partner->logo) {
            Storage::disk('public')->delete($partner->logo);
        }
        $partner->delete();

        session()->flash('success', 'Partner dihapus.');
    }

    private function resetForm(): void
    {
        $this->reset('partnerId', 'name', 'logo', 'logo_current', 'link', 'type', 'is_active', 'sort_order');
        $this->is_active = true;
        $this->type = 'sponsor';
    }

    public function render()
    {
        return view('livewire.admin.landing-partner-index', [
            'partners' => LandingPartner::orderBy('type')->orderBy('sort_order')->get(),
        ])->title('Sponsor & Media Partner - ' . app_name());
    }
}
