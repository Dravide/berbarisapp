<?php

namespace Tests\Feature;

use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Participant;
use App\Models\Registration;
use App\Models\Sponsor;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_loads()
    {
        Eventner::factory(3)->create(['status' => 'approved']);
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_penyelenggara_menampilkan_tanggal_pelaksanaan()
    {
        Eventner::factory()->create([
            'status' => 'approved',
            'tanggal' => '2026-10-05',
            'tanggal_akhir' => null,
        ]);

        $html = $this->penyelenggaraSection($this->get('/')->getContent());

        $this->assertStringContainsString('05 Okt 2026', $html);
    }

    public function test_penyelenggara_menampilkan_rentang_tanggal()
    {
        Eventner::factory()->create([
            'status' => 'approved',
            'tanggal' => '2026-10-05',
            'tanggal_akhir' => '2026-10-07',
        ]);

        $html = $this->penyelenggaraSection($this->get('/')->getContent());

        $this->assertStringContainsString('05 Okt 2026', $html);
        $this->assertStringContainsString('07 Okt 2026', $html);
    }

    /** Event yang sudah selesai ditandai "Terlaksana" dan kartunya diredam. */
    public function test_penyelenggara_menandai_event_terlaksana()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'nama_event' => 'Lomba Lampau',
            'tanggal' => now()->subMonths(2)->toDateString(),
            'tanggal_akhir' => null,
        ]);

        $html = $this->penyelenggaraSection($this->get('/')->getContent());

        $this->assertStringContainsString('Terlaksana', $html);
        $this->assertStringContainsString('chip-past', $html);
        $this->assertStringContainsString('card-past', $html);
        $this->assertStringContainsString('Lihat Hasil', $html);
    }

    /** Event yang belum lewat tidak boleh ikut ditandai. */
    public function test_penyelenggara_tidak_menandai_event_akan_datang()
    {
        Eventner::factory()->create([
            'status' => 'approved',
            'tanggal' => now()->addMonths(2)->toDateString(),
            'tanggal_akhir' => null,
        ]);

        $html = $this->penyelenggaraSection($this->get('/')->getContent());

        $this->assertStringNotContainsString('Terlaksana', $html);
        $this->assertStringContainsString('Lihat Event', $html);
    }

    /**
     * Tanggal akhir yang masih hari ini belum dihitung terlaksana — event
     * sehari tidak boleh berubah jadi "Terlaksana" begitu lewat tengah malam.
     */
    public function test_penyelenggara_memakai_tanggal_akhir_sebagai_penentu()
    {
        Eventner::factory()->create([
            'status' => 'approved',
            'tanggal' => now()->subDays(3)->toDateString(),
            'tanggal_akhir' => now()->addDay()->toDateString(),
        ]);

        $html = $this->penyelenggaraSection($this->get('/')->getContent());

        $this->assertStringNotContainsString('Terlaksana', $html);
    }

    /** Potongan markup <section id="eventners"> dari HTML halaman landing. */
    private function penyelenggaraSection(string $html): string
    {
        $start = strpos($html, '<section id="eventners"');
        $this->assertNotFalse($start, 'Section penyelenggara tidak ditemukan di halaman landing.');
        $end = strpos($html, '</section>', $start);

        return substr($html, $start, $end - $start);
    }

    public function test_event_detail_loads()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'nama_event' => 'Lomba Seni 2026',
        ]);
        $category = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => null,
            'name' => 'Seni Tari',
        ]);

        $response = $this->get("/event/{$eventner->slug}");
        $response->assertStatus(200);
    }

    public function test_event_participant_loads()
    {
        $eventner = Eventner::factory()->create(['status' => 'approved']);
        $cat = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => null,
        ]);
        $child = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => $cat->id,
        ]);
        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $child->id,
            'status_berkas' => 'confirmed',
        ]);

        $response = $this->get("/event/{$eventner->slug}/participant");
        $response->assertStatus(200);
    }

    public function test_scoreboard_page_loads()
    {
        Eventner::factory()->create([
            'status' => 'approved',
            'scoring_code' => 'SC-12345',
        ]);

        $response = $this->get('/scoreboard/SC-12345');
        $response->assertStatus(200);
    }

    public function test_champions_page_loads()
    {
        Eventner::factory()->create([
            'status' => 'approved',
            'scoring_code' => 'CH-67890',
        ]);

        $response = $this->get('/champions/CH-67890');
        $response->assertStatus(200);
    }

    public function test_event_detail_with_sponsors_and_tenants()
    {
        $eventner = Eventner::factory()->create(['status' => 'approved']);
        Sponsor::factory()->create([
            'eventner_id' => $eventner->id,
            'is_active' => true,
        ]);
        Tenant::factory()->create([
            'eventner_id' => $eventner->id,
            'is_active' => true,
        ]);

        $response = $this->get("/event/{$eventner->slug}");
        $response->assertStatus(200);
    }

    public function test_pending_event_not_accessible_via_slug()
    {
        $eventner = Eventner::factory()->pending()->create(['nama_event' => 'Pending Test']);

        // Pending event harus 404 — tidak boleh live sebelum disetujui.
        $response = $this->get("/event/{$eventner->slug}");
        $response->assertStatus(404);
    }

    public function test_ticket_page_loads()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'ticket_active' => true,
            'ticket_price' => 50000,
        ]);

        $response = $this->get("/event/{$eventner->slug}/ticket");
        $response->assertStatus(200);
    }

    public function test_404_for_nonexistent_slug()
    {
        $response = $this->get('/event/tidak-ada-99999');
        $response->assertStatus(404);
    }

    public function test_mobile_bottom_nav_replaces_hamburger()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'ticket_active' => true,
            'ticket_price' => 25000,
            // Menu Vote dan Tiket masing-masing ikut saklarnya — keduanya
            // harus menyala supaya bar-nya lengkap.
            'vote_active' => true,
            // Tombol "Daftar Sekarang" hanya muncul saat pendaftaran terbuka;
            // deadline kosong berarti ditutup.
            'tanggal_pendaftaran' => now()->addMonth()->toDateString(),
            'tanggal' => now()->addMonths(2)->toDateString(),
        ]);

        $response = $this->get("/event/{$eventner->slug}");
        $response->assertStatus(200);

        // Bottom bar: label semua item + FAB daftar tampil
        foreach (['Info', 'Peserta', 'Hasil', 'Vote', 'Tiket', 'Daftar Sekarang'] as $label) {
            $response->assertSee($label, false);
        }
        $response->assertSee('aria-label="Navigasi utama"', false);

        // Hamburger menu + dropdown hilang dari markup
        $response->assertDontSee('nav-toggle');
        $response->assertDontSee('mobile-menu');
    }
}
