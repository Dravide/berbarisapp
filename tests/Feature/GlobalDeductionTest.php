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
use App\Models\CompetitionRound;
use App\Models\CompetitionRoundRegistration;
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
 * kolom kategori, ikut pemecah nilai sama juara, dan TIDAK boleh menyentuh peserta
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
     * Pengurangan tingkat ikut pemecah nilai sama juara: dua peserta bernilai
     * sama, yang kena sanksi berperingkat lebih bawah.
     */
    public function test_global_ikut_pemecah_nilai_sama_juara()
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
            'Peserta tanpa sanksi tingkat harus menang saat nilainya sama.'
        );
    }

    /**
     * Pemecah nilai sama juara juga tidak boleh menghitung sanksi tingkat lain —
     * bila bocor, peserta tingkat lain yang bersih bisa tergeser.
     */
    public function test_pemecah_nilai_sama_juara_tidak_terpengaruh_sanksi_tingkat_lain()
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

    // ---------- batas babak ----------

    /**
     * Tingkat berbabak: penyisihan + final, dan peserta yang lolos final.
     *
     * @return array{0: CompetitionRound, 1: CompetitionRound}
     */
    private function berbabak(): array
    {
        $penyisihan = CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->lomba->id,
            'name' => 'Fase Grup',
            'type' => CompetitionRound::TYPE_PRELIMINARY,
            'sort_order' => 1,
        ]);
        $final = CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->lomba->id,
            'name' => 'Final',
            'type' => CompetitionRound::TYPE_FINAL,
            'sort_order' => 2,
        ]);

        CompetitionRoundRegistration::create([
            'eventner_id' => $this->eventner->id,
            'competition_round_id' => $final->id,
            'registration_id' => $this->reg->id,
        ]);

        return [$penyisihan, $final];
    }

    /**
     * Panel dengan babak tertentu dibuka, peserta utama.
     *
     * Babak tidak dipilih dari DOM — chip di daftar peserta yang menetapkannya
     * ('all' = penyisihan, 'final' = final), sama seperti panitia memakainya.
     */
    private function panelBabak(string $scope)
    {
        return Livewire::test(ScoringIndex::class)
            ->call('selectCategory', $this->lomba->id)
            ->call('selectScope', $scope)
            ->call('selectParticipant', $this->reg->id);
    }

    /**
     * Inti perbaikan: sanksi yang dibatasi ke fase grup tidak ikut tampil —
     * apalagi memotong NILAI AKHIR — saat panel membuka babak final.
     */
    public function test_pengurangan_tingkat_berbabak_tidak_bocor_ke_final()
    {
        [$penyisihan] = $this->berbabak();
        $this->globalCat->update(['competition_round_id' => $penyisihan->id]);

        // Fase grup: sanksinya tampil.
        $this->panelBabak('all')
            ->assertSee('Terlambat masuk lapangan')
            ->set("deductions.{$this->globalKrit->id}", -15)
            ->assertViewHas('totalDeductionsGlobal', 15);

        // Final: sanksi fase grup tidak ikut.
        $this->panelBabak('final')
            ->assertDontSee('Terlambat masuk lapangan')
            ->assertViewHas('totalDeductionsGlobal', 0)
            ->assertViewHas('totalDeductions', 0);
    }

    /**
     * Berpindah babak lewat chip membuang baris pengurangan babak lama dari
     * layar — bukan cuma dari angka yang tampil.
     */
    public function test_pindah_babak_membersihkan_pengurangan_babak_lama()
    {
        [$penyisihan] = $this->berbabak();
        $this->globalCat->update(['competition_round_id' => $penyisihan->id]);

        ScoreDeduction::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'deduction_criteria_id' => $this->globalKrit->id,
            'amount' => -15,
        ]);

        $this->panelBabak('all')
            ->assertSet("deductions.{$this->globalKrit->id}", '-15.00')
            ->call('selectScope', 'final')
            ->assertSet('deductions', []);
    }

    /** Kelompok tanpa babak tetap berlaku di semua babak. */
    public function test_pengurangan_tingkat_tanpa_babak_berlaku_di_semua_babak()
    {
        $this->berbabak();

        $this->assertNull($this->globalCat->competition_round_id);

        $this->panelBabak('final')
            ->assertSee('Terlambat masuk lapangan')
            ->set("deductions.{$this->globalKrit->id}", -15)
            ->assertViewHas('totalDeductionsGlobal', 15);
    }

    /** Baris babak lain tidak ikut ditulis ulang saat Simpan di babak ini. */
    public function test_simpan_di_final_tidak_menyentuh_pengurangan_fase_grup()
    {
        [$penyisihan] = $this->berbabak();
        $this->globalCat->update(['competition_round_id' => $penyisihan->id]);

        ScoreDeduction::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'deduction_criteria_id' => $this->globalKrit->id,
            'amount' => -15,
        ]);

        $this->panelBabak('final')->call('saveDeductions');

        $this->assertSame(-15.0, (float) ScoreDeduction::where('registration_id', $this->reg->id)
            ->where('deduction_criteria_id', $this->globalKrit->id)
            ->value('amount'));
    }

    /** Builder menyimpan batas babak kelompok tingkat. */
    public function test_builder_menyimpan_batas_babak_kelompok_tingkat()
    {
        [$penyisihan] = $this->berbabak();

        Livewire::test(Builder::class)
            ->call('saveDeductionScope', $this->globalCat->id)
            ->assertOk();

        // Dipilih lewat array state komponen, seperti select di kartu.
        Livewire::test(Builder::class)
            ->set("deductionRoundId.{$this->globalCat->id}", (string) $penyisihan->id)
            ->call('saveDeductionScope', $this->globalCat->id);

        $this->assertSame(
            $penyisihan->id,
            $this->globalCat->fresh()->competition_round_id,
            'Babak yang dipilih harus tersimpan di kelompok pengurangan tingkat.'
        );
    }

    /** Babak milik tingkat lain ditolak, sama seperti penanda rubrik. */
    public function test_builder_menolak_babak_tingkat_lain()
    {
        $lombaLain = CompetitionCategory::factory()->child(
            CompetitionCategory::find($this->lomba->parent_id)
        )->for($this->eventner, 'eventner')->create(['name' => 'PBB Putri']);

        $babakLain = CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $lombaLain->id,
            'name' => 'Final Putri',
            'type' => CompetitionRound::TYPE_FINAL,
            'sort_order' => 1,
        ]);

        Livewire::test(Builder::class)
            ->set("deductionRoundId.{$this->globalCat->id}", (string) $babakLain->id)
            ->call('saveDeductionScope', $this->globalCat->id);

        $this->assertNull(
            $this->globalCat->fresh()->competition_round_id,
            'Babak tingkat lain tidak boleh tersimpan.'
        );
    }

    /** Rekap: sanksi fase grup tidak memotong NILAI AKHIR tabel final. */
    public function test_rekap_final_tidak_ikut_sanksi_fase_grup()
    {
        [$penyisihan] = $this->berbabak();
        $this->globalCat->update(['competition_round_id' => $penyisihan->id]);

        $this->nilai(80);
        ScoreDeduction::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'deduction_criteria_id' => $this->globalKrit->id,
            'amount' => -15,
        ]);

        $komponen = Livewire::test(\App\Livewire\Eventner\ScoreRecap\Index::class)
            ->call('selectCategory', $this->lomba->id);

        $sections = collect($komponen->viewData('sections'));
        $this->assertCount(2, $sections, 'Satu bagian per babak.');

        $barisGrup = collect($sections->firstWhere('final', false)['groups'])
            ->flatMap(fn ($g) => $g['data'])
            ->firstWhere('participant.id', $this->reg->id);
        $barisFinal = collect($sections->firstWhere('final', true)['groups'])
            ->flatMap(fn ($g) => $g['data'])
            ->firstWhere('participant.id', $this->reg->id);

        $this->assertNotNull($barisGrup);
        $this->assertNotNull($barisFinal);

        $this->assertEquals(15, $barisGrup['globalDeduction'], 'Fase grup tetap kena sanksinya.');
        $this->assertEquals(0, $barisFinal['globalDeduction'], 'Final tidak boleh kena sanksi fase grup.');
    }

    /**
     * Kalkulator juara satu babak mengabaikan sanksi yang dibatasi babak lain.
     *
     * Sanksi hanya masuk sebagai pemecah nilai sama (bukan pemotong total), jadi
     * efeknya terlihat dari urutan: peserta bersanksi turun di babak sanksinya,
     * tetapi tidak di babak lain.
     */
    public function test_kalkulator_juara_satu_babak_mengabaikan_sanksi_babak_lain()
    {
        [$penyisihan, $final] = $this->berbabak();
        $this->globalCat->update(['competition_round_id' => $penyisihan->id]);

        // Urutan tampil dipakai sebagai penentu terakhir: peserta bersanksi
        // tampil lebih dulu, jadi kalah-menang murni dari sanksi — bukan dari
        // urutan nama atau urutan array yang tidak dijamin usort.
        $this->reg->update(['urutan_tampil' => 2]);
        $regBersanksi = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->lomba->id,
            'nama_sekolah' => 'SMP Andalan',
            'urutan_tampil' => 1,
        ]);

        $this->nilai(80);
        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $regBersanksi->id,
            'judge_id' => $this->juri->id,
            'assessment_criteria_id' => $this->kriteria->id,
            'score' => 80,
        ]);
        ScoreDeduction::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $regBersanksi->id,
            'deduction_criteria_id' => $this->globalKrit->id,
            'amount' => -15,
        ]);

        $juara = ChampionCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Juara Umum',
            'quantity' => 2,
            'sort_order' => 1,
        ]);
        $juara->assessmentSubCategories()->attach($this->formatNilai->subCategories->first()->id);

        $kalkulator = app(ChampionCalculator::class);

        [, , $grup] = $kalkulator->rankings($juara, $this->lomba->id, null, $penyisihan->id);
        [, , $babakFinal] = $kalkulator->rankings($juara, $this->lomba->id, null, $final->id);

        $this->assertSame(
            $this->reg->id,
            $grup[0]['registration']->id,
            'Di fase grup peserta bersanksi turun ke peringkat dua.'
        );
        $this->assertSame(
            $regBersanksi->id,
            $babakFinal[0]['registration']->id,
            'Di final sanksi fase grup tidak lagi memisahkan — urutan tampil yang menentukan.'
        );
    }

    /** Tanpa konteks babak, saringan babak mati — peringkat gabungan tetap utuh. */
    public function test_peringkat_gabungan_lintas_babak_tetap_memakai_semua_sanksi()
    {
        [$penyisihan] = $this->berbabak();
        $this->globalCat->update(['competition_round_id' => $penyisihan->id]);

        $this->nilai(80);
        ScoreDeduction::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'deduction_criteria_id' => $this->globalKrit->id,
            'amount' => -15,
        ]);

        $this->assertEquals(
            15,
            DeductionCategory::applicableToRound(
                ScoreDeduction::where('registration_id', $this->reg->id)->get(),
                DeductionCategory::roundMapOfCriteria($this->eventner->id),
                null
            )->sum(fn ($d) => $d->magnitude),
            'Babak null = pemanggil tanpa konteks babak; semua baris tetap ikut.'
        );
    }

    /** Pengurangan per kategori ikut babak lewat rubriknya, bukan kolom sendiri. */
    public function test_peta_babak_membaca_babak_pengurangan_per_kategori_dari_rubrik()
    {
        [$penyisihan, $final] = $this->berbabak();
        $this->formatNilai->update(['competition_round_id' => $penyisihan->id]);

        $catKategori = DeductionCategory::create([
            'eventner_id' => $this->eventner->id,
            'assessment_category_id' => $this->formatNilai->id,
            'scope' => DeductionCategory::SCOPE_CATEGORY,
            'name' => 'Pelanggaran Atribut',
            'sort_order' => 2,
        ]);
        $kritKategori = DeductionCriteria::create([
            'deduction_category_id' => $catKategori->id,
            'name' => 'Atribut tidak lengkap',
            'deduction_options' => [-5],
            'sort_order' => 1,
        ]);

        $peta = DeductionCategory::roundMapOfCriteria($this->eventner->id);

        $this->assertSame($penyisihan->id, $peta[$kritKategori->id], 'Babak datang dari rubrik induknya.');
        $this->assertNull($peta[$this->globalKrit->id], 'Kelompok tingkat dari setUp belum dibatasi babak.');

        $baris = ScoreDeduction::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'deduction_criteria_id' => $kritKategori->id,
            'amount' => -5,
        ]);

        $this->assertCount(
            1,
            DeductionCategory::applicableToRound(collect([$baris]), $peta, (int) $penyisihan->id),
            'Di babak rubriknya sendiri baris itu tetap ikut.'
        );
        $this->assertCount(
            0,
            DeductionCategory::applicableToRound(collect([$baris]), $peta, (int) $final->id),
            'Di babak lain baris rubrik babak penyisihan dibuang.'
        );
    }
}
