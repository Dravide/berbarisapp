<?php

namespace Tests\Feature;

use App\Http\Controllers\Eventner\ChampionCategoryController;
use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ChampionPdfSectionsTest extends TestCase
{
    use RefreshDatabase;

    private function pdfData(?int $competitionCategoryId = null): array
    {
        $request = Request::create('/eventner/champion-categories/pdf');
        if ($competitionCategoryId) {
            $request->query->set('competition_category_id', $competitionCategoryId);
        }

        return app(ChampionCategoryController::class)->pdfData($request);
    }

    /**
     * Bangun satu tingkat lomba: rubrik -> sub -> kriteria -> kategori juara,
     * plus satu peserta dengan nilai 10.
     *
     * @return array{0: CompetitionCategory, 1: ChampionCategory}
     */
    private function makeLevel(Eventner $eventner, CompetitionCategory $parent, string $label, string $school): array
    {
        $cc = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => $parent->id,
            'name' => $label,
        ]);

        $ac = AssessmentCategory::create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $cc->id,
            'name' => "Rubrik $label",
            'sort_order' => 1,
        ]);
        $sub = AssessmentSubCategory::create(['assessment_category_id' => $ac->id, 'name' => "Sub $label", 'sort_order' => 1]);
        $crit = AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => "Kriteria $label",
            'score_options' => [['score' => 10]],
            'weight' => 1,
            'sort_order' => 1,
        ]);

        $champion = ChampionCategory::create(['eventner_id' => $eventner->id, 'name' => "Juara $label", 'quantity' => 3]);
        $champion->assessmentSubCategories()->sync([$sub->id]);

        $reg = Registration::factory()->for($eventner, 'eventner')->create([
            'competition_category_id' => $cc->id,
            'nama_sekolah' => $school,
        ]);
        AssessmentScore::create([
            'eventner_id' => $eventner->id,
            'registration_id' => $reg->id,
            'assessment_criteria_id' => $crit->id,
            'score' => 10,
        ]);

        return [$cc, $champion];
    }

    private function bootstrappedEventner(): Eventner
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        $eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'logo_event' => null,
        ]);
        $this->actingAs($user);

        return $eventner;
    }

    public function test_tanpa_filter_semua_kategori_juara_masuk_dikelompokkan_per_tingkat(): void
    {
        $eventner = $this->bootstrappedEventner();
        $parent = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id, 'name' => 'PBB']);

        [, $juaraA] = $this->makeLevel($eventner, $parent, 'SMP', 'SMP Alpha');
        [, $juaraB] = $this->makeLevel($eventner, $parent, 'SMA', 'SMA Beta');

        // Kategori juara tanpa rubrik -> kelompok global
        $umum = ChampionCategory::create(['eventner_id' => $eventner->id, 'name' => 'Juara Umum', 'quantity' => 3]);

        $data = $this->pdfData();

        $this->assertSame(3, $data['sections']->count(), 'Tiap tingkat + kelompok global jadi satu bagian.');

        $byLabel = $data['sections']->mapWithKeys(fn ($s) => [
            ($s['level']?->name ?? 'GLOBAL') => $s['champions']->pluck('name')->all(),
        ]);

        $this->assertSame(['Juara SMP'], $byLabel['SMP']);
        $this->assertSame(['Juara SMA'], $byLabel['SMA']);
        $this->assertSame(['Juara Umum'], $byLabel['GLOBAL']);

        // Yang penting: ranking juara SMP hanya berisi peserta tingkat SMP.
        $this->assertSame(
            ['SMP Alpha'],
            collect($data['rankings'][$juaraA->id])->pluck('participant.nama_sekolah')->all()
        );
        $this->assertSame(
            ['SMA Beta'],
            collect($data['rankings'][$juaraB->id])->pluck('participant.nama_sekolah')->all()
        );

        // Kategori juara tanpa rubrik: tidak ada peserta cocok -> kosong, bukan
        // semua peserta event.
        $this->assertSame([], $data['rankings'][$umum->id]);
    }

    public function test_dengan_filter_hanya_satu_bagian_dan_tanpa_judul_tingkat(): void
    {
        $eventner = $this->bootstrappedEventner();
        $parent = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id, 'name' => 'PBB']);

        [$catA, $juaraA] = $this->makeLevel($eventner, $parent, 'SMP', 'SMP Alpha');
        $this->makeLevel($eventner, $parent, 'SMA', 'SMA Beta');

        $data = $this->pdfData($catA->id);

        $this->assertSame(1, $data['sections']->count());
        $this->assertSame('SMP', $data['sections'][0]['level']->name);
        $this->assertNotNull($data['competitionCategory']);
        $this->assertCount(1, $data['rankings'][$juaraA->id]);
    }

    public function test_pdf_view_render_tiap_tingkat_dengan_judul_dan_data_benar(): void
    {
        $eventner = $this->bootstrappedEventner();
        $parent = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id, 'name' => 'PBB']);

        $this->makeLevel($eventner, $parent, 'SMP', 'SMP Alpha');
        $this->makeLevel($eventner, $parent, 'SMA', 'SMA Beta');
        ChampionCategory::create(['eventner_id' => $eventner->id, 'name' => 'Juara Umum', 'quantity' => 3]);

        $html = view('eventner.champion-category.pdf_ranking', $this->pdfData())->render();

        $this->assertSame(3, substr_count($html, 'class="level-head"'), 'Tiga bagian, tiga judul tingkat.');
        $this->assertStringContainsString('PBB — SMP', $html);
        $this->assertStringContainsString('PBB — SMA', $html);
        $this->assertStringContainsString('Semua Tingkat (Rubrik Global)', $html);
        $this->assertStringContainsString('SMP Alpha', $html);
        $this->assertStringContainsString('SMA Beta', $html);
        $this->assertStringContainsString('Belum ada nilai peserta pada rubrik global.', $html);
    }
}
