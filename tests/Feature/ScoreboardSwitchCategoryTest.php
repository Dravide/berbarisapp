<?php

namespace Tests\Feature;

use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScoreboardSwitchCategoryTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;
    private CompetitionCategory $catA;
    private CompetitionCategory $catB;
    private AssessmentCriteria $criteria;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventner = Eventner::factory()->create([
            'status' => 'approved',
            'scoring_code' => 'SC-SWITCH',
        ]);

        $parent = CompetitionCategory::factory()->for($this->eventner, 'eventner')->create();
        $this->catA = CompetitionCategory::factory()->child($parent)->for($this->eventner, 'eventner')->create(['name' => 'Kategori A']);
        $this->catB = CompetitionCategory::factory()->child($parent)->for($this->eventner, 'eventner')->create(['name' => 'Kategori B']);

        // Format nilai: 1 kategori penilaian → 1 sub → 1 kriteria bobot 1
        $assessmentCategory = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->catA->id,
            'name' => 'Penilaian Umum',
            'sort_order' => 1,
        ]);
        $subCategory = AssessmentSubCategory::create([
            'assessment_category_id' => $assessmentCategory->id,
            'name' => 'Sub Umum',
            'sort_order' => 1,
        ]);
        $this->criteria = AssessmentCriteria::create([
            'assessment_sub_category_id' => $subCategory->id,
            'name' => 'Teknik',
            'score_options' => [['score' => 10], ['score' => 20]],
            'weight' => 1,
            'sort_order' => 1,
        ]);
    }

    public function test_ganti_kategori_dropdown_data_ranking_berubah()
    {
        $regA = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->catA->id,
            'nama_sekolah' => 'SMP Alpha',
        ]);
        $regB = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->catB->id,
            'nama_sekolah' => 'SMP Beta',
        ]);

        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $regA->id,
            'assessment_criteria_id' => $this->criteria->id,
            'score' => 20,
        ]);

        $component = Livewire::test(\App\Livewire\Public\Scoreboard\Index::class, [
            'scoringCode' => 'SC-SWITCH',
        ]);

        // Awal: kategori pertama (A, urut nama) → SMP Alpha tampil
        $component->assertSee('SMP Alpha');

        // Ganti dropdown ke kategori B
        $component->set('selectedOption', 'cat:' . $this->catB->id);

        // Data ranking harus ikut berubah → SMP Beta muncul, Alpha hilang
        $component->assertSee('SMP Beta');
        $component->assertDontSee('SMP Alpha');
    }
}
