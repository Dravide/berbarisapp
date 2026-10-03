<?php

namespace App\Livewire\Eventner\Drawing;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Locked;
use App\Models\Registration;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\CompetitionRoundRegistration;

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

    /**
     * Babak yang sedang diundi. '' = penyisihan / tingkat tanpa babak.
     * Babak final diundi di pool-nya sendiri (satu tingkat, semua finalis) dan
     * nomornya TIDAK diambil dari nomor undian grup.
     */
    public $activeRoundId = '';

    /** Daftar grup tingkat terpilih (untuk pemilih di view). */
    public $groups = [];

    /** Daftar babak tingkat terpilih (untuk pemilih di view). */
    public $rounds = [];

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
        $this->rounds = $this->roundsOfActiveTab();

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
     * Babak milik tingkat yang sedang diundi.
     */
    private function roundsOfActiveTab(): array
    {
        if ($this->activeTab === '') {
            return [];
        }

        return CompetitionRound::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $this->activeTab)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn ($r) => [
                'id' => (string) $r->id,
                'name' => $r->name,
                'isFinal' => $r->isFinal(),
            ])
            ->all();
    }

    /** Babak yang sedang diundi, atau null bila tingkat ini tanpa babak. */
    private function activeRound(): ?CompetitionRound
    {
        if ($this->activeRoundId === '') {
            return null;
        }

        return CompetitionRound::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $this->activeTab)
            ->find($this->activeRoundId);
    }

    /** Apakah yang sedang diundi adalah babak final. */
    private function isFinalRound(): bool
    {
        return (bool) $this->activeRound()?->isFinal();
    }

    /**
     * Registrasi pada tingkat + grup + babak yang sedang dibuka. Nomor undian
     * penyisihan hanya unik di dalam satu grup; nomor undian final unik
     * se-tingkat, jadi di babak final saringan grup dimatikan dan pool-nya
     * diambil dari baris kelolosan babak itu.
     */
    private function scopedRegistrations()
    {
        return Registration::where('eventner_id', $this->eventnerId)
            ->where('competition_category_id', $this->activeTab)
            ->when(! $this->isFinalRound() && $this->activeGroupId !== '', function ($q) {
                $q->where('competition_group_id', $this->activeGroupId);
            })
            ->when($this->isFinalRound(), function ($q) {
                $q->whereIn('id', CompetitionRoundRegistration::where('eventner_id', $this->eventnerId)
                    ->where('competition_round_id', $this->activeRound()->id)
                    ->pluck('registration_id'));
            });
    }

    /**
     * Nomor yang sudah terpakai di babak ini — per grup di penyisihan,
     * se-tingkat di final.
     */
    private function nomorTerpakai(): array
    {
        if (! $this->isFinalRound()) {
            return Registration::where('eventner_id', $this->eventnerId)
                ->where('competition_category_id', $this->activeTab)
                ->when($this->activeGroupId !== '', fn ($q) => $q->where('competition_group_id', $this->activeGroupId))
                ->whereNotNull('urutan_tampil')
                ->pluck('urutan_tampil')
                ->map(fn ($n) => (int) $n)
                ->all();
        }

        return CompetitionRoundRegistration::where('eventner_id', $this->eventnerId)
            ->where('competition_round_id', $this->activeRound()->id)
            ->whereNotNull('urutan_tampil')
            ->pluck('urutan_tampil')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    /** Tulis nomor undian ke tempat yang benar untuk babak ini. */
    private function simpanNomor($registrationId, int $nomor): void
    {
        if (! $this->isFinalRound()) {
            Registration::whereKey($registrationId)->update(['urutan_tampil' => $nomor]);

            return;
        }

        CompetitionRoundRegistration::where('competition_round_id', $this->activeRound()->id)
            ->where('registration_id', $registrationId)
            ->update(['urutan_tampil' => $nomor]);
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
        $this->activeRoundId = '';
        $this->groups = $this->groupsOfActiveTab();
        $this->rounds = $this->roundsOfActiveTab();
        $this->spinResult = null;
        $this->isSpinning = false;
        $this->loadNextSchool();
    }

    /**
     * Ganti babak. Babak dari DOM wajib milik tingkat yang sedang diundi.
     * Mengganti babak mengosongkan grup: pool final satu tingkat.
     */
    public function switchRound($roundId)
    {
        $this->activeRoundId = '';
        $this->activeGroupId = '';

        $ada = collect($this->rounds)->firstWhere('id', (string) $roundId);

        if ($roundId !== '' && $roundId !== null && ! $ada) {
            $this->addError('activeRoundId', 'Babak tidak ditemukan pada tingkat lomba ini.');

            return;
        }

        if ($ada) {
            $this->activeRoundId = (string) $ada['id'];
        }

        $this->resetErrorBag('activeRoundId');
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

        // Pool final satu tingkat, jadi grup asal tidak menyaring apa pun —
        // biarkan pemilihnya tidak berpengaruh daripada diam-diam mengubah
        // daftar yang sedang diundi.
        if ($this->isFinalRound()) {
            return;
        }

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
        // Di babak final "belum diundi" berarti baris babaknya masih kosong,
        // bukan kolom registrasi — nomor fase grup tidak dianggap undian final.
        $query = $this->scopedRegistrations();

        if ($this->isFinalRound()) {
            $query->whereNotIn('id', CompetitionRoundRegistration::where('eventner_id', $this->eventnerId)
                ->where('competition_round_id', $this->activeRound()->id)
                ->whereNotNull('urutan_tampil')
                ->pluck('registration_id'));
        } else {
            $query->whereNull('urutan_tampil');
        }

        $this->currentSchool = $query->inRandomOrder()->first();

        $this->allDrawn = is_null($this->currentSchool);
        $this->spinResult = null;
    }

    public function spin()
    {
        if (!$this->isAuthenticated) return;
        if (!$this->currentSchool || $this->isSpinning) return;

        // Hitung nomor urut yang belum terpakai — per grup di penyisihan, karena
        // nomor undian fase grup hanya unik di dalam grupnya sendiri; se-tingkat
        // di final, karena pool finalnya satu tingkat.
        $usedNumbers = $this->nomorTerpakai();

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

        $this->simpanNomor($this->currentSchool->id, (int) $this->spinResult);

        session()->flash('success', $this->currentSchool->nama_sekolah . ' mendapat urutan tampil #' . $this->spinResult);

        $this->loadNextSchool();
    }

    public function resetDrawing()
    {
        if (!$this->isAuthenticated) return;

        if ($this->isFinalRound()) {
            // Satu tingkat, tanpa saringan grup — sama dengan pool undiannya.
            // Hanya kolom babak yang dikosongkan: nomor undian fase grup tetap.
            CompetitionRoundRegistration::where('eventner_id', $this->eventnerId)
                ->where('competition_round_id', $this->activeRound()->id)
                ->update(['urutan_tampil' => null]);

            session()->flash('success', 'Semua hasil undian babak final pada kategori ini telah di-reset.');

            return;
        }

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

        // Di babak final daftar terundinya disusun dari baris babak, bukan dari
        // kolom registrasi — nomor fase grup bukan nomor undian final.
        if ($this->isFinalRound()) {
            $nomorPerRegistrasi = CompetitionRoundRegistration::where('eventner_id', $this->eventnerId)
                ->where('competition_round_id', $this->activeRound()->id)
                ->whereNotNull('urutan_tampil')
                ->pluck('urutan_tampil', 'registration_id');

            $drawnSchools = Registration::whereIn('id', $nomorPerRegistrasi->keys())
                ->get()
                ->each(fn ($reg) => $reg->urutan_tampil = $nomorPerRegistrasi->get($reg->id))
                ->sortBy('urutan_tampil')
                ->values();
        } else {
            $drawnSchools = $this->scopedRegistrations()
                ->whereNotNull('urutan_tampil')
                ->orderBy('urutan_tampil')
                ->get();
        }

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
