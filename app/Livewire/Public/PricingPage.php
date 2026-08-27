<?php

namespace App\Livewire\Public;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Setting;

#[Layout('layouts.frontend')]
#[Title('Harga & Paket - BARIS APP')]
class PricingPage extends Component
{
    public function render()
    {
        $features = collect(config('eventner_features', []))
            ->map(fn($config, $key) => [
                'key' => $key,
                'label' => $config['label'],
                'locked_free' => $config['locked_free'] ?? true,
            ])
            // Fitur selalu terbuka tidak perlu ditampilkan sebagai "bonus paid"
            ->filter(fn($f) => $f['locked_free'])
            ->values();

        return view('livewire.public.pricing-page', [
            'planPrice' => (int) Setting::get('eventner_plan_price', 150000),
            'regFee' => (int) Setting::get('eventner_registration_fee', 50000),
            'premiumFeatures' => $features,
        ]);
    }
}
