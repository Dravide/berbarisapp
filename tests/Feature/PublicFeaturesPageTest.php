<?php

namespace Tests\Feature;

use App\Livewire\Admin\Setting\LandingPage as AdminLandingPage;
use App\Models\Setting;
use App\Models\User;
use App\Support\ProductFeatures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Halaman publik /fitur — katalog lengkap fitur produk.
 *
 * Landing cuma menjual sembilan ringkasan; katalog penuhnya hidup di sini,
 * disusun dari App\Support\ProductFeatures (kode, bukan Setting). Tes di sini
 * menjaga tiga hal yang tidak bisa dijaga tempat lain:
 *
 * 1. Katalog tidak boleh berisi fitur karangan — setiap `route` wajib ada.
 * 2. `gates` tidak boleh melenceng dari config/eventner_features.php.
 * 3. Setiap kunci `miniature` wajib punya cabang @case di Blade; yang tidak
 *    punya jatuh ke @default dan tampil seragam — gagal senyap.
 */
class PublicFeaturesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_fitur_memuat()
    {
        $response = $this->get('/fitur');

        $response->assertStatus(200);
        $response->assertSee('id="g-pendaftaran"', false);
    }

    public function test_seluruh_katalog_dirender()
    {
        $html = $this->get('/fitur')->getContent();

        foreach (ProductFeatures::groups() as $group) {
            $this->assertStringContainsString(e($group['title']), $html);

            foreach ($group['items'] as $item) {
                $this->assertStringContainsString(
                    e($item['title']),
                    $html,
                    "Judul \"{$item['title']}\" tidak dirender."
                );
            }
        }

        // Bukan cuma judulnya: satu rangka miniatur per item. Kalau satu item
        // diam-diam lolos dari loop Blade, judulnya tetap lulus tanpa ini.
        $this->assertSame(
            ProductFeatures::total(),
            substr_count($html, 'class="feat-mini"')
        );
    }

    /**
     * Penjaga yang membuat katalog tidak boleh berisi fitur aspirasional:
     * setiap rujukan route harus benar-benar terdaftar.
     */
    public function test_katalog_terikat_route_yang_benar_benar_ada()
    {
        foreach (ProductFeatures::all() as $item) {
            $this->assertTrue(
                Route::has($item['route']),
                "Route \"{$item['route']}\" (fitur \"{$item['title']}\") tidak terdaftar."
            );
        }
    }

    /**
     * Katalog SENGAJA pisah berkas dari config/eventner_features.php — itu
     * config paywall, ini halaman pemasaran. Pemisahan itu aman hanya kalau
     * keduanya dibandingkan di suatu tempat; di sini tempatnya.
     *
     * Diuji dua arah: kunci config yang tidak dipetakan siapa pun, DAN gate
     * katalog yang menunjuk kunci yang sudah tidak ada.
     */
    public function test_gate_katalog_sepadan_dengan_config()
    {
        $dariKatalog = [];
        foreach (ProductFeatures::all() as $item) {
            foreach ($item['gates'] as $gate) {
                $dariKatalog[] = $gate;
            }
        }
        $dariKatalog = array_values(array_unique($dariKatalog));
        $dariConfig = array_keys(config('eventner_features'));

        sort($dariKatalog);
        sort($dariConfig);

        $this->assertSame(
            array_diff($dariConfig, $dariKatalog),
            [],
            'Kunci config/eventner_features.php ada yang tidak dipetakan ke katalog /fitur.'
        );
        $this->assertSame(
            array_diff($dariKatalog, $dariConfig),
            [],
            'Katalog /fitur memetakan gate yang tidak ada di config/eventner_features.php.'
        );
    }

    /**
     * `@default` (feature-miniature.blade.php) adalah satu-satunya cabang yang
     * mencetak judul di dalam pill, dan komponen ini dipanggil TANPA `title`
     * dari /fitur — jadi cabang itu memancarkan "Fitur" harfiah. Kunci
     * `miniature` yang salah ketik (tidak punya @case) tertangkap di sini.
     */
    public function test_miniature_tidak_jatuh_ke_cabang_default()
    {
        $html = $this->get('/fitur')->getContent();

        $this->assertStringNotContainsString(
            '<span class="feat-pill feat-pill-accent">Fitur</span>',
            $html,
            'Ada kunci miniature di katalog yang tidak punya cabang @case.'
        );
    }

    public function test_setiap_chip_kategori_punya_section()
    {
        $html = $this->get('/fitur')->getContent();

        foreach (ProductFeatures::groups() as $group) {
            $this->assertStringContainsString('href="#g-' . $group['key'] . '"', $html);
            $this->assertStringContainsString('id="g-' . $group['key'] . '"', $html);
        }
    }

    /** Tautan footer "Semua Fitur" + tombol di section #features. */
    public function test_landing_menaut_ke_halaman_fitur()
    {
        Setting::set('landing_features', json_encode([
            'title' => 'Fitur Lengkap untuk Event Sukses',
            'items' => [
                ['icon' => 'icon3.png', 'title' => 'Manajemen Pendaftaran', 'description' => 'Deskripsi.'],
                ['icon' => 'icon4.png', 'title' => 'Penilaian Juri Digital', 'description' => 'Deskripsi.'],
            ],
        ]));

        $html = $this->get('/')->getContent();

        $start = strpos($html, '<section id="features"');
        $this->assertNotFalse($start, 'Section #features tidak ditemukan.');

        $section = substr($html, $start, strpos($html, '</section>', $start) - $start);
        $this->assertStringContainsString(route('public.features'), $section);

        $footer = substr($html, strpos($html, '<footer'));
        $this->assertStringContainsString(route('public.features'), $footer);
    }

    /**
     * Admin bisa menyunting sembilan item landing dan melihat laman depan
     * berubah — tanpa catatan, wajar ia menyimpulkan ia memegang seluruh
     * daftar fitur publik. Tab Fitur harus menyebut batasnya.
     */
    public function test_admin_features_tab_menyebut_katalog_lengkap()
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(AdminLandingPage::class)
            ->set('activeTab', 'features')
            ->assertSee(route('public.features'))
            ->assertSee('Katalog fitur lengkap');
    }

    /**
     * Layout landing dipakai halaman ini tanpa satu pun section #hero…#contact.
     * Anchor `#g-…` milik chip kategori SAH (section-nya ada di halaman ini);
     * yang tidak boleh ada adalah sembilan anchor nav yang bocor ke sini.
     */
    public function test_halaman_fitur_tidak_punya_tautan_anchor_nav_yang_bocor()
    {
        $html = $this->get('/fitur')->getContent();

        foreach (['#hero', '#features', '#pricing', '#eventners', '#vote', '#team', '#faq', '#contact'] as $anchor) {
            $this->assertStringNotContainsString(
                'href="' . $anchor . '"',
                $html,
                "Anchor nav {$anchor} bocor ke /fitur padahal section-nya tidak ada di sini."
            );
        }
    }
}
