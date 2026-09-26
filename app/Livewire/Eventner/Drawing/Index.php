<?php

namespace App\Livewire\Eventner\Drawing;

use App\Traits\FeatureGatedComponent;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Registration;
use App\Models\AssessmentScore;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithPagination;
    use FeatureGatedComponent;

    protected string $requiredFeature = 'drawing';

    public $eventner;
    public $activeTab = '';
    public $categories = [];
    public $drawing_code = '';

    /**
     * Grup yang sedang diundi. '' = seluruh tingkat (perilaku lama).
     * Nomor undian hanya unik di dalam satu grup — kalau tidak, Grup A #1 dan
     * Grup B #1 bertabrakan dan cek bentroknya tidak menangkap.
     */
    public $activeGroupId = '';

    /**
     * Grup milik tingkat yang sedang dibuka.
     */
    #[Computed]
    public function groups()
    {
        if ($this->activeTab === '') {
            return collect();
        }

        return CompetitionGroup::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->activeTab)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Terapkan saringan tingkat + grup ke query registrasi.
     */
    private function scopedRegistrations()
    {
        return Registration::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->activeTab)
            ->when($this->activeGroupId !== '', function ($q) {
                $q->where('competition_group_id', $this->activeGroupId);
            });
    }

    // Manual input state
    public $manualRegistrationId = null;
    public $manualUrutan = null;

    public function mount()
    {
        $this->bootFeatureGate();
        $this->eventner = Auth::user()->eventner;

        if (!$this->eventner) {
            abort(403, 'Anda belum memiliki data Event terdaftar.');
        }

        $this->categories = $this->eventner->competitionCategories()
            ->whereNotNull('parent_id')
            ->with('parent')
            ->get()
            ->toArray();
        $this->drawing_code = $this->eventner->drawing_code ?? '';

        if (count($this->categories) > 0) {
            $this->activeTab = $this->categories[0]['id'];
        }
    }

    public function saveDrawingCode()
    {
        $this->eventner->update(['drawing_code' => $this->drawing_code ?: null]);
        session()->flash('success', 'Kode proteksi pengundian berhasil disimpan.');
    }

    public function switchTab($categoryId)
    {
        $this->activeTab = $categoryId;
        $this->activeGroupId = '';
        $this->manualRegistrationId = null;
        $this->manualUrutan = null;
        $this->dispatch('reinit-select2');
    }

    /**
     * Ganti tingkat lewat wire:model langsung (tanpa switchTab) — grup lama
     * milik tingkat lama, jadi wajib dilepas.
     */
    public function updatedActiveTab()
    {
        if ($this->activeGroupId !== '') {
            $this->activeGroupId = '';
        }

        $this->manualRegistrationId = null;
        $this->manualUrutan = null;
    }

    /**
     * Grup dari DOM wajib milik tingkat yang sedang dibuka.
     */
    public function switchGroup($groupId)
    {
        $this->activeGroupId = '';

        if ($groupId !== '' && $groupId !== null) {
            $ada = CompetitionGroup::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $this->activeTab)
                ->find($groupId);

            if (! $ada) {
                $this->addError('activeGroupId', 'Grup tidak ditemukan pada tingkat lomba ini.');

                return;
            }

            $this->activeGroupId = (string) $ada->id;
        }

        $this->manualRegistrationId = null;
        $this->manualUrutan = null;
        $this->resetErrorBag('activeGroupId');
        $this->dispatch('reinit-select2');
    }

    public function assignManual()
    {
        $this->validate([
            'manualRegistrationId' => 'required|exists:registrations,id',
            'manualUrutan' => 'required|integer|min:1',
        ]);

        $registration = $this->scopedRegistrations()->findOrFail($this->manualRegistrationId);

        // Check if number already taken
        $existingNumber = $this->scopedRegistrations()
            ->where('urutan_tampil', $this->manualUrutan)
            ->where('id', '!=', $registration->id)
            ->first();

        if ($existingNumber) {
            $this->addError('manualUrutan', "Nomor urut {$this->manualUrutan} sudah digunakan oleh {$existingNumber->display_name}.");
            return;
        }

        $registration->update([
            'urutan_tampil' => $this->manualUrutan,
        ]);

        session()->flash('success', "{$registration->display_name} mendapat urutan tampil #{$this->manualUrutan}.");
        $this->manualRegistrationId = null;
        $this->manualUrutan = null;
        $this->dispatch('reinit-select2');
    }

    public function removeDrawing($id)
    {
        $registration = $this->scopedRegistrations()->findOrFail($id);

        $registration->update(['urutan_tampil' => null]);
        session()->flash('success', "Urutan tampil {$registration->display_name} telah dihapus.");
    }

    public function resetDrawing()
    {
        // Guard yang sama dengan Tukar Pasukan: urutan tampil menempel ke
        // registrasi, dan mengganti nomor setelah nilai masuk membuat undian
        // tidak lagi cocok dengan penilaian yang sudah berjalan.
        // Sengaja di level TINGKAT, bukan grup: nilai satu grup sudah cukup
        // membuat undian tingkat ini tidak lagi cocok di grup mana pun.
        $sudahDinilai = AssessmentScore::where('eventner_id', $this->eventner->id)
            ->whereHas('registration', fn ($q) => $q->where('competition_category_id', $this->activeTab))
            ->exists();

        if ($sudahDinilai) {
            session()->flash('error', 'Undian tidak bisa di-reset: sudah ada nilai juri pada kategori ini. Hapus nilai dulu di halaman Input Nilai.');
            return;
        }

        Registration::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->activeTab)
            ->update(['urutan_tampil' => null]);

        session()->flash('success', 'Semua hasil undian pada kategori ini telah di-reset.');
    }

    public function render()
    {
        $drawnResults = $this->scopedRegistrations()
            ->whereNotNull('urutan_tampil')
            ->orderBy('urutan_tampil')
            ->get();

        $undrawnParticipants = $this->scopedRegistrations()
            ->whereNull('urutan_tampil')
            ->orderBy('nama_sekolah')
            ->get();

        return view('livewire.eventner.drawing.index', [
            'drawnResults' => $drawnResults,
            'undrawnParticipants' => $undrawnParticipants,
        ])->title('Hasil Drawing - ' . $this->eventner->nama_event);
    }
}
