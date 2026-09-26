<?php

namespace App\Livewire\Eventner\Drawing;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Locked;
use App\Models\Registration;
use App\Models\CompetitionGroup;

use App\Models\Eventner;

#[Layout('layouts.scoreboard')]
class Spin extends Component
{
    public $slug;

    // Dikunci server-side — client tidak boleh mutasi ke tenant lain.
    #[Locked]
    public $eventnerId;

    // Kode yang benar-benar sudah diverifikasi pada sesi ini. null = belum ada.
    // Disimpan sebagai nilai kode, bukan sekadar boolean, supaya bisa
    // dibandingkan ulang dengan DB: kalau panitia mengganti kodenya, nilai
    // lama tidak lagi cocok dan sesi otomatis tertutup.
    #[Locked]
    public $grantedCode = null;

    // Dihitung ulang dari DB tiap request (lihat boot()). Dipakai view untuk
    // memilih tampil terkunci atau isi halaman.
    #[Locked]
    public $isAuthenticated = false;

    public $activeTab = '';
    public $categories = [];
    public $currentSchool = null;
    public $spinResult = null;
    public $isSpinning = false;
    public $inputCode = '';
    public $allDrawn = false;

    /**
     * Grup yang sedang diundi. '' = seluruh tingkat (perilaku lama).
     */
    public $activeGroupId = '';

    /** Daftar grup tingkat terpilih (untuk pemilih di view). */
    public $groups = [];

    public function mount($slug = null)
    {
        $resolved = app()->bound('current_eventner') ? app('current_eventner') : null;
        if ($resolved) {
            $this->slug = $resolved->slug;
            $eventner = $resolved;
        } else {
            $this->slug = $slug;
            $eventner = Eventner::where('slug', $slug)->firstOrFail();
        }
        $this->eventnerId = $eventner->id;

        if (!$eventner->drawing_code) {
            $this->grantedCode = '';
        }
        // Kalau kode sudah dipasang, halaman terkunci sampai verifyCode()
        // dijalankan.
        $this->boot(); // boot() bawaan Livewire jalan sebelum mount, saat
                       // eventnerId masih kosong — jadi dihitung ulang di sini
                       // supaya render pertama sudah memakai nilai yang benar.

        if ($eventner) {
            $this->categories = $eventner->competitionCategories()
                ->whereNotNull('parent_id')
                ->with('parent')
                ->get()
                ->toArray();
        }

        if (count($this->categories) > 0) {
            $this->activeTab = $this->categories[0]['id'];
        }

        $this->groups = $this->groupsOfActiveTab();

        $this->loadNextSchool();
    }

