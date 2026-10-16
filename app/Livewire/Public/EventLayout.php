<?php

namespace App\Livewire\Public;

use App\Models\CompetitionLayout;
use App\Models\Eventner;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Denah lapangan di halaman publik event — hanya yang sudah diterbitkan.
 *
 * Gerbangnya konten, bukan saklar pengaturan: tampil kalau memang ada denah
 * terbit, 404 kalau tidak.
 */
#[Layout('layouts.frontend')]
class EventLayout extends Component
{
    public $eventner;

    /** Denah terpilih; null = daftar seluruh denah terbit. */
    public ?int $layoutId = null;

    public function mount($slug = null, $layout = null): void
    {
        $resolved = app()->bound('current_eventner') ? app('current_eventner') : null;

        $this->eventner = $resolved
            ?: Eventner::approved()->where('slug', $slug)->firstOrFail();

        if ($layout !== null) {
            $this->layoutId = (int) $layout;
        }
    }

    /** Denah terbit yang boleh dilihat. Draf tak pernah masuk daftar ini. */
    public function denahTerbit()
    {
        return CompetitionLayout::where('eventner_id', $this->eventner->id)
            ->terbit()
            ->with(['competitionCategory.parent', 'venue'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function pilihDenah(int $id): void
    {
        // Dipilih dari daftar yang sudah terkurung ke event ini dan terbit, jadi
        // id denah draf atau milik event lain tak akan pernah cocok.
        if ($this->denahTerbit()->contains('id', $id)) {
            $this->layoutId = $id;
        }

        if (request()->routeIs('subdomain.*')) {
            return;
        }
    }

    public function tampilkanSemua(): void
    {
        $this->layoutId = null;
    }

    public function render()
    {
        $semua = $this->denahTerbit();

        // Tak ada denah terbit = halamannya memang belum ada. 404 lebih jujur
        // daripada halaman kosong: peserta tahu denahnya belum diterbitkan,
        // bukan mengira tautannya rusak.
        if ($semua->isEmpty()) {
            abort(404);
        }

        $denah = null;

        if ($this->layoutId) {
            $denah = $semua->firstWhere('id', $this->layoutId);

            if (! $denah) {
                abort(404);
            }

            $denah->load('zones');
        } elseif ($semua->count() === 1) {
            // Satu denah: tak ada gunanya menampilkan daftar berisi satu baris.
            $denah = $semua->first()->load('zones');
        }

        return view('livewire.public.event-layout', [
            'denahTerbit' => $semua,
            'denah' => $denah,
        ])
            ->title('Denah Lapangan - '.$this->eventner->nama_event)
            ->layoutData(['eventner' => $this->eventner]);
    }
}
