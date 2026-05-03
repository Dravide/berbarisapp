<?php

namespace App\Livewire\Public;

use Livewire\Component;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class PrivacyPolicy extends Component
{
    public $logoPath = null;
    public $favicon = null;

    public function mount()
    {
        // Load logo
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
        return view('livewire.public.privacy-policy')
            ->layout('layouts.zubaz', [
                'logoPath' => $this->logoPath,
                'favicon' => $this->favicon,
            ])
            ->title('Kebijakan Privasi - ' . get_setting('site_title', 'BARIS APP'));
    }
}
