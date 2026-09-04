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

    public function test_dashboard_shows_verification_counts()
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
            ->assertSet('berkasMenungguCount', 1);
    }

    public function test_dashboard_revenue_labels_fixed()
    {
        [$user, $eventner, $category] = $this->setupEventnerUser();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Eventner\Dashboard::class)
            ->assertSee('Total Pendapatan')
            ->assertDontSee('Estimasi Voting')
            ->assertDontSee('paymentStatusChart')
            ->assertDontSee('Status Pembayaran Pendaftar');
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

    public function test_dashboard_readiness_shows_progress()
    {
        [$user, $eventner, $category] = $this->setupEventnerUser();

        // Event kosong: 1 kategori tanpa kuota, tanpa juri, tanpa rundown — readiness < 100
        $component = Livewire::actingAs($user)
            ->test(\App\Livewire\Eventner\Dashboard::class)
            ->assertSet('readinessPercent', fn ($percent) => $percent < 100)
            ->assertSee('Kesiapan Event Anda')
            ->assertSee('Juri belum ada')
            ->assertSee('Rundown kosong');

        // Isi semua readiness → 100%
        // Refresh user — Auth::user()->eventner relasi ter-cache dari mount pertama
        $user = $user->fresh();
        $eventner->update([
            'logo_event' => 'logos/test.png',
            'poster' => 'posters/test.png',
            'vote_active' => true,
            'vote_start' => now()->subDay(),
            'vote_end' => now()->addDays(5),
        ]);
        $category->update(['kuota' => 10]);
        \App\Models\Judge::create([
            'eventner_id' => $eventner->id,
            'name' => 'Juri Satu',
            'phone_number' => '08123456789',
        ]);
        \App\Models\AssessmentCategory::create([
            'eventner_id' => $eventner->id,
            'name' => 'Penampilan',
        ]);
        \App\Models\EventRundown::create([
            'eventner_id' => $eventner->id,
            'title' => 'Opening',
            'start_time' => '08:00',
        ]);
        // Undian: 1 registrasi dengan urutan_tampil
        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $category->id,
            'urutan_tampil' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Eventner\Dashboard::class)
            ->assertSet('readinessPercent', 100);
    }

    public function test_dashboard_alerts_generated()
    {
        [$user, $eventner, $category] = $this->setupEventnerUser();

        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $category->id,
            'payment_status' => 'pending_verification',
        ]);
        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $category->id,
            'payment_status' => 'pending_verification',
        ]);

        // Kuota penuh: kuota 1 + 1 pendaftar = 100% ≥ 80%
        $category->update(['kuota' => 1]);
        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $category->id,
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Eventner\Dashboard::class)
            ->assertSee('Perlu Perhatian')
            ->assertSee('2 pembayaran menunggu verifikasi')
            ->assertSee('% penuh');
    }

    public function test_dashboard_kpi_modules_render()
    {
        [$user, $eventner, $category] = $this->setupEventnerUser();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Eventner\Dashboard::class)
            ->assertSee('Aksi Cepat')
            ->assertSee('Kategori Lomba')
            ->assertSee('Undian')
            ->assertSee('Skoring')
            ->assertSee('Check-in Tiket')
            ->assertDontSee('Pintasan Panitia')
            ->assertDontSee('Informasi Event Anda');
    }
}
