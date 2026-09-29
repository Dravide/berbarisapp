<?php

namespace App\Livewire\Eventner\DaftarUlang;

use App\Models\AssessmentScore;
use App\Models\CompetitionGroup;
use App\Models\CompetitionSeries;
use App\Models\Registration;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Meja daftar ulang — satu layar untuk seluruh penetapan hari H.
 *
 * Di sinilah tiga keputusan panitia diketik, tepat saat pasukan berdiri di
 * depan meja:
 *
 *   Hadir   -> menandai pasukan sudah datang (registrations.daftar_ulang_at)
 *   Grup    -> pool peringkat + nomor undian
 *   Seri    -> lembar nilai yang dipakai pasukan itu
 *   Undian  -> nomor urut tampil di dalam grupnya
 *
 * Kenapa digabung satu layar: seri ikut ditetapkan di meja yang sama
 * (keputusan panitia), dan sebelum layar ini ada, satu-satunya sumber seri
 * sebuah registrasi adalah migrasi backfill — tidak ada UI yang menulisnya.
 *
 * Sengaja TIDAK di-gate fitur. Mengunci meja daftar ulang akan menghentikan
 * acara bagi paket gratis, sedangkan setiap kemampuan yang disentuh di sini
 * (peserta, grup, undian) memang sudah boleh dipakai paket gratis — yang
 * di-gate adalah Drawing/Undian sebagai halaman undiannya sendiri.
 */
#[Layout('layouts.admin')]
class Index extends Component
{
    public $eventner;

    /** Tingkat lomba yang sedang dibuka. */
    public $activeTab = '';

    /** Kotak cari: potongan nama sekolah. Meja ini dilayani sambil mengantre. */
    public $search = '';

    public array $categories = [];

    public function mount()
    {
        $this->eventner = Auth::user()->eventner;

        if (! $this->eventner) {
            abort(403, 'Anda belum memiliki data Event terdaftar.');
        }

        $this->categories = $this->eventner->competitionCategories()
            ->whereNotNull('parent_id')
            ->with('parent')
            ->get()
            ->toArray();

        if (count($this->categories) > 0) {
            $this->activeTab = (string) $this->categories[0]['id'];
        }
    }

    /** Grup milik tingkat yang sedang dibuka. */
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

