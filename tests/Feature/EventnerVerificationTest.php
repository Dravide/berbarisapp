<?php

namespace Tests\Feature;

use App\Livewire\Admin\Eventner\Show as AdminEventnerShow;
use App\Livewire\Public\EventnerList;
use App\Models\Eventner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventnerVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create(['is_active' => true]);
    }

    private function organizer(): User
    {
        return User::factory()->eventner()->create(['is_active' => true]);
    }

    public function test_admin_can_toggle_and_revoke_eventner_verification()
    {
        $admin = $this->admin();
        $eventner = Eventner::factory()->create([
            'is_verified' => false,
            'verified_at' => null,
            'verified_by' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(AdminEventnerShow::class, ['id' => $eventner->id])
            ->call('toggleVerification');

        $eventner->refresh();
        $this->assertTrue($eventner->is_verified);
        $this->assertNotNull($eventner->verified_at);
        $this->assertEquals($admin->id, $eventner->verified_by);
        $this->assertEquals($admin->id, $eventner->verifiedBy->id);

        // Revoke
        Livewire::actingAs($admin)
            ->test(AdminEventnerShow::class, ['id' => $eventner->id])
            ->call('toggleVerification');

        $eventner->refresh();
        $this->assertFalse($eventner->is_verified);
        $this->assertNull($eventner->verified_at);
        $this->assertNull($eventner->verified_by);
    }

    public function test_non_admin_cannot_toggle_verification()
    {
        $organizer = $this->organizer();
        $eventner = Eventner::factory()->create(['is_verified' => false]);

        Livewire::actingAs($organizer)
            ->test(AdminEventnerShow::class, ['id' => $eventner->id])
            ->call('toggleVerification')
            ->assertForbidden();
    }

    public function test_public_eventner_list_filters_verified_organizers()
    {
        Eventner::factory()->create([
            'nama_event' => 'Event Terverifikasi',
            'status' => 'approved',
            'is_verified' => true,
        ]);

        Eventner::factory()->create([
            'nama_event' => 'Event Biasa',
            'status' => 'approved',
            'is_verified' => false,
        ]);

        // Default: 'all'
        Livewire::test(EventnerList::class)
            ->assertSee('Event Terverifikasi')
            ->assertSee('Event Biasa')
            ->set('filter', 'verified')
            ->assertSee('Event Terverifikasi')
            ->assertDontSee('Event Biasa');
    }

    public function test_verified_badge_appears_on_eventner_card_when_verified()
    {
        $verifiedEventner = Eventner::factory()->create([
            'nama_event' => 'Kejuaraan Resmi',
            'diselenggarakan_oleh' => 'Purna Paskibraka Indonesia',
            'status' => 'approved',
            'is_verified' => true,
        ]);

        $unverifiedEventner = Eventner::factory()->create([
            'nama_event' => 'Lomba Sekolah',
            'diselenggarakan_oleh' => 'OSIS SMA 1',
            'status' => 'approved',
            'is_verified' => false,
        ]);

        $viewVerified = $this->blade('<x-landing.eventner-card :eventner="$eventner" />', ['eventner' => $verifiedEventner]);
        $viewVerified->assertSee('Penyelenggara Terverifikasi');

        $viewUnverified = $this->blade('<x-landing.eventner-card :eventner="$eventner" />', ['eventner' => $unverifiedEventner]);
        $viewUnverified->assertDontSee('Penyelenggara Terverifikasi');
    }

    public function test_verified_badge_appears_on_event_detail_page()
    {
        $verifiedEventner = Eventner::factory()->create([
            'nama_event' => 'Kejuaraan Nasional 2026',
            'diselenggarakan_oleh' => 'Kemenpora RI',
            'status' => 'approved',
            'is_verified' => true,
            'slug' => 'kejurnas-2026',
        ]);

        $unverifiedEventner = Eventner::factory()->create([
            'nama_event' => 'Lomba Regional',
            'diselenggarakan_oleh' => 'Panitia Lokal',
            'status' => 'approved',
            'is_verified' => false,
            'slug' => 'lomba-regional',
        ]);

        Livewire::test(\App\Livewire\Public\EventDetail::class, ['slug' => $verifiedEventner->slug])
            ->assertSee('Terverifikasi')
            ->assertSee('Penyelenggara Terverifikasi');

        Livewire::test(\App\Livewire\Public\EventDetail::class, ['slug' => $unverifiedEventner->slug])
            ->assertDontSee('Terverifikasi')
            ->assertDontSee('Penyelenggara Terverifikasi');
    }
}
