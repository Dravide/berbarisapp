<?php

namespace App\Livewire\Eventner\Drawing;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Registration;
use App\Models\CompetitionCategory;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithPagination;

    public $eventner;
    public $activeTab = '';
    public $categories = [];

    // Manual input state
    public $manualRegistrationId = null;
    public $manualUrutan = null;

    public function mount()
    {
        $this->eventner = Auth::user()->eventner;

        if (!$this->eventner) {
            abort(403, 'Anda belum memiliki data Event terdaftar.');
        }

        $this->categories = $this->eventner->competitionCategories()->get()->toArray();

        if (count($this->categories) > 0) {
            $this->activeTab = $this->categories[0]['id'];
        }
    }

    public function switchTab($categoryId)
    {
        $this->activeTab = $categoryId;
        $this->manualRegistrationId = null;
        $this->manualUrutan = null;
    }

    public function assignManual()
    {
        $this->validate([
            'manualRegistrationId' => 'required|exists:registrations,id',
            'manualUrutan' => 'required|integer|min:1',
        ]);

        $registration = Registration::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->activeTab)
            ->findOrFail($this->manualRegistrationId);

        // Check if number already taken
        $existingNumber = Registration::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->activeTab)
            ->where('urutan_tampil', $this->manualUrutan)
            ->where('id', '!=', $registration->id)
            ->first();

        if ($existingNumber) {
            $this->addError('manualUrutan', "Nomor urut {$this->manualUrutan} sudah digunakan oleh {$existingNumber->nama_sekolah}.");
            return;
        }

        $registration->update([
            'urutan_tampil' => $this->manualUrutan,
        ]);

        session()->flash('success', "{$registration->nama_sekolah} mendapat urutan tampil #{$this->manualUrutan}.");
        $this->manualRegistrationId = null;
        $this->manualUrutan = null;
    }

    public function removeDrawing($id)
    {
        $registration = Registration::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->activeTab)
            ->findOrFail($id);

        $registration->update(['urutan_tampil' => null]);
        session()->flash('success', "Urutan tampil {$registration->nama_sekolah} telah dihapus.");
    }

    public function resetDrawing()
    {
        Registration::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->activeTab)
            ->update(['urutan_tampil' => null]);

        session()->flash('success', 'Semua hasil undian pada kategori ini telah di-reset.');
    }

    public function render()
    {
        $drawnResults = Registration::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->activeTab)
            ->whereNotNull('urutan_tampil')
            ->orderBy('urutan_tampil')
            ->get();

        $undrawnParticipants = Registration::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->activeTab)
            ->whereNull('urutan_tampil')
            ->orderBy('nama_sekolah')
            ->get();

        return view('livewire.eventner.drawing.index', [
            'drawnResults' => $drawnResults,
            'undrawnParticipants' => $undrawnParticipants,
        ])->title('Hasil Drawing - ' . $this->eventner->nama_event);
    }
}
