<?php

namespace App\Livewire\Public;

use Livewire\Component;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\CompetitionRoundRegistration;
use App\Models\Eventner;
use Livewire\Attributes\Layout;

#[Layout('layouts.frontend')]
class EventParticipant extends Component
{
    public $eventner;

    /**
     * Lingkup peserta yang sedang dilihat.
     *
     * '' = seluruh peserta (perilaku lama). Selain itu berisi kunci lingkup
     * 'grup-{id}' atau 'babak-{id}' — sama seperti halaman kategori juara,
     * dan alasannya sama: satu pilihan mewakili sepasang (grup, babak), jadi
     * id mentahnya tidak bisa dipakai sebagai nilai pilihan.
     */
    public $selectedScopeId = '';

    public function mount($slug = null)
    {
        $resolved = app()->bound('current_eventner') ? app('current_eventner') : null;
        if ($resolved) {
            $this->eventner = $resolved;
            // Subdomain: load relasi if not yet loaded
            $this->eventner->loadMissing(['competitionCategories' => function ($q) {
                    $q->whereNotNull('parent_id')->orderBy('sort_order');
                }, 'competitionCategories.registrations' => function ($q) {
                    $q->where('status_berkas', '!=', 'dibatalkan')
                      ->orderBy('urutan_tampil', 'asc');
                }, 'competitionCategories.registrations.participants',
                   'competitionCategories.groups',
                   'competitionCategories.rounds']);
        } else {
            $this->eventner = Eventner::with(['competitionCategories' => function ($q) {
                $q->whereNotNull('parent_id')->orderBy('sort_order');
            }, 'competitionCategories.registrations' => function ($q) {
                $q->where('status_berkas', '!=', 'dibatalkan')
                  ->orderBy('urutan_tampil', 'asc');
            }, 'competitionCategories.registrations.participants',
               'competitionCategories.groups',
               'competitionCategories.rounds'])
                ->approved()->where('slug', $slug)->firstOrFail();
        }
    }

    /** Tingkat lomba (anak), urut sesuai sort_order. */
    private function levels()
    {
        return $this->eventner->competitionCategories
            ->filter(fn ($c) => ! is_null($c->parent_id))
            ->sortBy('sort_order')
            ->values();
    }

    /**
     * Lingkup yang bisa dipilih di halaman ini: tiap grup, dan tiap babak
     * final yang sudah punya finalis.
     *
     * Babak penyisihan tidak berdiri sendiri sebagai pilihan — lingkupnya
     * sudah terwakili grup. Babak final ikut karena ia satu-satunya lingkup
     * yang pesertanya BUKAN himpunan bagian dari satu grup: finalis diambil
     * dari semua grup, jadi tidak ada pilihan grup yang mewakilinya.
     *
     * Nama tingkat ditempelkan hanya bila lebih dari satu tingkat punya grup —
     * kalau tidak, "Grup A" muncul dua kali di dropdown dengan arti berbeda.
     */
    public function scopeOptions(): \Illuminate\Support\Collection
    {
        $levels = $this->levels();
        $levelsWithGroups = $levels->filter(fn ($l) => $l->groups->isNotEmpty())->count();

        $scopes = collect();

        foreach ($levels as $level) {
            foreach ($level->groups->sortBy('sort_order') as $group) {
                $scopes->push([
                    'key' => 'grup-'.$group->id,
                    'label' => $levelsWithGroups > 1
                        ? $group->name.' — '.$level->name
                        : $group->name,
                    'group_id' => (string) $group->id,
                    'round_id' => '',
                    'level_id' => $level->id,
                ]);
            }
        }

        foreach ($levels as $level) {
            foreach ($level->rounds->sortBy('sort_order') as $round) {
                if (! $round->isFinal()) {
                    continue;
                }

                // Babak final tanpa finalis tidak menambah pilihan apa pun:
                // memilihnya hanya menghasilkan halaman kosong.
                $ada = CompetitionRoundRegistration::where('eventner_id', $this->eventner->id)
                    ->where('competition_round_id', $round->id)
                    ->exists();

                if (! $ada) {
                    continue;
                }

                $scopes->push([
                    'key' => 'babak-'.$round->id,
                    'label' => $round->name.' (Finalis)',
                    'group_id' => '',
                    'round_id' => (string) $round->id,
                    'level_id' => $level->id,
                ]);
            }
        }

        return $scopes;
    }

    /**
     * Ganti lingkup peserta.
     *
     * Nilainya kunci dari daftar, bukan id mentah dari DOM: yang divalidasi
     * adalah apakah kunci itu benar-benar ada di daftar lingkup event ini.
     * Tanpa itu, id grup dari event lain bisa dipakai membaca daftar peserta
     * yang bukan milik halaman ini.
     */
    public function selectScope($id)
    {
        $this->selectedScopeId = '';

        if ($id !== '' && $id !== null) {
            $ada = $this->scopeOptions()->firstWhere('key', (string) $id);

            if (! $ada) {
                $this->addError('selectedScopeId', 'Lingkup peserta tidak ditemukan pada event ini.');

                return;
            }

            $this->selectedScopeId = (string) $ada['key'];
        }

        $this->resetErrorBag('selectedScopeId');
    }

