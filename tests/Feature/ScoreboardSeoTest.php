<?php

namespace Tests\Feature;

use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoreboardSeoTest extends TestCase
{
    use RefreshDatabase;

    private function makeEvent(array $attrs = []): Eventner
    {
        return Eventner::factory()->create(array_merge([
            'status' => 'approved',
            'scoring_code' => 'SC-12345',
            'nama_event' => 'Kejuaraan PBB Nasional',
            'diselenggarakan_oleh' => 'Dinas Pendidikan',
            'lokasi' => 'Jakarta',
            'logo_event' => 'logos/event.png',
        ], $attrs));
    }

    public function test_halaman_champion_punya_meta_seo_lengkap(): void
    {
        $eventner = $this->makeEvent();
        $parent = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id]);
        $category = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => $parent->id,
        ]);
        $champion = ChampionCategory::create([
            'eventner_id' => $eventner->id,
            'name' => 'Juara Umum',
            'quantity' => 3,
        ]);

        $response = $this->get("/scoreboard/SC-12345/champion/{$champion->id}?category_id={$category->id}");

        $response->assertOk();

        // Judul memuat nama kategori juara + nama event
        $response->assertSee('Live Scoreboard Juara Umum — Kejuaraan PBB Nasional', false);

        // Deskripsi, keywords, canonical, robots
        $response->assertSee('Juara Umum', false);
        $response->assertSee('<link rel="canonical"', false);
        $response->assertSee('name="robots" content="index, follow"', false);

        // Open Graph
        $response->assertSee('property="og:title"', false);
        $response->assertSee('property="og:image"', false);
        $response->assertSee('property="og:url"', false);
        $response->assertSee('property="og:locale" content="id_ID"', false);

        // Twitter Card
        $response->assertSee('name="twitter:card" content="summary_large_image"', false);
        $response->assertSee('name="twitter:title"', false);

        // JSON-LD
        $response->assertSee('application/ld+json', false);
        $response->assertSee('"@type":"WebPage"', false);
        $response->assertSee('"na' . 'me":"Live Scoreboard Juara Umum', false);
    }

    public function test_favicon_memakai_logo_event(): void
    {
        $eventner = $this->makeEvent(['logo_event' => 'logos/event.png']);

        $response = $this->get('/scoreboard/SC-12345');

        $response->assertOk();
        $response->assertSee('rel="shortcut icon"', false);
        $response->assertSee('storage/logos/event.png', false);
    }

    public function test_favicon_fallback_ke_logo_platform_tanpa_logo_event(): void
    {
        $eventner = $this->makeEvent(['logo_event' => null]);

        $response = $this->get('/scoreboard/SC-12345');

        $response->assertOk();
        $response->assertSee('rel="shortcut icon"', false);
        $response->assertSee('templates/assets/images/logos/favicon.png', false);
    }

    public function test_judul_halaman_tanpa_champion_tidak_mengandung_nama_kategori(): void
    {
        $this->makeEvent();

        $response = $this->get('/scoreboard/SC-12345');

        $response->assertOk();
        $response->assertSee('<title>Live Scoreboard — Kejuaraan PBB Nasional</title>', false);
    }

    public function test_puncak_peringkat_menampilkan_logo_sekolah(): void
    {
        $eventner = $this->makeEvent();
        $parent = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id]);
        $category = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => $parent->id,
        ]);

        // Tiga peserta agar podium terisi, satu dengan logo sekolah
        foreach ([['SMP Alpha', 'logos/alpha.png'], ['SMP Beta', null], ['SMP Gamma', null]] as [$school, $logo]) {
            Registration::factory()->create([
                'eventner_id' => $eventner->id,
                'competition_category_id' => $category->id,
                'nama_sekolah' => $school,
                'logo_sekolah' => $logo,
            ]);
        }

        $response = $this->get("/scoreboard/SC-12345/category/{$category->id}");

        $response->assertOk();
        $response->assertSee('storage/logos/alpha.png', false);
        $response->assertSee('alt="Logo SMP Alpha"', false);
    }

    public function test_scoreboard_lain_tetap_pakai_layout_yang_sama(): void
    {
        $eventner = $this->makeEvent(['scoring_code' => 'CH-67890']);

        $this->get('/champions/CH-67890')->assertOk();
        $this->get('/scoreboard/CH-67890')->assertOk();
    }
}
