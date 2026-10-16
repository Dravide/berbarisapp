<?php

namespace App\Livewire\Eventner\Layout;

use App\Livewire\Concerns\MelaporKePengguna;
use App\Models\CompetitionCategory;
use App\Models\CompetitionLayout;
use App\Models\EventnerVenue;
use App\Traits\FeatureGatedComponent;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Daftar denah lapangan milik satu event.
 *
 * Dikurung eventner_id pengguna yang login; findOrFail() pada query itu yang
 * menolak denah event lain, bukan policy.
 */
#[Layout('layouts.admin')]
class Index extends Component
{
    use FeatureGatedComponent;
    use MelaporKePengguna;

    protected string $requiredFeature = 'layout';

    public string $name = '';

    public string $competition_category_id = '';

    public string $eventner_venue_id = '';

    public string $ground_width = '40';

    public string $ground_height = '22';

    public bool $showFormModal = false;

    public ?int $editingId = null;

    private function eventnerId(): int
    {
        return (int) Auth::user()->eventner->id;
    }

    private function milikSaya(int $id): CompetitionLayout
    {
        return CompetitionLayout::where('eventner_id', $this->eventnerId())->findOrFail($id);
    }

    public function mount(): void
    {
        $this->bootFeatureGate();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function edit(int $id): void
    {
        $denah = $this->milikSaya($id);

        $this->editingId = $denah->id;
        $this->name = $denah->name;
        $this->competition_category_id = $denah->competition_category_id ? (string) $denah->competition_category_id : '';
        $this->eventner_venue_id = $denah->eventner_venue_id ? (string) $denah->eventner_venue_id : '';
        $this->ground_width = (string) $denah->ground_width;
        $this->ground_height = (string) $denah->ground_height;
        $this->showFormModal = true;
    }

    public function simpan(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'ground_width' => 'required|numeric|min:1|max:9999',
            'ground_height' => 'required|numeric|min:1|max:9999',
        ]);

        $eventnerId = $this->eventnerId();

        $data = [
            'name' => strip_tags($this->name),
            'ground_width' => (float) $this->ground_width,
            'ground_height' => (float) $this->ground_height,
            // Kategori & tempat opsional: null berarti denah umum yang dipakai
            // lintas tingkat lomba.
            'competition_category_id' => $this->kategoriTerpilih(),
            'eventner_venue_id' => $this->tempatTerpilih(),
        ];

        if ($this->editingId) {
            $this->milikSaya($this->editingId)->update($data);
            $this->toast('Denah diperbarui.');
        } else {
            CompetitionLayout::create(array_merge($data, [
                'eventner_id' => $eventnerId,
                'sort_order' => (int) CompetitionLayout::where('eventner_id', $eventnerId)->max('sort_order') + 1,
            ]));
            $this->toast('Denah baru dibuat. Buka untuk mulai menggambar.');
        }

        $this->tutupForm();
    }

    /**
     * Kategori divalidasi milik event yang sama sebelum dipakai.
     *
     * Tanpa cek ini, id kategori event lain bisa lolos lewat request langsung.
     */
    private function kategoriTerpilih(): ?int
    {
        if ($this->competition_category_id === '') {
            return null;
        }

        $id = (int) $this->competition_category_id;

        return CompetitionCategory::where('eventner_id', $this->eventnerId())->whereKey($id)->exists() ? $id : null;
    }

    private function tempatTerpilih(): ?int
    {
        if ($this->eventner_venue_id === '') {
            return null;
        }

        $id = (int) $this->eventner_venue_id;

        return EventnerVenue::where('eventner_id', $this->eventnerId())->whereKey($id)->exists() ? $id : null;
    }

    public function tutupForm(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->reset(['name', 'competition_category_id', 'eventner_venue_id', 'editingId']);
        $this->ground_width = '40';
        $this->ground_height = '22';
        $this->resetValidation();
    }

    public function terbitkan(int $id): void
    {
        $denah = $this->milikSaya($id);

        // Denah kosong yang diterbitkan akan tampil sebagai kotak hampa di
        // halaman publik — lebih baik ditolak dengan alasan yang jelas.
        if (! $denah->sudahTerisi()) {
            $this->gagal('Denah masih kosong. Tambahkan minimal satu zona sebelum diterbitkan.');
            $this->redirect(route('eventner.denah.show', $denah->id), navigate: true);

            return;
        }

        $denah->update(['is_published' => true, 'published_at' => now()]);
        $this->toast('Denah diterbitkan ke halaman publik event.');
    }

    public function tarik(int $id): void
    {
        $this->milikSaya($id)->update(['is_published' => false]);
        $this->toast('Denah ditarik dari halaman publik.', 'info');
    }

    public function delete(int $id): void
    {
        $this->milikSaya($id)->delete();
        $this->toast('Denah dihapus.');
    }

    public function render()
    {
        $eventnerId = $this->eventnerId();

        return view('livewire.eventner.layout.index', [
            'layouts' => CompetitionLayout::where('eventner_id', $eventnerId)
                ->with('competitionCategory.parent', 'venue', 'eventner')
                ->withCount('zones')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'kategoris' => CompetitionCategory::where('eventner_id', $eventnerId)
                ->with('parent')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'tempat' => EventnerVenue::where('eventner_id', $eventnerId)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
        ])->title('Denah Lapangan - '.app_name());
    }
}
