<?php

namespace App\Livewire\Eventner\Participant;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\AssessmentScore;
use App\Models\CompetitionCategory;
use App\Models\Registration;
use App\Models\RegistrationField;
use App\Support\PendaftarImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

#[Layout('layouts.admin')]
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

    // Bagi Grup modal
    public $showGroupModal = false;
    /** [registration_id => competition_group_id|null] — draf, baru ditulis saat Simpan. */
    public $groupAssignments = [];
    /** Berapa peserta yang dipindah DAN sudah punya nilai (peringatan undian). */
    public $groupMoveWarnCount = 0;

    public function mount()
    {
        $eventner = auth()->user()->eventner;
        if ($eventner) {
            // Satu definisi dengan formulir pendaftaran: tingkat lomba, atau
            // induk lama tanpa anak. Menyaring `parent_id` saja membuat event
            // dengan kategori flat lama tampil tanpa tab sama sekali —
            // pendaftarannya ada, tapi tidak pernah muncul di halaman ini.
            $this->categories = $eventner->competitionCategories()
                ->selectable()
                ->with('parent')
                ->get()
                ->toArray();
        }

        if (count($this->categories) > 0) {
            $this->activeTab = $this->categories[0]['id'];
        }
    }

    /** Tab aktif juga datang dari DOM — di-scope ke kategori yang boleh dipakai. */
    public function switchTab($categoryId)
    {
        if ($this->kategoriTerpilih($categoryId)) {
            $this->activeTab = $categoryId;
        }
    }

    /**
     * Beri tahu komponen import (nested) bahwa kategori tujuan berubah.
     *
     * Tanpa ini, panitia yang mengganti kategori setelah modal import terbuka
     * akan menyimpan ke kategori lama — komponen nested tidak ikut re-render
     * saat properti induknya berubah.
     */
    public function updatedActiveTab($value)
    {
        $this->dispatch('pesan:ganti-kategori', id: $value);
    }

    public function openModal($categoryId = null)
    {
        $this->resetForm();

        if ($categoryId) {
            // Kategori bawaan modal harus tingkat lomba. Tab induk (dipakai
            // untuk cetak QR / daftar ulang semua tingkat) bukan tujuan
            // pendaftaran, jadi tidak dioper ke pilihan kategori.
            $this->competition_category_id = $this->kategoriTerpilih($categoryId)?->id ?? '';
        }

        $this->showModal = true;
    }

    /**
     * Kategori milik event panitia yang boleh jadi tujuan pendaftaran: tingkat
     * lomba, atau induk lama tanpa anak.
     *
     * Logikanya hidup di PendaftarImport supaya halaman import memakai definisi
     * yang sama — id kategori datang dari DOM, jadi `exists` polos tidak cukup.
     */
    private function kategoriTerpilih($categoryId): ?\App\Models\CompetitionCategory
    {
        return PendaftarImport::kategoriUntukPendaftaran(auth()->user()->eventner, $categoryId);
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

    /**
     * Field yang boleh diisi panitia di modal ini: kolom identitas registrations
     * yang punya baris builder aktif DAN punya properti di komponen ini (Livewire
     * menolak memvalidasi nama properti yang tidak ada). Field yang dimatikan
     * panitia tidak dirender dan tidak ikut divalidasi/ditulis.
     */
    public function fieldsModal()
    {
        return RegistrationField::forEventner(auth()->user()->eventner)
            ->reject(fn ($f) => $f->isFile() || $f->isGroup())
            ->filter(fn ($f) => $f->builtin_source !== null && property_exists($this, $f->builtin_source));
    }

    /**
     * Aturan validasi dibangun dari baris builder, bukan literal.
     *
     * Pemetaannya hidup di PendaftarImport supaya modal ini dan import Excel
     * tidak bisa berbeda diam-diam.
     */
    private function aturanFieldModal(): array
    {
        return PendaftarImport::aturanDari($this->fieldsModal());
    }

    /** Pesan error memakai label panitia, bukan nama kolom. */
    public function pesanFieldModal(): array
    {
        return PendaftarImport::pesanDari($this->fieldsModal());
    }

    public function save()
    {
        $eventner = auth()->user()->eventner;

        $this->validate(array_merge([
            // Kategori harus milik eventner ini, dan harus tingkat lomba.
            // `exists` polos menerima id kategori tenant lain, sehingga
            // pendaftar kita bisa dicemplungkan ke kategori event orang;
            // tanpa selectable() id induk juga ikut diterima.
            'competition_category_id' => [
                'required',
                Rule::exists('competition_categories', 'id')
                    ->where('eventner_id', $eventner->id)
                    ->where(function ($q) {
                        $q->whereNotNull('parent_id');
                        $q->orWhere(function ($sq) {
                            $sq->whereNull('parent_id')
                                ->whereNotExists(function ($sub) {
                                    $sub->select(DB::raw(1))
                                        ->from('competition_categories as anak')
                                        ->whereColumn('anak.parent_id', 'competition_categories.id');
                                });
                        });
                    }),
            ],
            'jumlah_pasukan' => 'required|integer|min:1',
        ], $this->aturanFieldModal()), $this->pesanFieldModal());

        if ($this->editId) {
            $reg = Registration::where('eventner_id', $eventner->id)->findOrFail($this->editId);

            $pindahKategori = (int) $reg->competition_category_id !== (int) $this->competition_category_id;

            if ($pindahKategori) {
                // Nomor undian dan nilai menempel pada kategori lomba.
                // Memindahkan peserta ke kategori lain tanpa membersihkan
                // urutan_tampil meninggalkan nomor undian kategori lama yang
                // bentrok dengan peserta kategori baru.
                $punyaNilai = AssessmentScore::where('registration_id', $reg->id)->exists();

                if ($punyaNilai) {
                    $this->addError('competition_category_id', 'Peserta sudah punya nilai juri. Hapus nilainya dulu di halaman Input Nilai sebelum memindahkan kategori.');
                    return;
                }
            }

            // Hanya field yang masih aktif di builder yang ditulis — mematikan
            // field di builder tidak boleh menghapus isian lama diam-diam.
            $data = [];

            foreach ($this->fieldsModal() as $field) {
                $nilai = $this->{$field->builtin_source} ?? null;
                $data[$field->builtin_source] = $nilai !== null && $nilai !== '' ? strip_tags((string) $nilai) : null;
            }

            $reg->update([
                ...$data,
                'competition_category_id' => $this->competition_category_id,
                // Ganti kategori = undian diulang dari nol untuk peserta ini,
                // dan grup lama ikut dilepas karena grup milik kategori lama
                // (kalau dibiarkan, peserta Grup A tingkat lama bisa tampil di
                // grup tingkat barunya).
                ...($pindahKategori ? ['urutan_tampil' => null, 'competition_group_id' => null] : []),
            ]);
            session()->flash('success', 'Data pendaftar berhasil diperbarui.');
        } else {
            $data = [];

            foreach ($this->fieldsModal() as $field) {
                $nilai = $this->{$field->builtin_source} ?? null;
                $data[$field->builtin_source] = $nilai !== null && $nilai !== '' ? strip_tags((string) $nilai) : null;
            }

            $letters = range('A', 'Z');
            for ($i = 0; $i < $this->jumlah_pasukan; $i++) {
                $suffix = $this->jumlah_pasukan > 1 ? ' (' . $letters[$i] . ')' : '';
                Registration::create([
                    ...$data,
                    'eventner_id' => $eventner->id,
                    'nama_sekolah' => strip_tags($this->nama_sekolah) . $suffix,
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
        $reg = Registration::where('eventner_id', $eventner->id)->findOrFail($id);

        // vote_transactions.registration_id cascade — menghapus pendaftar ikut
        // membuang transaksi vote yang SUDAH DIBAYAR, sehingga pendapatan yang
        // sudah masuk hilang dari rekap dan tidak bisa dipulihkan.
        $voteBerbayar = \App\Models\VoteTransaction::where('registration_id', $reg->id)
            ->where('status', 'PAID')
            ->count();

        if ($voteBerbayar > 0) {
            session()->flash('error', 'Tidak bisa menghapus: ada ' . $voteBerbayar . ' transaksi vote yang sudah dibayar untuk pendaftar ini. Menghapusnya akan membuang pendapatan tersebut dari rekap.');
            return;
        }

        $reg->delete();
        session()->flash('success', 'Data pendaftar berhasil dihapus.');
    }

    public function openVerifyModal($id)
    {
        $eventner = auth()->user()->eventner;
        $this->selectedRegistration = Registration::with(['participants', 'fieldValues'])
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

    // ── Bagi Grup ──────────────────────────────────────────────────────

    /** Grup milik kategori yang sedang dibuka di tab. */
    public function getGroupsProperty()
    {
        if (!$this->activeTab) {
            return collect();
        }

        return \App\Models\CompetitionGroup::where('eventner_id', auth()->user()->eventner->id)
            ->where('competition_category_id', $this->activeTab)
            ->orderBy('sort_order')->orderBy('id')
            ->get();
    }

    public function openGroupModal()
    {
        $groups = $this->groups;

        if ($groups->isEmpty()) {
            session()->flash('error', 'Tingkat ini belum punya grup. Buat grupnya dulu di halaman Kategori Lomba.');
            return;
        }

        $eventner = auth()->user()->eventner;

        $this->groupAssignments = Registration::where('eventner_id', $eventner->id)
            ->where('competition_category_id', $this->activeTab)
            ->pluck('competition_group_id', 'id')
            ->map(fn ($gid) => $gid === null ? '' : (string) $gid)
            ->all();

        $this->groupMoveWarnCount = 0;
        $this->showGroupModal = true;
    }

    public function closeGroupModal()
    {
        $this->showGroupModal = false;
        $this->groupAssignments = [];
        $this->groupMoveWarnCount = 0;
    }

    /** Pindahkan satu peserta ke grup lain (atau keluar dari semua grup). */
    public function setGroup($registrationId, $groupId)
    {
        $eventner = auth()->user()->eventner;

        $reg = Registration::where('eventner_id', $eventner->id)
            ->where('competition_category_id', $this->activeTab)
            ->findOrFail($registrationId);

        $groupId = $groupId === '' || $groupId === null ? null : (int) $groupId;

        // Grup tujuan wajib milik tingkat ini — id datang dari DOM.
        if ($groupId !== null && !$this->groups->contains('id', $groupId)) {
            abort(403);
        }

        $this->groupAssignments[$reg->id] = $groupId === null ? '' : (string) $groupId;
        $this->groupMoveWarnCount = $this->hitungPerpindahanBernilai();
    }

    /** Bagi rata otomatis berdasarkan urutan nama sekolah (round-robin). */
    public function autoSplitGroups()
    {
        $ids = Registration::where('eventner_id', auth()->user()->eventner->id)
            ->where('competition_category_id', $this->activeTab)
            ->orderBy('nama_sekolah')
            ->pluck('id')
            ->all();

        $groupIds = $this->groups->pluck('id')->all();

        if (empty($groupIds)) {
            return;
        }

        foreach ($ids as $i => $id) {
            $this->groupAssignments[$id] = (string) $groupIds[$i % count($groupIds)];
        }

        $this->groupMoveWarnCount = $this->hitungPerpindahanBernilai();
    }

    public function clearGroups()
    {
        foreach (array_keys($this->groupAssignments) as $id) {
            $this->groupAssignments[$id] = '';
        }

        $this->groupMoveWarnCount = $this->hitungPerpindahanBernilai();
    }

    /**
     * Berapa peserta yang akan berpindah grup PADAHAL sudah punya nilai juri.
     *
     * Ganti grup tidak diblokir (berbeda dengan pindah kategori): nilainya
     * tetap sah, hanya nomor undiannya yang dihapus karena urutan tampil
     * disusun per grup. Angkanya ditampilkan sebagai peringatan, bukan error.
     */
    private function hitungPerpindahanBernilai(): int
    {
        $berubah = [];

        foreach ($this->groupAssignments as $regId => $gid) {
            $berubah[$regId] = $gid === '' || $gid === null ? null : (int) $gid;
        }

        if (empty($berubah)) {
            return 0;
        }

        $lama = Registration::whereIn('id', array_keys($berubah))
            ->pluck('competition_group_id', 'id');

        $pindah = [];
        foreach ($berubah as $regId => $baru) {
            $sebelum = $lama[$regId] ?? null;
            $sebelum = $sebelum === null ? null : (int) $sebelum;

            if ($sebelum !== $baru) {
                $pindah[] = (int) $regId;
            }
        }

        if (empty($pindah)) {
            return 0;
        }

        return AssessmentScore::whereIn('registration_id', $pindah)->distinct()->count('registration_id');
    }

    public function saveGroups()
    {
        $eventner = auth()->user()->eventner;
        $groupIds = $this->groups->pluck('id')->all();

        $dipindah = 0;

        foreach ($this->groupAssignments as $regId => $gid) {
            $reg = Registration::where('eventner_id', $eventner->id)
                ->where('competition_category_id', $this->activeTab)
                ->find($regId);

            if (!$reg) {
                continue;
            }

            $baru = $gid === '' || $gid === null ? null : (int) $gid;

            // Id grup dari DOM wajib milik tingkat ini.
            if ($baru !== null && !in_array($baru, array_map('intval', $groupIds), true)) {
                continue;
            }

            $sebelum = $reg->competition_group_id === null ? null : (int) $reg->competition_group_id;

            if ($sebelum === $baru) {
                continue;
            }

            $reg->update([
                'competition_group_id' => $baru,
                // Nomor undian disusun per grup, jadi pindah grup = undian
                // peserta ini diulang. Nilai juri tidak disentuh.
                'urutan_tampil' => null,
            ]);
            $dipindah++;
        }

        session()->flash('success', $dipindah > 0
            ? "Pembagian grup disimpan: {$dipindah} peserta dipindah, nomor undiannya direset."
            : 'Tidak ada perubahan pembagian grup.');

        $this->closeGroupModal();
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
        ])->title('Daftar Peserta - ' . app_name());
    }
}
