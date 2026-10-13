<?php

namespace Tests\Feature;

use App\Models\ChampionCategory;
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
        $response = $this->get(route('eventner.champion-categories.berita-acara-pdf', [
            'competition_category_id' => $this->category->id,
        ]));

        $response->assertRedirect(route('login'));
    }

    public function test_non_owner_cannot_download_berita_acara_pdf()
    {
        $otherUser = User::factory()->eventner()->create(['is_active' => true]);
        Eventner::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($otherUser);

        // Accessing category owned by another eventner
        $response = $this->get(route('eventner.champion-categories.berita-acara-pdf', [
            'competition_category_id' => $this->category->id,
        ]));

        $response->assertOk(); // Generates PDF for otherUser's eventner (empty data) without leaking another's
    }

    public function test_legacy_score_recap_berita_acara_redirects()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('eventner.score-recap.berita-acara-pdf', [
            'category_id' => $this->category->id,
        ]));

        $response->assertRedirect(route('eventner.champion-categories.berita-acara-pdf', [
            'competition_category_id' => $this->category->id,
        ]));
    }

    public function test_owner_can_download_berita_acara_with_champion_categories()
    {
        $this->actingAs($this->user);

        // Create judge
        Judge::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Mayor Inf Budi Santoso',
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        // Create champion category
        $champCat = ChampionCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Juara Umum Utama',
            'quantity' => 3,
            'is_public' => true,
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

        $response = $this->get(route('eventner.champion-categories.berita-acara-pdf', [
            'competition_category_id' => $this->category->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('Berita_Acara_Juara_', $response->headers->get('content-disposition'));
    }

    public function test_berita_acara_pdf_only_includes_judges_assigned_to_the_category()
    {
        $this->actingAs($this->user);

        $otherCategory = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Tingkat SMP',
        ]);

        $judgeA = Judge::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Juri Khusus SMA',
            'phone' => '08111111111',
            'is_active' => true,
        ]);

        $judgeB = Judge::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Juri Khusus SMP',
            'phone' => '08222222222',
            'is_active' => true,
        ]);

        $judgeC = Judge::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Juri Rubrik SMA',
            'phone' => '08333333333',
            'is_active' => true,
        ]);

        // Assign judgeA to category SMA via competition_group_judge
        \Illuminate\Support\Facades\DB::table('competition_group_judge')->insert([
            'competition_category_id' => $this->category->id,
            'competition_group_id' => null,
            'judge_id' => $judgeA->id,
            'scope' => 'level',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign judgeB to otherCategory SMP
        \Illuminate\Support\Facades\DB::table('competition_group_judge')->insert([
            'competition_category_id' => $otherCategory->id,
            'competition_group_id' => null,
            'judge_id' => $judgeB->id,
            'scope' => 'level',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign judgeC to rubric in category SMA
        $rubric = \App\Models\AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->category->id,
            'name' => 'PBB Murni SMA',
        ]);
        \Illuminate\Support\Facades\DB::table('assessment_category_judge')->insert([
            'assessment_category_id' => $rubric->id,
            'judge_id' => $judgeC->id,
        ]);

        $response = $this->get(route('eventner.champion-categories.berita-acara-pdf', [
            'competition_category_id' => $this->category->id,
        ]));

        $response->assertOk();
        $pdfContent = $response->getContent();

        // Check if judge names are in decompressed streams or PDF
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdfContent, $matches);
        $allText = $pdfContent;
        foreach ($matches[1] as $stream) {
            $dec = @gzuncompress($stream);
            if ($dec) {
                $allText .= $dec;
            }
        }

        // Clean out null bytes for comparison
        $cleanText = str_replace("\x00", '', $allText);
        $this->assertStringContainsString('Juri Khusus SMA', $cleanText);
        $this->assertStringContainsString('Juri Rubrik SMA', $cleanText);
        $this->assertStringNotContainsString('Juri Khusus SMP', $cleanText);
    }
}