    /** Seri milik tingkat yang sedang dibuka. */
    #[Computed]
    public function series()
    {
        if ($this->activeTab === '') {
            return collect();
        }

        return CompetitionSeries::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->activeTab)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /** Pendaftar tingkat ini, tersaring kotak cari. */
    #[Computed]
    public function participants()
    {
        if ($this->activeTab === '') {
            return collect();
        }

        return Registration::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->activeTab)
            ->when(trim($this->search) !== '', fn ($q) => $q->where('nama_sekolah', 'like', '%' . trim($this->search) . '%'))
            ->orderBy('nama_sekolah')
            ->get();
    }

    /**
     * Belum hadir dihitung dari SELURUH tingkat, bukan dari hasil pencarian.
     * Angka inilah yang dipakai panitia memutuskan kapan meja ditutup —
     * menghitungnya dari hasil saringan akan menyesatkan.
     */
    #[Computed]
    public function belumHadir(): int
    {
        if ($this->activeTab === '') {
            return 0;
        }

        return Registration::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->activeTab)
            ->whereNull('daftar_ulang_at')
            ->count();
    }

    public function switchTab($categoryId)
    {
        $this->activeTab = (string) $categoryId;
        $this->search = '';
        $this->resetErrorBag();
    }

    /** Ganti tingkat lewat wire:model langsung — saringan sekolah tak berlaku lagi. */
    public function updatedActiveTab()
    {
        $this->search = '';
        $this->resetErrorBag();
    }

    /** Registrasi dari DOM wajib milik Event & tingkat yang sedang dibuka. */
    private function findParticipant($registrationId): Registration
    {
        return Registration::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->activeTab)
            ->findOrFail($registrationId);
    }

    /**
     * Tandai sudah/belum datang.
     *
     * Dua arah dalam satu tombol: meja ini juga melayani pembatalan salah
     * centang, dan mengetik ulang jam kedatangan tidak masuk akal.
     */
    public function toggleHadir($registrationId)
    {
        $peserta = $this->findParticipant($registrationId);

        $peserta->update([
            'daftar_ulang_at' => $peserta->daftar_ulang_at ? null : now(),
        ]);

        session()->flash(
            'success',
            $peserta->daftar_ulang_at
                ? "{$peserta->display_name} ditandai sudah daftar ulang."
                : "Tanda daftar ulang {$peserta->display_name} dibatalkan."
        );
    }

    /**
     * Pindah grup dari DOM. Grup wajib milik tingkat yang sedang dibuka.
     *
     * Nomor undian ikut dikosongkan: undian disusun per grup, jadi nomor lama
     * milik grup lama — persis seperti Bagi Grup di halaman Peserta.
     */
    public function setGroup($registrationId, $groupId)
    {
        $peserta = $this->findParticipant($registrationId);

        $groupId = ($groupId === '' || $groupId === null) ? null : (string) $groupId;

        if ($groupId !== null) {
            $ada = CompetitionGroup::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $this->activeTab)
                ->find($groupId);

            if (! $ada) {
                $this->addError('grup', 'Grup tidak ditemukan pada tingkat lomba ini.');

                return;
            }
        }

        $berubah = (string) $peserta->competition_group_id !== (string) $groupId;

        $peserta->update([
            'competition_group_id' => $groupId,
            'urutan_tampil' => $berubah ? null : $peserta->urutan_tampil,
        ]);

        if ($berubah) {
            session()->flash('success', "{$peserta->display_name} dipindah grup; nomor undiannya dikosongkan.");
        }

        $this->resetErrorBag('grup');
    }

    /**
     * Tetapkan seri dari DOM. Seri wajib milik tingkat yang sedang dibuka.
     *
     * BLOKIR bila pasukan ini sudah punya nilai juri. Seri menentukan rubrik,
     * jadi memindahkannya setelah nilai masuk membuat nilai lama menempel di
     * kriteria yang tak lagi dihuni pasukan itu — nilainya lenyap diam-diam
     * dari rekap, sementara barisnya tetap tampak benar. Dibanding menghapus
     * nilai diam-diam, panitia diminta menghapusnya sendiri di Input Nilai.
     */
    public function setSeries($registrationId, $seriesId)
    {
        $peserta = $this->findParticipant($registrationId);

        $seriesId = ($seriesId === '' || $seriesId === null) ? null : (string) $seriesId;

        if ($seriesId !== null) {
            $ada = CompetitionSeries::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $this->activeTab)
                ->find($seriesId);

            if (! $ada) {
                $this->addError('seri', 'Seri tidak ditemukan pada tingkat lomba ini.');

                return;
            }
        }

        // Seri yang sama bukan pemindahan — tak ada yang perlu diblokir.
        if ((string) $peserta->competition_series_id === (string) $seriesId) {
            $this->resetErrorBag('seri');

            return;
        }

        $sudahDinilai = AssessmentScore::where('registration_id', $peserta->id)->exists();

        if ($sudahDinilai) {
            $this->addError(
                'seri',
                "Seri {$peserta->display_name} tidak bisa dipindah: sudah ada nilai juri masuk. Hapus nilai dulu di halaman Input Nilai."
            );

            return;
        }

        $peserta->update(['competition_series_id' => $seriesId]);

        session()->flash('success', "{$peserta->display_name} mendapat seri baru.");
        $this->resetErrorBag('seri');
    }

    /**
     * Nomor undian dari DOM.
     *
     * Cek bentrok mengikuti Drawing\Index::assignManual(): nomor hanya unik di
     * dalam satu grup. Pasukan bergrup diperiksa terhadap grupnya, yang belum
     * bergrup terhadap sesama yang belum bergrup — kalau tidak, Grup A #1 dan
     * Grup B #1 akan saling dianggap bentrok.
     */
    public function setUndian($registrationId, $nomor)
    {
        $peserta = $this->findParticipant($registrationId);

        $nomor = ($nomor === '' || $nomor === null) ? null : (int) $nomor;

        if ($nomor !== null && $nomor < 1) {
            $this->addError('undian', 'Nomor undian minimal 1.');

            return;
        }

        if ($nomor !== null) {
            $bentrok = Registration::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $this->activeTab)
                ->where('id', '!=', $peserta->id)
                ->where('urutan_tampil', $nomor)
                ->when(
                    $peserta->competition_group_id,
                    fn ($q) => $q->where('competition_group_id', $peserta->competition_group_id),
                    fn ($q) => $q->whereNull('competition_group_id')
                )
                ->first();

            if ($bentrok) {
                $this->addError('undian', "Nomor undian {$nomor} sudah dipakai {$bentrok->display_name}.");

                return;
            }
        }

        $peserta->update(['urutan_tampil' => $nomor]);

        session()->flash(
            'success',
            $nomor
                ? "{$peserta->display_name} mendapat nomor undian #{$nomor}."
                : "Nomor undian {$peserta->display_name} dikosongkan."
        );

        $this->resetErrorBag('undian');
    }

    public function render()
    {
        return view('livewire.eventner.daftar-ulang.index')
            ->title('Daftar Ulang - ' . $this->eventner->nama_event);
    }
}
