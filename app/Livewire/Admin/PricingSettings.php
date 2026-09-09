<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Setting;

#[Layout('layouts.admin')]
#[Title('Harga & Paket SaaS - BARIS APP')]
class PricingSettings extends Component
{
    // Biaya pendaftaran eventner (saat daftar paket paid)
    public $registration_fee = 50000;

    // Harga paket Event Penuh (bayar sekali via QRIS di /upgrade)
    public $plan_price = 150000;

    // [key => bool] fitur premium yang dipamerkan di halaman harga
    public $premium_features = [];

    public function mount()
    {
        $this->registration_fee = (int) Setting::get('eventner_registration_fee', 50000);
        $this->plan_price = (int) Setting::get('eventner_plan_price', 150000);

        $saved = json_decode(Setting::get('saas_pricing', '{}'), true) ?? [];
        foreach (config('eventner_features', []) as $key => $config) {
            if (!($config['locked_free'] ?? true)) {
                continue; // fitur selalu terbuka tak perlu ditampilkan
            }
            $this->premium_features[$key] = !isset($saved['premium_features'])
                || in_array($key, $saved['premium_features'], true);
        }
    }

    public function save()
    {
        $this->validate([
            'registration_fee' => 'required|integer|min:0',
            'plan_price' => 'required|integer|min:0',
        ]);

        Setting::set('eventner_registration_fee', (int) $this->registration_fee);
        Setting::set('eventner_plan_price', (int) $this->plan_price);
        Setting::set('saas_pricing', json_encode([
            'plan_price' => (int) $this->plan_price,
            'premium_features' => array_keys(array_filter($this->premium_features)),
        ]));

        session()->flash('success', 'Pengaturan harga & paket berhasil diperbarui.');
    }

    public function render()
    {
        return view('livewire.admin.pricing-settings');
    }
}