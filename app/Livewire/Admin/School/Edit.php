<?php

namespace App\Livewire\Admin\School;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\School;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Storage;

#[Layout('layouts.admin')]
#[Title('Edit Sekolah - BARIS APP')]
class Edit extends Component
{
    use WithFileUploads;

    public $npsn;
    public $nama_sekolah;
    public $no_hp;
    public $school_email;
    public $existing_logo;
    public $newLogo;

    public function mount($npsn)
    {
        $this->npsn = $npsn;
        $school = School::find($npsn);

        if (!$school) {
            return redirect()->route('admin.schools.index')->with('error', 'Sekolah tidak ditemukan.');
        }

        $this->nama_sekolah = $school->nama_sekolah;
        $this->no_hp = $school->no_hp;
        $this->school_email = $school->school_email;
        $this->existing_logo = $school->logo_sekolah;
    }

    public function save()
    {
        $this->validate([
            'nama_sekolah' => 'required|string|max:255',
            'no_hp' => 'nullable|string|max:255',
            'school_email' => 'nullable|email|max:255',
            'newLogo' => 'nullable|image|mimes:jpg,jpeg,png,svg,gif,webp|max:3072',
        ]);

        $logoPath = $this->existing_logo;

        if ($this->newLogo) {
            // Hapus logo lama
            if ($this->existing_logo) {
                Storage::disk('public')->delete($this->existing_logo);
            }

            // Resize & konversi ke PNG via GD
            $resource = imagecreatefromstring(file_get_contents($this->newLogo->getRealPath()));
            if (!$resource) {
                // Fallback: simpan asli
                $logoPath = $this->newLogo->store('logos', 'public');
            } else {
                $width = imagesx($resource);
                $height = imagesy($resource);
                $maxSize = 512;
                if ($width > $maxSize || $height > $maxSize) {
                    $ratio = min($maxSize / $width, $maxSize / $height);
                    $newWidth = (int)($width * $ratio);
                    $newHeight = (int)($height * $ratio);
                    $resized = imagecreatetruecolor($newWidth, $newHeight);
                    imagesavealpha($resized, true);
                    $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
                    imagefill($resized, 0, 0, $transparent);
                    imagecopyresampled($resized, $resource, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    imagedestroy($resource);
                    $resource = $resized;
                }
                $path = 'logos/' . uniqid() . '.png';
                imagepng($resource, Storage::disk('public')->path($path));
                imagedestroy($resource);
                $logoPath = $path;
            }
        }

        School::where('npsn', $this->npsn)->update([
            'nama_sekolah' => $this->nama_sekolah,
            'no_hp' => $this->no_hp,
            'school_email' => $this->school_email,
            'logo_sekolah' => $logoPath,
        ]);

        session()->flash('success', 'Data sekolah berhasil diperbarui.');

        return redirect()->route('admin.schools.show', $this->npsn);
    }

    public function render()
    {
        return view('livewire.admin.school.edit');
    }
}
