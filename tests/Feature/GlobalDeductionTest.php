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
 * Pengurangan Nilai Tingkat di Struktur Rubrik Penilaian.
 *
 * Sanksi seperti keterlambatan atau pelanggaran disiplin berlaku untuk semua
 * kategori penilaian DI SATU TINGKAT LOMBA, jadi tidak bisa dibuat menempel
 * pada satu kategori penilaian saja. Kelompok ber-scope 'global' tidak
 * menempel ke kategori penilaian mana pun, tetapi terikat pada satu tingkat
 * lomba (competition_category_id): potongannya mengurangi NILAI AKHIR di luar
 * kolom kategori, ikut pemecah seri juara, dan TIDAK boleh menyentuh peserta
 * tingkat lain.
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

        // Kelompok pengurangan tingkat: tidak menempel ke kategori penilaian
        // mana pun, tetapi milik satu tingkat lomba.
        $this->globalCat = DeductionCategory::create([
            'eventner_id' => $this->eventner->id,
            'assessment_category_id' => null,
            'competition_category_id' => $this->lomba->id,
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

    /**
     * Builder menyimpan kelompok tingkat: tidak menempel ke kategori
     * penilaian mana pun, tetapi terikat pada tingkat lomba aktif.
     */
    public function test_builder_membuat_kelompok_tingkat_tanpa_kategori_penilaian()
    {
        Livewire::test(Builder::class)
            ->set('newGlobalDeductionCategoryName', 'Atribut Tidak Lengkap')
            ->call('addGlobalDeductionCategory');

        $cat = DeductionCategory::where('eventner_id', $this->eventner->id)
            ->where('name', 'Atribut Tidak Lengkap')
            ->firstOrFail();

        $this->assertSame(DeductionCategory::SCOPE_GLOBAL, $cat->scope);
        $this->assertNull($cat->assessment_category_id);
        $this->assertSame(
            $this->lomba->id,
            $cat->competition_category_id,
            'Kelompok tingkat harus terikat pada tingkat lomba yang sedang dipilih.'
        );
        $this->assertTrue($cat->isGlobal());
    }

    /**
     * Tanpa "Tambah" apa pun, tab "Semua Tingkat" harus tetap menghasilkan
     * kelompok yang punya tingkat — bukan kelompok tanpa tingkat yang tidak
     * akan pernah tampil di panel Input Nilai.
     */
    public function test_builder_mengisi_tingkat_saat_tab_semua_tingkat()
    {
        Livewire::test(Builder::class)
            ->set('activeTab', '')
            ->set('newGlobalDeductionCategoryName', 'Sanksi Umum')
            ->call('addGlobalDeductionCategory');

        $cat = DeductionCategory::where('eventner_id', $this->eventner->id)
            ->where('name', 'Sanksi Umum')
            ->firstOrFail();

        $this->assertNotNull($cat->competition_category_id);
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

    /** Kelompok tingkat tidak muncul sebagai pengurangan per kategori. */
    public function test_kelompok_global_tidak_terbaca_sebagai_per_kategori()
    {
        $this->assertSame(0, DeductionCategory::category()->count());
        $this->assertSame(1, DeductionCategory::global()->count());
    }

    /** Panel input nilai memuat kelompok tingkat walaupun tidak menempel kategori. */
    public function test_panel_operator_menampilkan_pengurangan_tingkat()
    {
        $this->panel()
            ->assertSee('Pengurangan Tingkat')
            ->assertSee('Terlambat masuk lapangan');
    }

    /**
     * Inti koreksi: sanksi satu tingkat lomba tidak boleh muncul — apalagi
     * memotong nilai — peserta tingkat lomba lain.
     */
    public function test_kelompok_tingkat_tidak_bocor_ke_tingkat_lain()
    {
        $lombaLain = CompetitionCategory::factory()->child(
            CompetitionCategory::find($this->lomba->parent_id)
        )->for($this->eventner, 'eventner')->create(['name' => 'PBB Putri']);

        $regLain = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $lombaLain->id,
            'nama_sekolah' => 'SMP Tingkat Lain',
        ]);

        // Panel peserta tingkat ini: kelompoknya tampil.
        $this->panel()->assertSee('Terlambat masuk lapangan');

        // Panel peserta tingkat lain: kelompok tingkat pertama tidak ikut.
        Livewire::test(ScoringIndex::class)
            ->call('selectCategory', $lombaLain->id)
            ->call('selectParticipant', $regLain->id)
            ->assertDontSee('Terlambat masuk lapangan')
            ->assertDontSee('Pengurangan Tingkat');
    }

    /** Ketentuan tingkat juga berlaku di rekapitulasi nilai. */
    public function test_rekap_tidak_memotong_peserta_tingkat_lain()
    {
        $lombaLain = CompetitionCategory::factory()->child(
            CompetitionCategory::find($this->lomba->parent_id)
        )->for($this->eventner, 'eventner')->create(['name' => 'PBB Putri']);

        $regLain = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $lombaLain->id,
            'nama_sekolah' => 'SMP Tingkat Lain',
        ]);

        $this->nilai(80);
        ScoreDeduction::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $regLain->id,
            'deduction_criteria_id' => $this->globalKrit->id,
            'amount' => -40,
        ]);

        $komponen = Livewire::test(\App\Livewire\Eventner\ScoreRecap\Index::class)
            ->call('selectCategory', $lombaLain->id);

        $baris = collect($komponen->viewData('scoringData'))->firstWhere('participant.id', $regLain->id);

        $this->assertNotNull($baris);
        $this->assertEquals(0, $baris['globalDeduction'], 'Sanksi tingkat lain tidak boleh memotong.');
        $this->assertEquals(0, $baris['totalDeduction']);
    }

    /** Nilai pengurangan tingkat tersimpan lewat jalur yang sama. */
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
     * Inti permintaan: pengurangan tingkat memotong NILAI AKHIR, tetapi nilai
     * kolom kategori tidak berubah — sanksinya tidak menyentuh rubrik.
     */
    public function test_global_memotong_nilai_akhir_tanpa_menyentuh_kolom_kategori()
    {
        $this->nilai(80);

        $html = $this->panel()
            ->set("deductions.{$this->globalKrit->id}", -15)
            ->html();

        // NILAI AKHIR = 80 - 15 = 65, tapi Nilai Juri tetap 80.
        $this->assertStringContainsString('Pengurangan Tingkat', $html);
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

    /** Rekapitulasi ikut memotong nilai akhir dengan pengurangan tingkat. */
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
     * Pengurangan tingkat ikut pemecah seri juara: dua peserta bernilai sama,
     * yang kena sanksi berperingkat lebih bawah.
     */
    public function test_global_ikut_pemecah_seri_juara()
    {
        $bersih = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->lomba->id,
            'nama_sekolah' => 'SMP Bersih',
        ]);

        // Peserta utama bernilai sama, tapi kena sanksi tingkat.
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
            'Peserta tanpa sanksi tingkat harus menang saat nilainya seri.'
        );
    }

    /**
     * Pemecah seri juara juga tidak boleh menghitung sanksi tingkat lain —
     * bila bocor, peserta tingkat lain yang bersih bisa tergeser.
     */
    public function test_pemecah_seri_juara_tidak_terpengaruh_sanksi_tingkat_lain()
    {
        $lombaLain = CompetitionCategory::factory()->child(
            CompetitionCategory::find($this->lomba->parent_id)
        )->for($this->eventner, 'eventner')->create(['name' => 'PBB Putri']);

        $regLain = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $lombaLain->id,
            'nama_sekolah' => 'SMP Tingkat Lain',
        ]);

        // Sanksi milik tingkat lain ditempelkan ke peserta tingkat ini.
        ScoreDeduction::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'deduction_criteria_id' => $this->globalKrit->id,
            'amount' => -40,
        ]);

        $this->assertEquals(
            0,
            DeductionCategory::applicableToLevel(
                ScoreDeduction::where('registration_id', $this->reg->id)->get(),
                $lombaLain->id,
                DeductionCategory::levelMapOfCriteria($this->eventner->id)
            )->sum(fn ($d) => $d->magnitude),
            'Sanksi tingkat PBB Beregu bukan milik PBB Putri.'
        );

        $this->assertEquals(
            40,
            DeductionCategory::applicableToLevel(
                ScoreDeduction::where('registration_id', $this->reg->id)->get(),
                $this->lomba->id,
                DeductionCategory::levelMapOfCriteria($this->eventner->id)
            )->sum(fn ($d) => $d->magnitude),
            'Sanksi tingkat PBB Beregu tetap berlaku di tingkatnya sendiri.'
        );

        $this->assertNotNull($regLain->id);
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
