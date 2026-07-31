<?php

namespace Tests\Feature;

use App\Models\Eventner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_redirected_from_dashboard()
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_guest_sees_login_page()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_eventner_user_can_login_via_livewire()
    {
        $user = User::factory()->eventner()->create([
            'email' => 'eventner@test.com',
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);
        Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        $component = \Livewire\Livewire::test(\App\Livewire\Auth\Login::class);

        $component->set('login', 'eventner@test.com')
            ->set('password', 'secret123')
            ->call('authenticate')
            ->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }

    public function test_login_fails_with_wrong_password()
    {
        User::factory()->eventner()->create([
            'email' => 'eventner@test.com',
            'password' => bcrypt('secret123'),
        ]);

        $component = \Livewire\Livewire::test(\App\Livewire\Auth\Login::class);

        $component->set('login', 'eventner@test.com')
            ->set('password', 'wrong-password')
            ->call('authenticate')
            ->assertHasErrors('login');

        $this->assertGuest();
    }

    public function test_logout_redirects_and_clears_session()
    {
        $user = User::factory()->eventner()->create();
        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_authenticated_user_gets_dashboard_redirect()
    {
        // Dashboard/Index mount() redirects Eventner to eventner.dashboard
        $user = User::factory()->eventner()->create(['is_active' => true]);
        Eventner::factory()->create(['user_id' => $user->id, 'status' => 'approved']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect();
    }

    public function test_register_page_accessible()
    {
        $response = $this->get('/register/eventner');

        $response->assertStatus(200);
    }

    public function test_landing_page_accessible()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_event_detail_page_shows_event()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'registration_status' => 'open',
        ]);

        $response = $this->get("/event/{$eventner->slug}");

        $response->assertStatus(200);
        $response->assertSee($eventner->nama_event);
    }

    public function test_event_detail_returns_404_for_nonexistent_slug()
    {
        $response = $this->get('/event/tidak-ada-12345');

        $response->assertStatus(404);
    }

    public function test_pending_event_not_accessible_via_slug()
    {
        $eventner = Eventner::factory()->pending()->create([
            'nama_event' => 'Pending Event Test',
        ]);

        $response = $this->get("/event/{$eventner->slug}");

        // Pending event harus 404 — tidak boleh live sebelum disetujui.
        $response->assertStatus(404);
    }

    public function test_participant_page_accessible()
    {
        $eventner = Eventner::factory()->create(['status' => 'approved']);

        $response = $this->get("/event/{$eventner->slug}/participant");

        $response->assertStatus(200);
    }

    public function test_privacy_page_accessible()
    {
        $response = $this->get('/privacy');
        $response->assertStatus(200);
    }

    public function test_terms_page_accessible()
    {
        $response = $this->get('/terms');
        $response->assertStatus(200);
    }

    public function test_help_page_accessible()
    {
        $response = $this->get('/help');
        $response->assertStatus(200);
    }

    public function test_autogopay_webhook_accessible()
    {
        $response = $this->post('/webhook/autogopay', [], [
            'Accept' => 'application/json',
        ]);

        // Should process (even if data invalid) — no CSRF, no auth
        $this->assertNotEquals(404, $response->getStatusCode());
        $this->assertNotEquals(419, $response->getStatusCode());
    }
}
