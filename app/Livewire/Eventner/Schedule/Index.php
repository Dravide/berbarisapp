<?php

namespace App\Livewire\Eventner\Schedule;

use App\Livewire\Concerns\MelaporKePengguna;
use App\Models\EventSchedule;
use App\Models\Registration;
use App\Traits\FeatureGatedComponent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Jadwal pertandingan — pertemuan per tingkat (+grup, +babak) di suatu
 * venue pada jam tertentu.
 *
 * Terpisah dari Rundown Acara: rundown adalah urutan acara global
 * (pembukaan, istirahat, penutupan), jadwal adalah pertandingan yang bisa
 * diikat ke tempat dan babak. Dua tabel, dua layar, satu halaman publik:
 * jadwal tampil di halaman rundown publik, dikelompokkan tanggal → venue.
 *
 * Generate dari undian menyalin pola Rundown\Index::generateFromDrawing —
 * sumber barisnya registrasi urut undian, venue diambil dari
 * competition_categories.venue_id.
 */
#[Layout('layouts.admin')]
class Index extends Component
{
    use FeatureGatedComponent;
    use MelaporKePengguna;

    protected string $requiredFeature = 'schedule';

    public $eventner;
    public $categories = [];
    public $groups = [];
    public $rounds = [];
    public $venues = [];

    // Form manual
    public $editingId = null;
    public $title = '';
    public $tanggal = '';
    public $categoryId = '';
    public $groupId = '';
    public $roundId = '';
    public $venueId = '';
    public $startTime = '';
    public $endTime = '';

    // Generate dari undian
    public $importCategoryId = '';
    public $importStartTime = '08:00';
    public $importDefaultDuration = 30;

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

