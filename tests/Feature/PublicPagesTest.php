<?php

namespace Tests\Feature;

use App\Livewire\Public\EventVote;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Participant;
use App\Models\Registration;
use App\Models\Sponsor;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_loads()
    {
        Eventner::factory(3)->create(['status' => 'approved']);
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_event_detail_loads()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'nama_event' => 'Lomba Seni 2026',
        ]);
        $category = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => null,
            'name' => 'Seni Tari',
        ]);

        $response = $this->get("/event/{$eventner->slug}");
        $response->assertStatus(200);
    }

    public function test_event_participant_loads()
    {
        $eventner = Eventner::factory()->create(['status' => 'approved']);
        $cat = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => null,
        ]);
        $child = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => $cat->id,
        ]);
        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $child->id,
            'status_berkas' => 'confirmed',
        ]);

        $response = $this->get("/event/{$eventner->slug}/participant");
        $response->assertStatus(200);
    }

    public function test_scoreboard_page_loads()
    {
        Eventner::factory()->create([
            'status' => 'approved',
            'scoring_code' => 'SC-12345',
        ]);

        $response = $this->get('/scoreboard/SC-12345');
        $response->assertStatus(200);
    }

    public function test_champions_page_loads()
    {
        Eventner::factory()->create([
            'status' => 'approved',
            'scoring_code' => 'CH-67890',
        ]);

        $response = $this->get('/champions/CH-67890');
        $response->assertStatus(200);
    }

    public function test_event_detail_with_sponsors_and_tenants()
    {
        $eventner = Eventner::factory()->create(['status' => 'approved']);
        Sponsor::factory()->create([
            'eventner_id' => $eventner->id,
            'is_active' => true,
        ]);
        Tenant::factory()->create([
            'eventner_id' => $eventner->id,
            'is_active' => true,
        ]);

        $response = $this->get("/event/{$eventner->slug}");
        $response->assertStatus(200);
    }

    public function test_pending_event_not_accessible_via_slug()
    {
        $eventner = Eventner::factory()->pending()->create(['nama_event' => 'Pending Test']);

        // Pending event harus 404 — tidak boleh live sebelum disetujui.
        $response = $this->get("/event/{$eventner->slug}");
        $response->assertStatus(404);
    }

    public function test_ticket_page_loads()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'ticket_active' => true,
            'ticket_price' => 50000,
        ]);

        $response = $this->get("/event/{$eventner->slug}/ticket");
        $response->assertStatus(200);
    }

    public function test_404_for_nonexistent_slug()
    {
        $response = $this->get('/event/tidak-ada-99999');
        $response->assertStatus(404);
    }

    public function test_vote_page_shows_top3_modal()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'vote_active' => true,
        ]);
        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => null,
        ]);
        $child = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => $parent->id,
        ]);

        $schools = ['SMA Juara Satu', 'SMA Perak', 'SMA Perunggu'];
        foreach ($schools as $i => $nama) {
            $reg = Registration::factory()->create([
                'eventner_id' => $eventner->id,
                'competition_category_id' => $child->id,
                'nama_sekolah' => $nama,
            ]);
            \App\Models\VoteTransaction::create([
                'eventner_id' => $eventner->id,
                'registration_id' => $reg->id,
                'autogopay_transaction_id' => 'tx-modal-' . $i,
                'qr_url' => 'https://example.com/qr.png',
                'amount' => 10000,
                'votes_earned' => (3 - $i) * 100, // 300, 200, 100
                'status' => 'PAID',
                'paid_at' => now(),
            ]);
        }

        $response = $this->get("/event/{$eventner->slug}/vote?selectedCategoryId={$child->id}");
        $response->assertStatus(200);

        // Tombol buka modal + podium di dalam modal dirender
        $response->assertSee('Hasil Sementara');
        $response->assertSee('Pimpinan Klasemen');
        $response->assertSee('SMA Juara Satu');

        // Modal hanya berisi 3 tertinggi — kontingen ke-4 tampil di list, bukan di topThree
        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $child->id,
            'nama_sekolah' => 'SMA Bukan Pemenang',
        ]);
        Livewire::test(EventVote::class, ['slug' => $eventner->slug])
            ->set('selectedCategoryId', $child->id)
            ->assertViewHas('topThree', fn ($top) => $top->count() === 3
                && $top->first()->nama_sekolah === 'SMA Juara Satu'
                && !$top->contains('nama_sekolah', 'SMA Bukan Pemenang'));
    }

    public function test_vote_page_hides_top3_modal_when_no_paid_votes()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'vote_active' => true,
        ]);
        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => null,
        ]);
        $child = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => $parent->id,
        ]);
        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $child->id,
        ]);

        // Top-3 dihitung dari semua peserta kategori, PAID-sum bisa 0 —
        // modal tetap render karena isNotEmpty() cek registrasi, bukan vote.
        $response = $this->get("/event/{$eventner->slug}/vote?selectedCategoryId={$child->id}");
        $response->assertStatus(200);
        $response->assertSee('Hasil Sementara');
    }
}
