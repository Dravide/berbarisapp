<?php

namespace Tests\Feature;

use App\Livewire\Public\EventResult;
use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\CompetitionRoundRegistration;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tingkat yang sudah sampai babak final menyajikan hasil FINAL di /hasil.
 *
 * Dua hal yang dijaga:
 *
 *  1. Pemilih grup tidak ditawarkan dan tidak berpengaruh. Final adalah satu
 *     pool se-tingkat — dulu pemilihnya tetap muncul, dan memilih grup malah
 *     menyaring tabel final sampai kosong/salah.
 *  2. Nilai penyisihan tidak lagi ikut dijumlahkan. Juara final ditentukan
 *     nilai final; nilai penyisihan cuma penentu siapa yang lolos.
 */
class PublicResultsFinalOnlyTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $level;

    private CompetitionGroup $groupA;

    private CompetitionGroup $groupB;

    private CompetitionRound $penyisihan;

    private CompetitionRound $final;

    /** @var array{0: AssessmentSubCategory, 1: AssessmentCriteria} */
    private array $rubrikFinal = [];

    private Registration $finalisA;

    private Registration $finalisB;

    private Registration $tersingkir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventner = Eventner::factory()->create([
            'status' => 'approved',
            'slug' => 'lomba-final',
        ]);

        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
            'name' => 'PBB Putra',
        ]);
        $this->level = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
            'name' => 'Regu Inti',
        ]);

        $this->groupA = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup A',
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
            'name' => 'Fase Grup',
            'type' => CompetitionRound::TYPE_PRELIMINARY,
            'sort_order' => 1,
        ]);
        $this->final = CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Final Stage',
            'type' => CompetitionRound::TYPE_FINAL,
            'sort_order' => 2,
        ]);

        $this->finalisA = $this->peserta('SMPN 1', $this->groupA);
        $this->finalisB = $this->peserta('SMPN 2', $this->groupB);
        $this->tersingkir = $this->peserta('SMPN 3', $this->groupA);

        CompetitionRoundRegistration::create([
            'eventner_id' => $this->eventner->id,
            'competition_round_id' => $this->final->id,
            'registration_id' => $this->finalisA->id,
            'competition_group_id' => $this->groupA->id,
        ]);
        CompetitionRoundRegistration::create([
            'eventner_id' => $this->eventner->id,
            'competition_round_id' => $this->final->id,
            'registration_id' => $this->finalisB->id,
            'competition_group_id' => $this->groupB->id,
        ]);
    }

    private function peserta(string $sekolah, CompetitionGroup $grup): Registration
    {
        return Registration::factory()->create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $grup->id,
            'nama_sekolah' => $sekolah,
            'status_berkas' => 'confirmed',
        ]);
    }

    /** @return array{0: AssessmentCategory, 1: AssessmentCriteria} */
    private function rubrik(string $nama, ?int $roundId, ?int $groupId = null): array
    {
        $cat = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'competition_round_id' => $roundId,
            'competition_group_id' => $groupId,
            'name' => $nama,
            'sort_order' => 1,
        ]);
        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $cat->id,
            'name' => 'Sub '.$nama,
            'sort_order' => 1,
        ]);
        $crit = AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Kriteria '.$nama,
            'score_options' => [['score' => 10]],
            'weight' => 1,
            'sort_order' => 1,
        ]);

        return [$cat, $crit];
    }

    private function juara(string $nama, AssessmentSubCategory $sub, bool $publik = true): ChampionCategory
    {
        $champion = ChampionCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => $nama,
            'quantity' => 3,
            'is_public' => $publik,
        ]);
        $champion->assessmentSubCategories()->attach($sub->id);

        return $champion;
    }

    private function nilai(Registration $reg, AssessmentCriteria $criteria, int $skor): void
    {
        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $reg->id,
            'assessment_criteria_id' => $criteria->id,
            'judge_id' => Judge::create([
                'eventner_id' => $this->eventner->id,
                'name' => 'Juri '.$reg->id,
            ])->id,
            'score' => $skor,
            'is_finalized' => true,
        ]);
    }

    private function panel()
    {
        return Livewire::test(EventResult::class, ['slug' => 'lomba-final']);
    }

    /** Tingkat bergrup + final: pemilih grup tidak ditawarkan sama sekali. */
    public function test_tingkat_final_tidak_menawarkan_pemilih_grup()
    {
        [$cat, ] = $this->rubrik('PBB Final', $this->final->id);
        $this->juara('Juara Utama', $cat->subCategories->first());

        $html = $this->panel()->html();

        $this->assertStringNotContainsString('Pilih Grup', $html);
        $this->assertStringNotContainsString('Peringkat Gabungan', $html);
        $this->assertStringNotContainsString('Grup A', $html);
    }

    /** Tingkat yang belum sampai final tetap menampilkan pemilih grup. */
    public function test_tingkat_penyisihan_tetap_menawarkan_pemilih_grup()
    {
        [$cat, ] = $this->rubrik('PBB Penyisihan', $this->penyisihan->id);
        $this->juara('Juara Grup', $cat->subCategories->first());

        $html = $this->panel()->html();

        $this->assertStringContainsString('Pilih Grup', $html);
        $this->assertStringContainsString('Peringkat Gabungan', $html);
        $this->assertStringContainsString('Grup A', $html);
    }

    /** Tingkat tanpa babak (perilaku lama) tidak berubah. */
    public function test_tingkat_tanpa_babak_tidak_berubah()
    {
        [$cat, ] = $this->rubrik('PBB Umum', null);
        $this->juara('Juara Umum', $cat->subCategories->first());

        $panel = $this->panel();

        $this->assertFalse($panel->get('finalOnly'));
        $this->assertStringContainsString('Pilih Grup', $panel->html());
    }

    /** Memilih grup di tingkat final ditolak — final bukan milik grup mana pun. */
    public function test_pilihan_grup_ditolak_di_tingkat_final()
    {
        [$cat, ] = $this->rubrik('PBB Final', $this->final->id);
        $this->juara('Juara Utama', $cat->subCategories->first());

        $this->panel()
            ->call('switchGroup', $this->groupA->id)
            ->assertSet('selectedGroupId', '');
    }

    /** Nilai penyisihan tidak ikut dijumlahkan — yang dinilai hanya nilai final. */
    public function test_nilai_penyisihan_tidak_ikut_dihitung()
    {
        [, $critFinal] = $this->rubrik('PBB Final', $this->final->id);
        [, $critGrup] = $this->rubrik('PBB Fase Grup', $this->penyisihan->id, $this->groupA->id);

        $subFinal = AssessmentCategory::where('name', 'PBB Final')->first()->subCategories->first();
        $this->juara('Juara Utama', $subFinal);

        // Tersingkir unggul di penyisihan, tapi tak pernah dinilai di final —
        // dan memang bukan finalis. Tabel harus memuat finalis saja.
        $this->nilai($this->finalisA, $critFinal, 8);
        $this->nilai($this->finalisB, $critFinal, 7);
        $this->nilai($this->finalisA, $critGrup, 10);
        $this->nilai($this->tersingkir, $critGrup, 9);

        $rankings = $this->panel()->get('allRankings');

        $this->assertCount(1, $rankings);
        $namaSekolah = collect($rankings[0]['participants'])->pluck('participant.nama_sekolah');

        $this->assertTrue($namaSekolah->contains('SMPN 1'));
        $this->assertTrue($namaSekolah->contains('SMPN 2'));
        $this->assertFalse($namaSekolah->contains('SMPN 3'), 'Non-finalis tidak boleh muncul di hasil final.');
    }

    /** Nilai penyisihan tak menambah total: urutan murni dari nilai final. */
    public function test_total_hanya_dari_nilai_final()
    {
        [$catFinal, $critFinal] = $this->rubrik('PBB Final', $this->final->id);
        [, $critGrup] = $this->rubrik('PBB Fase Grup', $this->penyisihan->id, $this->groupA->id);
        $this->juara('Juara Utama', $catFinal->subCategories->first());

        // A menang final (8 > 7) walau B jauh unggul di penyisihan.
        $this->nilai($this->finalisA, $critFinal, 8);
        $this->nilai($this->finalisB, $critFinal, 7);
        $this->nilai($this->finalisA, $critGrup, 1);
        $this->nilai($this->finalisB, $critGrup, 10);

        $peserta = collect($this->panel()->get('allRankings')[0]['participants']);

        $this->assertSame('SMPN 1', $peserta->first()['participant']['nama_sekolah']);
        $this->assertSame(8.0, $peserta->first()['total'], 'Total harus dari nilai final saja.');
    }

    /** Kategori juara dari babak penyisihan tidak tampil di tingkat final. */
    public function test_kategori_juara_penyisihan_tidak_tampil_di_final()
    {
        [$catFinal, $critFinal] = $this->rubrik('PBB Final', $this->final->id);
        [$catGrup, ] = $this->rubrik('PBB Fase Grup', $this->penyisihan->id, $this->groupA->id);

        $this->juara('Juara Final', $catFinal->subCategories->first());
        $this->juara('Juara Grup A', $catGrup->subCategories->first());

        $this->nilai($this->finalisA, $critFinal, 8);

        $rankings = $this->panel()->get('allRankings');
        $nama = collect($rankings)->pluck('champion.name');

        $this->assertTrue($nama->contains('Juara Final'));
        $this->assertFalse($nama->contains('Juara Grup A'), 'Kategori juara babak penyisihan bukan hasil akhir.');
    }

    /**
     * Seri di final diputus nomor urut tampil final, bukan nilai penyisihan.
     * Nilai kriteria di luar kategori juara dulu ikut jadi kunci urutan, jadi
     * pasukan yang unggul di penyisihan bisa menyalip walau nilainya sama.
     */
    public function test_seri_di_final_tidak_diputus_nilai_penyisihan()
    {
        [$catFinal, $critFinal] = $this->rubrik('PBB Final', $this->final->id);
        [, $critGrup] = $this->rubrik('PBB Fase Grup', $this->penyisihan->id, $this->groupA->id);
        $this->juara('Juara Utama', $catFinal->subCategories->first());

        // Nilai final sama persis; A unggul jauh di kriteria luar rubrik final.
        $this->nilai($this->finalisA, $critFinal, 8);
        $this->nilai($this->finalisB, $critFinal, 8);
        $this->nilai($this->finalisA, $critGrup, 10);

        // Nomor urut tampil final: B lebih dulu.
        $this->finalisA->update(['urutan_tampil' => 2]);
        $this->finalisB->update(['urutan_tampil' => 1]);

        $peserta = collect($this->panel()->get('allRankings')[0]['participants']);

        $this->assertSame('SMPN 2', $peserta->first()['participant']['nama_sekolah']);
    }
}
