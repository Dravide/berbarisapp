<?php

namespace App\Livewire\Eventner\Participant;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\AssessmentScore;
use App\Models\Registration;

#[Layout('layouts.admin')]
#[Title('Daftar Peserta - BARIS APP')]
class Index extends Component
{
    public $activeTab = '';
    public $categories = [];
    public $search = '';
    public $statusFilter = 'all';

    public $competition_category_id = '';
    public $jumlah_pasukan = 1;

    // Modal form fields
    public $showModal = false;
    public $editId = null;
    public $nama_sekolah = '';
    public $npsn = '';
    public $nama_pelatih = '';
    public $no_hp = '';
    public $school_email = '';

    // Verification modal
    public $showVerifyModal = false;
    public $selectedRegistration = null;

    // Swap pasukan modal
    public $showSwapModal = false;
    public $swapSource = null; // Registration sumber (yang datanya salah)

    public function mount()
    {
        $eventner = auth()->user()->eventner;
        if ($eventner) {
            // Only show child categories (leaf nodes) with parent eager-loaded
            $this->categories = $eventner->competitionCategories()
                ->whereNotNull('parent_id')
                ->with('parent')
                ->get()
                ->toArray();
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
        $this->school_email = '';
        $this->competition_category_id = '';
        $this->jumlah_pasukan = 1;
    }

    public function save()
    {
        $this->validate([
            'competition_category_id' => 'required|exists:competition_categories,id',
            'npsn' => 'required|string|max:20',
            'nama_sekolah' => 'required|string|max:255',
            'no_hp' => 'required|string|max:20',
            'school_email' => 'nullable|email|max:255',
            'nama_pelatih' => 'nullable|string|max:255',
            'jumlah_pasukan' => 'required|integer|min:1',
        ]);

        $eventner = auth()->user()->eventner;

        if ($this->editId) {
            $reg = Registration::where('eventner_id', $eventner->id)->findOrFail($this->editId);
            $reg->update([
                'nama_sekolah' => strip_tags($this->nama_sekolah),
                'npsn' => strip_tags($this->npsn),
                'nama_pelatih' => $this->nama_pelatih ? strip_tags($this->nama_pelatih) : null,
                'no_hp' => strip_tags($this->no_hp),
                'school_email' => $this->school_email ? strip_tags($this->school_email) : null,
                'competition_category_id' => $this->competition_category_id,
            ]);
            session()->flash('success', 'Data pendaftar berhasil diperbarui.');
        } else {
            $letters = range('A', 'Z');
            for ($i = 0; $i < $this->jumlah_pasukan; $i++) {
                $suffix = $this->jumlah_pasukan > 1 ? ' (' . $letters[$i] . ')' : '';
                Registration::create([
                    'eventner_id' => $eventner->id,
                    'nama_sekolah' => strip_tags($this->nama_sekolah) . $suffix,
                    'npsn' => strip_tags($this->npsn),
                    'nama_pelatih' => $this->nama_pelatih ? strip_tags($this->nama_pelatih) : null,
                    'no_hp' => strip_tags($this->no_hp),
                    'school_email' => $this->school_email ? strip_tags($this->school_email) : null,
                    'competition_category_id' => $this->competition_category_id,
                    'status_berkas' => 'Menunggu',
                ]);
            }

            $label = $this->jumlah_pasukan > 1 ? "{$this->jumlah_pasukan} pasukan" : 'pasukan';
            session()->flash('success', "Sekolah pendaftar berhasil ditambahkan ({$label}) & Magic Link telah dibuat.");
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
        $this->school_email = $reg->school_email;
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

    /**
     * Kandidat tukar: pasukan lain dari sekolah yang sama (sama NPSN),
     * satu event, satu kategori lomba. Data pasukan (anggota + danton)
     * hanya bisa bertukar antar pasukan satu sekolah.
     */
    public function getSwapCandidatesProperty()
    {
        if (!$this->swapSource) {
            return collect();
        }

        return Registration::with('participants')
            ->where('eventner_id', $this->swapSource->eventner_id)
            ->where('npsn', $this->swapSource->npsn)
            ->where('competition_category_id', $this->swapSource->competition_category_id)
            ->where('id', '!=', $this->swapSource->id)
            ->orderBy('label_pasukan')
            ->get();
    }

    public function openSwapModal($id)
    {
        $eventner = auth()->user()->eventner;
        $this->swapSource = Registration::where('eventner_id', $eventner->id)
            ->with('participants')
            ->findOrFail($id);
        $this->showSwapModal = true;
    }

    public function closeSwapModal()
    {
        $this->showSwapModal = false;
        $this->swapSource = null;
    }

    /**
     * Tukar data pasukan (anggota + danton) antara 2 registration.
     * Identitas registrasi (magic link, pembayaran, status) tidak disentuh.
     */
    public function swapPasukan($targetId)
    {
        $eventner = auth()->user()->eventner;
        $source = $this->swapSource;

        if (!$source) return;

        $target = Registration::where('eventner_id', $eventner->id)
            ->where('npsn', $source->npsn)
            ->where('competition_category_id', $source->competition_category_id)
            ->findOrFail($targetId);

        // Guard: nilai juri menempel ke registration — tukar setelah dinilai
        // bikin nilai tercampur antar pasukan. Blokir total.
        $hasScores = AssessmentScore::whereIn('registration_id', [$source->id, $target->id])
            ->exists();
        if ($hasScores) {
            session()->flash('error', 'Tukar tidak bisa dilakukan: salah satu pasukan sudah memiliki nilai juri. Hapus nilai dulu di halaman Input Nilai (Reset Nilai).');
            $this->closeSwapModal();
            return;
        }

        \DB::transaction(function () use ($source, $target) {
            // Tukar anggota pasukan: satu UPDATE dengan CASE — tanpa nilai
            // registration_id pernah kosong/illegit (FK tetap valid).
            \DB::table('participants')
                ->whereIn('registration_id', [$source->id, $target->id])
                ->update([
                    'registration_id' => \DB::raw(
                        'CASE registration_id WHEN ' . (int) $source->id . ' THEN ' . (int) $target->id
                        . ' ELSE ' . (int) $source->id . ' END'
                    ),
                ]);

            // Tukar data danton (bagian dari data pasukan yang tertukar)
            [$source->danton_nama, $target->danton_nama] = [$target->danton_nama, $source->danton_nama];
            [$source->danton_nisn, $target->danton_nisn] = [$target->danton_nisn, $source->danton_nisn];
            [$source->danton_foto, $target->danton_foto] = [$target->danton_foto, $source->danton_foto];
            $source->save();
            $target->save();

            // Jejak audit — model Registration tidak me-log kolom danton.
            activity()
                ->performedOn($source)
                ->withProperties(['target_registration_id' => $target->id])
                ->log('Tukar data pasukan: ' . $source->display_name . ' <-> ' . $target->display_name);
        });

        session()->flash('success', "Data pasukan {$source->display_name} dan {$target->display_name} berhasil ditukar.");
        $this->closeSwapModal();
    }

    public function verifyStatus($status)
    {
        if (!$this->selectedRegistration) return;

        // Draft (belum difinalisasi sekolah) tidak boleh diverifikasi
        if (!$this->selectedRegistration->is_finalized) {
            session()->flash('error', 'Pendaftaran ' . $this->selectedRegistration->display_name . ' masih draft. Sekolah belum menekan tombol "Finalisasi" pada portal.');
            $this->closeVerifyModal();
            return;
        }

        $updateData = ['status_berkas' => $status];
        
        // Jika ditolak, kembalikan status finalized ke false agar bisa diperbaiki
        if ($status === 'Ditolak') {
            $updateData['is_finalized'] = false;
        }

        $this->selectedRegistration->update($updateData);

        session()->flash('success', 'Status pendaftaran ' . $this->selectedRegistration->display_name . ' berhasil diubah menjadi ' . $status . '.');
        $this->closeVerifyModal();
    }

    public function render()
    {
        $eventner = auth()->user()->eventner;
        $registrations = $eventner
            ? Registration::with('participants')
                ->where('eventner_id', $eventner->id)
                ->where('competition_category_id', $this->activeTab)
                ->when($this->search !== '', fn ($q) => $q->where(fn ($q) => $q
                    ->where('nama_sekolah', 'like', "%{$this->search}%")
                    ->orWhere('npsn', 'like', "%{$this->search}%")
                    ->orWhere('nama_pelatih', 'like', "%{$this->search}%")))
                ->when($this->statusFilter === 'draft', fn ($q) => $q->where('is_finalized', false))
                ->when($this->statusFilter === 'finalized', fn ($q) => $q->where('is_finalized', true))
                ->when($this->statusFilter === 'booking', fn ($q) => $q->where('status_berkas', 'booking'))
                ->when($this->statusFilter === 'menunggu', fn ($q) => $q->whereIn('status_berkas', ['confirmed', 'Menunggu']))
                ->when($this->statusFilter === 'terverifikasi', fn ($q) => $q->where('status_berkas', 'Terverifikasi'))
                ->when($this->statusFilter === 'ditolak', fn ($q) => $q->where('status_berkas', 'Ditolak'))
                ->get()
            : collect();

        // Summary stats across all registrations in the event
        $allRegs = $eventner
            ? Registration::with('participants')->where('eventner_id', $eventner->id)->get()
            : collect();

        $summary = [
            'total_registrations' => $allRegs->count(),
            'total_anggota' => $allRegs->sum(fn($r) => $r->participants->count()),
            'booking' => $allRegs->where('status_berkas', 'booking')->count(),
            'confirmed' => $allRegs->where('status_berkas', 'confirmed')->count(),
            'verified' => $allRegs->where('status_berkas', 'Terverifikasi')->count(),
            'rejected' => $allRegs->where('status_berkas', 'Ditolak')->count(),
        ];

        // Registrasi yang punya pasukan-pasukan lain dari sekolah yang sama
        // (kandidat tukar data pasukan) — dipetakan per id supaya tombol
        // "Tukar" tidak memicu query per baris.
        $swapCandidateIds = [];
        if ($eventner) {
            $grouped = $allRegs->groupBy(fn($r) => $r->npsn . '|' . $r->competition_category_id);
            foreach ($grouped as $regs) {
                if ($regs->count() > 1) {
                    foreach ($regs as $r) {
                        $swapCandidateIds[$r->id] = true;
                    }
                }
            }
        }

        return view('livewire.eventner.participant.index', [
            'registrations' => $registrations,
            'summary' => $summary,
            'swapCandidateIds' => $swapCandidateIds,
        ]);
    }
}
