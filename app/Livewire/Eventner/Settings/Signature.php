<?php

namespace App\Livewire\Eventner\Settings;

use App\Models\Eventner;
use App\Models\EventnerSignature;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

#[Layout('layouts.admin')]
#[Title('TTD & Stempel - BARIS APP')]
class Signature extends Component
{
    use WithFileUploads;

    public $name = '';
    public $image; // upload baru (PNG)

    public $signatureMode = 'qr'; // 'qr' | 'image'
    public $activeSignatureId;

    protected $eventnerId;

    public function boot()
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) abort(403);
        $this->eventnerId = $eventner->id;
    }

    public function mount()
    {
        $eventner = Eventner::findOrFail($this->eventnerId);
        $this->signatureMode = $eventner->signature_mode ?? 'qr';
        $this->activeSignatureId = $eventner->active_signature_id;
    }

    public function getSignaturesProperty()
    {
        return EventnerSignature::where('eventner_id', $this->eventnerId)
            ->orderBy('created_at')
            ->get();
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:100',
            'image' => 'required|image|mimes:png|max:2048',
        ], [
            'image.required' => 'File PNG wajib dipilih.',
            'image.image' => 'File harus berupa gambar.',
            'image.mimes' => 'File harus berformat PNG (disarankan transparan).',
            'image.max' => 'Ukuran file maksimal 2MB.',
        ]);

        $path = $this->image->store('signatures', 'public');

        EventnerSignature::create([
            'eventner_id' => $this->eventnerId,
            'name' => strip_tags($this->name),
            'image' => $path,
        ]);

        $this->reset(['name', 'image']);
        session()->flash('success', 'TTD/Stempel berhasil diunggah.');
    }

    public function selectSignature($id)
    {
        $sig = EventnerSignature::where('eventner_id', $this->eventnerId)->findOrFail($id);

        $eventner = Eventner::findOrFail($this->eventnerId);
        $eventner->active_signature_id = $sig->id;
        $eventner->signature_mode = 'image';
        $eventner->save();

        $this->activeSignatureId = $sig->id;
        $this->signatureMode = 'image';

        session()->flash('success', "TTD/Stempel '{$sig->name}' dipakai pada dokumen (invoice, kwitansi).");
    }

    public function useQrMode()
    {
        $eventner = Eventner::findOrFail($this->eventnerId);
        $eventner->signature_mode = 'qr';
        $eventner->save();

        $this->signatureMode = 'qr';

        session()->flash('success', 'Mode QR otomatis dipakai pada dokumen.');
    }

    public function delete($id)
    {
        $sig = EventnerSignature::where('eventner_id', $this->eventnerId)->findOrFail($id);

        // Hapus file fisik
        if ($sig->image && Storage::disk('public')->exists($sig->image)) {
            Storage::disk('public')->delete($sig->image);
        }

        // Bila yang aktif dihapus, mode balik ke QR
        $eventner = Eventner::findOrFail($this->eventnerId);
        if ($eventner->active_signature_id == $sig->id) {
            $eventner->active_signature_id = null;
            $eventner->signature_mode = 'qr';
            $eventner->save();

            $this->activeSignatureId = null;
            $this->signatureMode = 'qr';
        }

        $sig->delete();
        session()->flash('success', 'TTD/Stempel berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.eventner.settings.signature');
    }
}
