<?php

namespace App\Livewire\Eventner\Layout;

use App\Livewire\Concerns\MelaporKePengguna;
use App\Models\CompetitionLayout;
use App\Models\LayoutZone;
use App\Traits\FeatureGatedComponent;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Editor denah: kanvas, daftar zona, panel sifat zona terpilih, dan alur.
 *
 * Kanvasnya punya dua mode. Di mode Zona kotak-kotak yang diseret; di mode
 * Alur yang diseret titik alurnya. Dua mode, bukan satu kanvas pintar, karena
 * titik alur sering jatuh tepat di atas arena — kalau keduanya bisa diseret
 * sekaligus, memindahkan titik alur akan selalu berarti memindahkan arena.
 *
 * Posisi dikirim SEKALI saat seret selesai, bukan tiap piksel — kalau tidak,
 * satu seretan lima detik jadi ratusan request.
 *
 * Yang disimpan selalu persen, jadi denah yang sama tampil identik di kanvas,
 * halaman publik, dan PDF.
 */
#[Layout('layouts.admin')]
class Show extends Component
{
    use FeatureGatedComponent;
    use MelaporKePengguna;

    protected string $requiredFeature = 'layout';

    public CompetitionLayout $layout;

    public ?int $selectedId = null;

    // ── Mode kanvas & alur ─────────────────────────────────────────────────
    public string $mode = 'zona';

    public ?int $titikTerpilih = null;

    public string $titikLabel = '';

    public string $titikHadap = '';

    // ── Sifat zona terpilih (form panel kanan) ─────────────────────────────
    public string $nama = '';

    public string $kapasitas = '';

    public string $catatan = '';

    public string $nomorDp = '';

    // ── Modal "Susun DP otomatis" ──────────────────────────────────────────
    public bool $showDpModal = false;

    public string $dpJumlah = '5';

    public string $dpLetak = 'bawah';

    public string $dpTinggi = '5';

    public bool $dpDenganPanah = true;

    // ── Modal pengaturan papan ─────────────────────────────────────────────
    public bool $showPapanModal = false;

    public string $papanLebar = '';

    public string $papanTinggi = '';

    protected function rules(): array
    {
        return [
            'nama' => 'required|string|max:255',
            'kapasitas' => 'nullable|string|max:64',
            'catatan' => 'nullable|string|max:255',
            'nomorDp' => 'nullable|integer|min:1|max:99',
            'titikLabel' => 'nullable|string|max:60',
            'titikHadap' => 'nullable|integer|min:0|max:359',
        ];
    }

    public function mount(CompetitionLayout $layout): void
    {
        // Denah milik event lain ditolak lewat pengurungan eventner_id, bukan
        // policy — pola yang sama dengan Venue/Document.
        if ((int) $layout->eventner_id !== (int) Auth::user()->eventner->id) {
            abort(404);
        }

        $this->bootFeatureGate();

        $this->layout = $layout->load('competitionCategory.parent', 'venue');
        $this->papanLebar = (string) $this->layout->ground_width;
        $this->papanTinggi = (string) $this->layout->ground_height;
    }

    /** Segarkan model dari DB — dipakai setelah susun DP & hapus zona. */
    private function segarkan(): void
    {
        $this->layout->refresh()->load('competitionCategory.parent', 'venue');
    }

    // ── Panel kiri: pilih & tambah ─────────────────────────────────────────

    public function pilihZona(int $id): void
    {
        $zona = $this->zonaMilikSaya($id);

        $this->selectedId = $zona->id;
        $this->nama = $zona->name;
        $this->kapasitas = (string) ($zona->capacity ?? '');
        $this->catatan = (string) ($zona->note ?? '');
        $this->nomorDp = $zona->dp_number ? (string) $zona->dp_number : '';
    }

    public function batalPilih(): void
    {
        $this->reset(['selectedId', 'nama', 'kapasitas', 'catatan', 'nomorDp']);
        $this->resetValidation();
    }

