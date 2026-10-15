<?php

namespace App\Livewire\Eventner\Document;

use App\Livewire\Concerns\MelaporKePengguna;
use App\Models\EventDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Juknis & dokumen unggahan panitia.
 *
 * Semua query dikurung eventner_id milik pengguna yang login — pola yang sama
 * dengan Gallery/Faq. findOrFail() pada query yang sudah dikurung itulah yang
 * menolak dokumen milik event lain, bukan policy.
 */
#[Layout('layouts.admin')]
class Index extends Component
{
    use MelaporKePengguna;
    use WithFileUploads;

    public $newFile;

    public $title = '';

    public $description = '';

    /** Dokumen yang sedang diubah judul/deskripsinya. */
    public $editingId = null;

    /** Dokumen yang sedang diganti berkasnya. */
    public $replacingId = null;

    public $replacementFile;

    protected array $jenisBerkas = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];

    private function eventnerId(): int
    {
        return (int) Auth::user()->eventner->id;
    }

    private function milikSaya(int $id): EventDocument
    {
        return EventDocument::where('eventner_id', $this->eventnerId())->findOrFail($id);
    }

    /**
     * Sengaja BUKAN `upload()`: `upload` adalah alias bawaan Livewire
     * (`aliases.upload = "$upload"` di dist/livewire.js) dan proxy `$wire`
     * memeriksa alias SEBELUM method komponen. Method bernama `upload` karena
     * itu tak pernah terpanggil dari `wire:submit="upload"` — Livewire malah
     * menjalankan magic $upload dengan argumen kosong, yang melempar
     * "Cannot read properties of undefined (reading 'name')" di browser.
     */
    public function unggahDokumen()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'newFile' => 'required|file|mimes:'.implode(',', $this->jenisBerkas).'|max:10240',
        ]);

        $eventnerId = $this->eventnerId();
        $adaSebelumnya = EventDocument::where('eventner_id', $eventnerId)->exists();

        EventDocument::create([
            'eventner_id' => $eventnerId,
            'title' => strip_tags($this->title),
            'file' => $this->newFile->store('documents', 'public'),
            'original_name' => $this->newFile->getClientOriginalName(),
            'file_size' => $this->newFile->getSize(),
            'description' => $this->description ? strip_tags($this->description) : null,
            'sort_order' => EventDocument::where('eventner_id', $eventnerId)->max('sort_order') + 1,
            // Dokumen pertama otomatis jadi Utama: tanpa itu, panitia yang
            // hanya mengunggah satu berkas tak pernah menyentuh tombol Utama
            // dan tautan "Unduh Juknis" cepat tetap menunjuk ke juknis rubrik.
            'is_primary' => ! $adaSebelumnya,
        ]);

        $this->reset(['newFile', 'title', 'description']);
        $this->toast('Dokumen berhasil diunggah.');
    }

    public function edit(int $id)
    {
        $doc = $this->milikSaya($id);

        $this->editingId = $doc->id;
        $this->title = $doc->title;
        $this->description = $doc->description ?? '';
    }

    public function simpanPerubahan()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        $this->milikSaya($this->editingId)->update([
            'title' => strip_tags($this->title),
            'description' => $this->description ? strip_tags($this->description) : null,
        ]);

        $this->batalkan();
        $this->toast('Perubahan dokumen disimpan.');
    }

    public function batalkan()
    {
        $this->reset(['editingId', 'title', 'description']);
    }

    public function mulaiGantiBerkas(int $id)
    {
        $this->milikSaya($id);

        $this->replacingId = $id;
        $this->replacementFile = null;
    }

    public function batalkanGantiBerkas()
    {
        $this->reset(['replacingId', 'replacementFile']);
    }

    public function gantiBerkas()
    {
        $this->validate([
            'replacementFile' => 'required|file|mimes:'.implode(',', $this->jenisBerkas).'|max:10240',
        ]);

        $doc = $this->milikSaya($this->replacingId);

        if ($doc->file && Storage::disk('public')->exists($doc->file)) {
            Storage::disk('public')->delete($doc->file);
        }

        $doc->update([
            'file' => $this->replacementFile->store('documents', 'public'),
            'original_name' => $this->replacementFile->getClientOriginalName(),
            'file_size' => $this->replacementFile->getSize(),
        ]);

        $this->batalkanGantiBerkas();
        $this->toast('Berkas dokumen diganti.');
    }

    public function jadikanUtama(int $id)
    {
        $doc = $this->milikSaya($id);
        $eventnerId = $this->eventnerId();

        EventDocument::where('eventner_id', $eventnerId)->update(['is_primary' => false]);
        $doc->update(['is_primary' => true]);

        $this->toast('Dokumen ditandai sebagai utama.');
    }

    public function naikkan(int $id)
    {
        $this->tukarUrutan($id, -1);
    }

    public function turunkan(int $id)
    {
        $this->tukarUrutan($id, 1);
    }

    /**
     * Tukar sort_order dengan tetangga terdekat.
     *
     * sort_order bisa kembar (kolomnya default 0), jadi tetangga dicari
     * berdasarkan pasangan (sort_order, id) — bukan sort_order saja, yang akan
     * membuat tombol naik/turun tidak bergerak saat ada nilai kembar.
     */
    private function tukarUrutan(int $id, int $arah): void
    {
        $doc = $this->milikSaya($id);
        $eventnerId = $this->eventnerId();

        $tetangga = EventDocument::where('eventner_id', $eventnerId)
            ->where('id', '!=', $doc->id)
            ->when($arah < 0, fn ($q) => $q->where(function ($sq) use ($doc) {
                $sq->where('sort_order', '<', $doc->sort_order)
                    ->orWhere(fn ($ssq) => $ssq->where('sort_order', $doc->sort_order)->where('id', '<', $doc->id));
            }))
            ->when($arah > 0, fn ($q) => $q->where(function ($sq) use ($doc) {
                $sq->where('sort_order', '>', $doc->sort_order)
                    ->orWhere(fn ($ssq) => $ssq->where('sort_order', $doc->sort_order)->where('id', '>', $doc->id));
            }))
            ->orderBy('sort_order', $arah < 0 ? 'desc' : 'asc')
            ->orderBy('id', $arah < 0 ? 'desc' : 'asc')
            ->first();

        if (! $tetangga) {
            return;
        }

        // Nilai kembar harus diselesaikan dulu, kalau tidak penukaran tidak
        // mengubah apa pun dan dokumen tampak tidak bergerak.
        if ($tetangga->sort_order === $doc->sort_order) {
            $tetangga->update(['sort_order' => $tetangga->sort_order + $arah]);

            return;
        }

        $urutDoc = $doc->sort_order;
        $doc->update(['sort_order' => $tetangga->sort_order]);
        $tetangga->update(['sort_order' => $urutDoc]);
    }

    public function delete(int $id)
    {
        $doc = $this->milikSaya($id);

        if ($doc->file && Storage::disk('public')->exists($doc->file)) {
            Storage::disk('public')->delete($doc->file);
        }

        $adalahUtama = $doc->is_primary;
        $doc->delete();

        // Jangan tinggalkan event tanpa dokumen utama: kalau yang dihapus
        // memang dokumen utama, dokumen tersisa pertama yang mengambil alih.
        if ($adalahUtama) {
            EventDocument::where('eventner_id', $this->eventnerId())
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first()
                ?->update(['is_primary' => true]);
        }

        if ($this->editingId === $id) {
            $this->batalkan();
        }

        $this->toast('Dokumen dihapus.');
    }

    public function render()
    {
        return view('livewire.eventner.document.index', [
            'documents' => EventDocument::where('eventner_id', $this->eventnerId())
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
        ])->title('Juknis & Dokumen - '.app_name());
    }
}
