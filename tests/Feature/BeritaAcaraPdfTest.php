<?php

namespace Tests\Feature;

use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Participant;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeritaAcaraPdfTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Eventner $eventner;
    private CompetitionCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->eventner()->create(['is_active' => true]);
        $this->eventner = Eventner::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'approved',
            'nama_event' => 'LKBB Nasional Juara',
        ]);

        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
        ]);

        $this->category = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
            'name' => 'Tingkat SMA',
        ]);
    }

    public function test_guest_cannot_download_berita_acara_pdf()
    {
        $response = $this->get(route('eventner.score-recap.berita-acara-pdf', [
            'category_id' => $this->category->id,
        ]));

        $response->assertRedirect(route('login'));
    }

    public function test_non_owner_cannot_download_berita_acara_pdf()
    {
        $otherUser = User::factory()->eventner()->create(['is_active' => true]);
        Eventner::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($otherUser);

        // Accessing category owned by another eventner
        $response = $this->get(route('eventner.score-recap.berita-acara-pdf', [
            'category_id' => $this->category->id,
        ]));

        $response->assertNotFound();
    }

    public function test_missing_category_id_returns_bad_request()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('eventner.score-recap.berita-acara-pdf'));

        $response->assertStatus(400);
    }

    public function test_owner_can_download_berita_acara_pdf()
    {
        $this->actingAs($this->user);

        // Create judge
        Judge::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Mayor Inf Budi Santoso',
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        // Create participant
        $reg = Registration::factory()->create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->category->id,
        ]);

        Participant::create([
            'registration_id' => $reg->id,
            'nama' => 'Paskibra Inti',
            'nisn' => '1234567890',
        ]);

        $response = $this->get(route('eventner.score-recap.berita-acara-pdf', [
            'category_id' => $this->category->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('Berita_Acara_', $response->headers->get('content-disposition'));
    }
}