    /**
     * Tambah zona baru di tengah kanvas.
     *
     * Ukuran bawaannya berbeda per jenis: pos penilaian itu meja kecil, tribun
     * memanjang, arena luas.
     */
    public function tambahZona(string $type): void
    {
        if (! array_key_exists($type, LayoutZone::TIPE_LABEL)) {
            return;
        }

        $ukuran = match ($type) {
            LayoutZone::TYPE_ARENA => ['w' => 40, 'h' => 26, 'x' => 30, 'y' => 34],
            LayoutZone::TYPE_TRIBUN => ['w' => 60, 'h' => 9, 'x' => 20, 'y' => 6],
            LayoutZone::TYPE_POS_PENILAIAN => ['w' => 14, 'h' => 12, 'x' => 80, 'y' => 20],
            LayoutZone::TYPE_SEKRETARIAT => ['w' => 16, 'h' => 12, 'x' => 80, 'y' => 60],
            LayoutZone::TYPE_JALUR_MASUK => ['w' => 8, 'h' => 30, 'x' => 4, 'y' => 34],
            LayoutZone::TYPE_PANAH => ['w' => 40, 'h' => 4, 'x' => 30, 'y' => 70],
            // Tiang bendera itu tonggak, bukan bidang: tinggi sedikit, sempit,
            // dan ditaruh di tepi arena — bukan di tengahnya.
            LayoutZone::TYPE_TIANG_BENDERA => ['w' => 5, 'h' => 12, 'x' => 6, 'y' => 70],
            LayoutZone::TYPE_DP => ['w' => 12, 'h' => 20, 'x' => 2, 'y' => 40],
            default => ['w' => 18, 'h' => 8, 'x' => 40, 'y' => 44],
        };

        $urut = (int) $this->layout->zones()->max('sort_order') + 1;

        // Nomor DP dipakai untuk nama DAN kolomnya; kalau namanya saja yang
        // diisi, "DP 3" terlihat di denah tapi daftar DP publik jadi kosong.
        $nomorDp = $type === LayoutZone::TYPE_DP
            ? (int) $this->layout->zones()->dp()->max('dp_number') + 1
            : null;

        $zona = $this->layout->zones()->create(array_merge($ukuran, [
            'name' => $this->namaBawaan($type, $urut, $nomorDp),
            'type' => $type,
            'dp_number' => $nomorDp,
            'sort_order' => $urut,
        ]));

        $this->segarkan();
        $this->pilihZona($zona->id);
        $this->toast('Zona ditambahkan. Seret untuk memindahkan.');
    }

    /**
     * Nama bawaan per jenis.
     *
     * Zona tanpa nama tak bisa dibedakan saat denahnya sudah penuh. Arena &
     * tribun cukup satu karena memang tunggal; pos penilaian & DP diberi nomor.
     */
    private function namaBawaan(string $type, int $urut, ?int $nomorDp = null): string
    {
        if ($type === LayoutZone::TYPE_POS_PENILAIAN) {
            $nomor = $this->layout->zones()->where('type', $type)->count() + 1;

            return 'Pos Penilaian '.$this->huruf($nomor);
        }

        if ($type === LayoutZone::TYPE_DP) {
            return 'DP '.$nomorDp;
        }

        return LayoutZone::TIPE_LABEL[$type].' '.$urut;
    }

    private function huruf(int $n): string
    {
        return chr(64 + max(1, min(26, $n)));
    }

    private function zonaMilikSaya(int $id): LayoutZone
    {
        return $this->layout->zones()->findOrFail($id);
    }

    // ── Kanvas: seret & ubah ukuran ────────────────────────────────────────

    /**
     * Simpan geometri satu zona. Dipanggil sekali di akhir seretan.
     *
     * Nilainya dijepit ke 0–100: kanvas bisa menghasilkan angka di luar rentang
     * saat diseret melewati tepi, dan tanpa jepitan zona tersimpan di luar papan
     * lalu tak terlihat lagi.
     */
    public function simpanPosisi(int $id, $x, $y, $w = null, $h = null): void
    {
        $zona = $this->zonaMilikSaya($id);
        $zona->update([
            'x' => $this->jepit($x),
            'y' => $this->jepit($y),
            'w' => $w === null ? $zona->w : $this->jepit($w, 2),
            'h' => $h === null ? $zona->h : $this->jepit($h, 2),
        ]);

        $this->segarkan();
    }

    private function jepit($nilai, float $minimum = 0): float
    {
        $angka = (float) $nilai;

        return round(max($minimum, min(100, $angka)), 3);
    }

    // ── Panel kanan: sifat zona ────────────────────────────────────────────

    public function simpanZona(): void
    {
        if (! $this->selectedId) {
            return;
        }

        $this->validate();

        $zona = $this->zonaMilikSaya($this->selectedId);

        $zona->update([
            'name' => strip_tags($this->nama),
            'capacity' => $this->kapasitas ? strip_tags($this->kapasitas) : null,
            'note' => $this->catatan ? strip_tags($this->catatan) : null,
            // dp_number hanya bermakna untuk zona DP; menaruhnya di zona lain
            // akan membuat "DP 1" muncul dua kali di daftar.
            'dp_number' => $zona->type === LayoutZone::TYPE_DP && $this->nomorDp !== ''
                ? (int) $this->nomorDp
                : null,
        ]);

        $this->segarkan();
        $this->toast('Zona disimpan.');
    }

