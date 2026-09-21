<?php

namespace App\Livewire\Admin\School;

use Livewire\Component;
use App\Models\School;
use App\Models\Registration;
use App\Models\CompetitionCategory;
use App\Models\AssessmentScore;
use App\Models\VoteTransaction;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
class Show extends Component
{
    public $npsn;
    public $schoolInfo;
    public $registrations;

    // Modal ubah kategori
    public $showCategoryModal = false;
    public $editingRegistrationId = null;
    public $editingRegistration = null;
    public $newCategoryId = '';
    /** Kategori milik event registrasi yang sedang diedit, siap render select. */
    public $categoryOptions = [];

    public function mount($npsn)
    {
        $this->npsn = $npsn;
        $this->loadData();
    }

    public function loadData()
    {
        $school = School::find($this->npsn);
        if (!$school) {
            return redirect()->route('admin.schools.index')->with('error', 'Sekolah tidak ditemukan.');
        }

        // children_count dipakai blade untuk menandai kategori induk — tanpa
        // ini tiap baris menembak query exists() sendiri.
        $registrations = Registration::with([
                'eventner',
                'competitionCategory.parent',
                'competitionCategory' => fn ($q) => $q->withCount('children'),
                'participants',
            ])
            ->where('npsn', $this->npsn)
            ->orderBy('created_at', 'desc')
            ->get();

        $this->schoolInfo = [
            'npsn' => $school->npsn,
            'nama_sekolah' => $school->nama_sekolah,
            'logo_sekolah' => $school->logo_sekolah,
            'no_hp' => $school->no_hp,
            'school_email' => $school->school_email,
            'total_registrations' => $registrations->count(),
            'total_participants' => $registrations->sum(fn($r) => $r->participants->count()),
            'events' => $registrations->pluck('eventner.nama_event')->unique()->values(),
        ];

        $this->registrations = $registrations;
    }

    // ────────────────────────────────────────────────
    // Ubah kategori lomba satu pendaftaran
    // ────────────────────────────────────────────────

    /**
     * Kategori pendaftaran bisa nyangkut ke kategori event lain — jalur
     * pendaftaran publik pernah menerima id kategori tanpa memastikan
     * eventnya cocok. Panitia tidak bisa membetulkannya sendiri: halaman
     * kategori & peserta mereka di-scope ke event milik mereka, jadi baris
     * lintas-event tidak muncul di sana. Admin yang memperbaiki.
     */
    public function openCategoryModal($registrationId)
    {
        $reg = Registration::with(['eventner', 'competitionCategory.parent'])->find($registrationId);

        if (!$reg || $reg->npsn !== $this->npsn) {
            session()->flash('error', 'Pendaftaran tidak ditemukan untuk sekolah ini.');
            return;
        }

        $this->editingRegistrationId = $reg->id;
        $this->editingRegistration = $reg;
        $this->newCategoryId = (string) $reg->competition_category_id;

        // Hanya kategori milik event registrasi ini — memindahkan lintas event
        // mengulang masalah yang sama.
        $this->categoryOptions = CompetitionCategory::where('eventner_id', $reg->eventner_id)
            ->with('parent')
            ->orderByRaw('COALESCE(parent_id, id)')
            ->orderBy('sort_order')
            ->get();

        $this->showCategoryModal = true;
    }

    public function closeCategoryModal()
    {
        $this->showCategoryModal = false;
        $this->editingRegistrationId = null;
        $this->editingRegistration = null;
        $this->newCategoryId = '';
        $this->categoryOptions = [];
    }

