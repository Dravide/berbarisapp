<?php

namespace App\Livewire\Public;

use App\Models\ChampionCategory;
use App\Models\Eventner;
use App\Services\ChampionCalculator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Hall of Fame — rekap juara lintas event milik satu penyelenggara.
 *
 * Kelompoknya dari nama penyelenggara (diselenggarakan_oleh, string bebas)
 * yang dinormalisasi trim + case-fold, bukan dari user pemilik: satu akun
 * saat ini hanya memegang satu event, dan nama yang diketik adalah identitas
 * yang tampil ke publik. Parameter route = slug nama tersebut; slug yang tak
 * cocok grup mana pun berarti 404.
 *
 * Juara dihitung on-the-fly via ChampionCalculator (tanpa tabel snapshot),
 * hanya kategori is_public. Seluruh payload halaman di-cache 5 menit supaya
 * kalkulasi lintas event tidak diulang tiap kunjungan; model berat dilepas —
 * yang masuk cache array ringkas siap render.
 */
#[Layout('layouts.frontend')]
class HallOfFame extends Component
{
    public string $penyelenggara = '';

    /** @var array<int, array> event + kategori juara siap render */
    public array $events = [];

    public function mount(string $penyelenggara)
    {
        $slug = Str::slug($penyelenggara);
        $cacheKey = 'hof:penyelenggara:' . md5($slug);

        $payload = Cache::get($cacheKey);
        if ($payload === null) {
            $payload = $this->build($slug);
            Cache::put($cacheKey, $payload, 300);
        }

        if ($payload === null) {
            abort(404, 'Penyelenggara tidak ditemukan.');
        }

        $this->penyelenggara = $payload['penyelenggara'];
        $this->events = $payload['events'];
    }

    private function build(string $slug): ?array
    {
        $groups = Eventner::approved()
            ->whereNotNull('diselenggarakan_oleh')
            ->where('diselenggarakan_oleh', '!=', '')
            ->orderByDesc('tanggal')
            ->get()
            ->groupBy(fn ($e) => mb_strtolower(trim($e->diselenggarakan_oleh)));

        $group = $groups->first(fn ($items, $nama) => Str::slug($nama) === $slug);
        if (! $group) {
            return null;
        }

        // Ejaan yang ditampilkan = milik event terlama (penulis asli nama).
        $penyelenggara = trim((string) optional($group->sortBy(fn ($e) => (string) $e->tanggal)->first())->diselenggarakan_oleh);

        $events = [];
        foreach ($group as $event) {
            $categories = $this->juaraEvent($event);
            if ($categories === []) {
                continue;
            }

            $events[] = [
                'nama_event' => $event->nama_event,
                'url' => $event->publicUrl('detail'),
                // tanggal TIDAK di-cast di model Eventner — string Y-m-d.
                'tanggal' => $event->tanggal ? (string) $event->tanggal : null,
                'categories' => $categories,
            ];
        }

        return [
            'penyelenggara' => $penyelenggara,
            'events' => $events,
        ];
    }

    /**
     * Kategori juara publik satu event beserta pemegang peringkatnya.
     * Kategori tanpa pemenang sah (tanpa nilai, data janggal) dilewati —
     * kegagalan satu kategori tidak boleh mematikan halaman.
     *
     * @return array<int, array{name: string, winners: array}>
     */
    private function juaraEvent(Eventner $event): array
    {
        $categories = [];

        $champions = ChampionCategory::where('eventner_id', $event->id)
            ->where('is_public', true)
            ->orderBy('id')
            ->get();

        foreach ($champions as $champion) {
            try {
                [, , $winners] = app(ChampionCalculator::class)->winners($champion);
            } catch (\Throwable $e) {
                report($e);
                continue;
            }

            $winners = array_values(array_filter($winners, fn ($w) => $w['total'] > 0));
            if ($winners === []) {
                continue;
            }

            $categories[] = [
                'name' => $champion->name,
                'winners' => array_map(fn ($w) => [
                    'rank' => $w['rank'],
                    'title' => $w['title'],
                    'nama' => $w['registration']->display_name,
                    'total' => $w['total'],
                ], $winners),
            ];
        }

        return $categories;
    }

    public function render()
    {
        return view('livewire.public.hall-of-fame')
            ->title('Hall of Fame - ' . $this->penyelenggara);
    }
}