    public function hapusZona(int $id): void
    {
        $this->zonaMilikSaya($id)->delete();

        if ($this->selectedId === $id) {
            $this->batalPilih();
        }

        $this->segarkan();
        $this->toast('Zona dihapus.');
    }

    /** Duplikat zona terpilih, digeser sedikit supaya tidak menumpuk persis. */
    public function duplikatZona(int $id): void
    {
        $zona = $this->zonaMilikSaya($id);

        $salin = $this->layout->zones()->create([
            'name' => $zona->name,
            'type' => $zona->type,
            'x' => min(92, (float) $zona->x + 3),
            'y' => min(92, (float) $zona->y + 3),
            'w' => $zona->w,
            'h' => $zona->h,
            'capacity' => $zona->capacity,
            'note' => $zona->note,
            'dp_number' => null,
            'sort_order' => (int) $this->layout->zones()->max('sort_order') + 1,
        ]);

        $this->segarkan();
        $this->pilihZona($salin->id);
        $this->toast('Zona diduplikat.');
    }

    // ── Alur gerak pasukan ─────────────────────────────────────────────────

    public function gantiMode(string $mode): void
    {
        if (! in_array($mode, ['zona', 'alur'], true)) {
            return;
        }

        $this->mode = $mode;
        $this->resetValidation();

        if ($mode === 'zona') {
            $this->batalPilihTitik();
        } else {
            $this->batalPilih();
        }
    }

    /**
     * Mulai alur baru dengan dua titik contoh di tepi kiri papan.
     *
     * Menaruhnya di kiri, bukan di tengah: alur baris-berbaris hampir selalu
     * masuk dari sisi, dan titik di tengah kanvas langsung menimpa arena.
     */
    public function mulaiAlur(): void
    {
        if ($this->layout->punya_alur) {
            return;
        }

        $this->simpanTitikMentah([
            ['x' => 8.0, 'y' => 88.0, 'kind' => 'start', 'label' => 'Mulai', 'heading' => 0],
            ['x' => 8.0, 'y' => 30.0, 'kind' => 'end', 'label' => 'Selesai', 'heading' => 0],
        ]);

        $this->mode = 'alur';
        $this->pilihTitik(0);
        $this->toast('Alur dibuat. Seret titiknya, atau klik garisnya untuk menyisipkan titik.');
    }

    public function hapusAlur(): void
    {
        $this->layout->update(['route_points' => null]);
        $this->segarkan();
        $this->batalPilihTitik();
        $this->toast('Alur dihapus.', 'info');
    }

    /** Simpan titik di ujung alur — dipakai tombol "Tambah titik". */
    public function tambahTitikAkhir(): void
    {
        $titik = $this->layout->alur_titik;

        if ($titik === []) {
            $this->mulaiAlur();

            return;
        }

        // Digeser sedikit dari titik terakhir supaya ruasnya terlihat; titik
        // yang menumpuk persis di atas titik sebelumnya tak bisa diseret.
        $akhir = end($titik);

        $titik[] = [
            'x' => min(100, $akhir['x'] + 12),
            'y' => max(0, $akhir['y'] - 12),
            'kind' => 'turn',
            'label' => '',
            'heading' => null,
        ];

        $this->simpanTitikMentah($titik);
        $this->pilihTitik(count($titik) - 1);
        $this->toast('Titik ditambahkan di ujung alur.');
    }

    /**
     * Sisipkan titik di tengah satu ruas.
     *
     * Titik baru ditaruh di titik tengah ruas itu, jadi menyisipkan tak pernah
     * mengubah bentuk alur yang sudah digambar — panitia tinggal menyeretnya.
     */
    public function sisipTitik(int $ruas): void
    {
        $titik = $this->layout->alur_titik;

        if (! isset($titik[$ruas], $titik[$ruas + 1])) {
            return;
        }

        $a = $titik[$ruas];
        $b = $titik[$ruas + 1];

        array_splice($titik, $ruas + 1, 0, [[
            'x' => round(($a['x'] + $b['x']) / 2, 3),
            'y' => round(($a['y'] + $b['y']) / 2, 3),
            'kind' => 'turn',
            'label' => '',
            'heading' => null,
        ]]);

        $this->simpanTitikMentah($titik);
        $this->pilihTitik($ruas + 1);
        $this->toast('Titik disisipkan. Seret untuk menempatkannya.');
    }

