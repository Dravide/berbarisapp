<?php

namespace Tests\Feature;

use App\Livewire\Public\EventVote;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventVoteCountdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_countdown_timer_displayed_when_voting_is_active_and_has_end_date()
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        $eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'slug' => 'event-vote-test',
            'status' => 'approved',
            'vote_active' => true,
            'vote_start' => now()->subDay(),
            'vote_end' => now()->addDays(5),
        ]);

        $parentCat = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => null,
            'name' => 'Tingkat SMA',
        ]);

        CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => $parentCat->id,
            'name' => 'Utama Putra',
        ]);

        Livewire::test(EventVote::class, ['slug' => $eventner->slug])
            ->assertSee('Voting Berakhir Dalam')
            ->assertSee('Batas Vote:');

        // Test with selectedCategoryId query parameter
        Livewire::withQueryParams(['selectedCategoryId' => ''])
            ->test(EventVote::class, ['slug' => $eventner->slug])
            ->assertSee('Voting Berakhir Dalam')
            ->assertSee('Batas Vote:');
    }

    public function test_countdown_timer_displayed_when_voting_is_scheduled()
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        $eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'slug' => 'event-vote-scheduled',
            'status' => 'approved',
            'vote_active' => true,
            'vote_start' => now()->addDays(3),
            'vote_end' => now()->addDays(10),
        ]);

        Livewire::test(EventVote::class, ['slug' => $eventner->slug])
            ->assertSee('Voting Dimulai Dalam')
            ->assertSee('Voting Belum Dibuka');
    }
}
