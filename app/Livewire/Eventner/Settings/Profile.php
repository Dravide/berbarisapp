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
    public $surat_tugas_required = true;
    public $kwitansi_required = true;



    public $logo;
    public $newLogo;

    public $poster;
    public $newPoster;

    // Theme
    public $theme_preset = 'ocean';
    public $theme_primary = '#0072FF';
    public $theme_accent = '#00D4AA';
    public $theme_bg_type = 'solid';
    public $theme_gradient_dir = 'to right';
    public $theme_gradient_color = '#0ea5e9';
    public $theme_bg;
    public $theme_bg_preview;
    public $theme_bg_url;

    // Font
    public $theme_font_sans = 'Inter';
    public $theme_font_display = 'Plus Jakarta Sans';

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
        $this->surat_tugas_required = (bool)($eventner->surat_tugas_required ?? true);
        $this->kwitansi_required = (bool)($eventner->kwitansi_required ?? true);


        $this->logo = $eventner->logo_event;
        $this->poster = $eventner->poster;

        // Load theme config
        $theme = $eventner->theme_config ?? [];
        $this->theme_preset = $theme['preset'] ?? 'ocean';
        $this->theme_primary = $theme['primary_color'] ?? '#0072FF';
        $this->theme_accent = $theme['accent_color'] ?? '#00D4AA';
        $this->theme_bg_type = $theme['bg_type'] ?? 'solid';
        $this->theme_gradient_dir = $theme['gradient_dir'] ?? 'to right';
        $this->theme_gradient_color = $theme['gradient_color'] ?? '#0ea5e9';
        $this->theme_bg_url = $theme['bg_image'] ?? '';
        $this->theme_font_sans = $theme['font_sans'] ?? 'Inter';
        $this->theme_font_display = $theme['font_display'] ?? 'Plus Jakarta Sans';
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
            'surat_tugas_required' => 'required|boolean',
            'kwitansi_required' => 'required|boolean',

            'newLogo' => 'nullable|image|max:2048',
            'newPoster' => 'nullable|image|max:3072', // allow up to 3MB for poster
            'theme_bg' => 'nullable|image|max:2048',
        ]);

        // Auto-compute registration_status from dates
        $now = now();
        $tm = $this->technical_meeting ? \Carbon\Carbon::parse($this->technical_meeting) : null;
        $tglPendaftaran = $this->tanggal_pendaftaran ? \Carbon\Carbon::parse($this->tanggal_pendaftaran) : null;
        $tglEvent = $this->tanggal ? \Carbon\Carbon::parse($this->tanggal) : null;

        if ($tglPendaftaran && $now->gt($tglPendaftaran)) {
            $this->registration_status = 'closed';
        } elseif ($tglEvent && $now->gt($tglEvent)) {
            $this->registration_status = 'closed';
        } elseif ($tm && $now->lt($tm)) {
            $this->registration_status = 'booking';
        } else {
            $this->registration_status = 'open';
        }

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

        if ($this->theme_bg) {
            if ($eventner->theme_config['bg_image'] ?? false) {
                Storage::disk('public')->delete($eventner->theme_config['bg_image']);
            }
            $bgPath = $this->theme_bg->store('themes', 'public');
            $this->theme_bg_url = $bgPath;
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
            'surat_tugas_required' => $this->surat_tugas_required,
            'kwitansi_required' => $this->kwitansi_required,

            'logo_event' => $eventner->logo_event,
            'poster' => $eventner->poster,
            'theme_config' => [
                'preset' => $this->theme_preset,
                'primary_color' => $this->theme_primary,
                'accent_color' => $this->theme_accent,
                'bg_type' => $this->theme_bg_type,
                'gradient_dir' => $this->theme_gradient_dir,
                'gradient_color' => $this->theme_gradient_color,
                'bg_image' => $this->theme_bg_url,
                'font_sans' => $this->theme_font_sans,
                'font_display' => $this->theme_font_display,
            ],
        ]);

        $this->newLogo = null; 
        $this->newPoster = null; 

        session()->flash('success', 'Profil Event berhasil diperbarui!');
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
        return view('livewire.eventner.settings.profile');
    }
}
