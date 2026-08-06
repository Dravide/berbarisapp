<?php

namespace App\Livewire\Public;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.landing')]
#[Title('Bantuan & Support - BARIS APP')]
class HelpSupport extends Component
{
    public $logoPath = null;

    public $favicon = null;

    public $contact = [];

    public $social = [];

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

        $this->contact = Setting::get('landing_contact', []);
        $this->social = Setting::get('landing_social_links', []);
    }

    public function render()
    {
        return view('livewire.public.help-support')
            ->title('Bantuan & Support - '.get_setting('site_title', 'BARIS APP'));
    }
}
