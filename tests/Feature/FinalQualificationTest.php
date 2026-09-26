<?php

namespace Tests\Feature;

use App\Livewire\Eventner\CompetitionCategory\Index;
use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\CompetitionRoundRegistration;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Loloskan Top-N tiap grup ke babak final.
 *
 * Usulan otomatis harus memakai angka yang sama dengan penentu juara
 * (ChampionCalculator::rankOrdered), tetapi keputusan akhir tetap milik
 * panitia: centang manualnya yang disimpan, bukan hitungan ulang.
 */
class FinalQualificationTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $level;

    private CompetitionGroup $groupA;

    private CompetitionGroup $groupB;

    private CompetitionRound $penyisihan;

    private CompetitionRound $final;

    private AssessmentCriteria $kriteria;

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
        $this->groupB = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup B',
            'sort_order' => 2,
        ]);

        $this->penyisihan = CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Penyisihan',
            'type' => CompetitionRound::TYPE_PRELIMINARY,
            'sort_order' => 1,
        ]);
        $this->final = CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Final',
            'type' => CompetitionRound::TYPE_FINAL,
            'sort_order' => 2,
        ]);

        $this->kriteria = $this->makeRubrik($this->penyisihan);
    }

    private function makeRubrik(CompetitionRound $round): AssessmentCriteria
    {
        $category = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'competition_round_id' => $round->id,
            'name' => 'PBB ' . $round->name,
            'sort_order' => 1,
        ]);
        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $category->id,
            'name' => 'Sub',
            'sort_order' => 1,
        ]);

        return AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Kriteria',
            'score_options' => [['score' => 10], ['score' => 20]],
            'weight' => 1,
            'sort_order' => 1,
        ]);
    }

    private function makeParticipant(string $school, CompetitionGroup $group, int $score): Registration
    {
        $reg = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $group->id,
            'nama_sekolah' => $school,
        ]);

        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $reg->id,
            'assessment_criteria_id' => $this->kriteria->id,
            'judge_id' => Judge::create([
                'eventner_id' => $this->eventner->id,
                'name' => 'Juri ' . $school,
            ])->id,
            'score' => $score,
        ]);

        return $reg;
    }

    private function panel()
    {
        return Livewire::test(Index::class)->call('openQualifyPanel', $this->final->id);
    }

    public function test_usulan_mengambil_top_n_per_grup()
    {
        $a1 = $this->makeParticipant('SMPN A1', $this->groupA, 20);
        $a2 = $this->makeParticipant('SMPN A2', $this->groupA, 10);
        $a3 = $this->makeParticipant('SMPN A3', $this->groupA, 5);
        $b1 = $this->makeParticipant('SMPN B1', $this->groupB, 15);

        $component = $this->panel()->set('qualifyTopN', 2);

        $selection = $component->get('qualifySelection');

        $this->assertTrue($selection[(string) $a1->id], 'Peringkat 1 Grup A tidak terpilih.');
        $this->assertTrue($selection[(string) $a2->id], 'Peringkat 2 Grup A tidak terpilih.');
        $this->assertFalse($selection[(string) $a3->id] ?? false, 'Peringkat 3 Grup A ikut terpilih.');
        // Grup B hanya punya 1 peserta bernilai — satu-satunya terpilih.
        $this->assertTrue($selection[(string) $b1->id], 'Peserta Grup B tidak terpilih.');
    }

    public function test_menyimpan_mencatat_seed_dan_total_penyisihan()
    {
        $a1 = $this->makeParticipant('SMPN A1', $this->groupA, 20);

        $this->panel()->set('qualifyTopN', 1)->call('saveQualify');

        $pivot = CompetitionRoundRegistration::where('competition_round_id', $this->final->id)
            ->where('registration_id', $a1->id)
            ->first();

        $this->assertNotNull($pivot);
        $this->assertSame(1, (int) $pivot->seed);
        $this->assertSame('20.00', (string) $pivot->preliminary_total);
        $this->assertSame($this->groupA->id, (int) $pivot->competition_group_id);
    }

    public function test_menyimpan_dua_kali_tidak_menggandakan_finalis()
    {
        $a1 = $this->makeParticipant('SMPN A1', $this->groupA, 20);
        $b1 = $this->makeParticipant('SMPN B1', $this->groupB, 15);

        $this->panel()->set('qualifyTopN', 1)->call('saveQualify');
        $this->panel()->set('qualifyTopN', 1)->call('saveQualify');

        $this->assertSame(2, CompetitionRoundRegistration::where('competition_round_id', $this->final->id)->count());
        $this->assertDatabaseHas('competition_round_registrations', [
            'competition_round_id' => $this->final->id,
            'registration_id' => $a1->id,
        ]);
        $this->assertDatabaseHas('competition_round_registrations', [
            'competition_round_id' => $this->final->id,
            'registration_id' => $b1->id,
        ]);
    }

    public function test_koreksi_manual_menang_atas_usulan_otomatis()
    {
        $a1 = $this->makeParticipant('SMPN A1', $this->groupA, 20);
        $a2 = $this->makeParticipant('SMPN A2', $this->groupA, 10);

        // Usulan otomatis memilih A1 saja…
        $this->panel()->set('qualifyTopN', 1)->call('saveQualify');
        $this->assertDatabaseHas('competition_round_registrations', [
            'competition_round_id' => $this->final->id,
            'registration_id' => $a1->id,
        ]);

        // …panitia lalu menukarnya dengan A2.
        $this->panel()
            ->set('qualifyTopN', 1)
            ->call('toggleQualifySelection', $a1->id)
            ->call('toggleQualifySelection', $a2->id)
            ->call('saveQualify');

        $this->assertDatabaseMissing('competition_round_registrations', [
            'competition_round_id' => $this->final->id,
            'registration_id' => $a1->id,
        ]);
        $this->assertDatabaseHas('competition_round_registrations', [
            'competition_round_id' => $this->final->id,
            'registration_id' => $a2->id,
        ]);
    }

    public function test_id_peserta_yang_tidak_ada_di_pratinjau_diabaikan()
    {
        $this->makeParticipant('SMPN A1', $this->groupA, 20);
        $lain = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $this->groupB->id,
            'nama_sekolah' => 'SMPN Lain',
        ]);

        $this->panel()
            ->set('qualifyTopN', 1)
            ->set('qualifySelection.' . $lain->id, true)
            ->call('saveQualify');

        $this->assertDatabaseMissing('competition_round_registrations', [
            'competition_round_id' => $this->final->id,
            'registration_id' => $lain->id,
        ]);
    }

    public function test_peserta_tanpa_nilai_tidak_diusulkan()
    {
        $a1 = $this->makeParticipant('SMPN A1', $this->groupA, 20);
        $nol = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $this->groupA->id,
            'nama_sekolah' => 'SMPN Nol',
        ]);

        $selection = $this->panel()->set('qualifyTopN', 3)->get('qualifySelection');

        $this->assertTrue($selection[(string) $a1->id]);
        $this->assertArrayNotHasKey((string) $nol->id, $selection, 'Peserta tanpa nilai ikut diusulkan.');
    }

    /**
     * Idempoten benare-benar di tingkat database, bukan cuma lewat jalur UI:
     * UNIQUE (round, registration) menolak duplikat.
     */
    public function test_unique_index_menolak_finalis_ganda()
    {
        $a1 = $this->makeParticipant('SMPN A1', $this->groupA, 20);

        CompetitionRoundRegistration::create([
            'eventner_id' => $this->eventner->id,
            'competition_round_id' => $this->final->id,
            'registration_id' => $a1->id,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        CompetitionRoundRegistration::create([
            'eventner_id' => $this->eventner->id,
            'competition_round_id' => $this->final->id,
            'registration_id' => $a1->id,
        ]);
    }

    public function test_babak_milik_event_lain_ditolak()
    {
        $lain = Eventner::factory()->create(['status' => 'approved']);
        $parentLain = CompetitionCategory::factory()->create(['eventner_id' => $lain->id, 'parent_id' => null]);
        $babakLain = CompetitionRound::create([
            'eventner_id' => $lain->id,
            'competition_category_id' => CompetitionCategory::factory()->create([
                'eventner_id' => $lain->id,
                'parent_id' => $parentLain->id,
            ])->id,
            'name' => 'Final',
            'type' => CompetitionRound::TYPE_FINAL,
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Livewire::test(Index::class)->call('openQualifyPanel', $babakLain->id);
    }
}
