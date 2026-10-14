<?php

namespace Tests\Feature;

use App\Models\Eventner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventDetailPosterTest extends TestCase
{
    use RefreshDatabase;

    public function test_poster_acara_menampilkan_tombol_dan_modal_zoom_saat_ada_poster()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'subdomain' => 'pbb2026',
            'slug' => 'pbb-2026',
            'nama_event' => 'Lomba PBB 2026',
            'poster' => 'posters/pbb2026.jpg',
        ]);

        $response = $this->get("/event/{$eventner->slug}");

        $response->assertStatus(200)
            ->assertSee('Poster Acara')
            ->assertSee('open-poster-zoom', false)
            ->assertSee('posterZoomViewer()', false)
            ->assertSee('ti ti-zoom-in', false)
            ->assertSee('ti ti-zoom-out', false)
            ->assertSee('ti ti-rotate', false)
            ->assertSee('Perbesar', false);
    }

    public function test_poster_acara_tidak_tampil_jika_poster_kosong()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'slug' => 'event-tanpa-poster',
            'poster' => null,
        ]);

        $response = $this->get("/event/{$eventner->slug}");

        $response->assertStatus(200)
            ->assertDontSee('posterZoomViewer()', false);
    }

    public function test_poster_acara_zoom_berfungsi_pada_subdomain()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'subdomain' => 'pbb2026',
            'slug' => 'pbb-2026',
            'nama_event' => 'Lomba PBB 2026',
            'poster' => 'posters/pbb2026.jpg',
        ]);

        $host = parse_url(config('app.url'), PHP_URL_HOST);
        $url = 'http://pbb2026.' . $host . '/';

        $response = $this->get($url);

        $response->assertStatus(200)
            ->assertSee('Poster Acara')
            ->assertSee('open-poster-zoom', false)
            ->assertSee('posterZoomViewer()', false)
            ->assertSee('ti ti-zoom-in', false);
    }
}
