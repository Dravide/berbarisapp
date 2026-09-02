<?php

namespace App\Livewire\Eventner\Rundown;

use App\Models\EventRundown;
use App\Models\Registration;
use App\Traits\FeatureGatedComponent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Index extends Component
{
    use FeatureGatedComponent;

    protected string $requiredFeature = 'rundown';

    public $eventner;
    public $categories = [];

    // CRUD manual state
    public $title = '';
    public $description = '';
    public $startTime = '';
    public $endTime = '';
    public $editingId = null;

    // Generate dari undian state
    public $importCategoryId = '';
    public $importStartTime = '08:00';
    public $importDefaultDuration = 10;

    // Durasi per pasukan (edit inline)
    public $durations = [];

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
    }

    public function save()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'startTime' => 'required|date_format:H:i',
            'endTime' => 'nullable|date_format:H:i|after:startTime',
            'description' => 'nullable|string|max:2000',
        ], [
            'title.required' => 'Judul item wajib diisi.',
            'startTime.required' => 'Jam mulai wajib diisi.',
            'startTime.date_format' => 'Format jam mulai harus HH:MM (contoh: 08:00).',
            'endTime.date_format' => 'Format jam selesai harus HH:MM (contoh: 08:30).',
            'endTime.after' => 'Jam selesai harus setelah jam mulai.',
        ]);

        if ($this->editingId) {
            $item = EventRundown::where('eventner_id', $this->eventner->id)->findOrFail($this->editingId);
            $item->update([
                'title' => strip_tags($this->title),
                'description' => $this->description ? strip_tags($this->description) : null,
                'start_time' => $this->startTime,
                'end_time' => $this->endTime ?: null,
            ]);
            session()->flash('success', 'Item rundown berhasil diperbarui.');
        } else {
            EventRundown::create([
                'eventner_id' => $this->eventner->id,
                'title' => strip_tags($this->title),
                'description' => $this->description ? strip_tags($this->description) : null,
                'start_time' => $this->startTime,
                'end_time' => $this->endTime ?: null,
                'sort_order' => EventRundown::where('eventner_id', $this->eventner->id)->max('sort_order') + 1,
            ]);
            session()->flash('success', 'Item rundown berhasil ditambahkan.');
        }

        $this->reset(['title', 'description', 'startTime', 'endTime', 'editingId']);
    }

    public function edit($id)
    {
        $item = EventRundown::where('eventner_id', $this->eventner->id)->findOrFail($id);
        $this->editingId = $item->id;
        $this->title = $item->title;
        $this->description = $item->description ?? '';
        $this->startTime = $item->start_time?->format('H:i');
        $this->endTime = $item->end_time?->format('H:i');
    }

    public function delete($id)
    {
        EventRundown::where('eventner_id', $this->eventner->id)->findOrFail($id)->delete();
        session()->flash('success', 'Item rundown dihapus.');
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
        $item = EventRundown::where('eventner_id', $this->eventner->id)->findOrFail($id);
        $all = EventRundown::where('eventner_id', $this->eventner->id)->orderBy('sort_order')->get();
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
            'importCategoryId' => 'required|exists:competition_categories,id',
            'importStartTime' => 'required|date_format:H:i',
            'importDefaultDuration' => 'required|integer|min:1|max:600',
        ], [
            'importCategoryId.required' => 'Pilih kategori lomba terlebih dahulu.',
            'importStartTime.required' => 'Jam mulai wajib diisi.',
            'importDefaultDuration.required' => 'Durasi default wajib diisi.',
            'importDefaultDuration.min' => 'Durasi minimal 1 menit.',
        ]);

        // Pastikan kategori milik eventner ini
        $category = collect($this->categories)->firstWhere('id', $this->importCategoryId);
        if (!$category) {
            $this->addError('importCategoryId', 'Kategori tidak valid.');
            return;
        }

        $drawn = Registration::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->importCategoryId)
            ->whereNotNull('urutan_tampil')
            ->orderBy('urutan_tampil')
            ->get();

        if ($drawn->isEmpty()) {
            $this->addError('importCategoryId', 'Belum ada hasil undian (urutan tampil) untuk kategori ini.');
            return;
        }

        // Hapus item generate lama dari kategori yang sama (item manual aman)
        EventRundown::where('eventner_id', $this->eventner->id)
            ->where('source_category_id', $this->importCategoryId)
            ->delete();

        $sort = EventRundown::where('eventner_id', $this->eventner->id)->max('sort_order') ?? 0;
        $cursor = Carbon::createFromFormat('H:i', $this->importStartTime);
        $duration = (int) $this->importDefaultDuration;
        $categoryName = !empty($category['parent']) ? $category['parent']['name'] . ' — ' . $category['name'] : $category['name'];

        foreach ($drawn as $reg) {
            $label = $reg->label_pasukan ? " (Pasukan {$reg->label_pasukan})" : '';
            EventRundown::create([
                'eventner_id' => $this->eventner->id,
                'title' => "{$categoryName} — Urutan {$reg->urutan_tampil} — {$reg->nama_sekolah}{$label}",
                'start_time' => $cursor->format('H:i'),
                'end_time' => $cursor->copy()->addMinutes($duration)->format('H:i'),
                'duration_minutes' => $duration,
                'sort_order' => ++$sort,
                'source_category_id' => $this->importCategoryId,
                'source_registration_id' => $reg->id,
            ]);
            $cursor->addMinutes($duration);
        }

        session()->flash('success', "Rundown berhasil dibuat dari undian kategori ini ({$drawn->count()} pasukan, {$duration} menit per pasukan). Ubah kolom durasi untuk atur menit tampil tiap pasukan.");
    }

    public function updateDuration($id)
    {
        $minutes = (int) ($this->durations[$id] ?? 0);

        $this->validate([
            'durations.' . $id => 'required|integer|min:1|max:600',
        ], [
            'durations.' . $id . '.required' => 'Durasi wajib diisi.',
            'durations.' . $id . '.min' => 'Durasi minimal 1 menit.',
        ]);

        $item = EventRundown::where('eventner_id', $this->eventner->id)->findOrFail($id);

        if (!$item->source_category_id) {
            return; // item manual tidak pakai durasi
        }

        $item->update(['duration_minutes' => $minutes]);
        $this->recomputeChain($item->source_category_id);
    }

    /** Recompute jam berantai: start pasukan berikut = end pasukan sebelumnya. */
    private function recomputeChain($categoryId)
    {
        $chain = EventRundown::where('eventner_id', $this->eventner->id)
            ->where('source_category_id', $categoryId)
            ->orderBy('sort_order')
            ->get();

        $cursor = null;
        foreach ($chain as $item) {
            if ($cursor === null) {
                // Pertahankan start_time pasukan pertama
                $cursor = Carbon::createFromFormat('H:i', $item->start_time->format('H:i'));
            }

            $duration = $item->duration_minutes ?: $this->importDefaultDuration;
            $item->update([
                'start_time' => $cursor->format('H:i'),
                'end_time' => $cursor->copy()->addMinutes($duration)->format('H:i'),
            ]);
            $cursor->addMinutes($duration);
        }
    }

    public function render()
    {
        $items = EventRundown::with('sourceCategory.parent')
            ->where('eventner_id', $this->eventner->id)
            ->orderBy('sort_order')
            ->get();

        // Sync state durasi untuk input inline
        $this->durations = $items->where('source_category_id')->pluck('duration_minutes', 'id')->toArray();

        return view('livewire.eventner.rundown.index', [
            'items' => $items,
        ])->title('Rundown Acara - ' . $this->eventner->nama_event);
    }
}
