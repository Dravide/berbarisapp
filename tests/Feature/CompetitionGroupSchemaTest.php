<?php

namespace Tests\Feature;

use App\Models\AssessmentCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\CompetitionRoundRegistration;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Skema grup & babak: batasan unique, dan aturan hapus yang sengaja melepas
 * (nullOnDelete) alih-alih merambat menghapus peserta atau nilai.
 */
class CompetitionGroupSchemaTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $level;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->eventner()->create(['is_active' => true]);
        $this->eventner = Eventner::factory()->create(['user_id' => $user->id, 'status' => 'approved']);
        $this->level = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => CompetitionCategory::factory()->create(['eventner_id' => $this->eventner->id])->id,
        ]);
    }

    private function makeGroup(string $name): CompetitionGroup
    {
        return CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => $name,
        ]);
    }

    private function makeRound(string $name, string $type = CompetitionRound::TYPE_PRELIMINARY): CompetitionRound
    {
        return CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => $name,
            'type' => $type,
        ]);
    }

    public function test_nama_grup_unik_per_tingkat()
    {
        $this->makeGroup('Grup A');

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->makeGroup('Grup A');
    }

    public function test_nama_sama_boleh_di_tingkat_berbeda()
    {
        $this->makeGroup('Grup A');

        $lain = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->level->parent_id,
        ]);

        $group = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $lain->id,
            'name' => 'Grup A',
        ]);

        $this->assertNotNull($group->id);
    }

    public function test_nama_babak_unik_per_tingkat()
    {
        $this->makeRound('Final', CompetitionRound::TYPE_FINAL);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->makeRound('Final', CompetitionRound::TYPE_FINAL);
    }

    public function test_hapus_grup_melepas_peserta_tanpa_menghapusnya()
    {
        $group = $this->makeGroup('Grup A');

        $registration = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $group->id,
        ]);

        $group->delete();

        $registration->refresh();
        $this->assertNull($registration->competition_group_id);
        $this->assertDatabaseHas('registrations', ['id' => $registration->id]);
    }

    public function test_hapus_grup_melepas_rubrik_tanpa_menghapusnya()
    {
        $group = $this->makeGroup('Grup A');

        $ac = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $group->id,
            'name' => 'PBB Grup A',
            'sort_order' => 1,
        ]);

        $group->delete();

        $ac->refresh();
        $this->assertNull($ac->competition_group_id);
        $this->assertDatabaseHas('assessment_categories', ['id' => $ac->id]);
    }

    public function test_hapus_babak_melepas_rubrik_dan_finalis_tanpa_menghapusnya()
    {
        $round = $this->makeRound('Final', CompetitionRound::TYPE_FINAL);

        $registration = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
        ]);

        $ac = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'competition_round_id' => $round->id,
            'name' => 'PBB Final',
            'sort_order' => 1,
        ]);

        $pivot = CompetitionRoundRegistration::create([
            'eventner_id' => $this->eventner->id,
            'competition_round_id' => $round->id,
            'registration_id' => $registration->id,
            'seed' => 1,
            'preliminary_total' => 300,
        ]);

        $round->delete();

        $ac->refresh();
        $this->assertNull($ac->competition_round_id);
        $this->assertDatabaseHas('assessment_categories', ['id' => $ac->id]);
        $this->assertDatabaseHas('registrations', ['id' => $registration->id]);
        // Pivot ini memang milik babak — ikut terhapus bersama babaknya.
        $this->assertDatabaseMissing('competition_round_registrations', ['id' => $pivot->id]);
    }

    public function test_hapus_tingkat_merambat_ke_grup_dan_babak()
    {
        $group = $this->makeGroup('Grup A');
        $round = $this->makeRound('Final', CompetitionRound::TYPE_FINAL);

        $this->level->delete();

        $this->assertDatabaseMissing('competition_groups', ['id' => $group->id]);
        $this->assertDatabaseMissing('competition_rounds', ['id' => $round->id]);
    }

    public function test_finalis_unik_per_babak()
    {
        $round = $this->makeRound('Final', CompetitionRound::TYPE_FINAL);
        $registration = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
        ]);

        CompetitionRoundRegistration::create([
            'eventner_id' => $this->eventner->id,
            'competition_round_id' => $round->id,
            'registration_id' => $registration->id,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        CompetitionRoundRegistration::create([
            'eventner_id' => $this->eventner->id,
            'competition_round_id' => $round->id,
            'registration_id' => $registration->id,
        ]);
    }
}
