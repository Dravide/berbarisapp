<?php

namespace App\Livewire\Eventner\ScoreRecap;

use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Services\ScoreRecapBuilder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Index extends Component
{
    public $eventner;
    public $selectedCategoryId;

    /**
     * Grup yang sedang direkap. '' = seluruh tingkat (perilaku lama).
     */
    public $selectedGroupId = '';

    protected $queryString = [
        'selectedCategoryId' => ['except' => ''],
        'selectedGroupId' => ['except' => ''],
    ];

    public function mount()
    {
        $this->eventner = Auth::user()->eventner;

        if (!$this->eventner) {
            abort(403, 'Anda belum memiliki data Event terdaftar.');
        }

        // Default ke kategori pertama yang punya peserta. Dulu memakai
        // competitionCategories->first() apa adanya — itu bisa kategori
        // INDUK, sedangkan registrasi selalu menempel ke kategori anak, dan
        // pilihan di layar hanya menampilkan anak. Akibatnya rekap tampak
        // kosong saat halaman pertama dibuka.
        if (!$this->selectedCategoryId) {
            $daftar = $this->eventner->competitionCategories()
                ->whereNotNull('parent_id')
                ->withCount('registrations')
                ->orderBy('sort_order')
                ->get();

            $first = $daftar->firstWhere('registrations_count', '>', 0) ?? $daftar->first();

            if ($first) {
                $this->selectedCategoryId = $first->id;
            }
        }

        // selectedGroupId datang dari $queryString, dan hook updatedSelectedGroupId()
        // TIDAK jalan saat hidrasi awal — jadi tanpa pemeriksaan ini URL yang
        // menyebut grup milik tingkat lain (tautan lama, atau grup yang sejak
        // itu dihapus) menyaring seluruh peserta tanpa satu pun pesan, dan
        // rekapnya cuma "Belum Ada Data" padahal datanya ada.
        $this->updatedSelectedGroupId();
    }

    public function selectCategory($id)
    {
        if ((string) $this->selectedCategoryId !== (string) $id) {
            $this->selectedGroupId = '';
        }

        $this->selectedCategoryId = $id;
    }

    /**
     * Ganti grup. Grup dari klien wajib milik tingkat terpilih — kalau tidak,
     * rekap bisa menampilkan peserta dan nilai tenant/tingkat lain.
     */
    public function selectGroup($id)
    {
        $this->selectedGroupId = '';

        if ($id !== '' && $id !== null) {
            $ada = CompetitionGroup::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $this->selectedCategoryId)
                ->find($id);

            if (! $ada) {
                $this->addError('selectedGroupId', 'Grup tidak ditemukan pada tingkat lomba ini.');

                return;
            }

            $this->selectedGroupId = (string) $ada->id;
        }

        $this->resetErrorBag('selectedGroupId');
    }

    public function updatedSelectedCategoryId()
    {
        // selectedCategoryId juga bisa datang dari klien — scope ulang.
        if ($this->selectedCategoryId
            && !CompetitionCategory::where('eventner_id', $this->eventner->id)->find($this->selectedCategoryId)) {
            $this->selectedCategoryId = null;
        }

        // Grup lama milik tingkat lama, jadi wajib dilepas.
        if ($this->selectedGroupId !== '' && ! CompetitionGroup::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->selectedCategoryId)
            ->whereKey($this->selectedGroupId)
            ->exists()) {
            $this->selectedGroupId = '';
        }
    }

    public function updatedSelectedGroupId()
    {
        if ($this->selectedGroupId === '' || $this->selectedGroupId === null) {
            $this->selectedGroupId = '';

            return;
        }

        $ada = CompetitionGroup::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->selectedCategoryId)
            ->find($this->selectedGroupId);

        $this->selectedGroupId = $ada ? (string) $ada->id : '';
    }

    public function render()
    {
        $categories = $this->eventner->competitionCategories()
            ->whereNotNull('parent_id')
            ->withCount('registrations')
            ->get();

        $selectedCategory = null;
        $scoringData = collect();
        $sections = [];
        $hasRounds = false;
        $roundOptions = collect();
        $groups = collect();

        if ($this->selectedCategoryId) {
            // Scoping ke eventner sendiri — cegah baca data kategori/registrasi tenant lain.
            $selectedCategory = CompetitionCategory::where('eventner_id', $this->eventner->id)
                ->find($this->selectedCategoryId);

            // Seluruh susunan babak/grup/peringkat dihitung ScoreRecapBuilder:
            // angka yang sama dipakai berkas PDF rekap keseluruhan, jadi dua
            // salinan rumusnya pasti berbeda begitu salah satunya diperbaiki.
            $rekap = (new ScoreRecapBuilder)->build(
                $this->eventner,
                (int) $this->selectedCategoryId,
                $this->selectedGroupId,
            );

            $sections = $rekap['sections'];
            $hasRounds = $rekap['hasRounds'];
            $roundOptions = $rekap['rounds'];
            $groups = $rekap['groups'];
            $scoringData = $rekap['scoringData'];
        }

        return view('livewire.eventner.score-recap.index', [
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'sections' => $sections,
            'hasRounds' => $hasRounds,
            'rounds' => $roundOptions,
            'scoringData' => $scoringData,
            'groups' => $groups,
        ])->title('Rekap Nilai - ' . $this->eventner->nama_event);
    }
}
