<?php

namespace App\Livewire\Eventner\Participant;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Registration;

#[Layout('layouts.admin')]
#[Title('Daftar Peserta - BARIS APP')]
class Index extends Component
{
    public $activeTab = '';
    public $categories = [];

    public $competition_category_id = '';
    
    // Modal form fields
    public $showModal = false;
    public $editId = null;
    public $nama_sekolah = '';
    public $npsn = '';
    public $nama_pelatih = '';
    public $no_hp = '';

    // Verification modal
    public $showVerifyModal = false;
    public $selectedRegistration = null;

    public function mount()
    {
        $eventner = auth()->user()->eventner;
        if ($eventner) {
            $this->categories = $eventner->competitionCategories()->get()->toArray();
        }

        if (count($this->categories) > 0) {
            $this->activeTab = $this->categories[0]['id'];
        }
    }

    public function switchTab($categoryId)
    {
        $this->activeTab = $categoryId;
    }

    public function openModal($categoryId = null)
    {
        $this->resetForm();
        if ($categoryId) {
            $this->competition_category_id = $categoryId;
        }
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->editId = null;
        $this->nama_sekolah = '';
        $this->npsn = '';
        $this->nama_pelatih = '';
        $this->no_hp = '';
        $this->competition_category_id = '';
    }

    public function save()
    {
        $this->validate([
            'nama_sekolah' => 'required|string|max:255',
            'npsn' => 'required|string|max:20',
            'nama_pelatih' => 'required|string|max:255',
            'no_hp' => 'required|string|max:20',
            'competition_category_id' => 'required|exists:competition_categories,id',
        ]);

        $eventner = auth()->user()->eventner;

        if ($this->editId) {
            $reg = Registration::where('eventner_id', $eventner->id)->findOrFail($this->editId);
            $reg->update([
                'nama_sekolah' => strip_tags($this->nama_sekolah),
                'npsn' => strip_tags($this->npsn),
                'nama_pelatih' => strip_tags($this->nama_pelatih),
                'no_hp' => strip_tags($this->no_hp),
                'competition_category_id' => $this->competition_category_id,
            ]);
            session()->flash('success', 'Data pendaftar berhasil diperbarui.');
        } else {
            Registration::create([
                'eventner_id' => $eventner->id,
                'nama_sekolah' => strip_tags($this->nama_sekolah),
                'npsn' => strip_tags($this->npsn),
                'nama_pelatih' => strip_tags($this->nama_pelatih),
                'no_hp' => strip_tags($this->no_hp),
                'competition_category_id' => $this->competition_category_id,
                'status_berkas' => 'Menunggu',
            ]);
            session()->flash('success', 'Sekolah pendaftar berhasil ditambahkan & Magic Link telah dibuat.');
        }

        $this->closeModal();
    }

    public function edit($id)
    {
        $eventner = auth()->user()->eventner;
        $reg = Registration::where('eventner_id', $eventner->id)->findOrFail($id);
        $this->editId = $reg->id;
        $this->nama_sekolah = $reg->nama_sekolah;
        $this->npsn = $reg->npsn;
        $this->nama_pelatih = $reg->nama_pelatih;
        $this->no_hp = $reg->no_hp;
        $this->competition_category_id = $reg->competition_category_id;
        $this->showModal = true;
    }

    public function delete($id)
    {
        $eventner = auth()->user()->eventner;
        Registration::where('eventner_id', $eventner->id)->findOrFail($id)->delete();
        session()->flash('success', 'Data pendaftar berhasil dihapus.');
    }

    public function openVerifyModal($id)
    {
        $eventner = auth()->user()->eventner;
        $this->selectedRegistration = Registration::with('participants')
            ->where('eventner_id', $eventner->id)
            ->findOrFail($id);
        
        $this->showVerifyModal = true;
    }

    public function closeVerifyModal()
    {
        $this->showVerifyModal = false;
        $this->selectedRegistration = null;
    }

    public function verifyStatus($status)
    {
        if (!$this->selectedRegistration) return;

        $updateData = ['status_berkas' => $status];
        
        // Jika ditolak, kembalikan status finalized ke false agar bisa diperbaiki
        if ($status === 'Ditolak') {
            $updateData['is_finalized'] = false;
        }

        $this->selectedRegistration->update($updateData);

        session()->flash('success', 'Status pendaftaran ' . $this->selectedRegistration->nama_sekolah . ' berhasil diubah menjadi ' . $status . '.');
        $this->closeVerifyModal();
    }

    public function render()
    {
        $eventner = auth()->user()->eventner;
        $registrations = $eventner
            ? Registration::with('participants')
                ->where('eventner_id', $eventner->id)
                ->where('competition_category_id', $this->activeTab)
                ->get()
            : collect();

        return view('livewire.eventner.participant.index', [
            'registrations' => $registrations,
        ]);
    }
}
