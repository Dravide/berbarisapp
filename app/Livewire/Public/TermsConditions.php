<?php

namespace App\Livewire\Public;

use Livewire\Component;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class TermsConditions extends Component
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
        return view('livewire.public.terms-conditions')
            ->layout('layouts.zubaz', [
                'logoPath' => $this->logoPath,
                'favicon' => $this->favicon,
            ])
            ->title('Syarat & Ketentuan - ' . get_setting('site_title', 'BARIS APP'));
    }
}