        $this->groups = $this->eventner->competitionGroups()->orderBy('sort_order')->get(['id', 'competition_category_id', 'name'])->toArray();
        $this->rounds = $this->eventner->competitionRounds()->orderBy('sort_order')->get(['id', 'competition_category_id', 'name'])->toArray();
        $this->venues = $this->eventner->activeVenues() // Collection jadi — activeVenues() sudah ->get()
            ->map(fn ($v) => ['id' => $v->id, 'name' => $v->name])
            ->toArray();
    }

    private function rules(): array
    {
        return [
            'categoryId' => 'required',
            'startTime' => 'required|date_format:H:i',
            'endTime' => 'nullable|date_format:H:i|after:startTime',
            'tanggal' => 'nullable|date',
        ];
    }

    private function messages(): array
    {
        return [
            'categoryId.required' => 'Pilih tingkat lomba terlebih dahulu.',
            'startTime.required' => 'Jam mulai wajib diisi.',
            'startTime.date_format' => 'Format jam mulai harus HH:MM (contoh: 08:00).',
            'endTime.date_format' => 'Format jam selesai harus HH:MM (contoh: 09:30).',
            'endTime.after' => 'Jam selesai harus setelah jam mulai.',
            'tanggal.date' => 'Tanggal tidak valid.',
        ];
    }

    /** Tingkat, grup, babak, venue yang dipilih harus milik event ini. */
    private function relasiSah(): bool
    {
        $categoryId = (int) $this->categoryId;
        $milikEvent = fn ($list, $id) => $id === 0 || collect($list)->contains('id', $id);

        return $milikEvent($this->categories, $categoryId)
            && $milikEvent($this->groups, (int) $this->groupId)
            && $milikEvent($this->rounds, (int) $this->roundId)
            && $milikEvent($this->venues, (int) $this->venueId);
    }

    public function save()
    {
        $this->validate($this->rules(), $this->messages());

        if (!$this->relasiSah()) {
            $this->addError('categoryId', 'Tingkat/grup/babak/venue tidak valid untuk event ini.');
            return;
        }

        $payload = [
            'competition_category_id' => $this->categoryId,
            'competition_group_id' => $this->groupId ?: null,
            'competition_round_id' => $this->roundId ?: null,
            'eventner_venue_id' => $this->venueId ?: null,
            'title' => $this->title ? strip_tags($this->title) : null,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime ?: null,
            'tanggal' => $this->tanggal ?: null,
        ];

        if ($this->editingId) {
            EventSchedule::where('eventner_id', $this->eventner->id)
                ->findOrFail($this->editingId)
                ->update($payload);
            $this->toast('Jadwal berhasil diperbarui.');
        } else {
            EventSchedule::create($payload + [
                'eventner_id' => $this->eventner->id,
                'sort_order' => (EventSchedule::where('eventner_id', $this->eventner->id)->max('sort_order') ?? 0) + 1,
            ]);
            $this->toast('Jadwal berhasil ditambahkan.');
        }

        $this->reset(['editingId', 'title', 'tanggal', 'categoryId', 'groupId', 'roundId', 'venueId', 'startTime', 'endTime']);
    }

    public function edit($id)
    {
        $item = EventSchedule::where('eventner_id', $this->eventner->id)->findOrFail($id);

        $this->editingId = $item->id;
        $this->title = $item->title ?? '';
        $this->tanggal = $item->tanggal?->format('Y-m-d') ?? '';
        $this->categoryId = (string) $item->competition_category_id;
        $this->groupId = (string) ($item->competition_group_id ?? '');
        $this->roundId = (string) ($item->competition_round_id ?? '');
        $this->venueId = (string) ($item->eventner_venue_id ?? '');
        $this->startTime = $item->start_time?->format('H:i');
        $this->endTime = $item->end_time?->format('H:i') ?? '';
    }

    public function delete($id)
    {
        EventSchedule::where('eventner_id', $this->eventner->id)->findOrFail($id)->delete();
        $this->toast('Jadwal dihapus.', 'error');
    }

    public function moveUp($id)
    {
        $this->swapSort($id, 'up');
    }

    public function moveDown($id)
    {
        $this->swapSort($id, 'down');
    }

    private function swapSort($id, $direction)
    {
        $item = EventSchedule::where('eventner_id', $this->eventner->id)->findOrFail($id);
        $all = EventSchedule::where('eventner_id', $this->eventner->id)
            ->orderByRaw('COALESCE(tanggal, ?) asc', [$this->eventner->tanggal])
            ->orderBy('sort_order')
            ->get();
        $index = $all->search(fn ($r) => $r->id === $item->id);

        $targetIndex = $direction === 'up' ? $index - 1 : $index + 1;
        if ($targetIndex < 0 || $targetIndex >= $all->count()) {
            return;
        }

        $neighbor = $all[$targetIndex];

        $itemOrder = $item->sort_order;
        $item->update(['sort_order' => $neighbor->sort_order]);
        $neighbor->update(['sort_order' => $itemOrder]);
    }

    public function generateFromDrawing()
    {
        $this->validate([
            'importCategoryId' => 'required',
            'importStartTime' => 'required|date_format:H:i',
            'importDefaultDuration' => 'required|integer|min:1|max:600',
        ], [
            'importCategoryId.required' => 'Pilih tingkat lomba terlebih dahulu.',
            'importStartTime.required' => 'Jam mulai wajib diisi.',
            'importDefaultDuration.required' => 'Durasi wajib diisi.',
            'importDefaultDuration.min' => 'Durasi minimal 1 menit.',
        ]);

        $categoryId = (int) $this->importCategoryId;

        // Tingkat harus milik event ini — pola kategori Rundown, tapi di sini
        // langsung ke model karena kategori dibaca ulang dari DB.
        $kategori = $this->eventner->competitionCategories()
            ->whereNotNull('parent_id')
            ->whereKey($categoryId)
            ->first();
        if (!$kategori) {
            $this->addError('importCategoryId', 'Tingkat tidak valid.');
            return;
        }

        $drawn = Registration::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $categoryId)
            ->whereNotNull('urutan_tampil')
            ->orderBy('urutan_tampil')
            ->get();

        if ($drawn->isEmpty()) {
            $this->addError('importCategoryId', 'Belum ada hasil undian (urutan tampil) untuk tingkat ini.');
            return;
        }

        // Hapus jadwal generate lama dari tingkat yang sama (manual aman).
        EventSchedule::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $categoryId)
            ->delete();

        $sort = EventSchedule::where('eventner_id', $this->eventner->id)->max('sort_order') ?? 0;
        $cursor = Carbon::createFromFormat('H:i', $this->importStartTime);
        $duration = (int) $this->importDefaultDuration;

        $induk = $kategori->parent?->name ? $kategori->parent->name . ' — ' : '';
        $grupName = fn ($reg) => $reg->competitionGroup?->name ? ' ' . $reg->competitionGroup->name : '';
        $judul = fn ($reg) => trim($induk . $kategori->name . $grupName($reg));

        foreach ($drawn as $reg) {
            EventSchedule::create([
                'eventner_id' => $this->eventner->id,
                'competition_category_id' => $categoryId,
                'competition_group_id' => $reg->competition_group_id,
                'eventner_venue_id' => $kategori->venue_id,
                'title' => $judul($reg),
                'start_time' => $cursor->format('H:i'),
                'end_time' => $cursor->copy()->addMinutes($duration)->format('H:i'),
                'sort_order' => ++$sort,
            ]);
            $cursor->addMinutes($duration);
        }

        $this->toast("Jadwal dibuat dari undian ({$drawn->count()} pasukan, {$duration} menit per pasukan).");
    }

    /** Jadwal terurut untuk tabel: tanggal → venue → jam. */
    public function getItemsProperty()
    {
        return EventSchedule::where('eventner_id', $this->eventner->id)
            ->with(['category.parent', 'group', 'round', 'venue'])
            ->orderByRaw('COALESCE(tanggal, ?) asc', [$this->eventner->tanggal])
            ->orderBy('sort_order')
            ->get();
    }

    public function render()
    {
        return view('livewire.eventner.schedule.index', [
            'items' => $this->items,
        ])->title('Jadwal Pertandingan - ' . app_name());
    }
}
