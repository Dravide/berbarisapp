<?php

namespace Tests\Feature;

use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\VoteTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventVoteTopSupportersTest extends TestCase
{
    use RefreshDatabase;

    private function createVoteTransaction($eventner, $registration, string $email, string $name, int $votes, array $extra = []): VoteTransaction
    {
        return VoteTransaction::create(array_merge([
            'eventner_id' => $eventner->id,
            'registration_id' => $registration->id,
            'autogopay_transaction_id' => 'AGP-' . uniqid(),
            'qr_url' => 'https://example.com/qr.png',
            'amount' => $votes * 1000,
            'votes_earned' => $votes,
            'voter_name' => $name,
            'voter_email' => $email,
            'status' => 'PAID',
            'paid_at' => now(),
        ], $extra));
    }

    public function test_top_supporters_aggregate_per_email()
    {
        $user = \App\Models\User::factory()->eventner()->create(['is_active' => true]);
        $eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'vote_active' => true,
        ]);
        $parent = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id, 'parent_id' => null]);
        $category = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id, 'parent_id' => $parent->id]);
        $otherCategory = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id, 'parent_id' => $parent->id]);

        $reg = Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $category->id,
        ]);
        $regOther = Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $otherCategory->id,
        ]);

        // Email sama transaksi 2x — harus tergabung (20 + 30 = 50)
        $this->createVoteTransaction($eventner, $reg, 'dedi@mail.com', 'Dedi', 20);
        $this->createVoteTransaction($eventner, $reg, 'dedi@mail.com', 'Dedi', 30);
        // Email beda, vote lebih kecil
        $this->createVoteTransaction($eventner, $reg, 'andi@mail.com', 'Andi', 40);
        // Email sama tapi PENDING — tidak dihitung
        $this->createVoteTransaction($eventner, $reg, 'pending@mail.com', 'Pending Guy', 99, [
            'status' => 'PENDING',
            'paid_at' => null,
        ]);
        // Email sama, vote besar, tapi kategori lain — tidak muncul di kategori ini
        $this->createVoteTransaction($eventner, $regOther, 'other@mail.com', 'Other Cat', 100);

        Livewire::actingAs($user)
            ->withQueryParams(['selectedCategoryId' => $category->id])
            ->test(\App\Livewire\Public\EventVote::class, ['slug' => $eventner->slug])
            ->assertSee('Top 10 Pendukung')
            ->assertSee('dedi@mail.com')
            ->assertSee('andi@mail.com')
            ->assertDontSee('pending@mail.com')
            ->assertDontSee('other@mail.com');

        // Urutan: dedi (50) di atas andi (40)
        $supporters = Livewire::withQueryParams(['selectedCategoryId' => $category->id])
            ->test(\App\Livewire\Public\EventVote::class, ['slug' => $eventner->slug])
            ->instance()->topSupporters;

        $this->assertSame('dedi@mail.com', $supporters->first()->voter_email);
        $this->assertEquals(50, $supporters->first()->total_votes);
        $this->assertSame('andi@mail.com', $supporters->get(1)->voter_email);
        $this->assertCount(2, $supporters);
    }
}
