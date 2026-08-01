<?php

namespace Tests\Feature;

use App\Models\AssessmentCategory;
use App\Models\CompetitionCategory;
use App\Models\EventFaq;
use App\Models\EventGallery;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\Sponsor;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventContentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_gallery_endpoint_returns_sorted_images()
    {
        $event = Eventner::factory()->create(['status' => 'approved']);
        EventGallery::factory()->create(['eventner_id' => $event->id, 'sort_order' => 2]);
        $first = EventGallery::factory()->create(['eventner_id' => $event->id, 'sort_order' => 1]);

        $response = $this->getJson("/api/v1/events/{$event->slug}/gallery");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $first->id);
    }

    public function test_faq_endpoint_returns_faqs()
    {
        $event = Eventner::factory()->create(['status' => 'approved']);
        EventFaq::factory()->create(['eventner_id' => $event->id]);

        $response = $this->getJson("/api/v1/events/{$event->slug}/faq");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data' => [['question', 'answer']]]);
    }

    public function test_sponsors_endpoint_returns_only_active()
    {
        $event = Eventner::factory()->create(['status' => 'approved']);
        Sponsor::factory()->create(['eventner_id' => $event->id, 'is_active' => true]);
        Sponsor::factory()->inactive()->create(['eventner_id' => $event->id]);

        $response = $this->getJson("/api/v1/events/{$event->slug}/sponsors");

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_tenants_endpoint_returns_only_active()
    {
        $event = Eventner::factory()->create(['status' => 'approved']);
        Tenant::factory()->create(['eventner_id' => $event->id, 'is_active' => true]);
        Tenant::factory()->inactive()->create(['eventner_id' => $event->id]);

        $response = $this->getJson("/api/v1/events/{$event->slug}/tenants");

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_drawing_results_endpoint_returns_ordered_schools()
    {
        $event = Eventner::factory()->create(['status' => 'approved']);
        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $event->id,
            'parent_id' => null,
        ]);
        $child = CompetitionCategory::factory()->child($parent)->create([
            'eventner_id' => $event->id,
        ]);

        Registration::factory()->create([
            'eventner_id' => $event->id,
            'competition_category_id' => $child->id,
            'urutan_tampil' => 2,
            'nama_sekolah' => 'SMA N 2 Bandung',
        ]);
        $first = Registration::factory()->create([
            'eventner_id' => $event->id,
            'competition_category_id' => $child->id,
            'urutan_tampil' => 1,
            'nama_sekolah' => 'SMA N 1 Bandung',
        ]);

        $response = $this->getJson("/api/v1/events/{$event->slug}/drawing-results");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', $child->full_name)
            ->assertJsonPath('data.0.results.0.nama_sekolah', $first->nama_sekolah);
    }

    public function test_juknis_endpoint_returns_404_when_no_categories()
    {
        $event = Eventner::factory()->create(['status' => 'approved']);

        $response = $this->getJson("/api/v1/events/{$event->slug}/juknis");

        $response->assertStatus(404);
    }

    public function test_content_endpoints_404_for_pending_event()
    {
        $event = Eventner::factory()->pending()->create();

        $this->getJson("/api/v1/events/{$event->slug}/gallery")->assertStatus(404);
        $this->getJson("/api/v1/events/{$event->slug}/faq")->assertStatus(404);
        $this->getJson("/api/v1/events/{$event->slug}/sponsors")->assertStatus(404);
        $this->getJson("/api/v1/events/{$event->slug}/tenants")->assertStatus(404);
        $this->getJson("/api/v1/events/{$event->slug}/drawing-results")->assertStatus(404);
    }

    public function test_content_endpoints_404_for_other_events_content()
    {
        // Konten event lain tidak boleh bocor ke event ini.
        $event = Eventner::factory()->create(['status' => 'approved']);
        $other = Eventner::factory()->create(['status' => 'approved']);
        EventGallery::factory()->create(['eventner_id' => $other->id]);

        $response = $this->getJson("/api/v1/events/{$event->slug}/gallery");

        $response->assertOk()->assertJsonCount(0, 'data');
    }
}
