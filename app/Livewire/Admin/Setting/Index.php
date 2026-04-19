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

    public function mount()
    {
        $this->site_title = Setting::get('site_title', 'BARIS APP');
        $this->meta_description = Setting::get('meta_description', '');
        $this->meta_keywords = Setting::get('meta_keywords', '');
        
        $this->logo_dark_path = Setting::get('logo_dark');
        $this->logo_light_path = Setting::get('logo_light');
        $this->favicon_path = Setting::get('favicon');
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

    public function render()
    {
        return view('livewire.admin.setting.index');
    }
}