    public function saveCategory()
    {
        $reg = Registration::find($this->editingRegistrationId);

        if (!$reg || $reg->npsn !== $this->npsn) {
            $this->closeCategoryModal();
            session()->flash('error', 'Pendaftaran tidak ditemukan untuk sekolah ini.');
            return;
        }

        $kategori = CompetitionCategory::where('eventner_id', $reg->eventner_id)
            ->find($this->newCategoryId);

        if (!$kategori) {
            $this->addError('newCategoryId', 'Kategori harus milik event pendaftaran ini.');
            return;
        }

        if ((int) $reg->competition_category_id === (int) $kategori->id) {
            $this->closeCategoryModal();
            return;
        }

        // Nilai juri menempel pada pendaftaran+kategori; pindah setelah dinilai
        // mencampur nilai antar kategori. Sama seperti guard panitia.
        if (AssessmentScore::where('registration_id', $reg->id)->exists()) {
            $this->addError('newCategoryId', 'Pendaftaran ini sudah punya nilai juri. Hapus nilainya dulu di halaman Input Nilai sebelum memindahkan kategori.');
            return;
        }

        $namaLama = $reg->competitionCategory?->full_name ?? '-';

        $reg->update([
            'competition_category_id' => $kategori->id,
            // Nomor undian menempel pada kategori — undian diulang dari nol.
            'urutan_tampil' => null,
        ]);

        $this->closeCategoryModal();
        $this->loadData();

        activity()
            ->performedOn($reg)
            ->withProperties([
                'kategori_lama' => $namaLama,
                'kategori_baru' => $kategori->full_name,
            ])
            ->log('Admin ubah kategori pendaftaran ' . $reg->display_name);

        session()->flash('success', 'Kategori pendaftaran ' . $reg->display_name . ' diubah dari "' . $namaLama . '" ke "' . $kategori->full_name . '".');
    }

    // ────────────────────────────────────────────────
    // Hapus
    // ────────────────────────────────────────────────

    /**
     * Hapus satu pendaftaran. Cascade DB ikut membuang peserta, nilai juri,
     * device token, dan transaksi vote — pendapatan vote yang sudah dibayar
     * tidak bisa dipulihkan, jadi itu diblokir. Pembayaran yang sudah masuk
     * juga diblokir supaya uang tidak hilang tanpa keputusan sadar.
     */
    public function deleteRegistration($registrationId)
    {
        $reg = Registration::find($registrationId);

        if (!$reg || $reg->npsn !== $this->npsn) {
            session()->flash('error', 'Pendaftaran tidak ditemukan untuk sekolah ini.');
            return;
        }

        $voteBerbayar = VoteTransaction::where('registration_id', $reg->id)
            ->where('status', 'PAID')
            ->count();

        if ($voteBerbayar > 0) {
            session()->flash('error', 'Tidak bisa menghapus: ada ' . $voteBerbayar . ' transaksi vote yang sudah dibayar untuk pendaftaran ini. Menghapusnya membuang pendapatan tersebut dari rekap.');
            return;
        }

        if (in_array($reg->payment_status, ['paid', 'pending_verification'], true)) {
            session()->flash('error', 'Tidak bisa menghapus: pendaftaran ini sudah dibayar (status: ' . $reg->payment_status . '). Selesaikan dulu pengembalian dana / verifikasi pembayarannya.');
            return;
        }

        $nilaiJuri = AssessmentScore::where('registration_id', $reg->id)->count();

        if ($nilaiJuri > 0) {
            session()->flash('error', 'Tidak bisa menghapus: ada ' . $nilaiJuri . ' nilai juri untuk pendaftaran ini. Reset nilainya dulu di halaman Input Nilai.');
            return;
        }

        $nama = $reg->display_name;
        $reg->delete();

        $this->loadData();

        session()->flash('success', 'Pendaftaran ' . $nama . ' berhasil dihapus.');
    }

    /**
     * Hapus baris sekolah. Ditahan selama masih ada pendaftaran: baris
     * registrations menyimpan riwayat, pembayaran, dan peserta — menghapusnya
     * demi membersihkan daftar sekolah akan membuang data yang tidak bisa
     * dipulihkan. Bersihkan pendaftarannya dulu satu per satu.
     */
    public function deleteSchool()
    {
        $school = School::find($this->npsn);

        if (!$school) {
            session()->flash('error', 'Sekolah tidak ditemukan.');
            return;
        }

        $jumlah = Registration::where('npsn', $this->npsn)->count();

        if ($jumlah > 0) {
            session()->flash('error', 'Tidak bisa menghapus sekolah: masih ada ' . $jumlah . ' pendaftaran atas nama NPSN ini. Hapus pendaftarannya dulu di tabel Riwayat Pendaftaran.');
            return;
        }

        if ($school->logo_sekolah) {
            Storage::disk('public')->delete($school->logo_sekolah);
        }

        $nama = $school->nama_sekolah;
        $school->delete();

        session()->flash('success', 'Sekolah ' . $nama . ' berhasil dihapus.');

        return redirect()->route('admin.schools.index');
    }

    public function render()
    {
        return view('livewire.admin.school.show')->title('Detail Sekolah - ' . app_name());
    }
}
