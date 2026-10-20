<?php

namespace Tests\Feature;

use App\Livewire\Admin\Setting\LandingPage as AdminLandingPage;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Section "Fitur Lengkap untuk Event Sukses" (#features).
 *
 * Dua temuan yang pernah nyata di produksi dan dijaga di sini:
 *
 * - `$iconMap` berhenti di `icon8`, jadi Sertifikat Digital, Livestream
 *   Overlay, dan Sponsor & Tenant semuanya memperoleh `ti-star` yang sama.
 * - `icon7` memakai `ti-scoreboard`, yang tidak punya glif di font Tabler
 *   lokal (1.112.0) yang dimuat halaman ini — ikon Live Scoreboard kosong
 *   begitu CDN gagal dimuat.
 */
class LandingFeaturesTest extends TestCase
{
    use RefreshDatabase;

    /** Sembilan item kanonik, sama dengan seeder. */
    private function features(array $overrides = []): array
    {
        $items = [];
        $judul = [
            'Manajemen Pendaftaran',
            'Penilaian Juri Digital',
            'Voting Online',
            'E-Tiket & Check-in QR',
            'Live Scoreboard',
            'Drawing & Undian',
            'Sertifikat Digital',
            'Livestream Overlay',
            'Sponsor & Tenant',
        ];

        foreach ($judul as $i => $t) {
            $items[] = array_merge([
                'icon' => 'icon' . ($i + 3) . '.png',
                'title' => $t,
                'description' => 'Deskripsi ' . $t . '.',
            ], $overrides[$i] ?? []);
        }

        return $items;
    }

    private function setFeatures(array $items): void
    {
        Setting::set('landing_features', json_encode([
            'title' => 'Fitur Lengkap untuk Event Sukses',
            'items' => $items,
        ]));
    }

    /** Posisi kemunculan pertama tiap judul, dalam urutan dokumen. */
    private function posisiJudul(string $html, array $items): array
    {
        $pos = [];
        foreach ($items as $i => $item) {
            // h3 judul kartu — satu-satunya tempat judul dirender untuk ikon
            // kanonik (miniatur cabang @default juga menyisipkan judul, tapi
            // cabang itu tidak pernah kena untuk icon3–icon11).
            // Judul di-escape Blade sebelum masuk HTML (`&` jadi `&amp;`),
            // jadi pembandingnya pun harus lewat e().
            $pos[$i] = strpos($html, $this->judulKartu($item));
        }
        return $pos;
    }

    private function judulKartu(array $item): string
    {
        return '<h3 class="text-lg font-bold text-deep-slate">' . e($item['title']) . '</h3>';
    }

    /** Indeks item yang kartunya benar-benar dirender, urut dokumen. */
    private function dirender(string $html, array $items): array
    {
        $pos = $this->posisiJudul($html, $items);
        $ada = array_filter($pos, fn ($p) => $p !== false);
        asort($ada);

        return array_keys($ada);
    }

    public function test_anchor_features_tetap_ada()
    {
        // Gerbang nav di proyek ini: tautan nav hanya sah kalau section-nya
        // benar-benar render. Header landing menaut ke #features.
        $this->assertStringContainsString('id="features"', $this->get('/')->getContent());
    }

    /**
     * Laman depan hanya memuat EMPAT fitur dari sembilan item tersimpan;
     * sisanya hidup di /fitur. Yang menjaga ini bukan cuma jumlah kartu —
     * kalau lima kartu lolos ke Blade, jumlah judulnya ikut berubah.
     */
    public function test_laman_depan_hanya_merender_empat_fitur()
    {
        $items = $this->features();
        $this->setFeatures($items);

        $html = $this->get('/')->getContent();

        $this->assertSame(4, substr_count($html, 'class="feat-mini"'));
        $this->assertCount(4, $this->dirender($html, $items));
    }

    /**
     * Data di produksi belum punya kunci `featured` sama sekali. Yang tampil
     * harus empat fitur utama versi kode, bukan empat teratas daftar (urutan
     * daftar mengikuti seeder, bukan prioritas pemasaran).
     */
    public function test_tanpa_penanda_empat_fitur_utama_bawaan_yang_tampil()
    {
        $items = $this->features();
        $this->setFeatures($items);

        $html = $this->get('/')->getContent();

        // icon3 pendaftaran, icon4 penilaian, icon7 skor, icon9 sertifikat.
        $this->assertSame([0, 1, 4, 6], $this->dirender($html, $items));
    }

    public function test_temuan_icon_map_tidak_kembali()
    {
        $this->setFeatures($this->features());

        $html = $this->get('/')->getContent();

        // Empat kartu yang tampil memakai empat glif berbeda — dulu `$iconMap`
        // berhenti di `icon8` dan tiga fitur terakhir jatuh ke `ti-star` yang
        // sama.
        $this->assertStringContainsString('ti ti-certificate', $html);
        $this->assertStringContainsString('ti ti-podium', $html);

        // Voting Online (satu-satunya pemakai bintang) tidak ikut dirender,
        // jadi tidak ada `ti-star` sama sekali — kalau ada, berarti sebuah
        // kartu jatuh ke fallback glif.
        $this->assertSame(0, substr_count($html, 'ti ti-star'));
    }

    public function test_temuan_ti_scoreboard_tidak_kembali()
    {
        $this->setFeatures($this->features());

        $html = $this->get('/')->getContent();

        // `ti-scoreboard` tidak ada di font lokal — halaman ini memuat font
        // lokal lebih dulu dan CDN sebagai tambahan, jadi memakainya berarti
        // ikon kosong saat CDN tidak termuat. `ti-podium` ada di keduanya.
        $this->assertStringNotContainsString('ti-scoreboard', $html);
        $this->assertStringContainsString('ti ti-podium', $html);
    }

    /**
     * `featured` menentukan item mana yang tampil, bukan item mana yang
     * diperbesar. Yang ditandai sengaja BUKAN empat yang pertama: kalau kode
     * diam-diam mengabaikan penanda dan selalu mengambil empat teratas, tes
     * ini gagal.
     */
    public function test_item_bertanda_featured_yang_tampil()
    {
        $items = $this->features([
            2 => ['featured' => true],
            7 => ['featured' => true],
        ]);
        $this->setFeatures($items);

        $html = $this->get('/')->getContent();

        // Hanya yang ditandai. Tanpa penanda tambahan, tidak ada lagi slot
        // yang diisi dari daftar atas.
        $this->assertSame([2, 7], $this->dirender($html, $items));
    }

    /** Penanda lebih dari empat dipotong, bukan menumbuhkan section. */
    public function test_penanda_lebih_dari_empat_dipotong()
    {
        $items = $this->features([
            0 => ['featured' => true],
            1 => ['featured' => true],
            2 => ['featured' => true],
            3 => ['featured' => true],
            4 => ['featured' => true],
            5 => ['featured' => true],
        ]);
        $this->setFeatures($items);

        $html = $this->get('/')->getContent();

        $this->assertSame(4, substr_count($html, 'class="feat-mini"'));
        $this->assertSame([0, 1, 2, 3], $this->dirender($html, $items));
    }

    public function test_data_lama_tanpa_kunci_featured_tetap_render()
    {
        Storage::fake('public');

        // Bentuk paling tua: tanpa `featured`, salah satu item punya ikon
        // unggahan sendiri (`icon_custom`) — ikon itu harus tetap menang atas
        // glif Tabler, tapi tidak boleh mematikan miniatur kartunya.
        $items = $this->features([
            0 => ['icon_custom' => 'landing/ikon-lama.png'],
        ]);
        $this->setFeatures($items);

        $response = $this->get('/');
        $response->assertStatus(200);

        $html = $response->getContent();
        $this->assertSame(4, substr_count($html, 'class="feat-mini"'));
        $this->assertSame([0, 1, 4, 6], $this->dirender($html, $items));
        $this->assertStringContainsString('landing/ikon-lama.png', $html);
    }

    /**
     * Daftar yang isinya tidak satu pun ikon kanonik (mis. admin mengganti
     * semua ikonnya) tetap harus menampilkan sesuatu, bukan section kosong.
     */
    public function test_daftar_tanpa_ikon_kanonik_tetap_render_empat()
    {
        $items = $this->features();
        foreach ($items as $i => $item) {
            $items[$i]['icon'] = 'ikon-unggahan-' . $i . '.png';
        }
        $this->setFeatures($items);

        $html = $this->get('/')->getContent();

        $this->assertSame(4, substr_count($html, 'class="feat-mini"'));
        $this->assertSame([0, 1, 2, 3], $this->dirender($html, $items));
    }

    /**
     * Asuransi terhadap suntingan tak sengaja atas sembilan cabang @case lama
     * selama katalog /fitur ditambahkan: resolusi kunci sekarang membaca
     * `miniature` lebih dulu, dan item landing tidak punya kunci itu — jadi
     * setiap seni harus tetap memakai kelas pembedanya.
     *
     * Diuji satu per satu, bukan sembilan sekaligus: laman depan hanya
     * merender empat, jadi cek serentak akan selalu gagal untuk lima sisanya.
     */
    public function test_sembilan_miniatur_masih_memakai_art_masing_masing()
    {
        // Satu kelas yang khas per cabang icon3…icon11.
        $seni = [
            0 => 'feat-pill-lime feat-end">Terverifikasi',   // icon3 — meja verifikasi
            1 => 'feat-numbox',                               // icon4 — lembar nilai
            2 => 'feat-ballot',                               // icon5 — papan perolehan
            3 => 'feat-qr',                                   // icon6 — tiket
            4 => 'feat-rk',                                   // icon7 — papan peringkat
            5 => 'feat-cellnum',                              // icon8 — nomor undian
            6 => 'feat-sign',                                 // icon9 — sertifikat
            7 => 'feat-stage',                                // icon10 — overlay siaran
            8 => 'feat-logobox',                              // icon11 — dinding sponsor
        ];

        foreach ($seni as $i => $kelas) {
            $items = $this->features([$i => ['featured' => true]]);
            $this->setFeatures($items);

            $html = $this->get('/')->getContent();

            $this->assertSame([$i], $this->dirender($html, $items));
            $this->assertStringContainsString($kelas, $html, "Seni miniatur \"{$kelas}\" hilang.");
        }
    }

    public function test_panel_admin_menawarkan_sembilan_ikon()
    {
        $admin = User::factory()->admin()->create();

        $comp = Livewire::actingAs($admin)->test(AdminLandingPage::class);
        $comp->set('activeTab', 'features');

        foreach (['icon3.png', 'icon7.png', 'icon11.png'] as $nilai) {
            $comp->assertSeeHtml('value="' . $nilai . '"');
        }

        // Label = nama fiturnya, bukan label template basi (Financial
        // Planning, Security, …) yang tidak berhubungan dengan artinya.
        $comp->assertSee('Manajemen Pendaftaran')
            ->assertSee('Sponsor &amp; Tenant', false)
            ->assertDontSee('Financial Planning');
    }

    public function test_panel_admin_menyimpan_penanda_unggulan()
    {
        $admin = User::factory()->admin()->create();

        $comp = Livewire::actingAs($admin)->test(AdminLandingPage::class);
        $comp->set('features_title', 'Fitur Lengkap untuk Event Sukses')
            ->set('features_items', $this->features([
                3 => ['featured' => true],
                5 => ['featured' => true],
            ]))
            ->call('save');

        $tersimpan = json_decode(Setting::get('landing_features'), true)['items'];

        $this->assertTrue($tersimpan[3]['featured']);
        $this->assertTrue($tersimpan[5]['featured']);
        $this->assertFalse($tersimpan[0]['featured'] ?? false);

        // Item baru lahir dengan bentuk data yang seragam, supaya tidak ada
        // pembaca yang menabrak kunci yang tidak ada.
        $comp->call('addFeatureItem');
        $this->assertFalse($comp->get('features_items')[9]['featured']);
    }
}
