<?php

namespace Tests\Feature;

use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\CompetitionCategory;
use App\Models\DeductionCategory;
use App\Models\DeductionCriteria;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\ScoreDeduction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Temuan audit #20, #21, #22 — integritas panel Input Nilai.
 *
 * #20: selectedCategoryId datang dari query string dan dipercaya begitu saja,
 *      sehingga id kategori tenant lain (atau id yang tidak ada) membuat
 *      halaman peserta dirender tanpa $selectedCategory → 500.
 * #21: status "sudah difinalisasi" hanya dibaca sekali saat peserta dibuka,
 *      jadi nilai yang terlanjur dikunci di tempat lain masih bisa ditimpa
 *      dan direset dari panel yang masih terbuka.
 * #22: finalisasi tidak ikut menyimpan pengurangan, padahal layar
 *      menampilkan "NILAI AKHIR" yang sudah dikurangi — angkanya lalu hilang.
 */
class ScoringIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;
    private CompetitionCategory $lomba;
    private AssessmentCategory $formatNilai;
    private AssessmentCriteria $kriteria;
    private Judge $juri;
    private Registration $reg;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventner = Eventner::factory()->create([
            'status' => 'approved',
            'scoring_code' => 'SC-INTEGRITAS',
        ]);

        $parent = CompetitionCategory::factory()->for($this->eventner, 'eventner')->create();
        $this->lomba = CompetitionCategory::factory()->child($parent)
            ->for($this->eventner, 'eventner')
            ->create(['name' => 'PBB Beregu']);

        $this->formatNilai = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->lomba->id,
            'name' => 'Penilaian Umum',
            'sort_order' => 1,
        ]);
        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $this->formatNilai->id,
            'name' => 'Sub Umum',
            'sort_order' => 1,
        ]);
        $this->kriteria = AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Ketepatan',
            'score_options' => [['score' => 10], ['score' => 20]],
            'weight' => 1,
            'sort_order' => 1,
        ]);

        $this->juri = Judge::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Juri Satu',
        ]);
        $this->juri->assessmentCategories()->attach($this->formatNilai->id);

        $this->reg = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->lomba->id,
            'nama_sekolah' => 'SMP Integritas',
        ]);

        $this->actingAs($this->eventner->user);
    }

    private function panel()
    {
        return Livewire::test(\App\Livewire\Eventner\Scoring\Index::class)
            ->call('selectCategory', $this->lomba->id)
            ->call('selectParticipant', $this->reg->id);
    }

    /**
     * #20 — kategori yang bukan milik event ini (atau tidak ada) tidak boleh
     * membawa halaman ke mode peserta.
     */
    public function test_kategori_milik_event_lain_ditolak()
    {
        $lain = Eventner::factory()->create(['status' => 'approved']);
        $kategoriLain = CompetitionCategory::factory()->for($lain, 'eventner')->create();

        Livewire::test(\App\Livewire\Eventner\Scoring\Index::class)
            ->call('selectCategory', $kategoriLain->id)
            ->assertSet('selectedCategoryId', null)
            ->assertSet('view', 'categories');
    }

    /**
     * #20 — id kategori dari query string tidak boleh menjatuhkan halaman.
     */
    public function test_kategori_tidak_dikenal_dari_query_string_tidak_meruntuhkan_halaman()
    {
        Livewire::test(\App\Livewire\Eventner\Scoring\Index::class, [
            'selectedCategoryId' => 999999,
        ])
            ->assertSet('selectedCategoryId', null)
            ->assertSet('view', 'categories')
            ->assertOk();
    }

    /**
     * #21 — nilai yang sudah dikunci di database tidak bisa ditimpa lewat
     * panel yang belum menyegarkan statusnya.
     */
    public function test_nilai_terkunci_tidak_bisa_ditimpa()
    {
        $this->panel()
            ->set('selectedJudgeId', $this->juri->id)
            ->set('scores.' . $this->kriteria->id, 20)
            ->call('saveScores');

        // Finalisasi dari luar panel (mis. tombol "Finalisasi Semua" atau
        // halaman tablet juri) sementara panel masih terbuka.
        AssessmentScore::where('registration_id', $this->reg->id)
            ->where('judge_id', $this->juri->id)
            ->update(['is_finalized' => true]);

        $this->panel()
            ->set('selectedJudgeId', $this->juri->id)
            ->set('scores.' . $this->kriteria->id, 10)
            ->call('saveScores');

        $tersimpan = AssessmentScore::where('registration_id', $this->reg->id)
            ->where('judge_id', $this->juri->id)
            ->value('score');

        $this->assertSame(20, (int) $tersimpan);
    }

    /** #21 — nilai terkunci juga tidak bisa dihapus lewat reset. */
    public function test_nilai_terkunci_tidak_bisa_direset()
    {
        $this->panel()
            ->set('selectedJudgeId', $this->juri->id)
            ->set('scores.' . $this->kriteria->id, 20)
            ->call('saveScores');

        AssessmentScore::where('registration_id', $this->reg->id)
            ->where('judge_id', $this->juri->id)
            ->update(['is_finalized' => true]);

        $this->panel()
            ->set('selectedJudgeId', $this->juri->id)
            ->call('resetScores');

        $this->assertDatabaseHas('assessment_scores', [
            'registration_id' => $this->reg->id,
            'judge_id' => $this->juri->id,
        ]);
    }

    /**
     * #22 — finalisasi menyimpan pengurangan sekaligus. Dulu pengurangan hanya
     * ada di form, sehingga angka "NILAI AKHIR" di layar tidak pernah
     * tersimpan dan hilang saat halaman dimuat ulang.
     */
    public function test_finalisasi_ikut_menyimpan_pengurangan()
    {
        $kategoriPotong = DeductionCategory::create([
            'eventner_id' => $this->eventner->id,
            'assessment_category_id' => $this->formatNilai->id,
            'name' => 'Pelanggaran',
            'sort_order' => 1,
        ]);
        $kriteriaPotong = DeductionCriteria::create([
            'deduction_category_id' => $kategoriPotong->id,
            'name' => 'Terlambat',
            // Opsi pengurangan disimpan sebagai daftar angka polos —
            // lihat FormatNilaiImport/Bina Rubrik.
            'deduction_options' => [5],
            'sort_order' => 1,
        ]);

        $this->panel()
            ->set('selectedJudgeId', $this->juri->id)
            ->set('scores.' . $this->kriteria->id, 20)
            ->set('deductions.' . $kriteriaPotong->id, 5)
            ->call('finalizeScores');

        $tersimpan = ScoreDeduction::where('registration_id', $this->reg->id)
            ->where('deduction_criteria_id', $kriteriaPotong->id)
            ->first();

        $this->assertNotNull($tersimpan, 'Pengurangan harus ikut tersimpan saat finalisasi.');
        $this->assertSame(5.0, $tersimpan->magnitude);
    }

    /** #22 — setelah finalisasi, pengurangan tidak bisa diubah lagi. */
    public function test_pengurangan_terkunci_setelah_finalisasi()
    {
        $kategoriPotong = DeductionCategory::create([
            'eventner_id' => $this->eventner->id,
            'assessment_category_id' => $this->formatNilai->id,
            'name' => 'Pelanggaran',
            'sort_order' => 1,
        ]);
        $kriteriaPotong = DeductionCriteria::create([
            'deduction_category_id' => $kategoriPotong->id,
            'name' => 'Terlambat',
            // Opsi pengurangan disimpan sebagai daftar angka polos —
            // lihat FormatNilaiImport/Bina Rubrik.
            'deduction_options' => [5],
            'sort_order' => 1,
        ]);

        $this->panel()
            ->set('selectedJudgeId', $this->juri->id)
            ->set('scores.' . $this->kriteria->id, 20)
            ->set('deductions.' . $kriteriaPotong->id, 5)
            ->call('finalizeScores');

        $this->panel()
            ->set('selectedJudgeId', $this->juri->id)
            ->set('deductions.' . $kriteriaPotong->id, 15)
            ->call('saveDeductions');

        $this->assertSame(5.0, ScoreDeduction::where('registration_id', $this->reg->id)
            ->where('deduction_criteria_id', $kriteriaPotong->id)
            ->first()->magnitude);
    }
}
