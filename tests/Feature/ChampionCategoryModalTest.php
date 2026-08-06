<?php

namespace Tests\Feature;

use App\Livewire\Eventner\ChampionCategory\Index;
use App\Models\AssessmentCategory;
use App\Models\AssessmentSubCategory;
use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChampionCategoryModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_modal_pre_checks_selected_sub_categories()
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        $eventner = Eventner::factory()->create(['user_id' => $user->id, 'status' => 'approved']);
        $level = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id]);

        $ac = AssessmentCategory::create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $level->id,
            'name' => 'Kekompakan',
            'sort_order' => 1,
        ]);
        $subA = AssessmentSubCategory::create(['assessment_category_id' => $ac->id, 'name' => 'Gerakan Dasar', 'sort_order' => 1]);
        $subB = AssessmentSubCategory::create(['assessment_category_id' => $ac->id, 'name' => 'Kedisiplinan', 'sort_order' => 2]);

        $champion = ChampionCategory::create([
            'eventner_id' => $eventner->id,
            'name' => 'Juara Umum',
            'quantity' => 3,
        ]);
        $champion->assessmentSubCategories()->sync([$subA->id, $subB->id]);
        $champion->tiebreakSubCategories()->sync([$subA->id]);

        $resp = Livewire::actingAs($user)->test(Index::class)->call('edit', $champion->id);

        $html = $resp->html();

        $checkedIn = fn (string $html, string $inputId): bool => (bool) preg_match(
            '/id="'.preg_quote($inputId, '/').'"(?:\s+checked|\s*)\s*checked/i',
            $html
        );

        $this->assertTrue($checkedIn($html, 'asc_'.$subA->id), 'asc sub A harus checked');
        $this->assertTrue($checkedIn($html, 'asc_'.$subB->id), 'asc sub B harus checked');
        $this->assertTrue($checkedIn($html, 'tb_asc_'.$subA->id), 'tiebreak sub A harus checked');
    }

    public function test_edit_modal_groups_rubrik_by_level()
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        $eventner = Eventner::factory()->create(['user_id' => $user->id, 'status' => 'approved']);
        $level1 = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id, 'name' => 'PBB Putra']);
        $level2 = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id, 'name' => 'PBB Putri']);

        // Dua kategori nama sama, level beda (simulasi duplikat per tingkat)
        $ac1 = AssessmentCategory::create(['eventner_id' => $eventner->id, 'competition_category_id' => $level1->id, 'name' => 'PBB', 'sort_order' => 1]);
        $ac2 = AssessmentCategory::create(['eventner_id' => $eventner->id, 'competition_category_id' => $level2->id, 'name' => 'PBB', 'sort_order' => 2]);
        AssessmentSubCategory::create(['assessment_category_id' => $ac1->id, 'name' => 'Gerakan Ditempat', 'sort_order' => 1]);
        AssessmentSubCategory::create(['assessment_category_id' => $ac2->id, 'name' => 'Gerakan Berjalan', 'sort_order' => 1]);

        $resp = Livewire::actingAs($user)->test(Index::class)->call('create');

        $html = $resp->html();
        // Rubrik difilter ke tingkat yang dipilih: hanya rubrik level1 yang tampil.
        $this->assertStringContainsString('ti-layers-subtract', $html);
        $this->assertStringContainsString('PBB Putra', $html);
        $this->assertStringNotContainsString('PBB Putri', $html);

        // Ganti filter ke level2 → rubrik level2 tampil, level1 hilang.
        $resp2 = Livewire::actingAs($user)->test(Index::class)
            ->set('selectedCompetitionCategoryId', (string) $level2->id)
            ->call('create');
        $html2 = $resp2->html();
        $this->assertStringContainsString('PBB Putri', $html2);
        $this->assertStringNotContainsString('PBB Putra', $html2);
    }
}