    public function pilihTitik(int $i): void
    {
        $titik = $this->layout->alur_titik;

        if (! isset($titik[$i])) {
            return;
        }

        $this->titikTerpilih = $i;
        $this->titikLabel = $titik[$i]['label'];
        $this->titikHadap = $titik[$i]['heading'] === null ? '' : (string) $titik[$i]['heading'];
    }

    public function batalPilihTitik(): void
    {
        $this->reset(['titikTerpilih', 'titikLabel', 'titikHadap']);
        $this->resetValidation();
    }

    /** Dipanggil sekali di akhir seretan titik alur. */
    public function simpanTitik(int $i, $x, $y): void
    {
        $titik = $this->layout->alur_titik;

        if (! isset($titik[$i])) {
            return;
        }

        $titik[$i]['x'] = $this->jepit($x);
        $titik[$i]['y'] = $this->jepit($y);

        $this->simpanTitikMentah($titik);
    }

    public function simpanSifatTitik(): void
    {
        if ($this->titikTerpilih === null) {
            return;
        }

        $this->validate([
            'titikLabel' => 'nullable|string|max:60',
            'titikHadap' => 'nullable|integer|min:0|max:359',
        ]);

        $titik = $this->layout->alur_titik;
        $i = $this->titikTerpilih;

        if (! isset($titik[$i])) {
            return;
        }

        $titik[$i]['label'] = trim(strip_tags($this->titikLabel));
        $titik[$i]['heading'] = $this->titikHadap === '' ? null : (int) $this->titikHadap;

        $this->simpanTitikMentah($titik);
        $this->toast('Titik alur disimpan.');
    }

    public function ubahJenisTitik(string $jenis): void
    {
        if (! array_key_exists($jenis, CompetitionLayout::TITIK_JENIS) || $this->titikTerpilih === null) {
            return;
        }

        $titik = $this->layout->alur_titik;
        $i = $this->titikTerpilih;

        if (! isset($titik[$i])) {
            return;
        }

        $titik[$i]['kind'] = $jenis;

        $this->simpanTitikMentah($titik);
        $this->toast('Jenis titik diubah.');
    }

    public function hapusTitik(int $i): void
    {
        $titik = $this->layout->alur_titik;

        if (! isset($titik[$i])) {
            return;
        }

        array_splice($titik, $i, 1);

        // Sisa satu titik bukan alur, dan menggambar satu titik saja di halaman
        // publik cuma membingungkan.
        if (count($titik) < 2) {
            $this->layout->update(['route_points' => null]);
            $this->segarkan();
            $this->batalPilihTitik();
            $this->toast('Alur dihapus karena titiknya tinggal satu.', 'info');

            return;
        }

        $this->simpanTitikMentah($titik);
        $this->batalPilihTitik();
        $this->toast('Titik dihapus.');
    }

    public function toggleAlurPublik(): void
    {
        $this->layout->update(['route_visible' => ! $this->layout->route_visible]);
        $this->segarkan();
        $this->toast(
            $this->layout->route_visible
                ? 'Alur ditampilkan di halaman publik.'
                : 'Alur disembunyikan dari halaman publik.',
            'info',
        );
    }

    /**
     * Tulis ulang seluruh kolom route_points.
     *
     * Selalu lewat sini, bukan update() bertarget: `route_points` bertipe array,
     * dan menuliskan satu indeksnya saja tak bisa dilacak Eloquent sebagai
     * perubahan — titik yang digeser akan tampak tersimpan padahal tidak.
     */
    private function simpanTitikMentah(array $titik): void
    {
        $this->layout->update(['route_points' => array_values($titik)]);
        $this->segarkan();
    }

    // ── Susun DP otomatis ──────────────────────────────────────────────────

    public function bukaDpModal(): void
    {
        $this->showDpModal = true;
        $this->resetValidation();
    }

    public function tutupDpModal(): void
    {
        $this->showDpModal = false;
    }