    public function updatedSelectedScopeId()
    {
        $this->selectScope($this->selectedScopeId);
    }

    /**
     * Kartu per tingkat: daftar peserta yang tampil, sudah dipisah per
     * bagian (grup / finalis / belum bergrup).
     *
     * Pemisahan hanya dilakukan pada tingkat yang memang berlingkup. Tingkat
     * tanpa grup dan tanpa finalis tampil sebagai satu daftar rata persis
     * seperti sebelum fitur grup — sub-judul kosong hanya menambah baris tanpa
     * membawa keterangan apa pun.
     */
    public function levelCards(): array
    {
        $scopes = $this->scopeOptions();
        $scope = $this->selectedScopeId
            ? $scopes->firstWhere('key', (string) $this->selectedScopeId)
            : null;

        // Finalis tiap babak final, sekali query untuk seluruh halaman.
        $finalistIds = CompetitionRoundRegistration::where('eventner_id', $this->eventner->id)
            ->get()
            ->groupBy('competition_round_id');

        $cards = [];

        foreach ($this->levels() as $level) {
            $registrations = $level->registrations;

            // Lingkup terpilih membatasi halaman ke satu tingkat saja — sisa
            // tingkat tidak punya peserta yang cocok, dan kartu kosong lebih
            // membingungkan daripada tidak ada kartu.
            if ($scope && (string) $scope['level_id'] !== (string) $level->id) {
                continue;
            }

            $finalRounds = $level->rounds->filter(fn ($r) => $r->isFinal())->sortBy('sort_order');
            $groups = $level->groups->sortBy('sort_order');

            $sections = [];
            $jumlah = 0;

            if ($scope) {
                if ($scope['group_id'] !== '') {
                    $anggota = $registrations->where('competition_group_id', (int) $scope['group_id'])->values();
                    $jumlah = $anggota->count();
                    $sections[] = ['label' => null, 'is_final' => false, 'registrations' => $anggota];
                } else {
                    $ids = $finalistIds->get((int) $scope['round_id'], collect())->pluck('registration_id');
                    $finalis = $registrations->whereIn('id', $ids)->values();
                    $jumlah = $finalis->count();
                    $sections[] = ['label' => null, 'is_final' => true, 'registrations' => $finalis];
                }
            } elseif ($groups->isEmpty()) {
                // Tingkat tanpa grup: perilaku lama, tanpa sub-judul — kecuali
                // ada babak final, karena finalisnya bukan daftar yang sama
                // dengan pendaftar biasa.
                $sections[] = ['label' => null, 'is_final' => false, 'registrations' => $registrations];
                $jumlah = $registrations->count();

                foreach ($finalRounds as $round) {
                    $ids = $finalistIds->get($round->id, collect())->pluck('registration_id');
                    $finalis = $registrations->whereIn('id', $ids)->values();

                    if ($finalis->isNotEmpty()) {
                        $sections[] = ['label' => $round->name.' (Finalis)', 'is_final' => true, 'registrations' => $finalis];
                    }
                }
            } else {
                foreach ($groups as $group) {
                    $anggota = $registrations->where('competition_group_id', $group->id)->values();

                    if ($anggota->isEmpty()) {
                        continue;
                    }

                    $sections[] = ['label' => $group->name, 'is_final' => false, 'registrations' => $anggota];
                }

                $tanpaGrup = $registrations->whereNull('competition_group_id')->values();

                if ($tanpaGrup->isNotEmpty()) {
                    $sections[] = ['label' => 'Belum Bergrup', 'is_final' => false, 'registrations' => $tanpaGrup];
                }

                // Finalis ditampilkan sebagai bagian tersendiri: satu sekolah
                // bisa muncul di bagian grupnya DAN di sini, dan itu memang
                // benar — ia lolos dari grupnya, bukan pindah keluar.
                foreach ($finalRounds as $round) {
                    $ids = $finalistIds->get($round->id, collect())->pluck('registration_id');
                    $finalis = $registrations->whereIn('id', $ids)->values();

                    if ($finalis->isEmpty()) {
                        continue;
                    }

                    $sections[] = ['label' => $round->name.' (Finalis)', 'is_final' => true, 'registrations' => $finalis];
                }

                // Angka di kepala kartu menghitung peserta, bukan baris: satu
                // sekolah yang lolos final muncul di dua bagian, dan menjumlah
                // baris akan membuat "9 kontingen" untuk 6 sekolah.
                $jumlah = $registrations->count();
            }

            $cards[] = [
                'category' => $level,
                'sections' => $sections,
                'jumlah' => $jumlah,
                // Judul bagian hanya berguna kalau ada lebih dari satu bagian;
                // kalau cuma satu, judulnya mengulang nama tingkat di atasnya.
                'bersection' => count($sections) > 1,
            ];
        }

        return $cards;
    }

    public function render()
    {
        return view('livewire.public.event-participant', [
            'cards' => $this->levelCards(),
            'scopes' => $this->scopeOptions(),
            'selectedScopeId' => $this->selectedScopeId,
        ])
            ->title('Daftar Peserta - ' . $this->eventner->nama_event)
            ->layoutData(['eventner' => $this->eventner]);
    }
}
