<?php

namespace Tests\Unit;

use App\Models\Eventner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventnerModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_slug_generated_on_create()
    {
        $eventner = Eventner::factory()->create([
            'nama_event' => 'Lomba Pramuka Se-Jawa Barat',
            'slug' => null,
        ]);

        $this->assertNotNull($eventner->slug);
        $this->assertStringContainsString('lomba-pramuka', $eventner->slug);
    }

    public function test_slug_updated_when_nama_event_changes()
    {
        $eventner = Eventner::factory()->create();
        $oldSlug = $eventner->slug;

        $eventner->update(['nama_event' => 'Event Baru Total']);

        $this->assertNotEquals($oldSlug, $eventner->fresh()->slug);
        $this->assertStringContainsString('event-baru-total', $eventner->fresh()->slug);
    }

    public function test_slug_stays_same_when_other_fields_change()
    {
        $eventner = Eventner::factory()->create();
        $oldSlug = $eventner->slug;

        $eventner->update(['lokasi' => 'Bandung']);

        $this->assertEquals($oldSlug, $eventner->fresh()->slug);
    }

    public function test_slug_collision_handled()
    {
        $eventner1 = Eventner::factory()->create(['nama_event' => 'Sama']);
        $eventner2 = Eventner::factory()->create(['nama_event' => 'Sama']);

        $this->assertNotNull($eventner1->slug);
        $this->assertNotNull($eventner2->slug);
        $this->assertNotEquals($eventner1->slug, $eventner2->slug);
    }

    public function test_is_on_trial_when_plan_free_and_not_expired()
    {
        $eventner = Eventner::factory()->create([
            'plan' => 'free',
            'trial_ends_at' => now()->addDays(10),
        ]);

        $this->assertTrue($eventner->isOnTrial());
        $this->assertFalse($eventner->isTrialExpired());
    }

    public function test_trial_expired_correctly()
    {
        $eventner = Eventner::factory()->expiredTrial()->create();

        $this->assertFalse($eventner->isOnTrial());
        $this->assertTrue($eventner->isTrialExpired());
    }

    public function test_paid_plan_not_on_trial()
    {
        $eventner = Eventner::factory()->paid()->create();

        $this->assertFalse($eventner->isOnTrial());
        $this->assertFalse($eventner->isTrialExpired());
    }

    public function test_trial_days_left()
    {
        $eventner = Eventner::factory()->create([
            'plan' => 'free',
            'trial_ends_at' => now()->addDays(5),
        ]);

        $this->assertEquals(5, $eventner->trialDaysLeft());
    }

    public function test_trial_days_left_zero_when_not_on_trial()
    {
        $eventner = Eventner::factory()->paid()->create();

        $this->assertEquals(0, $eventner->trialDaysLeft());
    }

    public function test_can_access_feature_when_paid()
    {
        $eventner = Eventner::factory()->paid()->create();

        $this->assertTrue($eventner->canAccessFeature('tickets'));
        $this->assertTrue($eventner->canAccessFeature('vote_settings'));
        $this->assertTrue($eventner->canAccessFeature('unknown_feature'));
    }

    public function test_can_access_feature_when_on_trial()
    {
        $eventner = Eventner::factory()->create([
            'plan' => 'free',
            'trial_ends_at' => now()->addDays(5),
        ]);

        $this->assertTrue($eventner->canAccessFeature('tickets'));
        $this->assertTrue($eventner->canAccessFeature('vote_settings'));
    }

    public function test_cannot_access_locked_feature_when_trial_expired()
    {
        config(['eventner_features.tickets' => ['label' => 'Tiket Event', 'locked_free' => true]]);

        $eventner = Eventner::factory()->expiredTrial()->create();

        $this->assertFalse($eventner->canAccessFeature('tickets'));
    }

    public function test_can_access_unlocked_feature_when_trial_expired()
    {
        config(['eventner_features.some_feature' => ['label' => 'Some Feature', 'locked_free' => false]]);

        $eventner = Eventner::factory()->expiredTrial()->create();

        $this->assertTrue($eventner->canAccessFeature('some_feature'));
    }

    public function test_can_access_unknown_feature_always()
    {
        $eventner = Eventner::factory()->expiredTrial()->create();

        $this->assertTrue($eventner->canAccessFeature('completely_unknown'));
    }

    public function test_locked_features_empty_when_paid()
    {
        $eventner = Eventner::factory()->paid()->create();

        $this->assertEmpty($eventner->lockedFeatures());
    }

    public function test_locked_features_empty_when_on_trial()
    {
        $eventner = Eventner::factory()->create([
            'plan' => 'free',
            'trial_ends_at' => now()->addDays(5),
        ]);

        $this->assertEmpty($eventner->lockedFeatures());
    }

    public function test_locked_features_contains_gated_keys_when_expired()
    {
        config(['eventner_features' => [
            'tickets' => ['label' => 'Tiket Event', 'locked_free' => true],
            'free_feature' => ['label' => 'Free', 'locked_free' => false],
        ]]);

        $eventner = Eventner::factory()->expiredTrial()->create();
        $locked = $eventner->lockedFeatures();

        $this->assertArrayHasKey('tickets', $locked);
        $this->assertArrayNotHasKey('free_feature', $locked);
    }

    public function test_public_url_with_subdomain()
    {
        config(['app.url' => 'http://berbaris.test']);
        $eventner = Eventner::factory()->create([
            'subdomain' => 'kejurcab',
            'slug' => 'kejurcab-abc12',
        ]);

        $url = $eventner->publicUrl('detail');

        $this->assertStringContainsString('kejurcab.berbaris.test', $url);
    }

    public function test_public_url_without_subdomain()
    {
        config(['app.url' => 'http://berbaris.test']);
        $eventner = Eventner::factory()->create([
            'subdomain' => null,
            'slug' => 'event-xyz99',
        ]);

        $url = $eventner->publicUrl('participant');

        $this->assertStringContainsString('event-xyz99', $url);
        $this->assertStringContainsString('/event/', $url);
    }
}
