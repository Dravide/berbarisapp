<?php

namespace App\Livewire\Admin\Setting;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Storage;

#[Layout('layouts.admin')]
#[Title('Pengaturan Situs - BARIS APP')]
class Index extends Component
{
    use WithFileUploads;

    public $site_title;
    public $meta_description;
    public $meta_keywords;
    
    public $new_logo_dark;
    public $new_logo_light;
    public $new_favicon;

    public $logo_dark_path;
    public $logo_light_path;
    public $favicon_path;

    // Tema
    public $site_primary_color = '#0062ff';
    public $site_accent_color = '#a3e635';
    public $site_font_sans = 'Inter';
    public $site_font_display = 'Plus Jakarta Sans';

    // Biaya pendaftaran eventner
    public $eventner_registration_fee = 50000;

    public function mount()
    {
        $this->site_title = Setting::get('site_title', 'BARIS APP');
        $this->meta_description = Setting::get('meta_description', '');
        $this->meta_keywords = Setting::get('meta_keywords', '');

        $this->logo_dark_path = Setting::get('logo_dark');
        $this->logo_light_path = Setting::get('logo_light');
        $this->favicon_path = Setting::get('favicon');

        // Theme
        $this->site_primary_color = Setting::get('site_primary_color', '#0062ff');
        $this->site_accent_color = Setting::get('site_accent_color', '#a3e635');
        $this->site_font_sans = Setting::get('site_font_sans', 'Inter');
        $this->site_font_display = Setting::get('site_font_display', 'Plus Jakarta Sans');

        // Biaya
        $this->eventner_registration_fee = (int) Setting::get('eventner_registration_fee', 50000);
    }

    public function save()
    {
        $this->validate([
            'site_title' => 'required|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'new_logo_dark' => 'nullable|mimes:svg,png,jpg,jpeg|max:2048',
            'new_logo_light' => 'nullable|mimes:svg,png,jpg,jpeg|max:2048',
            'new_favicon' => 'nullable|mimes:svg,png,jpg,jpeg,ico|max:1024',
        ]);

        Setting::set('site_title', $this->site_title);
        Setting::set('meta_description', $this->meta_description);
        Setting::set('meta_keywords', $this->meta_keywords);
        Setting::set('site_primary_color', $this->site_primary_color);
        Setting::set('site_accent_color', $this->site_accent_color);
        Setting::set('site_font_sans', $this->site_font_sans);
        Setting::set('site_font_display', $this->site_font_display);
        Setting::set('eventner_registration_fee', $this->eventner_registration_fee);

        if ($this->new_logo_dark) {
            if ($this->logo_dark_path) Storage::disk('public')->delete($this->logo_dark_path);
            $this->logo_dark_path = $this->new_logo_dark->store('settings', 'public');
            Setting::set('logo_dark', $this->logo_dark_path);
        }

        if ($this->new_logo_light) {
            if ($this->logo_light_path) Storage::disk('public')->delete($this->logo_light_path);
            $this->logo_light_path = $this->new_logo_light->store('settings', 'public');
            Setting::set('logo_light', $this->logo_light_path);
        }



        if ($this->new_favicon) {
            if ($this->favicon_path) Storage::disk('public')->delete($this->favicon_path);
            $this->favicon_path = $this->new_favicon->store('settings', 'public');
            Setting::set('favicon', $this->favicon_path);
        }

        $this->reset(['new_logo_dark', 'new_logo_light', 'new_favicon']);
        
        session()->flash('success', 'Pengaturan berhasil diperbarui.');
    }

    public function getAvailableFonts(): array
    {
        return [
            'sans' => [
                ['id' => 'Inter', 'name' => 'Inter', 'weights' => 'wght@400;500;600;700'],
                ['id' => 'Bricolage Grotesque', 'name' => 'Bricolage Grotesque', 'weights' => 'wght@400;500;600;700;800'],
                ['id' => 'DM Sans', 'name' => 'DM Sans', 'weights' => 'wght@400;500;700'],
                ['id' => 'Poppins', 'name' => 'Poppins', 'weights' => 'wght@400;500;600;700;800'],
                ['id' => 'Nunito', 'name' => 'Nunito', 'weights' => 'wght@400;500;600;700;800'],
                ['id' => 'Work Sans', 'name' => 'Work Sans', 'weights' => 'wght@400;500;600;700'],
                ['id' => 'Outfit', 'name' => 'Outfit', 'weights' => 'wght@400;500;600;700;800'],
                ['id' => 'Onest', 'name' => 'Onest', 'weights' => 'wght@400;500;600;700;800'],
                ['id' => 'Plus Jakarta Sans', 'name' => 'Plus Jakarta Sans', 'weights' => 'wght@400;500;600;700;800'],
            ],
            'display' => [
                ['id' => 'Plus Jakarta Sans', 'name' => 'Plus Jakarta Sans', 'weights' => 'wght@500;600;700;800'],
                ['id' => 'Bricolage Grotesque', 'name' => 'Bricolage Grotesque', 'weights' => 'wght@500;600;700;800'],
                ['id' => 'Poppins', 'name' => 'Poppins', 'weights' => 'wght@500;600;700;800'],
                ['id' => 'Onest', 'name' => 'Onest', 'weights' => 'wght@500;600;700;800'],
                ['id' => 'Outfit', 'name' => 'Outfit', 'weights' => 'wght@500;600;700;800'],
                ['id' => 'DM Serif Display', 'name' => 'DM Serif Display', 'weights' => 'wght@400'],
                ['id' => 'Playfair Display', 'name' => 'Playfair Display', 'weights' => 'wght@400;500;600;700;800'],
                ['id' => 'Bebas Neue', 'name' => 'Bebas Neue', 'weights' => 'wght@400'],
            ],
        ];
    }

    public function render()
    {
        return view('livewire.admin.setting.index');
    }
}
