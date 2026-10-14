<?php

namespace Tests\Feature;

use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionRound;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\ScoreDeduction;
use App\Services\ScoreRecapBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScoringDeductionManualTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;
    private CompetitionCategory $lomba;
    private AssessmentCategory $catPbb;
    private AssessmentCategory $catDanton;
    private Judge $juri;
    private Registration $reg;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventner = Eventner::factory()->create([
            'status' => 'approved',
            'scoring_code' => 'SC-MANUAL-DED',
        ]);

        $parent = CompetitionCategory::factory()->for($this->eventner, 'eventner')->create();
        $this->lomba = CompetitionCategory::factory()->child($parent)
            ->for($this->eventner, 'eventner')
            ->create(['name' => 'LKBB Tingkat SMA']);

        // 2 Kategori penilaian: PBB dan Danton
        $this->catPbb = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->lomba->id,
            'name' => 'PBB Murni',
            'sort_order' => 1,
        ]);
        $subPbb = AssessmentSubCategory::create([
            'assessment_category_id' => $this->catPbb->id,
            'name' => 'Gerakan Dasar',
            'sort_order' => 1,
        ]);
        $critPbb = AssessmentCriteria::create([
            'assessment_sub_category_id' => $subPbb->id,
            'name' => 'Langkah Tegap',
            'score_options' => [['score' => 100]],
            'weight' => 1,
            'sort_order' => 1,
        ]);

        $this->catDanton = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->lomba->id,
            'name' => 'Komandan Peleton',
            'sort_order' => 2,
        ]);
        $subDanton = AssessmentSubCategory::create([
            'assessment_category_id' => $this->catDanton->id,
            'name' => 'Ketegasan',
            'sort_order' => 1,
        ]);
        $critDanton = AssessmentCriteria::create([
            'assessment_sub_category_id' => $subDanton->id,
            'name' => 'Intonasi Aba-aba',
            'score_options' => [['score' => 100]],
            'weight' => 1,
            'sort_order' => 1,
        ]);

        $this->juri = Judge::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Juri Utama',
        ]);
        $this->juri->assessmentCategories()->attach([$this->catPbb->id, $this->catDanton->id]);

        $this->reg = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->lomba->id,
            'nama_sekolah' => 'SMA Kartika',
        ]);

        $this->actingAs($this->eventner->user);
    }

    public function test_score_deduction_model_accessors_for_manual_deduction(): void
    {
        $deduction = ScoreDeduction::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'deduction_criteria_id' => null,
            'assessment_category_id' => $this->catPbb->id,
            'amount' => 15.5,
            'note' => 'Menginjak garis batas',
        ]);

        $this->assertTrue($deduction->is_manual);
        $this->assertEquals('Menginjak garis batas', $deduction->label);
        $this->assertEquals(15.5, $deduction->magnitude);
        $this->assertEquals($this->catPbb->id, $deduction->assessmentCategory->id);
    }

    public function test_operator_dapat_menambah_dan_menyimpan_pengurangan_manual_global(): void
    {
        Livewire::test(\App\Livewire\Eventner\Scoring\Index::class)
            ->call('selectCategory', $this->lomba->id)
            ->call('selectParticipant', $this->reg->id)
            ->call('addManualDeduction')
            ->assertCount('manualDeductions', 1)
            ->set('manualDeductions.0.note', 'Waktu tampil lebih 12 detik')
            ->set('manualDeductions.0.amount', 20)
            ->set('manualDeductions.0.assessment_category_id', '') // Global / Nilai Akhir
            ->call('saveDeductions')
            ->assertSet('deductionSaveStatus', 'saved');

        $this->assertDatabaseHas('score_deductions', [
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'deduction_criteria_id' => null,
            'assessment_category_id' => null,
            'amount' => 20,
            'note' => 'Waktu tampil lebih 12 detik',
        ]);
    }

    public function test_operator_dapat_menyimpan_pengurangan_manual_target_kategori_tertentu(): void
    {
        Livewire::test(\App\Livewire\Eventner\Scoring\Index::class)
            ->call('selectCategory', $this->lomba->id)
            ->call('selectParticipant', $this->reg->id)
            ->call('addManualDeduction')
            ->set('manualDeductions.0.note', 'Atribut danton jatuh')
            ->set('manualDeductions.0.amount', 5)
            ->set('manualDeductions.0.assessment_category_id', (string) $this->catDanton->id)
            ->call('saveDeductions')
            ->assertSet('deductionSaveStatus', 'saved');

        $this->assertDatabaseHas('score_deductions', [
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'deduction_criteria_id' => null,
            'assessment_category_id' => $this->catDanton->id,
            'amount' => 5,
            'note' => 'Atribut danton jatuh',
        ]);
    }

    public function test_validasi_pengurangan_manual_menolak_keterangan_kosong_atau_nominal_tidak_valid(): void
    {
        // 1. Keterangan kosong tapi ada nominal
        Livewire::test(\App\Livewire\Eventner\Scoring\Index::class)
            ->call('selectCategory', $this->lomba->id)
            ->call('selectParticipant', $this->reg->id)
            ->call('addManualDeduction')
            ->set('manualDeductions.0.note', '')
            ->set('manualDeductions.0.amount', 10)
            ->call('saveDeductions')
            ->assertSet('deductionSaveStatus', 'error');

        $this->assertDatabaseCount('score_deductions', 0);

        // 2. Nominal negatif atau 0
        Livewire::test(\App\Livewire\Eventner\Scoring\Index::class)
            ->call('selectCategory', $this->lomba->id)
            ->call('selectParticipant', $this->reg->id)
            ->call('addManualDeduction')
            ->set('manualDeductions.0.note', 'Pelanggaran')
            ->set('manualDeductions.0.amount', -5)
            ->call('saveDeductions')
            ->assertSet('deductionSaveStatus', 'error');

        $this->assertDatabaseCount('score_deductions', 0);
    }

    public function test_operator_dapat_menghapus_baris_pengurangan_manual(): void
    {
        $existing = ScoreDeduction::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'deduction_criteria_id' => null,
            'assessment_category_id' => null,
            'amount' => 10,
            'note' => 'Pelanggaran tempo',
        ]);

        Livewire::test(\App\Livewire\Eventner\Scoring\Index::class)
            ->call('selectCategory', $this->lomba->id)
            ->call('selectParticipant', $this->reg->id)
            ->assertCount('manualDeductions', 1)
            ->call('removeManualDeduction', 0)
            ->assertCount('manualDeductions', 0);

        $this->assertDatabaseMissing('score_deductions', [
            'id' => $existing->id,
        ]);
    }

    public function test_pengurangan_manual_terkunci_jika_nilai_sudah_difinalisasi(): void
    {
        // Beri nilai dan finalisasi
        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'assessment_criteria_id' => $this->catPbb->subCategories->first()->criterias->first()->id,
            'judge_id' => $this->juri->id,
            'score' => '100',
            'is_finalized' => true,
        ]);

        Livewire::test(\App\Livewire\Eventner\Scoring\Index::class)
            ->call('selectCategory', $this->lomba->id)
            ->call('selectParticipant', $this->reg->id)
            ->call('addManualDeduction')
            ->assertCount('manualDeductions', 0); // Ditolak karena nilai sudah difinalisasi
    }

    public function test_score_recap_builder_mendistribusikan_pengurangan_manual_dengan_benar(): void
    {
        // Nilai juri: PBB = 100, Danton = 100. Total = 200
        $critPbbId = $this->catPbb->subCategories->first()->criterias->first()->id;
        $critDantonId = $this->catDanton->subCategories->first()->criterias->first()->id;

        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'assessment_criteria_id' => $critPbbId,
            'judge_id' => $this->juri->id,
            'score' => '100',
        ]);

        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'assessment_criteria_id' => $critDantonId,
            'judge_id' => $this->juri->id,
            'score' => '100',
        ]);

        // Pengurangan manual 1: potong kategori PBB sebesar 10 poin
        ScoreDeduction::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'deduction_criteria_id' => null,
            'assessment_category_id' => $this->catPbb->id,
            'amount' => 10,
            'note' => 'Langkah patah keluar arena',
        ]);

        // Pengurangan manual 2: potong Global (Nilai Akhir) sebesar 15 poin
        ScoreDeduction::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'deduction_criteria_id' => null,
            'assessment_category_id' => null,
            'amount' => 15,
            'note' => 'Melebihi durasi tampil',
        ]);

        $builder = new ScoreRecapBuilder();
        $recap = $builder->build($this->eventner, $this->lomba->id);

        $this->assertNotEmpty($recap['scoringData']);
        $row = $recap['scoringData']->first();

        // Total juri = 200
        $this->assertEquals(200, $row['grandTotal']);

        // Potongan PBB = -10, Danton = 0
        $this->assertEquals(-10, $row['categoryDeductions'][$this->catPbb->id] ?? 0);
        $this->assertEquals(0, $row['categoryDeductions'][$this->catDanton->id] ?? 0);

        // Kategori PBB: subtotal nilai = 100, potongan = -10, net = 90
        $this->assertEquals(100, $row['categoryTotals'][$this->catPbb->id]);
        $this->assertEquals(90, $row['categoryTotals'][$this->catPbb->id] + $row['categoryDeductions'][$this->catPbb->id]);
        $this->assertEquals(100, $row['categoryTotals'][$this->catDanton->id]);

        // Total pengurangan = -10 (PBB) + -15 (Global) = -25
        $this->assertEquals(-25, $row['totalDeduction']);

        // Nilai Akhir (finalScore) = 200 - 25 = 175
        $this->assertEquals(175, $row['finalScore']);
    }

    public function test_pengurangan_manual_terisolasi_per_babak(): void
    {
        $roundPenyisihan = CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->lomba->id,
            'name' => 'Babak Penyisihan',
            'type' => 'preliminary',
        ]);

        $roundFinal = CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->lomba->id,
            'name' => 'Babak Final',
            'type' => 'final',
        ]);

        // Sanksi manual hanya di babak penyisihan
        $deductionPenyisihan = ScoreDeduction::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'deduction_criteria_id' => null,
            'assessment_category_id' => null,
            'competition_round_id' => $roundPenyisihan->id,
            'amount' => 30,
            'note' => 'Sanksi Penyisihan',
        ]);

        $deductions = ScoreDeduction::where('registration_id', $this->reg->id)->get();

        // Di babak penyisihan, sanksi harus ikut
        $applicablePenyisihan = \App\Models\DeductionCategory::applicableToRound($deductions, [], $roundPenyisihan->id);
        $this->assertCount(1, $applicablePenyisihan);

        // Di babak final, sanksi penyisihan TIDAK boleh memotong nilai babak final
        $applicableFinal = \App\Models\DeductionCategory::applicableToRound($deductions, [], $roundFinal->id);
        $this->assertCount(0, $applicableFinal);
    }
}
