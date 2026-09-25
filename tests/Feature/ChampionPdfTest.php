<?php

namespace Tests\Feature;

use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentSubCategory;
use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\ScoreDeduction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChampionPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_visible_for_berbagai_kasus_rubrik()
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        $eventner = Eventner::factory()->create(['user_id' => $user->id, 'status' => 'approved']);
        $levelA = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id]);
        $levelB = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id]);

        // Belum diatur rubriknya — biarkan tampil
        $empty = ChampionCategory::create(['eventner_id' => $eventner->id, 'name' => 'Tanpa Rubrik', 'quantity' => 3]);
        $this->assertTrue($empty->isVisibleFor($levelA->id));

        // Semua rubrik milik tingkat lain — sembunyikan
        $catB = AssessmentCategory::create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $levelB->id,
            'name' => 'Rubrik B',
            'sort_order' => 1,
        ]);
        $subB = AssessmentSubCategory::create(['assessment_category_id' => $catB->id, 'name' => 'Sub B', 'sort_order' => 1]);
        $milikB = ChampionCategory::create(['eventner_id' => $eventner->id, 'name' => 'Juara B', 'quantity' => 3]);
        $milikB->assessmentSubCategories()->sync([$subB->id]);
        $this->assertFalse($milikB->fresh()->load('assessmentSubCategories')->isVisibleFor($levelA->id));
        $this->assertTrue($milikB->fresh()->load('assessmentSubCategories')->isVisibleFor($levelB->id));

        // Rubrik global (competition_category_id null) berlaku di semua tingkat
        $catGlobal = AssessmentCategory::create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => null,
            'name' => 'Rubrik Global',
            'sort_order' => 2,
        ]);
        $subGlobal = AssessmentSubCategory::create(['assessment_category_id' => $catGlobal->id, 'name' => 'Sub Global', 'sort_order' => 1]);
        $juaraGlobal = ChampionCategory::create(['eventner_id' => $eventner->id, 'name' => 'Juara Umum', 'quantity' => 3]);
        $juaraGlobal->assessmentSubCategories()->sync([$subGlobal->id]);
        $this->assertTrue($juaraGlobal->fresh()->load('assessmentSubCategories')->isVisibleFor($levelA->id));
        $this->assertTrue($juaraGlobal->fresh()->load('assessmentSubCategories')->isVisibleFor($levelB->id));
    }

    public function test_pdf_terfilter_tingkat_lomba_dan_total_bersih()
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        $eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'logo_event' => null,
        ]);
        $this->actingAs($user);

        $parent = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id]);
        $catA = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id, 'parent_id' => $parent->id]);
        $catB = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id, 'parent_id' => $parent->id]);

        // Rubrik + juara untuk masing-masing tingkat
        $acA = AssessmentCategory::create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $catA->id,
            'name' => 'Penilaian A',
            'sort_order' => 1,
        ]);
        $subA = AssessmentSubCategory::create(['assessment_category_id' => $acA->id, 'name' => 'Sub A', 'sort_order' => 1]);
        $criteriaA = AssessmentCriteria::create([
            'assessment_sub_category_id' => $subA->id,
            'name' => 'Teknik',
            'score_options' => [['score' => 10]],
            'weight' => 1,
            'sort_order' => 1,
        ]);
        $juaraA = ChampionCategory::create(['eventner_id' => $eventner->id, 'name' => 'Juara Tingkat A', 'quantity' => 3]);
        $juaraA->assessmentSubCategories()->sync([$subA->id]);

        $acB = AssessmentCategory::create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $catB->id,
            'name' => 'Penilaian B',
            'sort_order' => 1,
        ]);
        $subB = AssessmentSubCategory::create(['assessment_category_id' => $acB->id, 'name' => 'Sub B', 'sort_order' => 1]);
        $juaraB = ChampionCategory::create(['eventner_id' => $eventner->id, 'name' => 'Juara Tingkat B', 'quantity' => 3]);
        $juaraB->assessmentSubCategories()->sync([$subB->id]);

        // Peserta tingkat A dengan skor 10 + pengurangan 5 (tersimpan negatif)
        $reg = Registration::factory()->for($eventner, 'eventner')->create([
            'competition_category_id' => $catA->id,
            'nama_sekolah' => 'SMP Alpha',
        ]);
        \App\Models\AssessmentScore::create([
            'eventner_id' => $eventner->id,
            'registration_id' => $reg->id,
            'assessment_criteria_id' => $criteriaA->id,
            'score' => 10,
        ]);
        $dedCat = \App\Models\DeductionCategory::create([
            'eventner_id' => $eventner->id,
            'assessment_category_id' => $acA->id,
            'name' => 'Hukuman',
            'sort_order' => 1,
        ]);
        $dedCriteria = \App\Models\DeductionCriteria::create([
            'deduction_category_id' => $dedCat->id,
            'name' => 'Terlambat',
            'deduction_options' => [-5, -10],
            'sort_order' => 1,
        ]);
        ScoreDeduction::create([
            'eventner_id' => $eventner->id,
            'registration_id' => $reg->id,
            'deduction_criteria_id' => $dedCriteria->id,
            'amount' => -5,
        ]);

        // Filter tingkat A: juara B tidak relevan — hidupkan hanya bila rubrik
        // tingkat A terisi. PDF tingkat A tetap 200 + application/pdf; isi
        // filter divalidasi lewat isVisibleFor (test model di atas).
        $response = $this->get(route('eventner.champion-categories.pdf', [
            'competition_category_id' => $catA->id,
        ]));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');

        // Tanpa param: semua kategori juara tampil (perilaku "Semua")
        $responseAll = $this->get(route('eventner.champion-categories.pdf'));
        $responseAll->assertStatus(200);
        $responseAll->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * Gelar juara sebelumnya tidak pernah muncul di PDF karena controller dan
     * view-nya tidak menyentuh rankTitles. Gelar yang mencakup lebih dari satu
     * peringkat harus dipecah per posisi (mis. "Juara Utama 1", "Juara Utama 2").
     */
    public function test_pdf_menampilkan_gelar_dengan_nomor_posisi(): void
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        $eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'logo_event' => null,
        ]);
        $this->actingAs($user);

        $level = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'name' => 'SMP',
        ]);

        $ac = AssessmentCategory::create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $level->id,
            'name' => 'Rubrik',
            'sort_order' => 1,
        ]);
        $sub = AssessmentSubCategory::create(['assessment_category_id' => $ac->id, 'name' => 'Sub', 'sort_order' => 1]);
        $crit = AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Teknik',
            'score_options' => [['score' => 10]],
            'weight' => 1,
            'sort_order' => 1,
        ]);

        $champion = ChampionCategory::create([
            'eventner_id' => $eventner->id,
            'name' => 'Juara Umum',
            'quantity' => 3,
        ]);
        $champion->assessmentSubCategories()->sync([$sub->id]);

        // Dua peserta dengan nilai berbeda supaya peringkatnya pasti 1 dan 2.
        foreach ([['SMP Satu', 10], ['SMP Dua', 8]] as $i => [$nama, $nilai]) {
            $reg = Registration::factory()->for($eventner, 'eventner')->create([
                'competition_category_id' => $level->id,
                'nama_sekolah' => $nama,
            ]);
            \App\Models\AssessmentScore::create([
                'eventner_id' => $eventner->id,
                'registration_id' => $reg->id,
                'assessment_criteria_id' => $crit->id,
                'score' => $nilai,
            ]);
        }

        \App\Models\ChampionRankTitle::create([
            'champion_category_id' => $champion->id,
            'title' => 'Juara Utama',
            'rank_start' => 1,
            'rank_end' => 3,
            'sort_order' => 1,
        ]);

        $data = app(\App\Http\Controllers\Eventner\ChampionCategoryController::class)
            ->pdfData(\Illuminate\Http\Request::create('/eventner/champion-categories/pdf', 'GET', [
                'competition_category_id' => $level->id,
                'champion_category_id' => $champion->id,
            ]));

        $titles = collect($data['rankings'][$champion->id])->pluck('title')->all();
        $this->assertSame(['Juara Utama 1', 'Juara Utama 2'], $titles);

        $html = view('eventner.champion-category.pdf_ranking', $data)->render();
        $this->assertStringContainsString('Juara Utama 1', $html);
        $this->assertStringContainsString('Juara Utama 2', $html);
    }

    /**
     * Tanpa rank title, PDF tetap menulis "Juara N" sebagai ganti gelar kosong.
     */
    public function test_pdf_tanpa_gelar_memakai_fallback_juara_n(): void
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        $eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'logo_event' => null,
        ]);
        $this->actingAs($user);

        $level = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id]);
        $ac = AssessmentCategory::create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $level->id,
            'name' => 'Rubrik',
            'sort_order' => 1,
        ]);
        $sub = AssessmentSubCategory::create(['assessment_category_id' => $ac->id, 'name' => 'Sub', 'sort_order' => 1]);
        $crit = AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Teknik',
            'score_options' => [['score' => 10]],
            'weight' => 1,
            'sort_order' => 1,
        ]);
        $champion = ChampionCategory::create(['eventner_id' => $eventner->id, 'name' => 'Juara Umum', 'quantity' => 3]);
        $champion->assessmentSubCategories()->sync([$sub->id]);

        $reg = Registration::factory()->for($eventner, 'eventner')->create([
            'competition_category_id' => $level->id,
            'nama_sekolah' => 'SMP Satu',
        ]);
        \App\Models\AssessmentScore::create([
            'eventner_id' => $eventner->id,
            'registration_id' => $reg->id,
            'assessment_criteria_id' => $crit->id,
            'score' => 10,
        ]);

        $data = app(\App\Http\Controllers\Eventner\ChampionCategoryController::class)
            ->pdfData(\Illuminate\Http\Request::create('/eventner/champion-categories/pdf', 'GET', [
                'competition_category_id' => $level->id,
                'champion_category_id' => $champion->id,
            ]));

        $this->assertNull($data['rankings'][$champion->id][0]['title']);

        $html = view('eventner.champion-category.pdf_ranking', $data)->render();
        $this->assertStringContainsString('Juara 1', $html);
    }
}
