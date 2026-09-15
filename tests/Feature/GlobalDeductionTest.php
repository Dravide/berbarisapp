<?php

namespace Tests\Feature;

use App\Livewire\Eventner\FormatNilai\Builder;
use App\Livewire\Eventner\Scoring\Index as ScoringIndex;
use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\DeductionCategory;
use App\Models\DeductionCriteria;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\ScoreDeduction;
use App\Services\ChampionCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Pengurangan Nilai Global di Struktur Rubrik Penilaian.
 *
 * Sanksi seperti keterlambatan atau pelanggaran disiplin berlaku untuk semua
 * tingkat lomba, jadi tidak bisa dibuat menempel pada satu kategori penilaian
 * saja. Kelompok ber-scope 'global' tidak menempel ke kategori mana pun:
 * potongannya mengurangi NILAI AKHIR di luar kolom kategori, dan tetap ikut
 * pemecah seri juara.
 */
class GlobalDeductionTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;
    private CompetitionCategory $lomba;
    private AssessmentCategory $formatNilai;
    private AssessmentCriteria $kriteria;
    private Judge $juri;
    private Registration $reg;
    private DeductionCategory $globalCat;
    private DeductionCriteria $globalKrit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventner = Eventner::factory()->create([
            'status' => 'approved',
            'scoring_code' => 'SC-GLOBAL',
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
            'score_options' => [['score' => 100]],
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
            'nama_sekolah' => 'SMP Global',
        ]);

        // Kelompok pengurangan global: tidak menempel ke kategori mana pun.
        $this->globalCat = DeductionCategory::create([
            'eventner_id' => $this->eventner->id,
            'assessment_category_id' => null,
            'scope' => DeductionCategory::SCOPE_GLOBAL,
            'name' => 'Sanksi Lapangan',
            'sort_order' => 1,
        ]);
        $this->globalKrit = DeductionCriteria::create([
            'deduction_category_id' => $this->globalCat->id,
            'name' => 'Terlambat masuk lapangan',
            'deduction_options' => [-15, -40],
            'sort_order' => 1,
        ]);

        $this->actingAs($this->eventner->user);
    }

    private function nilai(int $skor): void
    {
        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'judge_id' => $this->juri->id,
            'assessment_criteria_id' => $this->kriteria->id,
            'score' => $skor,
        ]);
    }

    private function nilaiFinal(int $skor): void
    {
        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'judge_id' => $this->juri->id,
            'assessment_criteria_id' => $this->kriteria->id,
            'score' => $skor,
            'is_finalized' => true,
        ]);
    }

    private function panel()
    {
        // Juri pertama otomatis terpilih saat peserta dipilih.
        return Livewire::test(ScoringIndex::class)
            ->call('selectCategory', $this->lomba->id)
            ->call('selectParticipant', $this->reg->id);
    }

    /** Builder menyimpan kelompok global tanpa menempel ke kategori mana pun. */
    public function test_builder_membuat_kelompok_global_tanpa_kategori()
    {
        Livewire::test(Builder::class)
            ->set('newGlobalDeductionCategoryName', 'Atribut Tidak Lengkap')
            ->call('addGlobalDeductionCategory');

        $cat = DeductionCategory::where('eventner_id', $this->eventner->id)
            ->where('name', 'Atribut Tidak Lengkap')
            ->firstOrFail();

        $this->assertSame(DeductionCategory::SCOPE_GLOBAL, $cat->scope);
        $this->assertNull($cat->assessment_category_id);
        $this->assertTrue($cat->isGlobal());
    }

    /** Nama kelompok global wajib diisi — tidak boleh tersimpan kosong. */
    public function test_kelompok_global_tanpa_nama_ditolak()
    {
        Livewire::test(Builder::class)
            ->set('newGlobalDeductionCategoryName', '   ')
            ->call('addGlobalDeductionCategory');

        // Hanya kelompok global dari setUp yang ada — tidak ada yang baru.
        $this->assertSame(1, DeductionCategory::global()->count());
    }

    /** Kelompok global tidak muncul sebagai pengurangan per kategori. */
    public function test_kelompok_global_tidak_terbaca_sebagai_per_kategori()
    {
        $this->assertSame(0, DeductionCategory::category()->count());
        $this->assertSame(1, DeductionCategory::global()->count());
    }

    /** Panel input nilai memuat kelompok global walaupun tidak menempel kategori. */
    public function test_panel_operator_menampilkan_pengurangan_global()
    {
        $this->panel()
            ->assertSee('Pengurangan Global')
            ->assertSee('Terlambat masuk lapangan');
    }

    /** Nilai pengurangan global tersimpan lewat jalur yang sama. */
    public function test_nilai_pengurangan_global_tersimpan()
    {
        $this->panel()
            ->set("deductions.{$this->globalKrit->id}", -15)
            ->call('saveDeductions');

        $this->assertDatabaseHas('score_deductions', [
            'registration_id' => $this->reg->id,
            'deduction_criteria_id' => $this->globalKrit->id,
        ]);
    }

    /**
     * Inti permintaan: pengurangan global memotong NILAI AKHIR, tetapi nilai
     * kolom kategori tidak berubah — sanksinya tidak menyentuh rubrik.
     */
    public function test_global_memotong_nilai_akhir_tanpa_menyentuh_kolom_kategori()
    {
        $this->nilai(80);

        $html = $this->panel()
            ->set("deductions.{$this->globalKrit->id}", -15)
            ->html();

        // NILAI AKHIR = 80 - 15 = 65, tapi Nilai Juri tetap 80.
        $this->assertStringContainsString('Pengurangan Global', $html);
        $this->assertStringContainsString('Pengurangan Kategori', $html);

        $komponen = $this->panel()->set("deductions.{$this->globalKrit->id}", -15);

        $this->assertEquals(15, $komponen->viewData('totalDeductionsGlobal'));
        $this->assertEquals(0, $komponen->viewData('totalDeductionsKategori'));
        $this->assertEquals(15, $komponen->viewData('totalDeductions'));
    }

    /** Pengurangan per kategori tetap mengisi kolom kategorinya sendiri. */
    public function test_pengurangan_per_kategori_tetap_masuk_kolom_kategori()
    {
        $perKategori = DeductionCategory::create([
            'eventner_id' => $this->eventner->id,
            'assessment_category_id' => $this->formatNilai->id,
            'scope' => DeductionCategory::SCOPE_CATEGORY,
            'name' => 'Pelanggaran Disiplin',
            'sort_order' => 2,
        ]);
        $krit = DeductionCriteria::create([
            'deduction_category_id' => $perKategori->id,
            'name' => 'Seragam',
            'deduction_options' => [-10],
            'sort_order' => 1,
        ]);

        $komponen = $this->panel()
            ->set("deductions.{$krit->id}", -10)
            ->set("deductions.{$this->globalKrit->id}", -25);

        $this->assertEquals(10, $komponen->viewData('totalDeductionsKategori'));
        $this->assertEquals(25, $komponen->viewData('totalDeductionsGlobal'));
        $this->assertEquals(35, $komponen->viewData('totalDeductions'));
    }

    /** Rekapitulasi ikut memotong nilai akhir dengan pengurangan global. */
    public function test_rekap_memasukkan_global_ke_total_pengurangan()
    {
        $this->nilai(80);
        ScoreDeduction::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'deduction_criteria_id' => $this->globalKrit->id,
            'amount' => -15,
        ]);

        $komponen = Livewire::test(\App\Livewire\Eventner\ScoreRecap\Index::class)
            ->call('selectCategory', $this->lomba->id);

        $baris = collect($komponen->viewData('scoringData'))->firstWhere('participant.id', $this->reg->id);

        $this->assertNotNull($baris, 'Peserta harus muncul di rekapitulasi.');
        $this->assertEquals(80, $baris['grandTotal']);
        $this->assertEquals(15, $baris['globalDeduction']);
        $this->assertEquals(-15, $baris['totalDeduction']);
        $this->assertEquals(65, $baris['finalScore'], 'Pengurangan global harus memotong nilai akhir.');
    }

    /**
     * Pengurangan global ikut pemecah seri juara: dua peserta bernilai sama,
     * yang kena sanksi global berperingkat lebih bawah.
     */
    public function test_global_ikut_pemecah_seri_juara()
    {
        $bersih = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->lomba->id,
            'nama_sekolah' => 'SMP Bersih',
        ]);

        // Peserta utama bernilai sama, tapi kena sanksi global.
        $this->nilai(100);
        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $bersih->id,
            'judge_id' => $this->juri->id,
            'assessment_criteria_id' => $this->kriteria->id,
            'score' => 100,
        ]);
        ScoreDeduction::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'deduction_criteria_id' => $this->globalKrit->id,
            'amount' => -40,
        ]);

        $juara = ChampionCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Juara Umum',
            'quantity' => 1,
            'sort_order' => 1,
        ]);
        $juara->assessmentSubCategories()->attach($this->formatNilai->subCategories->first()->id);

        [, , $winners] = app(ChampionCalculator::class)->winners($juara, $this->lomba->id);

        $this->assertSame(
            'SMP Bersih',
            $winners[0]['registration']->nama_sekolah,
            'Peserta tanpa sanksi global harus menang saat nilainya seri.'
        );
    }

    /**
     * Rincian hasil publik memakai magnitude, bukan kolom amount mentah —
     * operator boleh mengetik -15 atau 15, dan amount positif tidak boleh
     * MENAMBAH skor.
     */
    public function test_rincian_hasil_tidak_menambah_skor_saat_amount_positif()
    {
        $this->nilaiFinal(80);
        ScoreDeduction::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'deduction_criteria_id' => $this->globalKrit->id,
            'amount' => 15, // positif: operator mengetik tanpa tanda minus
        ]);

        $this->eventner->update(['slug' => 'event-global']);

        $komponen = Livewire::test(\App\Livewire\Public\EventResultDetail::class, [
            'slug' => 'event-global',
            'registration' => $this->reg->id,
        ]);

        $this->assertEquals(80, $komponen->viewData('grandTotal'));
        $this->assertEquals(15, $komponen->viewData('totalDeduction'));
        $this->assertEquals(65, $komponen->viewData('finalTotal'));
    }
}
