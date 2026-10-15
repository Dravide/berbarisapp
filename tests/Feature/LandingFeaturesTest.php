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
            $needle = '<h3 class="text-lg font-bold text-deep-slate">' . e($item['title']) . '</h3>';
            $pos[$i] = strpos($html, $needle);
            $this->assertNotFalse($pos[$i], "Judul \"{$item['title']}\" tidak dirender.");
        }
        return $pos;
    }

    /**
     * Kartu dikatakan unggulan kalau penanda "Paling dipakai" muncul SESUDAH
     * judul kartu sebelumnya dan SEBELUM judul kartu ini — penanda ada di atas
     * miniatur, judul di bawahnya.
     */
    private function unggulanDariUrutan(string $html, array $items): array
    {
        $pos = $this->posisiJudul($html, $items);
        $urut = array_keys($pos);
        usort($urut, fn ($a, $b) => $pos[$a] <=> $pos[$b]);

        $penanda = [];
        $kursor = 0;
        foreach ($urut as $i) {
            $penanda[] = substr_count(substr($html, $kursor, $pos[$i] - $kursor), 'Paling dipakai');
            $kursor = $pos[$i];
        }

        return array_combine($urut, $penanda);
    }

    public function test_anchor_features_tetap_ada()
    {
        // Gerbang nav di proyek ini: tautan nav hanya sah kalau section-nya
        // benar-benar render. Header landing menaut ke #features.
        $this->assertStringContainsString('id="features"', $this->get('/')->getContent());
    }

    public function test_sembilan_judul_fitur_dirender()
    {
        $items = $this->features();
        $this->setFeatures($items);

        $html = $this->get('/')->getContent();

        foreach ($items as $item) {
            $this->assertStringContainsString(e($item['title']), $html);
        }

        // Bukan cuma sembilan judul: sembilan rangka miniatur juga. Kalau
        // hanya lima yang lolos ke Blade, judulnya tetap lulus tanpa ini.
        $this->assertSame(9, substr_count($html, 'class="feat-mini"'));
    }

    public function test_temuan_icon_map_tidak_kembali()
    {
        $this->setFeatures($this->features());

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('ti ti-certificate', $html);
        $this->assertStringContainsString('ti ti-broadcast', $html);
        $this->assertStringContainsString('ti ti-building-store', $html);

        // Hanya Voting Online yang memakai bintang; sebelumnya tiga fitur
        // terakhir ikut jatuh ke sini.
        $this->assertSame(1, substr_count($html, 'ti ti-star'));
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

    public function test_item_bertanda_featured_menjadi_unggulan()
    {
        // Yang ditandai sengaja BUKAN dua yang pertama: kalau kode diam-diam
        // mengabaikan penanda dan selalu mengambil dua teratas, tes ini gagal.
        $items = $this->features([
            4 => ['featured' => true],
            7 => ['featured' => true],
        ]);
        $this->setFeatures($items);

        $html = $this->get('/')->getContent();
        $unggulan = $this->unggulanDariUrutan($html, $items);

        $this->assertSame(2, array_sum($unggulan));
        $this->assertSame(1, $unggulan[4], 'Live Scoreboard ditandai unggulan tapi tidak dapat penanda.');
        $this->assertSame(1, $unggulan[7], 'Livestream Overlay ditandai unggulan tapi tidak dapat penanda.');
        $this->assertSame(0, $unggulan[0], 'Manajemen Pendaftaran tidak ditandai, tapi ikut jadi unggulan.');
    }

    public function test_tanpa_penanda_dua_item_pertama_jadi_unggulan()
    {
        // Data di produksi belum punya kunci `featured` sama sekali. Halaman
        // depan harus membaik tanpa admin menyentuh apa pun.
        $items = $this->features();
        $this->setFeatures($items);

        $html = $this->get('/')->getContent();
        $unggulan = $this->unggulanDariUrutan($html, $items);

        $this->assertSame(2, array_sum($unggulan));
        $this->assertSame(1, $unggulan[0]);
        $this->assertSame(1, $unggulan[1]);
        $this->assertSame(0, $unggulan[2]);
    }

    public function test_data_lama_tanpa_kunci_featured_tetap_render()
    {
        Storage::fake('public');

        // Bentuk paling tua: tanpa `featured`, salah satu item punya ikon
        // unggahan sendiri (`icon_custom`) — ikon itu harus tetap menang atas
        // glif Tabler, tapi tidak boleh mematikan miniatur kartunya.
        $items = $this->features([
            0 => ['icon_custom' => 'landing/ikon-lama.png'],
            8 => ['featured' => true],
        ]);
        $this->setFeatures($items);

        $response = $this->get('/');
        $response->assertStatus(200);

        $html = $response->getContent();
        $this->assertSame(9, substr_count($html, 'class="feat-mini"'));
        $this->assertStringContainsString('landing/ikon-lama.png', $html);
    }

    /**
     * Asuransi terhadap suntingan tak sengaja atas sembilan cabang @case lama
     * selama katalog /fitur ditambahkan: resolusi kunci sekarang membaca
     * `miniature` lebih dulu, dan item landing tidak punya kunci itu — jadi
     * setiap seni harus tetap memakai kelas pembedanya.
     */
    public function test_sembilan_miniatur_masih_memakai_art_masing_masing()
    {
        $this->setFeatures($this->features());

        $html = $this->get('/')->getContent();

        // Satu kelas yang khas per cabang icon3…icon11.
        foreach ([
            'feat-pill-lime feat-end">Terverifikasi',  // icon3 — meja verifikasi
            'feat-numbox',                              // icon4 — lembar nilai
            'feat-ballot',                              // icon5 — papan perolehan
            'feat-qr',                                  // icon6 — tiket
            'feat-rk',                                  // icon7 — papan peringkat
            'feat-cellnum',                             // icon8 — nomor undian
            'feat-sign',                                // icon9 — sertifikat
            'feat-stage',                               // icon10 — overlay siaran
            'feat-logobox',                             // icon11 — dinding sponsor
        ] as $kelas) {
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
