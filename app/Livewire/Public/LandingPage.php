<?php

namespace App\Livewire\Public;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Setting;
use App\Models\Eventner;
use Illuminate\Support\Facades\Storage;

class LandingPage extends Component
{
    public $sections = [];
    public $sectionsOrder = [];
    public $sectionsActive = [];
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

        // Load sections order & active state
        $this->sectionsOrder = json_decode(Setting::get('landing_sections_order', '["hero","features","about","eventners","cta"]'), true);
        $this->sectionsActive = json_decode(Setting::get('landing_sections_active', '{"hero":true,"features":true,"about":true,"eventners":true,"cta":true}'), true);

        // Load each section's content
        foreach ($this->sectionsOrder as $type) {
            $active = $this->sectionsActive[$type] ?? true;
            if (!$active) {
                continue;
            }

            $content = Setting::get("landing_{$type}");
            $this->sections[] = [
                'type' => $type,
                'content' => $content,
            ];
        }
    }

    public function render()
    {
        $eventners = Eventner::with('registrations')
            ->withCount('registrations')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('livewire.public.landing-page', [
            'eventners' => $eventners,
        ])
            ->layout('layouts.zubaz', [
                'logoPath' => $this->logoPath,
                'favicon' => $this->favicon,
            ])
            ->title(get_setting('site_title', 'BARIS APP'));
    }
}