    /**
     * Susun barisan DP 1..N + arena bawaan bila papan belum punya arena.
     *
     * IDEMPOTEN: DP lama dibuang lebih dulu, jadi menekan tombolnya dua kali
     * menghasilkan denah yang sama. Zona selain DP dan panah bawaan tak disentuh.
     */
    public function susunDp(): void
    {
        $this->validate([
            'dpJumlah' => 'required|integer|min:1|max:12',
            'dpTinggi' => 'required|numeric|min:1|max:30',
            'dpLetak' => 'required|in:bawah,atas,samping',
        ]);

        $zones = $this->layout->zones()->get();
        $kolomDp = $zones->where('type', LayoutZone::TYPE_DP)->pluck('id');

        // Panah bawaan ikut diganti supaya tak menumpuk; panah yang namanya
        // diubah panitia dipertahankan.
        $panahBawaan = $zones->where('type', LayoutZone::TYPE_PANAH)
            ->where('name', 'Arah gerak pasukan')
            ->pluck('id');

        LayoutZone::whereIn('id', $kolomDp->merge($panahBawaan))->delete();

        $adaArena = $zones->where('type', LayoutZone::TYPE_ARENA)->isNotEmpty();

        if (! $adaArena) {
            $this->layout->zones()->create(array_merge(
                LayoutZone::arenaBawaan($this->dpLetak),
                ['capacity' => null, 'sort_order' => 0],
            ));
        }

        $urut = (int) $this->layout->zones()->max('sort_order') + 1;

        $baris = LayoutZone::susunDp(
            (int) $this->dpJumlah,
            $this->dpLetak,
            (float) $this->dpTinggi,
            (float) $this->layout->ground_width,
            (float) $this->layout->ground_height,
            $this->dpDenganPanah,
        );

        foreach ($baris as $zona) {
            $this->layout->zones()->create(array_merge($zona, ['sort_order' => $urut++]));
        }

        $this->segarkan();
        $this->batalPilih();
        $this->showDpModal = false;

        $this->toast($this->dpJumlah.' daerah persiapan disusun otomatis. Seret bila perlu digeser.');
    }

    // ── Papan ──────────────────────────────────────────────────────────────

    public function bukaPapanModal(): void
    {
        $this->papanLebar = (string) $this->layout->ground_width;
        $this->papanTinggi = (string) $this->layout->ground_height;
        $this->showPapanModal = true;
    }

    public function tutupPapanModal(): void
    {
        $this->showPapanModal = false;
    }

    /**
     * Ubah ukuran papan dalam meter.
     *
     * Zona tidak ikut dihitung ulang, dan itu memang yang diinginkan: posisinya
     * persen, jadi denahnya tetap proporsional berapa pun ukuran papannya.
     */
    public function simpanPapan(): void
    {
        $this->validate([
            'papanLebar' => 'required|numeric|min:1|max:9999',
            'papanTinggi' => 'required|numeric|min:1|max:9999',
        ]);

        $this->layout->update([
            'ground_width' => (float) $this->papanLebar,
            'ground_height' => (float) $this->papanTinggi,
        ]);

        $this->segarkan();
        $this->showPapanModal = false;
        $this->toast('Ukuran papan disimpan.');
    }

    public function ubahNamaDenah(string $nama): void
    {
        $nama = trim(strip_tags($nama));

        if ($nama === '' || mb_strlen($nama) > 255) {
            return;
        }

        $this->layout->update(['name' => $nama]);
        $this->segarkan();
    }

    // ── Terbit ─────────────────────────────────────────────────────────────

    public function terbitkan(): void
    {
        if (! $this->layout->sudahTerisi()) {
            $this->gagal('Denah masih kosong. Tambahkan minimal satu zona sebelum diterbitkan.');

            return;
        }

        $this->layout->update(['is_published' => true, 'published_at' => now()]);
        $this->segarkan();
        $this->toast('Denah diterbitkan. Tautannya muncul di halaman publik event.');
    }

    public function tarik(): void
    {
        $this->layout->update(['is_published' => false]);
        $this->segarkan();
        $this->toast('Denah ditarik dari halaman publik.', 'info');
    }

    /**
     * PNG diambil dari kanvas yang sedang tampil, bukan digambar ulang server.
     *
     * Denah yang diunduh karena itu persis sama dengan yang dilihat panitia.
     * html2canvas diambil dari CDN saat diklik, jadi editor tidak menanggung
     * ±200 KB skrip itu bagi yang tak pernah mengunduh.
     */
    public function unduhPng(): void
    {
        $this->dispatch('unduh-denah-png', nama: $this->namaBerkas());
    }

    public function namaBerkas(): string
    {
        $nama = preg_replace('/[^A-Za-z0-9\-]+/', '-', $this->layout->name) ?: 'denah';

        return 'denah-'.trim($nama, '-').'-'.now()->format('Ymd');
    }

    public function render()
    {
        $zones = $this->layout->zones()->get();
        $terpilih = $this->selectedId ? $zones->firstWhere('id', $this->selectedId) : null;

        return view('livewire.eventner.layout.show', [
            'zones' => $zones,
            'terpilih' => $terpilih,
            'tipeTersedia' => LayoutZone::TIPE_LABEL,
            'ikonTipe' => LayoutZone::TIPE_IKON,
            'jenisTitik' => CompetitionLayout::TITIK_JENIS,
        ])->title('Editor Denah - '.$this->layout->name);
    }
}