    /**
     * Grup milik tingkat yang sedang diundi.
     */
    private function groupsOfActiveTab(): array
    {
        if ($this->activeTab === '') {
            return [];
        }

        return CompetitionGroup::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $this->activeTab)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn ($g) => ['id' => (string) $g->id, 'name' => $g->name])
            ->all();
    }

    /**
     * Registrasi pada tingkat + grup yang sedang dibuka. Nomor undian hanya
     * unik di dalam satu grup, jadi semua hitungan nomor wajib lewat sini.
     */
    private function scopedRegistrations()
    {
        return Registration::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $this->activeTab)
            ->when($this->activeGroupId !== '', function ($q) {
                $q->where('competition_group_id', $this->activeGroupId);
            });
    }

    /**
     * Segarkan status gerbang dari DB sebelum aksi apa pun dijalankan.
     *
     * Dulu gerbangnya cuma properti $isAuthenticated yang dikirim di
     * snapshot Livewire. Snapshot itu ditandatangani, tapi isinya berasal
     * dari request pertama: halaman publik dibuka saat kode belum dipasang
     * (true), panitia lalu memasang kode, dan snapshot lama tetap membawa
     * true — gerbangnya tidak pernah menutup lagi. Karena itu kode dibaca
     * ulang dari DB, dan yang dianggap sah hanya sesi yang kodenya masih
     * sama dengan kode yang berlaku sekarang.
     */
    public function boot()
    {
        if (!$this->eventnerId) {
            return;
        }

        $kodeBerlaku = Eventner::whereKey($this->eventnerId)->value('drawing_code');

        $this->isAuthenticated = !$kodeBerlaku || $this->grantedCode === $kodeBerlaku;
    }

    public function switchTab($categoryId)
    {
        $this->activeTab = $categoryId;
        $this->activeGroupId = '';
        $this->groups = $this->groupsOfActiveTab();
        $this->spinResult = null;
        $this->isSpinning = false;
        $this->loadNextSchool();
    }

    /**
     * Ganti grup. Grup dari DOM wajib milik tingkat yang sedang diundi —
     * kalau tidak, panitia bisa menarik peserta event lain ke undian ini.
     */
    public function switchGroup($groupId)
    {
        $this->activeGroupId = '';

        if ($groupId !== '' && $groupId !== null) {
            $ada = CompetitionGroup::where('eventner_id', $this->eventnerId)
                ->where('competition_category_id', $this->activeTab)
                ->find($groupId);

            if (! $ada) {
                $this->addError('activeGroupId', 'Grup tidak ditemukan pada tingkat lomba ini.');

                return;
            }

            $this->activeGroupId = (string) $ada->id;
        }

        $this->resetErrorBag('activeGroupId');
        $this->spinResult = null;
        $this->isSpinning = false;
        $this->loadNextSchool();
    }

    public function loadNextSchool()
    {
        $this->currentSchool = $this->scopedRegistrations()
            ->whereNull('urutan_tampil')
            ->inRandomOrder()
            ->first();

        $this->allDrawn = is_null($this->currentSchool);
        $this->spinResult = null;
    }

    public function spin()
    {
        if (!$this->isAuthenticated) return;
        if (!$this->currentSchool || $this->isSpinning) return;

        // Hitung nomor urut yang belum terpakai — per grup, karena nomor
        // undian hanya unik di dalam grupnya sendiri.
        $usedNumbers = $this->scopedRegistrations()
            ->whereNotNull('urutan_tampil')
            ->pluck('urutan_tampil')
            ->toArray();

        $category = \App\Models\CompetitionCategory::where('eventner_id', $this->eventnerId)
            ->find($this->activeTab);
        if (!$category) return;

        // Batas nomor = jumlah peserta yang benar-benar ada. Dulu memakai
        // kuota kategori, jadi kuota 5 dengan 8 pendaftar menyisakan 3
        // peserta yang tidak akan pernah dapat nomor undian.
        $jumlahPeserta = $this->scopedRegistrations()->count();

        $totalInCategory = max($jumlahPeserta, count($usedNumbers));

        $availableNumbers = array_diff(range(1, max(1, $totalInCategory)), $usedNumbers);

        if (empty($availableNumbers)) return;

        // Pilih secara acak dari yang tersedia
        $this->spinResult = collect($availableNumbers)->random();
    }

    public function saveResult()
    {
        if (!$this->isAuthenticated) return;
        if (!$this->currentSchool || !$this->spinResult) return;

        $this->currentSchool->update([
            'urutan_tampil' => $this->spinResult,
        ]);

        session()->flash('success', $this->currentSchool->nama_sekolah . ' mendapat urutan tampil #' . $this->spinResult);

        $this->loadNextSchool();
    }

    public function resetDrawing()
    {
        if (!$this->isAuthenticated) return;

        // Level tingkat, sama dengan halaman Drawing: satu grup sudah bernilai
        // berarti undian tingkat ini tidak lagi cocok di grup mana pun.
        Registration::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $this->activeTab)
            ->update(['urutan_tampil' => null]);

        session()->flash('success', 'Semua hasil undian pada kategori ini telah di-reset.');
        $this->loadNextSchool();
    }

    public function verifyCode()
    {
        $kodeBerlaku = Eventner::whereKey($this->eventnerId)->value('drawing_code');

        if (!$kodeBerlaku || $this->inputCode === $kodeBerlaku) {
            $this->grantedCode = (string) $kodeBerlaku;
            $this->inputCode = '';
            $this->boot();
        } else {
            $this->addError('inputCode', 'Kode akses salah!');
        }
    }

    public function render()
    {
        $eventner = Eventner::findOrFail($this->eventnerId);

        $drawnSchools = $this->scopedRegistrations()
                ->whereNotNull('urutan_tampil')
                ->orderBy('urutan_tampil')
                ->get();

        $category = \App\Models\CompetitionCategory::where('eventner_id', $eventner->id)
            ->find($this->activeTab);
        $totalSchools = $this->activeGroupId !== ''
            ? $this->scopedRegistrations()->count()
            : ($category->kuota ?? $this->scopedRegistrations()->count());

        return view('livewire.eventner.drawing.spin', [
            'drawnSchools' => $drawnSchools,
            'totalSchools' => $totalSchools,
            'eventner' => $eventner,
        ])->layoutData(['eventner' => $eventner])->title('Pengundian Urutan Tampil - ' . app_name());
    }
}
