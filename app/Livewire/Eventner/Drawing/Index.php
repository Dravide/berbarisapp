<?php

namespace App\Livewire\Eventner\Drawing;

use App\Traits\FeatureGatedComponent;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Registration;
use App\Models\AssessmentCategory;
use App\Models\AssessmentScore;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\CompetitionRoundRegistration;
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
     * Babak yang sedang diundi. '' = penyisihan / tingkat tanpa babak (perilaku
     * lama). Babak final punya undiannya SENDIRI — nomor final tidak diambil
     * dari nomor undian grup, jadi panitia bisa mengundi ulang final tanpa
     * merusak undian fase grup.
     */
    public $activeRoundId = '';

    /**
     * Babak milik tingkat yang sedang dibuka.
     */
    #[Computed]
    public function rounds()
    {
        if ($this->activeTab === '') {
            return collect();
        }

        return CompetitionRound::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->activeTab)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /** Babak yang sedang diundi, atau null bila tingkat ini tanpa babak. */
    private function activeRound(): ?CompetitionRound
    {
        if ($this->activeRoundId === '') {
            return null;
        }

        return $this->rounds->firstWhere('id', (int) $this->activeRoundId);
    }

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
     * Terapkan saringan tingkat + grup + babak ke query registrasi.
     *
     * Di babak final saringan grup SENGAJA dimatikan: pool undian final adalah
     * satu tingkat (semua finalis, apa pun grup asalnya), supaya nomornya unik
     * se-tingkat dan cocok dengan daftar panel nilai yang juga mengosongkan
     * grup saat chip Final dibuka.
     */
    private function scopedRegistrations()
    {
        return Registration::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->activeTab)
            ->when(! $this->isFinalRound() && $this->activeGroupId !== '', function ($q) {
                $q->where('competition_group_id', $this->activeGroupId);
            })
            ->when($this->isFinalRound(), function ($q) {
                $q->with('roundRegistrations')
                    ->whereIn('id', CompetitionRoundRegistration::where('eventner_id', $this->eventner->id)
                        ->where('competition_round_id', $this->activeRound()->id)
                        ->pluck('registration_id'));
            });
    }

    /** Apakah yang sedang diundi adalah babak final. */
    private function isFinalRound(): bool
    {
        return (bool) $this->activeRound()?->isFinal();
    }

    /**
     * Tulis nomor undian ke tempat yang benar untuk babak ini.
     */
    private function simpanNomor(Registration $registration, ?int $nomor): void
    {
        $round = $this->activeRound();

        if (! $round || ! $round->isFinal()) {
            $registration->update(['urutan_tampil' => $nomor]);

            return;
        }

        CompetitionRoundRegistration::where('competition_round_id', $round->id)
            ->where('registration_id', $registration->id)
            ->update(['urutan_tampil' => $nomor]);
    }

    /**
     * Nomor yang sudah terpakai di babak ini.
     *
     * Penyisihan: per GRUP, karena nomor undian fase grup hanya unik di dalam
     * grupnya. Final: se-TINGKAT, karena pool finalnya satu tingkat.
     */
    private function nomorTerpakai(): array
    {
        $round = $this->activeRound();

        if (! $round || ! $round->isFinal()) {
            return $this->scopedRegistrations()
                ->whereNotNull('urutan_tampil')
                ->pluck('urutan_tampil')
                ->map(fn ($n) => (int) $n)
                ->all();
        }

        return CompetitionRoundRegistration::where('eventner_id', $this->eventner->id)
            ->where('competition_round_id', $round->id)
            ->whereNotNull('urutan_tampil')
            ->pluck('urutan_tampil')
            ->map(fn ($n) => (int) $n)
            ->all();
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
        $this->activeRoundId = '';
        $this->manualRegistrationId = null;
        $this->manualUrutan = null;
        $this->dispatch('reinit-select2');
    }

    /**
     * Ganti tingkat lewat wire:model langsung (tanpa switchTab) — grup & babak
     * lama milik tingkat lama, jadi wajib dilepas.
     */
    public function updatedActiveTab()
    {
        if ($this->activeGroupId !== '') {
            $this->activeGroupId = '';
        }

        $this->activeRoundId = '';
        $this->manualRegistrationId = null;
        $this->manualUrutan = null;
    }

    /**
     * Ganti babak. Babak dari DOM wajib milik tingkat yang sedang dibuka —
     * kalau tidak, panitia bisa mengundi babak tingkat lain dari layar ini.
     * Mengganti babak selalu mengosongkan pilihan grup: pool final satu
     * tingkat, jadi grup asal tidak lagi relevan.
     */
    public function switchRound($roundId)
    {
        $this->activeRoundId = '';
        $this->activeGroupId = '';

        if ($roundId !== '' && $roundId !== null) {
            $ada = $this->rounds->firstWhere('id', (int) $roundId);

            if (! $ada) {
                $this->addError('activeRoundId', 'Babak tidak ditemukan pada tingkat lomba ini.');

                return;
            }

            $this->activeRoundId = (string) $ada->id;
        }

        $this->manualRegistrationId = null;
        $this->manualUrutan = null;
        $this->resetErrorBag('activeRoundId');
        $this->dispatch('reinit-select2');
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

        // Check if number already taken — di babak ini, bukan di babak lain.
        // Nomor yang sama di babak berbeda bukan bentrok: itu justru inti
        // perubahan ini.
        if (in_array((int) $this->manualUrutan, $this->nomorTerpakai(), true)) {
            $pemakai = $this->nomorPemakai((int) $this->manualUrutan, $registration->id);

            $this->addError('manualUrutan', $pemakai
                ? "Nomor urut {$this->manualUrutan} sudah digunakan oleh {$pemakai}."
                : "Nomor urut {$this->manualUrutan} sudah digunakan.");
            return;
        }

        $this->simpanNomor($registration, (int) $this->manualUrutan);

        session()->flash('success', "{$registration->display_name} mendapat urutan tampil #{$this->manualUrutan}.");
        $this->manualRegistrationId = null;
        $this->manualUrutan = null;
        $this->dispatch('reinit-select2');
    }

    /**
     * Nama pemakai nomor undian itu di babak ini, untuk pesan galat. null bila
     * nomornya terpakai peserta di luar daftar yang sedang tampil.
     */
    private function nomorPemakai(int $nomor, $kecualiRegistrationId): ?string
    {
        $round = $this->activeRound();

        if (! $round || ! $round->isFinal()) {
            return $this->scopedRegistrations()
                ->where('urutan_tampil', $nomor)
                ->where('id', '!=', $kecualiRegistrationId)
                ->first()?->display_name;
        }

        $registrationId = CompetitionRoundRegistration::where('eventner_id', $this->eventner->id)
            ->where('competition_round_id', $round->id)
            ->where('urutan_tampil', $nomor)
            ->where('registration_id', '!=', $kecualiRegistrationId)
            ->value('registration_id');

        return $registrationId
            ? Registration::find($registrationId)?->display_name
            : null;
    }

    public function removeDrawing($id)
    {
        $registration = $this->scopedRegistrations()->findOrFail($id);

        $this->simpanNomor($registration, null);
        session()->flash('success', "Urutan tampil {$registration->display_name} telah dihapus.");
    }

    public function resetDrawing()
    {
        $round = $this->activeRound();

        // Guard yang sama dengan Tukar Pasukan: urutan tampil menempel ke
        // registrasi, dan mengganti nomor setelah nilai masuk membuat undian
        // tidak lagi cocok dengan penilaian yang sudah berjalan.
        //
        // Di babak final nilainya disaring per BABAK FINAL, bukan seluruh
        // tingkat: nilai fase grup tidak membuat undian final tidak cocok, dan
        // tanpa saringan ini panitia tak akan pernah bisa mengundi ulang final
        // setelah penyisihan dinilai.
        $sudahDinilai = AssessmentScore::where('eventner_id', $this->eventner->id)
            ->whereHas('registration', fn ($q) => $q->where('competition_category_id', $this->activeTab))
            ->when($round, fn ($q) => $q->whereIn(
                'assessment_criteria_id',
                AssessmentCategory::where('eventner_id', $this->eventner->id)
                    ->where('competition_round_id', $round->id)
                    ->with('subCategories.criterias')
                    ->get()
                    ->flatMap(fn ($cat) => $cat->subCategories->flatMap(fn ($sub) => $sub->criterias->pluck('id')))
            ))
            ->exists();

        if ($sudahDinilai) {
            session()->flash('error', 'Undian tidak bisa di-reset: sudah ada nilai juri pada babak ini. Hapus nilai dulu di halaman Input Nilai.');
            return;
        }

        if ($round && $round->isFinal()) {
            // Satu tingkat, tanpa saringan grup — sama dengan pool undiannya.
            CompetitionRoundRegistration::where('eventner_id', $this->eventner->id)
                ->where('competition_round_id', $round->id)
                ->update(['urutan_tampil' => null]);

            session()->flash('success', 'Semua hasil undian babak ' . $round->name . ' pada tingkat ini telah di-reset.');

            return;
        }

        Registration::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->activeTab)
            ->update(['urutan_tampil' => null]);

        session()->flash('success', 'Semua hasil undian pada kategori ini telah di-reset.');
    }

    public function render()
    {
        // Di babak final undiannya hidup di baris babak, bukan di kolom
        // registrasi — jadi daftar terundinya disusun dari sana. Peserta yang
        // belum diundi tetap muncul di kolom Input Manual dengan nomor kosong.
        $round = $this->activeRound();

        if ($round && $round->isFinal()) {
            $nomorPerRegistrasi = CompetitionRoundRegistration::where('eventner_id', $this->eventner->id)
                ->where('competition_round_id', $round->id)
                ->pluck('urutan_tampil', 'registration_id');

            $peserta = $this->scopedRegistrations()->orderBy('nama_sekolah')->get();

            $drawnResults = $peserta
                ->filter(fn ($reg) => $nomorPerRegistrasi->get($reg->id) !== null)
                ->sortBy(fn ($reg) => (int) $nomorPerRegistrasi->get($reg->id))
                ->values();

            $undrawnParticipants = $peserta
                ->filter(fn ($reg) => $nomorPerRegistrasi->get($reg->id) === null)
                ->values();
        } else {
            $drawnResults = $this->scopedRegistrations()
                ->whereNotNull('urutan_tampil')
                ->orderBy('urutan_tampil')
                ->get();

            $undrawnParticipants = $this->scopedRegistrations()
                ->whereNull('urutan_tampil')
                ->orderBy('nama_sekolah')
                ->get();
        }

        return view('livewire.eventner.drawing.index', [
            'drawnResults' => $drawnResults,
            'undrawnParticipants' => $undrawnParticipants,
            // Blade mencetak nomor dari sumber yang sama dengan tabelnya; di
            // babak final kolom registrasi tidak berlaku.
            'nomorBabak' => ($round && $round->isFinal())
                ? CompetitionRoundRegistration::where('eventner_id', $this->eventner->id)
                    ->where('competition_round_id', $round->id)
                    ->pluck('urutan_tampil', 'registration_id')
                : null,
        ])->title('Hasil Drawing - ' . $this->eventner->nama_event);
    }
}
