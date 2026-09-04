<?php

namespace Tests\Feature;

use App\Models\Eventner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LivestreamOverlayTest extends TestCase
{
    use RefreshDatabase;

    public function test_overlay_full_mode_renders()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'slug' => 'test-overlay-full',
        ]);

        $response = $this->get('/event/' . $eventner->slug . '/overlay?mode=full');

        $response->assertStatus(200);
    }

    public function test_overlay_greenscreen_mode_renders()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'slug' => 'test-overlay-green',
        ]);

        $response = $this->get('/event/' . $eventner->slug . '/overlay?mode=greenscreen');

        $response->assertStatus(200);
    }

    public function test_overlay_vote_mode_renders()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'slug' => 'test-overlay-vote',
            'vote_active' => true,
            'vote_start' => now()->subDay(),
            'vote_end' => now()->addDay(),
        ]);

        $response = $this->get('/event/' . $eventner->slug . '/overlay?mode=vote');

        $response->assertStatus(200);
    }
}
