<?php

namespace Tests\Feature;

use App\Models\Eventner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic smoke test: landing page loads.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        Eventner::factory()->create(['status' => 'approved']);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
