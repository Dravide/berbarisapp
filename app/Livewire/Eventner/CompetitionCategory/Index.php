<?php

namespace App\Livewire\Eventner\CompetitionCategory;

use Livewire\Component;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\EventnerVenue;
use App\Livewire\Concerns\MelaporKePengguna;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

#[Layout('layouts.admin')]
class Index extends Component
{
    use MelaporKePengguna;

    public $name = '';
    public $parentId = null;
    public $tanggal_pelaksanaan = '';
    public $kuota = '';
    public $max_registrations_per_school = 1;
    public $registration_fee = '';
    public $venueId = null;

    public $isEditMode = false;
    public $editingId = null;

    /** Modal form Tambah/Edit Kategori sedang terbuka. */
    public $showCategoryForm = false;

    public $expandedParents = [];

    protected $eventnerId;

    protected function getListeners()
    {
        return [
            'updateParentSort' => 'updateParentSort',
            'updateChildSort' => 'updateChildSort',
        ];
    }

    public function boot()
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403);
        }
        $this->eventnerId = $eventner->id;
    }

    public function updateSortOrder($orderedIds)
    {
        foreach ($orderedIds as $index => $id) {
            CompetitionCategory::where('eventner_id', $this->eventnerId)
                ->where('id', $id)
                ->update(['sort_order' => $index]);
        }
    }

    public function updateParentSort($orderedIds)
    {
        $this->updateSortOrder($orderedIds);
    }

    public function updateChildSort($orderedIds)
    {
        $this->updateSortOrder($orderedIds);
    }

    #[Computed]
    public function parentCategories()
    {
        return CompetitionCategory::whereNull('parent_id')
            ->where('eventner_id', $this->eventnerId)
            ->with(['children' => fn($q) => $q->with('judges', 'venue')->withCount('registrations')->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function orphanChildren()
    {
        return CompetitionCategory::whereNotNull('parent_id')
            ->where('eventner_id', $this->eventnerId)
            ->whereDoesntHave('parent', fn($q) => $q->where('eventner_id', $this->eventnerId))
            ->with('judges', 'venue')
            ->withCount('registrations')
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function allParents()
    {
        return CompetitionCategory::whereNull('parent_id')
            ->where('eventner_id', $this->eventnerId)
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function availableVenues()
    {
        return EventnerVenue::where('eventner_id', $this->eventnerId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Grup semua tingkat, untuk badge jumlah peserta per grup di baris daftar.
     *
     * Satu query se-halaman — bukan per kartu tingkat.
     */
    #[Computed]
    public function groupsByCategory()
    {
        return CompetitionGroup::where('eventner_id', $this->eventnerId)
            ->withCount('registrations')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('competition_category_id');
    }

    /** Babak semua tingkat, untuk badge jumlah finalis di baris daftar. */
    #[Computed]
    public function roundsByCategory()
    {
        return CompetitionRound::where('eventner_id', $this->eventnerId)
            ->withCount('roundRegistrations')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('competition_category_id');
    }

    /** Buka modal form kategori; isi form bila $id diberikan (mode edit). */
    public function openCategoryForm(?int $id = null)
    {
        $this->resetForm();

        if ($id) {
            $cat = CompetitionCategory::where('eventner_id', $this->eventnerId)->findOrFail($id);
            $this->isEditMode = true;
            $this->editingId = $cat->id;
            $this->name = $cat->name;
            $this->parentId = $cat->parent_id;
            $this->kuota = $cat->kuota ?? '';
            $this->max_registrations_per_school = $cat->max_registrations_per_school ?? 1;
            $this->tanggal_pelaksanaan = $cat->tanggal_pelaksanaan ?? '';
            $this->registration_fee = $cat->registration_fee ?? '';
            $this->venueId = $cat->venue_id;
        }

        $this->showCategoryForm = true;
    }

    public function closeCategoryForm()
    {
        $this->resetForm();
        $this->showCategoryForm = false;
    }

    public function toggleExpand($id)
    {
        if (in_array($id, $this->expandedParents)) {
            $this->expandedParents = array_diff($this->expandedParents, [$id]);
        } else {
            $this->expandedParents[] = $id;
        }
    }

    public function save()
    {
        $isParent = is_null($this->parentId);

        $rules = [
            'name' => 'required|string|max:255',
            // parentId datang dari klien: tanpa scope, kategori bisa dipasang di
            // bawah Jenis Lomba tenant lain.
            'parentId' => [
                'nullable',
                Rule::exists('competition_categories', 'id')->where('eventner_id', $this->eventnerId),
            ],
        ];

        if (!$isParent) {
            $rules['kuota'] = 'nullable|integer|min:1';
            $rules['max_registrations_per_school'] = 'required|integer|min:1';
            $rules['tanggal_pelaksanaan'] = 'nullable|date';
            $rules['registration_fee'] = 'nullable|numeric|min:0';
            // exists saja tidak cukup — venueId datang dari klien, jadi harus
            // dipastikan tempatnya memang milik eventner ini (cegah IDOR).
            $rules['venueId'] = [
                'nullable',
                Rule::exists('eventner_venues', 'id')->where('eventner_id', $this->eventnerId),
            ];
        }

        $this->validate($rules);

        $data = [
            'name' => strip_tags($this->name),
            'parent_id' => $this->parentId,
        ];

        if ($isParent) {
            $data['kuota'] = null;
            $data['max_registrations_per_school'] = 1;
            $data['tanggal_pelaksanaan'] = null;
            $data['venue_id'] = null;
        } else {
            $data['kuota'] = $this->kuota ?: null;
            $data['max_registrations_per_school'] = $this->max_registrations_per_school;
            $data['tanggal_pelaksanaan'] = $this->tanggal_pelaksanaan ?: null;
            $data['registration_fee'] = $this->registration_fee !== '' ? $this->registration_fee : null;
            $data['venue_id'] = $this->venueId ?: null;
        }

        if ($this->isEditMode && $this->editingId) {
            $cat = CompetitionCategory::where('eventner_id', $this->eventnerId)->findOrFail($this->editingId);
            $cat->update($data);

            $this->toast('Kategori Lomba berhasil diperbarui.');
        } else {
            $maxOrder = CompetitionCategory::where('eventner_id', $this->eventnerId)
                ->where('parent_id', $this->parentId)
                ->max('sort_order') ?? -1;

            CompetitionCategory::create(array_merge($data, [
                'eventner_id' => $this->eventnerId,
                'sort_order' => $maxOrder + 1,
            ]));

            $this->toast(($isParent ? 'Jenis Lomba' : 'Tingkat Lomba') . ' baru berhasil ditambahkan.');
        }

        $this->resetForm();
        $this->showCategoryForm = false;
        unset($this->parentCategories, $this->orphanChildren, $this->allParents);
    }

    public function delete($id)
    {
        $cat = CompetitionCategory::where('eventner_id', $this->eventnerId)->findOrFail($id);

        if ($cat->isParent() && $cat->children()->exists()) {
            $this->gagal('Tidak bisa menghapus: Jenis Lomba ini masih memiliki ' . $cat->children()->count() . ' Tingkat Lomba. Hapus tingkatnya terlebih dahulu.');
            return;
        }

        // Foreign key-nya cascade: menghapus tingkat lomba ikut menghapus
        // pendaftar, peserta, nilai juri, dan potongannya — tanpa SoftDeletes,
        // jadi tidak bisa dikembalikan. Undian/penilaian yang sudah jalan
        // tidak boleh hilang karena satu klik; peserta harus dipindah atau
        // dihapus dulu.
        if ($cat->registrations()->exists()) {
            $this->gagal(
                'Tidak bisa menghapus: masih ada ' . $cat->registrations()->count() . ' pendaftar di tingkat ini, beserta nilai dan potongannya. Hapus pendaftarnya dulu di halaman Peserta bila memang sudah tidak dipakai.',
                route('eventner.participants.index'),
                'Buka Daftar Peserta'
            );
            return;
        }

        $cat->delete();
        $this->toast('Kategori dihapus.');
        unset($this->parentCategories, $this->orphanChildren);
    }

    public function resetForm()
    {
        $this->reset(['name', 'parentId', 'kuota', 'max_registrations_per_school', 'tanggal_pelaksanaan', 'registration_fee', 'venueId', 'isEditMode', 'editingId']);
        $this->max_registrations_per_school = 1;
    }

    public function render()
    {
        return view('livewire.eventner.competition-category.index');
    }
}
