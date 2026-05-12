<?php

namespace App\Livewire\Eventner\Settings;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Eventner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
#[Title('Profil Event - BARIS APP')]
class Profile extends Component
{
    use WithFileUploads;

    public $eventnerId;
    public $nama_event;
    public $deskripsi;
    public $diselenggarakan_oleh;
    public $lokasi;
    public $venue;
    public $tanggal;
    public $tanggal_pendaftaran;
    public $technical_meeting;
    public $tingkat_perlombaan;
    public $latitude;
    public $longitude;

    public $link_instagram;
    public $link_tiktok;
    public $link_whatsapp;
    public $link_livestreaming;
    public $registration_status = 'open';

    public $drawing_code;
    public $scoring_code;

    public $logo;
    public $newLogo;

    public $poster;
    public $newPoster;

    // Theme
    public $theme_preset = 'ocean';
    public $theme_primary = '#0072FF';
    public $theme_accent = '#00D4AA';

    public function mount()
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403);
        }

        $this->eventnerId = $eventner->id;
        $this->nama_event = $eventner->nama_event;
        $this->deskripsi = $eventner->deskripsi;
        $this->diselenggarakan_oleh = $eventner->diselenggarakan_oleh;
        $this->lokasi = $eventner->lokasi;
        $this->venue = $eventner->venue;
        $this->tanggal = $eventner->tanggal;
        $this->tanggal_pendaftaran = $eventner->tanggal_pendaftaran;
        $this->technical_meeting = $eventner->technical_meeting;
        $this->tingkat_perlombaan = $eventner->tingkat_perlombaan;
        $this->latitude = $eventner->latitude;
        $this->longitude = $eventner->longitude;
        
        $this->link_instagram = $eventner->link_instagram;
        $this->link_tiktok = $eventner->link_tiktok;
        $this->link_whatsapp = $eventner->link_whatsapp;
        $this->link_livestreaming = $eventner->link_livestreaming;
        $this->registration_status = $eventner->registration_status ?? 'open';
        $this->drawing_code = $eventner->drawing_code;
        $this->scoring_code = $eventner->scoring_code;

        $this->logo = $eventner->logo_event;
        $this->poster = $eventner->poster;

        // Load theme config
        $theme = $eventner->theme_config ?? [];
        $this->theme_preset = $theme['preset'] ?? 'ocean';
        $this->theme_primary = $theme['primary_color'] ?? '#0072FF';
        $this->theme_accent = $theme['accent_color'] ?? '#00D4AA';
    }

    public function save()
    {
        $this->validate([
            'nama_event' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'diselenggarakan_oleh' => 'required|string|max:255',
            'lokasi' => 'required|string|max:255',
            'venue' => 'nullable|string|max:255',
            'tanggal' => 'required|date',
            'tanggal_pendaftaran' => 'nullable|string|max:255',
            'technical_meeting' => 'nullable|string|max:255',
            'tingkat_perlombaan' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'link_instagram' => 'nullable|url|max:255',
            'link_tiktok' => 'nullable|url|max:255',
            'link_whatsapp' => 'nullable|string|max:255',
            'link_livestreaming' => 'nullable|url|max:255',
            'registration_status' => 'required|in:open,booking,closed',
            'drawing_code' => 'nullable|string|max:255',
            'scoring_code' => 'nullable|string|max:255',
            'newLogo' => 'nullable|image|max:2048', 
            'newPoster' => 'nullable|image|max:3072', // allow up to 3MB for poster
        ]);

        $eventner = Eventner::where('user_id', Auth::id())->findOrFail($this->eventnerId);

        if ($this->newLogo) {
            // Delete old logo if exists
            if ($eventner->logo_event && Storage::disk('public')->exists($eventner->logo_event)) {
                Storage::disk('public')->delete($eventner->logo_event);
            }
            
            // Save new logo
            $path = $this->newLogo->store('logos', 'public');
            $eventner->logo_event = $path;
            $this->logo = $path;
        }

        if ($this->newPoster) {
            // Delete old poster if exists
            if ($eventner->poster && Storage::disk('public')->exists($eventner->poster)) {
                Storage::disk('public')->delete($eventner->poster);
            }
            
            // Save new poster
            $path = $this->newPoster->store('posters', 'public');
            $eventner->poster = $path;
            $this->poster = $path;
        }

        $eventner->update([
            'nama_event' => strip_tags($this->nama_event),
            'deskripsi' => strip_tags($this->deskripsi),
            'diselenggarakan_oleh' => strip_tags($this->diselenggarakan_oleh),
            'lokasi' => strip_tags($this->lokasi),
            'venue' => strip_tags($this->venue),
            'tanggal' => $this->tanggal,
            'tanggal_pendaftaran' => strip_tags($this->tanggal_pendaftaran),
            'technical_meeting' => strip_tags($this->technical_meeting),
            'tingkat_perlombaan' => strip_tags($this->tingkat_perlombaan),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'link_instagram' => strip_tags($this->link_instagram),
            'link_tiktok' => strip_tags($this->link_tiktok),
            'link_whatsapp' => strip_tags($this->link_whatsapp),
            'link_livestreaming' => strip_tags($this->link_livestreaming),
            'registration_status' => $this->registration_status,
            'drawing_code' => strip_tags($this->drawing_code),
            'scoring_code' => strip_tags($this->scoring_code),
            'logo_event' => $eventner->logo_event,
            'poster' => $eventner->poster,
            'theme_config' => [
                'preset' => $this->theme_preset,
                'primary_color' => $this->theme_primary,
                'accent_color' => $this->theme_accent,
            ],
        ]);

        $this->newLogo = null; 
        $this->newPoster = null; 

        session()->flash('success', 'Profil Event berhasil diperbarui!');
    }

    public function render()
    {
        return view('livewire.eventner.settings.profile');
    }
}
