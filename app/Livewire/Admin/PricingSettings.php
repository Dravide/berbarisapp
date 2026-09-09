<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\SaasPlan;
use App\Models\Setting;

#[Layout('layouts.admin')]
class PricingSettings extends Component
{
    public bool $showModal = false;
    public ?int $planId = null;

    // Form paket
    public string $name = '';
    public $price = 0;
    public $registration_fee = 0;
    public string $description = '';
    public bool $is_active = true;
    public bool $is_free = false;
    public bool $is_contact = false;
    public string $contact_url = '';
    public bool $highlight = false;
    public $sort_order = 0;

    // [key => bool] fitur premium yang masuk paket (hanya relevan untuk paket berbayar)
    public array $plan_features = [];

    public function mount()
    {
        $this->loadFeatureDefaults();
    }

    private function loadFeatureDefaults(): void
    {
        foreach (config('eventner_features', []) as $key => $config) {
            if (!($config['locked_free'] ?? true)) {
                continue; // fitur selalu terbuka tak perlu ditampilkan
            }
            $this->plan_features[$key] = true;
        }
    }

    private function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'price' => 'required|integer|min:0',
            'registration_fee' => 'required|integer|min:0',
            'description' => 'nullable|string|max:255',
            'contact_url' => 'nullable|url|max:255',
            'sort_order' => 'required|integer|min:0',
        ];
    }

    public function createPlan()
    {
        $this->resetForm();
        $this->sort_order = SaasPlan::count() + 1;
        $this->showModal = true;
    }

    public function editPlan(int $id)
    {
        $plan = SaasPlan::with('features')->findOrFail($id);

        $this->planId = $plan->id;
        $this->name = $plan->name;
        $this->price = $plan->price;
        $this->registration_fee = $plan->registration_fee;
        $this->description = (string) $plan->description;
        $this->is_active = $plan->is_active;
        $this->is_free = $plan->is_free;
        $this->is_contact = $plan->is_contact;
        $this->contact_url = (string) $plan->contact_url;
        $this->highlight = $plan->highlight;
        $this->sort_order = $plan->sort_order;

        // Muat centang fitur: default false, lalu isi dari DB
        foreach (array_keys($this->plan_features) as $key) {
            $this->plan_features[$key] = false;
        }
        foreach ($plan->featureKeys() as $key) {
            $this->plan_features[$key] = true;
        }

        $this->showModal = true;
    }

    public function savePlan()
    {
        $this->validate($this->rules());

        $data = [
            'name' => $this->name,
            'slug' => \Illuminate\Support\Str::slug($this->name) . '-' . strtolower(\Illuminate\Support\Str::random(5)),
            'price' => $this->is_free ? 0 : (int) $this->price,
            'registration_fee' => $this->is_free ? 0 : (int) $this->registration_fee,
            'description' => $this->description ?: null,
            'is_active' => $this->is_active,
            'is_free' => $this->is_free,
            'is_contact' => $this->is_contact,
            'contact_url' => $this->is_contact ? ($this->contact_url ?: null) : null,
            'highlight' => $this->is_free ? false : $this->highlight,
            'sort_order' => (int) $this->sort_order,
        ];

        $plan = SaasPlan::updateOrCreate(['id' => $this->planId], $data);

        // Sinkron fitur (paket gratis tidak menyimpan fitur premium)
        $features = $this->is_free
            ? []
            : array_keys(array_filter($this->plan_features));
        if ($this->is_contact) {
            // Paket contact: fitur disimpan untuk display, guard tetap per fitur saat diaktifkan admin
        }
        $plan->features()->delete();
        $plan->features()->createMany(
            collect($features)->map(fn ($key) => ['feature_key' => $key])->all()
        );

        $this->showModal = false;
        $this->resetForm();
        session()->flash('success', 'Paket berhasil disimpan.');
    }

    public function toggleActive(int $id)
    {
        $plan = SaasPlan::findOrFail($id);
        $plan->update(['is_active' => !$plan->is_active]);
    }

    public function deletePlan(int $id)
    {
        $plan = SaasPlan::withCount('eventners')->findOrFail($id);

        if ($plan->is_free) {
            session()->flash('error', 'Paket gratis tidak bisa dihapus.');
            return;
        }
        if ($plan->eventners_count > 0) {
            session()->flash('error', "Paket masih dipakai {$plan->eventners_count} event. Nonaktifkan saja.");
            return;
        }

        $plan->delete();
        session()->flash('success', 'Paket dihapus.');
    }

    private function resetForm(): void
    {
        $this->reset('planId', 'name', 'price', 'registration_fee', 'description', 'is_active', 'is_free', 'is_contact', 'contact_url', 'highlight', 'sort_order');
        $this->is_active = true;
        $this->loadFeatureDefaults();
    }

    public function render()
    {
        return view('livewire.admin.pricing-settings', [
            'plans' => SaasPlan::with('features')->withCount('eventners')->orderBy('sort_order')->get(),
            'premiumFeatureKeys' => collect(config('eventner_features', []))
                ->filter(fn ($c) => $c['locked_free'] ?? true)
                ->keys()
                ->all(),
        ])->title('Harga & Paket SaaS - ' . app_name());
    }
}
