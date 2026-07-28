<?php

namespace Tests\Feature;

use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\CertificateTemplate;
use App\Models\CertificateTextField;
use App\Models\ChampionCategory;
use App\Models\ChampionRankTitle;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure storage link exists for testing
        if (!is_dir(public_path('storage'))) {
            \Illuminate\Support\Facades\Artisan::call('storage:link');
        }
    }

    public function test_certificate_page_requires_auth()
    {
        $response = $this->get(route('eventner.certificate.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_certificate_download_requires_params()
    {
        $user = User::factory()->create(['role' => 'Eventner']);
        $eventner = Eventner::factory()->create(['user_id' => $user->id, 'status' => 'approved', 'plan' => 'paid']);
        $this->actingAs($user);

        $response = $this->get(route('eventner.certificate.pdf'));
        $response->assertStatus(422);
    }

    public function test_certificate_download_returns_pdf()
    {
        $user = User::factory()->create(['role' => 'Eventner']);
        $eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'plan' => 'paid',
            'tanggal' => '2026-08-15',
        ]);

        // Create assets
        $template = CertificateTemplate::factory()->create([
            'eventner_id' => $eventner->id,
            'width' => 297,
            'height' => 210,
        ]);

        // Add a text field
        CertificateTextField::create([
            'certificate_template_id' => $template->id,
            'field_key' => 'nama_sekolah',
            'label' => 'Nama Sekolah',
            'x' => 148.5,
            'y' => 80,
            'font_size' => 24,
            'font_color' => '#000000',
            'text_align' => 'center',
            'font_weight' => 'bold',
        ]);

        $parentCat = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id, 'parent_id' => null]);
        $compCat = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id, 'parent_id' => $parentCat->id]);

        // Create assessment structure
        $assessmentCat = AssessmentCategory::create(['eventner_id' => $eventner->id, 'name' => 'Penilaian']);
        $subCat = AssessmentSubCategory::create(['assessment_category_id' => $assessmentCat->id, 'name' => 'Sub A']);
        $criteria = AssessmentCriteria::create([
            'assessment_sub_category_id' => $subCat->id,
            'name' => 'Kriteria A',
            'score_options' => json_encode([['label' => 'Buruk', 'value' => 10], ['label' => 'Baik', 'value' => 100]]),
            'weight' => 1,
        ]);

        $championCat = ChampionCategory::create([
            'eventner_id' => $eventner->id,
            'name' => 'Juara Umum',
            'quantity' => 3,
        ]);
        $championCat->assessmentSubCategories()->sync([$subCat->id]);

        ChampionRankTitle::create([
            'champion_category_id' => $championCat->id,
            'title' => 'Juara 1',
            'rank_start' => 1,
            'rank_end' => 1,
            'sort_order' => 1,
        ]);

        // Create registrations with scores
        for ($i = 0; $i < 3; $i++) {
            $reg = Registration::factory()->create([
                'eventner_id' => $eventner->id,
                'competition_category_id' => $compCat->id,
                'nama_sekolah' => "Sekolah Test {$i}",
            ]);

            $judge = Judge::create(['eventner_id' => $eventner->id, 'name' => "Juri {$i}"]);

            AssessmentScore::create([
                'eventner_id' => $eventner->id,
                'judge_id' => $judge->id,
                'registration_id' => $reg->id,
                'assessment_criteria_id' => $criteria->id,
                'score' => 80 + $i,
            ]);
        }

        $this->actingAs($user);

        $response = $this->get(route('eventner.certificate.pdf', [
            'template_id' => $template->id,
            'champion_category_id' => $championCat->id,
            'competition_category_id' => $compCat->id,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
