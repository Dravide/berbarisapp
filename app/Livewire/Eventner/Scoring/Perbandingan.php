<?php

namespace App\Livewire\Eventner\Scoring;

use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Services\JudgeScoreComparison;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Perbandingan nilai antar juri satu tingkat lomba.
 *
 * Halaman ini hanya MEMBACA. Mengoreksi nilai tetap lewat Input Nilai — satu
 * pintu tulis, seperti sebelumnya. Seluruh matematikanya ada di
 * JudgeScoreComparison supaya layar ini tak menyimpan rumus sendiri; pembaca
 * berikutnya (PDF/CSV) tinggal memanggil service yang sama.
 */
#[Layout('layouts.admin')]
class Perbandingan extends Component
{
    public $eventner;

    public $selectedCategoryId;

    /** Babak yang dianalisis (id CompetitionRound). '' = tanpa babak. */
    public $selectedRoundId = '';

    /** Grup yang dianalisis (id CompetitionGroup). '' = seluruh tingkat. */
    public $selectedGroupId = '';

    /**
     * Satu sel yang sedang dibuka rinciannya di modal — KUNCINYA saja, bukan
     * array-nya.
     *
     * Array sel memuat model Eloquent (peserta, kriteria). Menyimpannya di
     * properti publik berarti Livewire harus menyerialkannya ke klien, dan
     * model tidak bisa diserialkan begitu. Kunci kecil ini yang dikirim, lalu
     * selnya dicari ulang di render().
     */
    public $detailSelKey = null;

    protected $queryString = [
        'selectedCategoryId' => ['except' => ''],
        'selectedRoundId' => ['except' => ''],
        'selectedGroupId' => ['except' => ''],
    ];

    public function mount()
    {
        $this->eventner = Auth::user()->eventner;

        if (! $this->eventner) {
            abort(403, 'Anda belum memiliki data Event terdaftar.');
        }

        // Tingkat datang dari tiepkan Input Nilai. Kalau ia milik tenant lain
        // — atau sejak itu dihapus — jangan diteruskan: seluruh halaman ini
        // menampilkan nilai orang, jadi salah tingkat berarti nilai bocor.
        if ($this->selectedCategoryId && ! $this->findOwnCategory($this->selectedCategoryId)) {
            abort(404);
        }

        if (! $this->selectedCategoryId) {
            $this->selectedCategoryId = $this->tingkatPertama();
        }

        // Hook updated*() tidak jalan saat hidrasi awal, jadi nilai yang datang
        // dari query string harus disaring manual — URL lama bisa menyebut
        // babak/grup yang sudah tidak ada.
        $this->saringBabak();
        $this->saringGrup();
    }

    private function findOwnCategory($id): ?CompetitionCategory
    {
        return CompetitionCategory::where('eventner_id', $this->eventner->id)->find($id);
    }

    /** Tingkat pertama yang punya peserta — supaya halaman tak mendarat di tingkat hampa. */
    private function tingkatPertama(): ?int
    {
        $daftar = $this->eventner->competitionCategories()
            ->whereNotNull('parent_id')
            ->withCount('registrations')
            ->orderBy('sort_order')
            ->get();

        $pilih = $daftar->firstWhere('registrations_count', '>', 0) ?? $daftar->first();

        return $pilih?->id;
    }

    public function selectCategory($id)
    {
        if ((string) $this->selectedCategoryId !== (string) $id) {
            // Babak dan grup menempel pada tingkat; keduanya wajib dilepas.
            $this->selectedRoundId = '';
            $this->selectedGroupId = '';
        }

        $this->selectedCategoryId = $id;
        $this->detailSelKey = null;
        $this->hasilCache = null;
    }

    public function selectRound($id)
    {
        $this->selectedRoundId = $id === null ? '' : (string) $id;
        $this->saringBabak();
        $this->detailSelKey = null;
        $this->hasilCache = null;
    }

    public function selectGroup($id)
    {
        $this->selectedGroupId = $id === null ? '' : (string) $id;
        $this->saringGrup();
        $this->detailSelKey = null;
        $this->hasilCache = null;
    }

    public function updatedSelectedCategoryId()
    {
        if ($this->selectedCategoryId && ! $this->findOwnCategory($this->selectedCategoryId)) {
            $this->selectedCategoryId = null;
        }

        $this->saringBabak();
        $this->saringGrup();
    }

