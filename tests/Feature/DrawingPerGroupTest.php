<?php

namespace Tests\Feature;

use App\Livewire\Eventner\Drawing\Index;
use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Undian per grup.
 *
 * Nomor undian bergeser maknanya saat satu tingkat dipecah: "peserta #1" harus
 * berarti "peserta #1 GRUP INI", kalau tidak Grup A #1 dan Grup B #1 bertabrakan
 * dan cek bentrok nomor tidak pernah menangkapnya.
 */
class DrawingPerGroupTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $level;

    private CompetitionGroup $groupA;

    private CompetitionGroup $groupB;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->eventner()->create(['is_active' => true]);
        $this->eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'drawing_code' => 'UNDI-GRUP',
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
    }

    private function makeParticipant(string $school, CompetitionGroup $group): Registration
    {
        return Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $group->id,
            'nama_sekolah' => $school,
        ]);
    }

    private function panel(string $groupId = '')
    {
        $component = Livewire::test(Index::class)->call('switchTab', $this->level->id);

        if ($groupId !== '') {
            $component->call('switchGroup', $groupId);
        }

        return $component;
    }

    public function test_nomor_yang_sama_boleh_dipakai_di_grup_berbeda()
    {
        $a1 = $this->makeParticipant('SMPN A1', $this->groupA);
        $b1 = $this->makeParticipant('SMPN B1', $this->groupB);

        $this->panel((string) $this->groupA->id)
            ->set('manualRegistrationId', $a1->id)
            ->set('manualUrutan', 1)
            ->call('assignManual')
            ->assertHasNoErrors();

        // Nomor 1 sudah dipakai Grup A — di Grup B harus tetap boleh.
        $this->panel((string) $this->groupB->id)
            ->set('manualRegistrationId', $b1->id)
            ->set('manualUrutan', 1)
            ->call('assignManual')
            ->assertHasNoErrors();

        $this->assertSame(1, (int) $a1->refresh()->urutan_tampil);
        $this->assertSame(1, (int) $b1->refresh()->urutan_tampil);
    }

    public function test_nomor_yang_sama_ditolak_di_dalam_satu_grup()
    {
        $a1 = $this->makeParticipant('SMPN A1', $this->groupA);
        $a2 = $this->makeParticipant('SMPN A2', $this->groupA);

        $this->panel((string) $this->groupA->id)
            ->set('manualRegistrationId', $a1->id)
            ->set('manualUrutan', 1)
            ->call('assignManual');

        $this->panel((string) $this->groupA->id)
            ->set('manualRegistrationId', $a2->id)
            ->set('manualUrutan', 1)
            ->call('assignManual')
            ->assertHasErrors('manualUrutan');

        $this->assertNull($a2->refresh()->urutan_tampil);
    }

    public function test_peserta_grup_lain_tidak_bisa_diundi_dari_grup_ini()
    {
        $b1 = $this->makeParticipant('SMPN B1', $this->groupB);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        try {
            $this->panel((string) $this->groupA->id)
                ->set('manualRegistrationId', $b1->id)
                ->set('manualUrutan', 1)
                ->call('assignManual');
        } finally {
            $this->assertNull($b1->refresh()->urutan_tampil);
        }
    }

    public function test_ganti_tingkat_melepas_grup_lama()
    {
        $lain = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->level->parent_id,
        ]);

        $component = $this->panel((string) $this->groupA->id)
            ->assertSet('activeGroupId', (string) $this->groupA->id);

        $component->call('switchTab', $lain->id)
            ->assertSet('activeGroupId', '');
    }

    public function test_grup_milik_tingkat_lain_ditolak()
    {
        $lain = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->level->parent_id,
        ]);
        $grupLain = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $lain->id,
            'name' => 'Grup Lain',
        ]);

        $this->panel()
            ->call('switchGroup', $grupLain->id)
            ->assertSet('activeGroupId', '')
            ->assertHasErrors('activeGroupId');
    }

    public function test_reset_undian_tetap_menolak_saat_sudah_ada_nilai()
    {
        $a1 = $this->makeParticipant('SMPN A1', $this->groupA);
        $a1->update(['urutan_tampil' => 1]);

        $cat = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'PBB',
            'sort_order' => 1,
        ]);
        $sub = AssessmentSubCategory::create(['assessment_category_id' => $cat->id, 'name' => 'Sub', 'sort_order' => 1]);
        $kriteria = AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Kriteria',
            'score_options' => [['score' => 10]],
            'sort_order' => 1,
        ]);
        $juri = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri']);

        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $a1->id,
            'assessment_criteria_id' => $kriteria->id,
            'judge_id' => $juri->id,
            'score' => 10,
        ]);

        $this->panel((string) $this->groupA->id)->call('resetDrawing');

        $this->assertSame(1, (int) $a1->refresh()->urutan_tampil, 'Undian ter-reset padahal sudah ada nilai.');
    }

    /**
     * Reset sengaja di level TINGKAT: nilai di satu grup sudah cukup membuat
     * undian tingkat ini tak lagi cocok di grup mana pun.
     */
    public function test_reset_undian_menghapus_nomor_seluruh_grup_bila_belum_ada_nilai()
    {
        $a1 = $this->makeParticipant('SMPN A1', $this->groupA);
        $b1 = $this->makeParticipant('SMPN B1', $this->groupB);
        $a1->update(['urutan_tampil' => 1]);
        $b1->update(['urutan_tampil' => 1]);

        $this->panel((string) $this->groupA->id)->call('resetDrawing');

        $this->assertNull($a1->refresh()->urutan_tampil);
        $this->assertNull($b1->refresh()->urutan_tampil);
    }
}
