<?php

namespace App\Livewire\Public;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use App\Support\Pricing;

#[Layout('layouts.landing')]
class PricingPage extends Component
{
    public $logoPath = null;

    public $favicon = null;

    public function mount()
    {
        $this->logoPath = Setting::get('logo_dark')
            ? Storage::disk('public')->url(Setting::get('logo_dark'))
            : null;

        $faviconSetting = Setting::get('favicon');
        $this->favicon = $faviconSetting
            ? Storage::disk('public')->url($faviconSetting)
            : null;
    }

    public function render()
    {
        return view('livewire.public.pricing-page', [
            'plans' => Pricing::plans(),
        ])
            ->layout('layouts.landing', [
                'logoPath' => $this->logoPath,
                'favicon' => $this->favicon,
            ])
            ->title('Harga & Paket - ' . app_name());
    }
}