    public function updatedSelectedRoundId()
    {
        $this->saringBabak();
        $this->detailSelKey = null;
        $this->hasilCache = null;
    }

    public function updatedSelectedGroupId()
    {
        $this->saringGrup();
        $this->detailSelKey = null;
        $this->hasilCache = null;
    }

    /** Babak wajib milik tingkat terpilih; kalau tidak, dilepas. */
    private function saringBabak(): void
    {
        if ($this->selectedRoundId === '' || $this->selectedRoundId === null) {
            $this->selectedRoundId = '';

            return;
        }

        $ada = CompetitionRound::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->selectedCategoryId)
            ->find($this->selectedRoundId);

        $this->selectedRoundId = $ada ? (string) $ada->id : '';
    }

    /** Grup wajib milik tingkat terpilih; kalau tidak, dilepas. */
    private function saringGrup(): void
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

    /**
     * Buka rincian satu sel (nilai tiap juri untuk peserta × kriteria).
     *
     * Selnya dicari ulang saat render — yang disimpan cuma kuncinya, karena
     * array sel memuat model Eloquent yang tak bisa diserialkan ke klien.
     */
    public function lihatSel(string $key)
    {
        $this->detailSelKey = $key;
        $this->dispatch('buka-sel');
    }

    public function tutupSel()
    {
        $this->detailSelKey = null;
        $this->dispatch('tutup-sel');
    }

    /** Kunci satu sel yang stabil antara PHP dan DOM. */
    public function kunciSel(array $sel): string
    {
        return $sel['registration_id'] . '-' . $sel['criteria']->id;
    }

    /** Hasil analisis untuk tingkat/babak/grup yang sedang dipilih. */
    public function hasil(): array
    {
        if ($this->hasilCache !== null) {
            return $this->hasilCache;
        }

        if (! $this->selectedCategoryId) {
            return $this->hasilCache = (new JudgeScoreComparison)->build($this->eventner, 0);
        }

        return $this->hasilCache = (new JudgeScoreComparison)->build(
            $this->eventner,
            (int) $this->selectedCategoryId,
            $this->selectedRoundId !== '' ? (int) $this->selectedRoundId : null,
            $this->selectedGroupId !== '' ? (int) $this->selectedGroupId : null,
        );
    }

    public function render()
    {
        $this->eventner = Auth::user()->eventner;

        if (! $this->eventner) {
            abort(403, 'Anda belum memiliki data Event terdaftar.');
        }

        $kategori = $this->eventner->competitionCategories()
            ->whereNotNull('parent_id')
            ->withCount('registrations')
            ->orderBy('sort_order')
            ->get();

        $tingkat = $this->selectedCategoryId ? $this->findOwnCategory($this->selectedCategoryId) : null;

        $babak = $tingkat
            ? CompetitionRound::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $tingkat->id)
                ->orderBy('sort_order')->get()
            : collect();

        $grup = $tingkat
            ? CompetitionGroup::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $tingkat->id)
                ->orderBy('sort_order')->get()
            : collect();

        $hasil = $this->hasil();

        return view('livewire.eventner.scoring.perbandingan', [
            'categories' => $kategori,
            'selectedCategory' => $tingkat,
            'rounds' => $babak,
            'groups' => $grup,
            'hasil' => $hasil,
            'selTerbuka' => $this->cariSel($hasil),
            'kunciSel' => fn (array $sel) => $this->kunciSel($sel),
        ])->title('Perbandingan Nilai Juri - ' . $this->eventner->nama_event);
    }

    /**
     * Analisis dijalankan sekali per permintaan.
     *
     * render() membutuhkannya untuk seluruh tabel, dan modal butuh satu selnya.
     * Tanpa memo ini satu halaman dibuka = dua kali seluruh statistik dihitung.
     */
    private ?array $hasilCache = null;

    private function cariSel(array $hasil): ?array
    {
        if (! $this->detailSelKey) {
            return null;
        }

        foreach ($hasil['sel'] as $sel) {
            if ($this->kunciSel($sel) === $this->detailSelKey) {
                return $sel;
            }
        }

        return null;
    }
}
