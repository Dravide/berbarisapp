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

    public $drawing_code;

    public $logo;
    public $newLogo;

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
        $this->drawing_code = $eventner->drawing_code;

        $this->logo = $eventner->logo_event;
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
            'link_whatsapp' => 'nullable|string|max:255', // WA might be a generic string like a phone number or short link
            'link_livestreaming' => 'nullable|url|max:255',
            'drawing_code' => 'nullable|string|max:255',
            'newLogo' => 'nullable|image|max:2048', // max 2MB
        ]);

        $eventner = Eventner::findOrFail($this->eventnerId);

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

        $eventner->update([
            'nama_event' => $this->nama_event,
            'deskripsi' => $this->deskripsi,
            'diselenggarakan_oleh' => $this->diselenggarakan_oleh,
            'lokasi' => $this->lokasi,
            'venue' => $this->venue,
            'tanggal' => $this->tanggal,
            'tanggal_pendaftaran' => $this->tanggal_pendaftaran,
            'technical_meeting' => $this->technical_meeting,
            'tingkat_perlombaan' => $this->tingkat_perlombaan,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'link_instagram' => $this->link_instagram,
            'link_tiktok' => $this->link_tiktok,
            'link_whatsapp' => $this->link_whatsapp,
            'link_livestreaming' => $this->link_livestreaming,
            'drawing_code' => $this->drawing_code,
            'logo_event' => $eventner->logo_event,
        ]);

        $this->newLogo = null; // reset file input

        session()->flash('success', 'Profil Event berhasil diperbarui!');
    }

    public function render()
    {
        return view('livewire.eventner.settings.profile');
    }
}
