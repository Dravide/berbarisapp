<?php

namespace Tests\Feature;

use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\Ticket;
use App\Models\User;
use App\Models\VoteTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventnerDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function setupEventnerUser(): array
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        $eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);
        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => null,
        ]);
        $category = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => $parent->id,
        ]);

        return [$user, $eventner, $category];
    }

    private function createVoteTransaction($eventner, $registration, array $attrs = []): VoteTransaction
    {
        return VoteTransaction::create(array_merge([
            'eventner_id' => $eventner->id,
            'registration_id' => $registration->id,
            'autogopay_transaction_id' => 'AGP-' . uniqid(),
            'qr_url' => 'https://example.com/qr.png',
            'amount' => 10000,
            'votes_earned' => 10,
            'voter_name' => 'Voter Satu',
            'voter_email' => 'voter@example.com',
            'comment' => 'Semangat!',
            'status' => 'PAID',
            'paid_at' => now(),
        ], $attrs));
    }

    public function test_dashboard_shows_vote_and_ticket_stats()
    {
        [$user, $eventner, $category] = $this->setupEventnerUser();

        $reg = Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $category->id,
        ]);

        $this->createVoteTransaction($eventner, $reg, ['votes_earned' => 10]);
        $this->createVoteTransaction($eventner, $reg, ['votes_earned' => 5, 'status' => 'PENDING', 'paid_at' => null]);

        Ticket::create([
            'eventner_id' => $eventner->id,
            'buyer_name' => 'Pembeli A',
            'buyer_email' => 'a@example.com',
            'quantity' => 3,
            'price_per_ticket' => 25000,
            'total_amount' => 75000,
            'status' => 'PAID',
            'paid_at' => now(),
        ]);
        Ticket::create([
            'eventner_id' => $eventner->id,
            'buyer_name' => 'Pembeli B',
            'buyer_email' => 'b@example.com',
            'quantity' => 2,
            'price_per_ticket' => 25000,
            'total_amount' => 50000,
            'status' => 'CHECKED_IN',
            'paid_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Eventner\Dashboard::class)
            ->assertSet('totalVotes', 10)
            ->assertSet('votePaidCount', 1)
            ->assertSet('votePendingCount', 1)
            ->assertSet('ticketsSold', 5)
            ->assertSet('ticketsCheckedIn', 1);
    }

    public function test_dashboard_shows_payment_and_berkas_breakdown()
    {
        [$user, $eventner, $category] = $this->setupEventnerUser();

        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $category->id,
            'payment_status' => 'paid',
        ]);
        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $category->id,
            'payment_status' => 'pending_verification',
        ]);
        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $category->id,
            'payment_status' => 'unpaid',
        ]);
        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $category->id,
            'status_berkas' => 'Menunggu',
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Eventner\Dashboard::class)
            ->assertSet('pendingVerificationCount', 1)
            ->assertSet('berkasMenungguCount', 1)
            ->assertSet('paymentBreakdown.paid', 1)
            ->assertSet('paymentBreakdown.pending_verification', 1)
            ->assertSet('paymentBreakdown.unpaid', 1)
            ->assertSee('Menunggu Verifikasi (1)', false);
    }

    public function test_dashboard_revenue_labels_fixed()
    {
        [$user, $eventner, $category] = $this->setupEventnerUser();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Eventner\Dashboard::class)
            ->assertSee('Total Pendapatan')
            ->assertDontSee('Estimasi Voting');
    }

    public function test_dashboard_vote_schedule_belum()
    {
        [$user, $eventner, $category] = $this->setupEventnerUser();
        $eventner->update([
            'vote_active' => true,
            'vote_start' => now()->addDays(3),
            'vote_end' => now()->addDays(10),
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Eventner\Dashboard::class)
            ->assertSet('voteStatus', 'belum');
    }

    public function test_dashboard_vote_schedule_selesai()
    {
        [$user, $eventner, $category] = $this->setupEventnerUser();
        $eventner->update([
            'vote_active' => true,
            'vote_start' => now()->subDays(10),
            'vote_end' => now()->subDay(),
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Eventner\Dashboard::class)
            ->assertSet('voteStatus', 'selesai');
    }

    public function test_dashboard_vote_schedule_nonaktif()
    {
        [$user, $eventner, $category] = $this->setupEventnerUser();
        $eventner->update(['vote_active' => false]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Eventner\Dashboard::class)
            ->assertSet('voteStatus', 'nonaktif');
    }
}
