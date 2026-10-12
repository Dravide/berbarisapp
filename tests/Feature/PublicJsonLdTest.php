<?php

namespace Tests\Feature;

use App\Models\Eventner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * JSON-LD schema di halaman publik: Event di detail event, ItemList di
 * /eventner. Yang diuji: JSON valid, kolom sesuai, dan gerbang route —
 * halaman event lain (results, dsb) tidak boleh ikut meng-emit schema.
 */
class PublicJsonLdTest extends TestCase
{
    use RefreshDatabase;

    /** Ekstrak & decode JSON-LD pertama dari HTML; gagal decode = test gagal. */
    private function jsonLdDari(string $html): array
    {
        // Livewire 4 bisa menyisipkan awalan anotasi setelah <script …> di
        // dev; regex longgar: apapun di antara tag buka-tutup ld+json.
        preg_match('/<script[^>]*type="application\/ld\+json"[^>]*>(.*?)<\/script>/s', $html, $m);
        $this->assertArrayHasKey(1, $m, 'Markup JSON-LD tidak ditemukan di halaman.');
        $ld = json_decode($m[1], true);
        $this->assertIsArray($ld, 'JSON-LD tidak valid.');

        return $ld;
    }

    public function test_detail_event_memuat_json_ld_event_valid()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'subdomain' => null,
            'nama_event' => 'Lomba Schema',
            'tanggal' => '2026-11-11',
            'tanggal_akhir' => '2026-11-13',
            'lokasi' => 'Bandung',
            'venue' => 'Sasana Budaya',
        ]);

        $ld = $this->jsonLdDari($this->get("/event/{$eventner->slug}")->getContent());

        $this->assertSame('Event', $ld['@type']);
        $this->assertSame('Lomba Schema', $ld['name']);
        $this->assertSame('2026-11-11T00:00:00+07:00', $ld['startDate']);
        $this->assertSame('2026-11-13T00:00:00+07:00', $ld['endDate']);
        $this->assertSame('Sasana Budaya', $ld['location']['name']);
        $this->assertSame('https://schema.org/EventScheduled', $ld['eventStatus']);
    }

    /** Event sehari: endDate jatuh ke startDate, bukan null. */
    public function test_json_ld_event_sehari_end_date_sama_dengan_start()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'subdomain' => null,
            'tanggal' => '2026-11-11',
            'tanggal_akhir' => null,
        ]);

        $ld = $this->jsonLdDari($this->get("/event/{$eventner->slug}")->getContent());

        $this->assertSame($ld['startDate'], $ld['endDate']);
    }

    /** Tiket dijual → offers lengkap dengan harga & mata uang. */
    public function test_json_ld_event_menyertakan_tiket_saat_aktif()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'subdomain' => null,
            'ticket_active' => true,
            'ticket_price' => 50000,
        ]);

        $ld = $this->jsonLdDari($this->get("/event/{$eventner->slug}")->getContent());

        $this->assertSame('Offer', $ld['offers']['@type']);
        $this->assertSame(50000, $ld['offers']['price']);
        $this->assertSame('IDR', $ld['offers']['priceCurrency']);
        $this->assertStringContainsString('/ticket', $ld['offers']['url']);
    }

    public function test_json_ld_event_tanpa_tiket_tanpa_offers()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'subdomain' => null,
            'ticket_active' => false,
            'ticket_price' => 50000,
        ]);

        $ld = $this->jsonLdDari($this->get("/event/{$eventner->slug}")->getContent());

        $this->assertArrayNotHasKey('offers', $ld);
    }

    /** Pendaftaran tutup = SoldOut, tapi event tetap EventScheduled. */
    public function test_json_ld_pendaftaran_tutup_menandai_sold_out()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'subdomain' => null,
            'ticket_active' => true,
            'ticket_price' => 50000,
            'tanggal_pendaftaran' => now()->subWeek()->toDateString(),
        ]);

        $ld = $this->jsonLdDari($this->get("/event/{$eventner->slug}")->getContent());

        $this->assertSame('https://schema.org/SoldOut', $ld['offers']['availability']);
        $this->assertSame('https://schema.org/EventScheduled', $ld['eventStatus']);
    }

    /** Gerbang route: halaman turunan event tak boleh emit schema Event. */
    public function test_json_ld_tidak_muncul_di_halaman_lain_event()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'subdomain' => null,
        ]);

        $html = $this->get("/event/{$eventner->slug}/results")->getContent();

        $this->assertStringNotContainsString('application/ld+json', $html);
    }

    public function test_eventner_list_memuat_json_ld_itemlist()
    {
        Eventner::factory(3)->create([
            'status' => 'approved',
            'subdomain' => null,
        ]);

        $html = $this->get('/eventner')->getContent();
        $ld = $this->jsonLdDari($html);

        $this->assertSame('ItemList', $ld['@type']);
        $this->assertCount(3, $ld['itemListElement']);
        $this->assertSame(1, $ld['itemListElement'][0]['position']);
        $this->assertArrayHasKey('url', $ld['itemListElement'][0]);
        $this->assertArrayHasKey('name', $ld['itemListElement'][0]);
    }

    /** Halaman daftar kosong tidak boleh emit ItemList tanpa isi. */
    public function test_eventner_list_kosong_tanpa_itemlist()
    {
        $html = $this->get('/eventner')->getContent();

        $this->assertStringNotContainsString('application/ld+json', $html);
    }

    public function test_robots_txt_menunjuk_sitemap()
    {
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Sitemap:', $robots);
        $this->assertStringContainsString('/sitemap.xml', $robots);
    }
}
