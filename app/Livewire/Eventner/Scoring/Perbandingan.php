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

    /** Dua juri yang dihadapkan di modal (id Judge). */
    public $juriAId = '';

    public $juriBId = '';

    /** Modal perbandingan pasangan sedang terbuka. */
    public $pairTerbuka = false;

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
            // Pasangan juri ikut: juri tingkat lain tak ada di tingkat ini.
            $this->selectedRoundId = '';
            $this->selectedGroupId = '';
            $this->lepaskanPasangan();
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
        $this->lepaskanPasangan();
    }

    public function selectGroup($id)
    {
        $this->selectedGroupId = $id === null ? '' : (string) $id;
        $this->saringGrup();
        $this->detailSelKey = null;
        $this->hasilCache = null;
        $this->lepaskanPasangan();
    }

    public function updatedSelectedCategoryId()
    {
        if ($this->selectedCategoryId && ! $this->findOwnCategory($this->selectedCategoryId)) {
            $this->selectedCategoryId = null;
        }

        $this->saringBabak();
        $this->saringGrup();
        $this->lepaskanPasangan();
    }

    public function updatedSelectedRoundId()
    {
        $this->saringBabak();
        $this->detailSelKey = null;
        $this->hasilCache = null;
        $this->lepaskanPasangan();
    }

    public function updatedSelectedGroupId()
    {
        $this->saringGrup();
        $this->detailSelKey = null;
        $this->hasilCache = null;
        $this->lepaskanPasangan();
    }

    /** Juri yang bisa dibandingkan berubah begitu tingkat/babak/grup berganti. */
    private function lepaskanPasangan(): void
    {
        $this->juriAId = '';
        $this->juriBId = '';
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

    // ── Perbandingan pasangan (dua juri, di modal) ───────────────────────

    /**
     * Buka modal dan pilihkan dua juri pertama yang punya sel bersama.
     *
     * Mengisi pilihannya lebih dulu, bukan menyerahkan dua dropdown kosong:
     * juri yang tak punya satu sel pun beririsan dengan siapa pun akan
     * menghasilkan modal kosong tanpa penjelasan. Yang dipilih di sini adalah
     * pasangan yang memang bisa dibandingkan.
     */
    public function bukaPasangan()
    {
        $this->pairTerbuka = true;

        if ($this->juriAId === '' || $this->juriBId === '') {
            [$this->juriAId, $this->juriBId] = $this->pasanganAwal();
        } else {
            $this->saringPasangan();
        }

        $this->dispatch('buka-pasangan');
    }

    public function tutupPasangan()
    {
        $this->pairTerbuka = false;
        $this->dispatch('tutup-pasangan');
    }

    public function updatedJuriAId()
    {
        $this->saringPasangan();
    }

    public function updatedJuriBId()
    {
        $this->saringPasangan();
    }

    /** Juri di luar tingkat/babak/grup terpilih dilepas dari pilihan. */
    private function saringPasangan(): void
    {
        $sah = collect($this->hasil()['juri'])->map(fn ($j) => (string) $j['judge']->id)->all();

        if ($this->juriAId !== '' && ! in_array((string) $this->juriAId, $sah, true)) {
            $this->juriAId = '';
        }

        if ($this->juriBId !== '' && ! in_array((string) $this->juriBId, $sah, true)) {
            $this->juriBId = '';
        }
    }

    /**
     * Pasangan pertama yang benar-benar punya sel bersama.
     *
     * Dipilih dari jumlah pembanding tiap juri, bukan dari dua baris teratas:
     * juri "Tak dapat dibandingkan" selalu muncul di daftar tapi tak akan
     * pernah punya sel bersama siapa pun.
     *
     * @return array{0: string, 1: string}
     */
    private function pasanganAwal(): array
    {
        $baris = collect($this->hasil()['juri'])
            ->filter(fn ($j) => $j['pembanding'] > 0)
            ->take(2)
            ->values();

        return [
            $baris->get(0) ? (string) $baris[0]['judge']->id : '',
            $baris->get(1) ? (string) $baris[1]['judge']->id : '',
        ];
    }

    /** Hasil perbandingan dua juri terpilih; null kalau belum lengkap. */
    public function pasangan(): ?array
    {
        if ($this->juriAId === '' || $this->juriBId === '') {
            return null;
        }

        return (new JudgeScoreComparison)->bandingkanDuaJuri(
            $this->hasil(),
            (int) $this->juriAId,
            (int) $this->juriBId,
        );
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
            'pasangan' => $this->pasangan(),
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
