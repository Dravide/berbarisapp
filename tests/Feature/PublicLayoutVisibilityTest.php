<?php

namespace Tests\Feature;

use App\Livewire\Public\EventLayout;
use App\Models\CompetitionLayout;
use App\Models\Eventner;
use App\Models\LayoutZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Denah di halaman publik: yang terbit tampil, yang draf tidak.
 *
 * Gerbang halaman ini adalah konten — ada denah terbit atau tidak. Karena itu
 * yang diuji bukan cuma "draf tak terlihat", tapi juga bahwa halaman & menunya
 * benar-benar hilang saat belum ada yang diterbitkan.
 */
class PublicLayoutVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function eventner(array $atribut = []): Eventner
    {
        return Eventner::factory()->create(array_merge(['status' => 'approved'], $atribut));
    }

    private function denah(Eventner $eventner, array $atribut = []): CompetitionLayout
    {
        return CompetitionLayout::factory()->create(array_merge(
            ['eventner_id' => $eventner->id],
            $atribut
        ));
    }

    public function test_denah_draf_tidak_tampil_di_halaman_publik()
    {
        $eventner = $this->eventner();
        $terbit = $this->denah($eventner, ['name' => 'Denah Terbit', 'is_published' => true, 'published_at' => now()]);
        $draf = $this->denah($eventner, ['name' => 'Denah Draf', 'is_published' => false]);

        LayoutZone::factory()->create(['competition_layout_id' => $terbit->id]);
        LayoutZone::factory()->create(['competition_layout_id' => $draf->id]);

        $this->get(route('event.denah', $eventner->slug))
            ->assertOk()
            ->assertSee('Denah Terbit')
            ->assertDontSee('Denah Draf');
    }

    /**
     * Belum ada denah terbit = halamannya tidak ada.
     *
     * Halaman kosong membuat peserta mengira halamannya rusak; 404 menyatakan
     * denahnya memang belum diterbitkan.
     */
    public function test_denah_belum_diterbitkan_menghasilkan_404()
    {
        $eventner = $this->eventner();
        $this->denah($eventner, ['is_published' => false]);

        $this->get(route('event.denah', $eventner->slug))->assertNotFound();
    }

    public function test_event_tanpa_denah_sama_sekali_404()
    {
        $eventner = $this->eventner();

        $this->get(route('event.denah', $eventner->slug))->assertNotFound();
    }

    /** Denah event lain tak boleh bocor lewat id yang diketik di URL. */
    public function test_denah_event_lain_tidak_bisa_dibuka_lewat_url()
    {
        $saya = $this->eventner();
        $lain = $this->eventner();

        $milikLain = $this->denah($lain, ['is_published' => true, 'published_at' => now()]);
        $milikSaya = $this->denah($saya, ['is_published' => true, 'published_at' => now()]);

        LayoutZone::factory()->create(['competition_layout_id' => $milikLain->id]);
        LayoutZone::factory()->create(['competition_layout_id' => $milikSaya->id]);

        $this->get(route('event.denah.satu', ['slug' => $saya->slug, 'layout' => $milikLain->id]))
            ->assertNotFound();
    }

    /**
     * Tautan langsung ke satu denah tetap menampilkan denah itu.
     *
     * Ini yang dipakai panitia saat membagikan "denah kategori A" ke grup WA —
     * tanpa rute ber-{layout}, tautannya jadi parameter yang diabaikan.
     */
    public function test_tautan_langsung_menampilkan_denah_yang_dituju()
    {
        $eventner = $this->eventner();
        $a = $this->denah($eventner, ['name' => 'Denah Kategori A', 'is_published' => true, 'published_at' => now()]);
        $b = $this->denah($eventner, ['name' => 'Denah Kategori B', 'is_published' => true, 'published_at' => now()]);

        LayoutZone::factory()->create(['competition_layout_id' => $a->id, 'name' => 'Zona A']);
        LayoutZone::factory()->create(['competition_layout_id' => $b->id, 'name' => 'Zona B']);

        $this->get(route('event.denah.satu', ['slug' => $eventner->slug, 'layout' => $a->id]))
            ->assertOk()
            ->assertSee('Zona A')
            ->assertDontSee('Zona B');
    }

    /** Isi denahnya benar-benar dirender, bukan cuma judulnya. */
    public function test_zona_denah_terbit_ikut_dirender()
    {
        $eventner = $this->eventner();
        $denah = $this->denah($eventner, ['is_published' => true, 'published_at' => now()]);

        LayoutZone::factory()->dp(1)->create(['competition_layout_id' => $denah->id]);
        LayoutZone::factory()->create([
            'competition_layout_id' => $denah->id,
            'type' => LayoutZone::TYPE_ARENA,
            'name' => 'Arena Utama',
        ]);

        $this->get(route('event.denah', $eventner->slug))
            ->assertOk()
            ->assertSee('Arena Utama')
            ->assertSee('DP 1');
    }

    /**
     * Menu "Denah" di bottom-nav hanya muncul saat ada denah terbit.
     *
     * Pola yang sama dengan Vote & Tiket: menunya mengikuti isi halamannya.
     */
    public function test_menu_denah_muncul_hanya_saat_ada_yang_terbit()
    {
        $eventner = $this->eventner();

        $this->assertNotContains('denah', $this->itemNav($this->get("/event/{$eventner->slug}")->getContent()));

        $denah = $this->denah($eventner, ['is_published' => true, 'published_at' => now()]);
        LayoutZone::factory()->create(['competition_layout_id' => $denah->id]);

        $this->assertContains('denah', $this->itemNav($this->get("/event/{$eventner->slug}")->getContent()));
    }

    /**
     * Denah draf tak membuka menunya.
     *
     * Tanpa ini, panitia yang menerbitkan lalu menarik kembali masih melihat
     * menunya — dan menunya menunjuk 404.
     */
    public function test_denah_draf_tidak_membuka_menu()
    {
        $eventner = $this->eventner();
        $denah = $this->denah($eventner, ['is_published' => false]);
        LayoutZone::factory()->create(['competition_layout_id' => $denah->id]);

        $this->assertNotContains('denah', $this->itemNav($this->get("/event/{$eventner->slug}")->getContent()));
    }

    /** Menunya hilang lagi setelah denahnya ditarik. */
    public function test_menu_denah_hilang_setelah_ditarik()
    {
        $eventner = $this->eventner();
        $denah = $this->denah($eventner, ['is_published' => true, 'published_at' => now()]);
        LayoutZone::factory()->create(['competition_layout_id' => $denah->id]);

        $this->assertContains('denah', $this->itemNav($this->get("/event/{$eventner->slug}")->getContent()));

        $denah->update(['is_published' => false]);

        $this->assertNotContains('denah', $this->itemNav($this->get("/event/{$eventner->slug}")->getContent()));
    }

    /** Item bottom-nav yang benar-benar dirender, dari data-nav-nya. */
    private function itemNav(string $html): array
    {
        $awal = strpos($html, 'aria-label="Navigasi utama"');
        $this->assertNotFalse($awal, 'Bottom nav tidak ditemukan.');

        $akhir = strpos($html, '</nav>', $awal);
        $nav = substr($html, $awal, $akhir - $awal);

        preg_match_all('/data-nav="([^"]+)"/', $nav, $cocok);

        return $cocok[1];
    }
}
