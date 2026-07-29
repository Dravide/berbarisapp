<?php

namespace App\Livewire\Public;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Setting;
use App\Models\Eventner;
use App\Models\Registration;
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
        $this->sectionsOrder = json_decode(Setting::get('landing_sections_order', '["hero","features","about","eventners","ticket","vote","cta","partners"]'), true);
        $this->sectionsActive = json_decode(Setting::get('landing_sections_active', '{"hero":true,"features":true,"about":true,"eventners":true,"ticket":true,"vote":true,"cta":true,"partners":true}'), true);

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

        // Partners selalu tampil — tidak bergantung DB setting agar selalu muncul
        $partnersActive = $this->sectionsActive['partners'] ?? true;
        if ($partnersActive && !in_array('partners', array_column($this->sections, 'type'))) {
            $this->sections[] = [
                'type' => 'partners',
                'content' => Setting::get('landing_partners'),
            ];
        }
    }

    public function render()
    {
        $eventners = Eventner::withCount('registrations')
            ->orderBy('created_at', 'desc')
            ->limit(12)
            ->get();

        $ticketEvents = Eventner::where('ticket_active', true)
            ->whereNotNull('ticket_price')
            ->where(function ($q) {
                $q->whereNull('ticket_end')
                  ->orWhere('ticket_end', '>=', now());
            })
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        $voteEvents = Eventner::where('vote_active', true)
            ->where(function ($q) {
                $q->whereNull('vote_end')
                  ->orWhere('vote_end', '>=', now());
            })
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        // Logo sekolah yang terdata — untuk section partners marquee
        $schoolLogos = Registration::query()
            ->whereNotNull('logo_sekolah')
            ->where('logo_sekolah', '!=', '')
            ->select('logo_sekolah')
            ->distinct()
            ->limit(30)
            ->pluck('logo_sekolah');

        return view('livewire.public.landing-page', [
            'eventners' => $eventners,
            'ticketEvents' => $ticketEvents,
            'voteEvents' => $voteEvents,
            'schoolLogos' => $schoolLogos,
        ])
            ->layout('layouts.landing', [
                'logoPath' => $this->logoPath,
                'favicon' => $this->favicon,
                'sectionsActive' => $this->sectionsActive,
            ])
            ->title(get_setting('site_title', 'BARIS APP'));
    }
}
