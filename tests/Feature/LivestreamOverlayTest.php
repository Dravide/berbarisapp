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

    public function test_overlay_comments_mode_renders()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'slug' => 'test-overlay-comments',
        ]);

        $response = $this->get('/event/' . $eventner->slug . '/overlay?mode=comments');

        $response->assertStatus(200);
    }

    public function test_overlay_category_mode_scoped_ke_kategori()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'slug' => 'test-overlay-category',
        ]);

        $parent = \App\Models\CompetitionCategory::factory()->for($eventner, 'eventner')->create();
        $catA = \App\Models\CompetitionCategory::factory()->child($parent)->for($eventner, 'eventner')->create(['name' => 'Tari A']);
        $catB = \App\Models\CompetitionCategory::factory()->child($parent)->for($eventner, 'eventner')->create(['name' => 'Tari B']);

        $regA = \App\Models\Registration::factory()->for($eventner, 'eventner')->create([
            'competition_category_id' => $catA->id,
            'nama_sekolah' => 'SMP Kategori A',
        ]);
        $regB = \App\Models\Registration::factory()->for($eventner, 'eventner')->create([
            'competition_category_id' => $catB->id,
            'nama_sekolah' => 'SMP Kategori B',
        ]);

        \App\Models\VoteTransaction::create([
            'eventner_id' => $eventner->id,
            'registration_id' => $regA->id,
            'autogopay_transaction_id' => 'AGP-TEST-' . $eventner->id,
            'qr_url' => 'https://example.com/qr/test',
            'amount' => 10000,
            'votes_earned' => 10,
            'voter_name' => 'Donatur A',
            'comment' => 'Semangat kategori A!',
            'status' => 'PAID',
            'paid_at' => now(),
        ]);

        // Mode category default: kategori pertama (A) — hanya peserta A yang tampil
        $response = $this->get('/event/' . $eventner->slug . '/overlay?mode=category');
        $response->assertStatus(200)
            ->assertSee('SMP Kategori A')
            ->assertSee('Semangat kategori A!')
            ->assertDontSee('SMP Kategori B');

        // Switch ke kategori B — hanya peserta B, komentar A hilang
        $response = $this->get('/event/' . $eventner->slug . '/overlay?mode=category&selectedCategoryId=' . $catB->id);
        $response->assertStatus(200)
            ->assertSee('SMP Kategori B')
            ->assertDontSee('SMP Kategori A')
            ->assertDontSee('Semangat kategori A!');
    }
}
