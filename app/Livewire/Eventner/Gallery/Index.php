<?php

namespace App\Livewire\Eventner\Gallery;

use App\Livewire\Concerns\MelaporKePengguna;
use App\Models\EventGallery;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

#[Layout('layouts.admin')]
class Index extends Component
{
    use MelaporKePengguna;
    use WithFileUploads;

    public $newImage;
    public $caption = '';

    /**
     * Sengaja BUKAN `upload()`: `upload` adalah alias bawaan Livewire
     * (`aliases.upload = "$upload"` di dist/livewire.js) dan proxy `$wire`
     * memeriksa alias SEBELUM method komponen. Method bernama `upload` karena
     * itu tak pernah terpanggil dari `wire:submit="upload"` — Livewire malah
     * menjalankan magic $upload dengan argumen kosong, yang melempar
     * "Cannot read properties of undefined (reading 'name')" di browser.
     */
    public function unggahFoto()
    {
        $this->validate(['newImage' => 'required|image|max:5120']);

        $path = $this->newImage->store('galleries', 'public');

        EventGallery::create([
            'eventner_id' => Auth::user()->eventner->id,
            'image' => $path,
            'caption' => $this->caption ?: null,
            'sort_order' => EventGallery::where('eventner_id', Auth::user()->eventner->id)->max('sort_order') + 1,
        ]);

        $this->reset(['newImage', 'caption']);
        $this->toast('Foto berhasil diunggah.');
    }

    public function delete($id)
    {
        $gal = EventGallery::where('eventner_id', Auth::user()->eventner->id)->findOrFail($id);
        if ($gal->image && Storage::disk('public')->exists($gal->image)) {
            Storage::disk('public')->delete($gal->image);
        }
        $gal->delete();
        $this->toast('Foto dihapus.');
    }

    public function render()
    {
        return view('livewire.eventner.gallery.index', [
            'images' => EventGallery::where('eventner_id', Auth::user()->eventner->id)->orderBy('sort_order')->latest()->get(),
        ])->title('Galeri - ' . app_name());
    }
}
