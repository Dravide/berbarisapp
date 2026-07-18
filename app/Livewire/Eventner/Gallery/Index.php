<?php

namespace App\Livewire\Eventner\Gallery;

use App\Models\EventGallery;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithFileUploads;

    public $newImage;
    public $caption = '';

    public function upload()
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
        session()->flash('success', 'Foto berhasil diunggah.');
    }

    public function delete($id)
    {
        $gal = EventGallery::where('eventner_id', Auth::user()->eventner->id)->findOrFail($id);
        if ($gal->image && Storage::disk('public')->exists($gal->image)) {
            Storage::disk('public')->delete($gal->image);
        }
        $gal->delete();
        session()->flash('success', 'Foto dihapus.');
    }

    public function render()
    {
        return view('livewire.eventner.gallery.index', [
            'images' => EventGallery::where('eventner_id', Auth::user()->eventner->id)->orderBy('sort_order')->latest()->get(),
        ])->title('Galeri - BARIS APP');
    }
}
