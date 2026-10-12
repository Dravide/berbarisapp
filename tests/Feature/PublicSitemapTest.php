<?php

namespace Tests\Feature;

use App\Models\Eventner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sitemap.xml — peta halaman yang layak diindeks. Entry-nya hanya halaman
 * publik: event approved + root platform. Halaman ber-token dan turunan
 * event tidak boleh bocor ke sini.
 */
class PublicSitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_mengembalikan_xml_dengan_content_type_xml()
    {
        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));
    }

    public function test_sitemap_memuat_url_event_approved()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'subdomain' => null,
            'nama_event' => 'Lomba Sitemap',
        ]);

        $xml = $this->get('/sitemap.xml')->getContent();

        $this->assertStringContainsString('event/' . $eventner->slug, $xml);
    }

    /** Event belum disetujui belum punya halaman publik — jangan dipetakan. */
    public function test_sitemap_tidak_memuat_event_pending()
    {
        $eventner = Eventner::factory()->pending()->create([
            'nama_event' => 'Lomba Pending',
        ]);

        $xml = $this->get('/sitemap.xml')->getContent();

        $this->assertStringNotContainsString($eventner->slug, $xml);
    }

    public function test_sitemap_memuat_halaman_publik_statis()
    {
        $xml = $this->get('/sitemap.xml')->getContent();

        foreach (['pricing', 'eventner', 'privacy', 'terms', 'help'] as $path) {
            $this->assertStringContainsString('<loc>' . platform_url($path) . '</loc>', $xml);
        }
    }

    /** Path ber-token adalah kredensial — tidak boleh masuk sitemap. */
    public function test_sitemap_tidak_memuat_path_ber_token()
    {
        $xml = $this->get('/sitemap.xml')->getContent();

        foreach (['/scan/', '/reg/', '/juri/', '/panitia/'] as $path) {
            $this->assertStringNotContainsString($path, $xml);
        }
    }

    /** Factory default selalu set subdomain — URL-nya harus ber-domain itu. */
    public function test_sitemap_event_subdomain_memakai_domain_subdomain()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'subdomain' => 'lomba-sitemap',
        ]);

        $xml = $this->get('/sitemap.xml')->getContent();

        $this->assertStringContainsString('://lomba-sitemap.', $xml);
        $this->assertStringNotContainsString('event/' . $eventner->slug . '</loc>', $xml);
    }

    /** Turunan event bukan konten mandiri — hanya detail yang dipetakan. */
    public function test_sitemap_tidak_menunjuk_halaman_turunan_event()
    {
        Eventner::factory()->create(['status' => 'approved']);

        $xml = $this->get('/sitemap.xml')->getContent();

        foreach (['/participant', '/results', '/register'] as $path) {
            $this->assertStringNotContainsString($path, $xml);
        }
    }

    public function test_sitemap_memuat_hall_of_fame_penyelenggara()
    {
        Eventner::factory()->create([
            'status' => 'approved',
            'diselenggarakan_oleh' => 'Dinas Pendidikan Jawa Barat',
        ]);

        $xml = $this->get('/sitemap.xml')->getContent();

        // nama penyelenggara di-rawurlencode (spasi → %20), bukan slug kebab
        $this->assertStringContainsString('hall-of-fame/Dinas%20Pendidikan%20Jawa%20Barat', $xml);
    }
}
