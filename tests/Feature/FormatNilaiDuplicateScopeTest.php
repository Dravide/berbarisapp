<?php

namespace Tests\Feature;

use App\Livewire\Eventner\FormatNilai\Builder;
use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentSubCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\CompetitionSeries;
use App\Models\DeductionCategory;
use App\Models\DeductionCriteria;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Duplikat rubrik untuk babak lain yang formatnya sama.
 *
 * Kasus nyata: penyisihan dan final dinilai dengan kriteria identik. Karena
 * UNIQUE (registration_id, assessment_criteria_id, judge_id) hanya menampung
 * satu nilai per juri per baris kriteria, format yang sama tetap wajib jadi
 * rubrik TERPISAH — kalau tidak, nilai final menimpa nilai penyisihan dan
 * angka penentu siapa yang lolos ikut hilang.
 *
 * Yang dijaga di sini adalah dua jebakan jalur duplikat:
 *  - nama salinan tidak lagi "(Salinan)" yang harus di-rename manual;
 *  - seri/babak TIDAK diwarisi, karena salinan rubrik Seri A yang lahir
 *    bertanda Seri A diam-diam akan terkunci ke seri yang salah.
 */
class FormatNilaiDuplicateScopeTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $level;

    private CompetitionGroup $groupA;

    private CompetitionSeries $seriA;

    private CompetitionRound $penyisihan;

    private AssessmentCategory $sumber;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->eventner()->create(['is_active' => true]);
        $this->eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);
        $this->actingAs($user);

        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
        ]);
        $this->level = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
        ]);

        $this->groupA = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup A',
            'sort_order' => 1,
        ]);

        $this->seriA = CompetitionSeries::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Seri A',
            'sort_order' => 1,
        ]);

        $this->penyisihan = CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Penyisihan',
            'type' => CompetitionRound::TYPE_PRELIMINARY,
            'sort_order' => 1,
        ]);

        // Rubrik sumber: menempel di Seri A DAN babak Penyisihan.
        $this->sumber = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $this->groupA->id,
            'competition_series_id' => $this->seriA->id,
            'competition_round_id' => $this->penyisihan->id,
            'name' => 'PBB Penyisihan',
            'sort_order' => 1,
        ]);

        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $this->sumber->id,
            'name' => 'Gerakan Ditempat',
            'sort_order' => 1,
        ]);
        AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Sikap Sempurna',
            'score_options' => [['score' => 10], ['score' => 20]],
            'weight' => 3,
            'sort_order' => 1,
        ]);

        $dedCat = DeductionCategory::create([
            'eventner_id' => $this->eventner->id,
            'assessment_category_id' => $this->sumber->id,
            'name' => 'Pelanggaran',
            'sort_order' => 1,
        ]);
        DeductionCriteria::create([
            'deduction_category_id' => $dedCat->id,
            'name' => 'Keluar barisan',
            // Bentuk nyatanya array skalar, bukan array of array — blade
            // merender tiap entrinya langsung sebagai badge ({{ $option }}).
            'deduction_options' => ['-5', '-10'],
            'sort_order' => 1,
        ]);

        $juri = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Budi']);
        $this->sumber->judges()->attach($juri->id);
    }

    private function builder()
    {
        return Livewire::test(Builder::class);
    }

    public function test_duplikat_meminta_nama_lebih_dulu_dan_memakainya()
    {
        $this->builder()
            ->call('startDuplicateCategory', $this->sumber->id)
            ->assertSet('duplicateCategoryName', 'PBB Penyisihan (Salinan)')
            ->set('duplicateCategoryName', 'PBB Final')
            ->call('confirmDuplicateCategory');

        $this->assertDatabaseHas('assessment_categories', [
            'eventner_id' => $this->eventner->id,
            'name' => 'PBB Final',
        ]);
        $this->assertDatabaseMissing('assessment_categories', [
            'eventner_id' => $this->eventner->id,
            'name' => 'PBB Penyisihan (Salinan)',
        ]);
    }

    public function test_salinan_tidak_mewarisi_seri_dan_babak()
    {
        $this->builder()
            ->call('startDuplicateCategory', $this->sumber->id)
            ->set('duplicateCategoryName', 'PBB Final')
            ->call('confirmDuplicateCategory');

        $salinan = AssessmentCategory::where('eventner_id', $this->eventner->id)
            ->where('name', 'PBB Final')
            ->firstOrFail();

        $this->assertNull($salinan->competition_series_id, 'Salinan mewarisi seri sumber.');
        $this->assertNull($salinan->competition_round_id, 'Salinan mewarisi babak sumber.');
        // Tingkatnya tetap ikut — salinan tanpa tingkat tak akan muncul di mana pun.
        $this->assertSame($this->level->id, (int) $salinan->competition_category_id);
    }

    public function test_salinan_membawa_kriteria_bobot_dan_juri()
    {
        $this->builder()
            ->call('startDuplicateCategory', $this->sumber->id)
            ->set('duplicateCategoryName', 'PBB Final')
            ->call('confirmDuplicateCategory');

        $salinan = AssessmentCategory::where('eventner_id', $this->eventner->id)
            ->where('name', 'PBB Final')
            ->firstOrFail();

        $sub = $salinan->subCategories()->first();
        $this->assertNotNull($sub);
        $this->assertSame('Gerakan Ditempat', $sub->name);

        $kriteria = $sub->criterias()->first();
        $this->assertNotNull($kriteria);
        $this->assertSame('Sikap Sempurna', $kriteria->name);
        $this->assertSame(3, (int) $kriteria->weight);
        $this->assertSame([['score' => 10], ['score' => 20]], $kriteria->score_options);

        // Juri ikut tersalin, supaya salinan tidak langsung jadi rubrik yatim.
        $this->assertSame(
            $this->sumber->judges()->pluck('judges.id')->sort()->values()->all(),
            $salinan->judges()->pluck('judges.id')->sort()->values()->all()
        );

        $this->assertSame(1, $salinan->deductionCategories()->count());
    }

    /** Batal = tidak ada baris baru, state-nya bersih. */
    public function test_batal_duplikat_tidak_membuat_apa_pun()
    {
        $this->builder()
            ->call('startDuplicateCategory', $this->sumber->id)
            ->call('cancelDuplicateCategory')
            ->assertSet('duplicatingCategoryId', null)
            ->assertSet('duplicateCategoryName', '');

        $this->assertSame(1, AssessmentCategory::where('eventner_id', $this->eventner->id)->count());
    }

    public function test_nama_kosong_ditolak()
    {
        $this->builder()
            ->call('startDuplicateCategory', $this->sumber->id)
            ->set('duplicateCategoryName', '')
            ->call('confirmDuplicateCategory')
            ->assertHasErrors('duplicateCategoryName');

        $this->assertSame(1, AssessmentCategory::where('eventner_id', $this->eventner->id)->count());
    }

    /**
     * Jalur lama `duplicateCategory()` sudah tidak ada. Kalau ia hidup
     * kembali, hasil salinannya mewarisi grup/babak lagi dan tes ini gagal —
     * itu yang menjaga agar jebakan tadi tidak balik diam-diam.
     */
    public function test_jalur_duplikat_lama_sudah_tidak_ada()
    {
        $this->assertFalse(
            method_exists(Builder::class, 'duplicateCategory'),
            'duplicateCategory() lama hidup lagi — salinannya akan mewarisi grup/babak.'
        );
    }

    public function test_duplikat_rubrik_event_lain_ditolak()
    {
        $lain = Eventner::factory()->create(['status' => 'approved']);
        $parentLain = CompetitionCategory::factory()->create(['eventner_id' => $lain->id, 'parent_id' => null]);
        $levelLain = CompetitionCategory::factory()->create([
            'eventner_id' => $lain->id,
            'parent_id' => $parentLain->id,
        ]);
        $rubrikLain = AssessmentCategory::create([
            'eventner_id' => $lain->id,
            'competition_category_id' => $levelLain->id,
            'name' => 'Rubrik Lain',
            'sort_order' => 1,
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->builder()->call('startDuplicateCategory', $rubrikLain->id);
    }
}
