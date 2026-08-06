<?php

namespace App\Livewire\Public;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.landing')]
#[Title('Syarat & Ketentuan - BARIS APP')]
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
            ->title('Syarat & Ketentuan - '.get_setting('site_title', 'BARIS APP'));
    }
}
