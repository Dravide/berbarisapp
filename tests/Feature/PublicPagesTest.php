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
}
