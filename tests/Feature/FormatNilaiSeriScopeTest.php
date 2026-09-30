<?php

namespace Tests\Feature;

use App\Livewire\Eventner\FormatNilai\Builder;
use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\CompetitionSeries;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\MemeriksaToast;
use Tests\TestCase;

/**
 * Penanda seri pada rubrik di layar Format Nilai.
 *
 * Seri inilah yang dipilih panitia di kartu rubrik, dan sejak itu pula lembar
 * nilai sebuah pasukan ditentukan. Dua hal yang dijaga:
 *
 *  1. Seri dari tingkat/event lain tidak boleh masuk lewat parameter komponen.
 *  2. Rubrik tidak boleh dipindah seri setelah ada nilai yang masuk. Nilai
 *     terikat pada kriteria, bukan pada seri — memindahkannya membuat pasukan
 *     yang tak pernah dinilai tiba-tiba mewarisi angka orang lain, dan pasukan
 *     yang memang dinilai kehilangan angkanya dari lembar.
 */
class FormatNilaiSeriScopeTest extends TestCase
{
    use RefreshDatabase;
    use MemeriksaToast;

    private Eventner $eventner;

    private CompetitionCategory $level;

    private CompetitionSeries $seriA;

    private CompetitionSeries $seriB;

    private CompetitionGroup $groupA;

    private CompetitionRound $penyisihan;

    private AssessmentCategory $rubrik;

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

        $this->seriA = CompetitionSeries::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Seri A',
            'sort_order' => 1,
        ]);
        $this->seriB = CompetitionSeries::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Seri B',
            'sort_order' => 2,
        ]);

        // Grup tetap ada di data — ia tidak lagi jadi penanda rubrik, tapi
        // kolomnya masih dipakai peringkat & undian dan tidak boleh terhapus
        // diam-diam saat panitia menyimpan seri.
        $this->groupA = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup A',
            'sort_order' => 1,
        ]);

        $this->penyisihan = CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Penyisihan',
            'type' => CompetitionRound::TYPE_PRELIMINARY,
            'sort_order' => 1,
        ]);

        $this->rubrik = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $this->groupA->id,
            'competition_series_id' => $this->seriA->id,
            'name' => 'PBB Penyisihan',
            'sort_order' => 1,
        ]);

        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $this->rubrik->id,
            'name' => 'Gerakan Ditempat',
            'sort_order' => 1,
        ]);

        $this->kriteria = AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Sikap Sempurna',
            'score_options' => [['score' => 10]],
            'weight' => 1,
            'sort_order' => 1,
        ]);
    }

    private function builder()
    {
        return Livewire::test(Builder::class);
    }

    /** Satu nilai masuk — cukup untuk mengunci penanda rubrik. */
    private function beriNilai(): void
    {
        $juri = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Budi']);
        $peserta = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_series_id' => $this->seriA->id,
            'nama_sekolah' => 'SMPN 1',
        ]);

        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'judge_id' => $juri->id,
            'registration_id' => $peserta->id,
            'assessment_criteria_id' => $this->kriteria->id,
            'score' => '85',
        ]);
    }

    public function test_simpan_seri_dari_dropdown_tersimpan_di_rubrik()
    {
        $this->builder()
            ->set("rubricSeriesId.{$this->rubrik->id}", (string) $this->seriB->id)
            ->call('saveRubricScope', $this->rubrik->id);

        $this->assertSame($this->seriB->id, (int) $this->rubrik->fresh()->competition_series_id);
    }

    /**
     * Kolom grup tidak ditulis ulang. Dropdown grup sudah tidak ada, jadi
     * menyimpannya kembali berarti mengirim null dari field yang tak pernah
     * dirender — dan catatan asal seri itu hilang tanpa satu pun peringatan.
     */
    public function test_menyimpan_seri_tidak_menghapus_grup_yang_sudah_tercatat()
    {
        $this->builder()
            ->set("rubricSeriesId.{$this->rubrik->id}", (string) $this->seriB->id)
            ->call('saveRubricScope', $this->rubrik->id);

        $this->assertSame($this->groupA->id, (int) $this->rubrik->fresh()->competition_group_id);
    }

    public function test_seri_kosong_berarti_berlaku_semua_seri()
    {
        $this->builder()
            ->set("rubricSeriesId.{$this->rubrik->id}", '')
            ->call('saveRubricScope', $this->rubrik->id);

        $this->assertNull($this->rubrik->fresh()->competition_series_id);
    }

    public function test_babak_tetap_ikut_tersimpan()
    {
        $this->builder()
            ->set("rubricSeriesId.{$this->rubrik->id}", (string) $this->seriA->id)
            ->set("rubricRoundId.{$this->rubrik->id}", (string) $this->penyisihan->id)
            ->call('saveRubricScope', $this->rubrik->id);

        $this->assertSame($this->penyisihan->id, (int) $this->rubrik->fresh()->competition_round_id);
    }

    /** Seri dari event lain tidak boleh masuk lewat parameter komponen. */
    public function test_seri_event_lain_ditolak()
    {
        $lain = Eventner::factory()->create(['status' => 'approved']);
        $parentLain = CompetitionCategory::factory()->create(['eventner_id' => $lain->id, 'parent_id' => null]);
        $levelLain = CompetitionCategory::factory()->create([
            'eventner_id' => $lain->id,
            'parent_id' => $parentLain->id,
        ]);
        $seriLain = CompetitionSeries::create([
            'eventner_id' => $lain->id,
            'competition_category_id' => $levelLain->id,
            'name' => 'Seri Tetangga',
            'sort_order' => 1,
        ]);

        $panel = $this->builder()
            ->set("rubricSeriesId.{$this->rubrik->id}", (string) $seriLain->id)
            ->call('saveRubricScope', $this->rubrik->id);

        $this->assertAdaToast($panel, 'Seri yang dipilih bukan milik tingkat lomba ini.');

        $this->assertSame($this->seriA->id, (int) $this->rubrik->fresh()->competition_series_id);
    }

    /** Seri tingkat lain di event yang sama pun ditolak. */
    public function test_seri_tingkat_lain_ditolak()
    {
        $indukLain = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
        ]);
        $seriTingkatLain = CompetitionSeries::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $indukLain->id,
            'name' => 'Seri Tingkat Lain',
            'sort_order' => 1,
        ]);

        $this->builder()
            ->set("rubricSeriesId.{$this->rubrik->id}", (string) $seriTingkatLain->id)
            ->call('saveRubricScope', $this->rubrik->id);

        $this->assertSame($this->seriA->id, (int) $this->rubrik->fresh()->competition_series_id);
    }

    // ---------- inti tabungan ini: kunci pindah-rubrik ----------

    /**
     * Nilai sudah masuk → rubrik tidak boleh pindah seri.
     *
     * Inilah celah yang sudah ada sebelum fitur seri: `saveRubricScope()`
     * menulis penanda tanpa melihat apakah kriterianya sudah dinilai.
     */
    public function test_pindah_seri_ditolak_setelah_ada_nilai()
    {
        $this->beriNilai();

        $panel = $this->builder()
            ->set("rubricSeriesId.{$this->rubrik->id}", (string) $this->seriB->id)
            ->call('saveRubricScope', $this->rubrik->id);

        $this->assertAdaToast($panel, 'Tidak bisa memindahkan rubrik ke seri lain');

        $this->assertSame($this->seriA->id, (int) $this->rubrik->fresh()->competition_series_id);
    }

    /** Mengosongkan seri juga memindahkan rubrik — ikut terkunci. */
    public function test_mengosongkan_seri_juga_ditolak_setelah_ada_nilai()
    {
        $this->beriNilai();

        $this->builder()
            ->set("rubricSeriesId.{$this->rubrik->id}", '')
            ->call('saveRubricScope', $this->rubrik->id);

        $this->assertSame($this->seriA->id, (int) $this->rubrik->fresh()->competition_series_id);
    }

    /**
     * Menyimpan ulang seri yang SAMA tetap boleh — itulah satu-satunya cara
     * mengubah babaknya setelah nilai masuk (mis. salah pilih babak).
     */
    public function test_menyimpan_ulang_seri_yang_sama_tetap_boleh()
    {
        $this->beriNilai();

        $this->builder()
            ->set("rubricSeriesId.{$this->rubrik->id}", (string) $this->seriA->id)
            ->set("rubricRoundId.{$this->rubrik->id}", (string) $this->penyisihan->id)
            ->call('saveRubricScope', $this->rubrik->id)
            ->assertDontSee('sudah ada nilai yang masuk');

        $this->assertSame($this->penyisihan->id, (int) $this->rubrik->fresh()->competition_round_id);
    }

    /** Nilai milik rubrik lain tidak mengunci rubrik ini. */
    public function test_nilai_rubrik_lain_tidak_mengunci()
    {
        $this->beriNilai();

        $rubrikLain = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'competition_series_id' => $this->seriA->id,
            'name' => 'PBB Cadangan',
            'sort_order' => 2,
        ]);

        $this->builder()
            ->set("rubricSeriesId.{$rubrikLain->id}", (string) $this->seriB->id)
            ->call('saveRubricScope', $rubrikLain->id);

        $this->assertSame($this->seriB->id, (int) $rubrikLain->fresh()->competition_series_id);
    }

    /** Rubrik tanpa kriteria belum punya nilai apa pun — selalu bisa dipindah. */
    public function test_rubrik_tanpa_kriteria_selalu_bisa_pindah_seri()
    {
        $kosong = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'competition_series_id' => $this->seriA->id,
            'name' => 'PBB Baru',
            'sort_order' => 3,
        ]);

        $this->builder()
            ->set("rubricSeriesId.{$kosong->id}", (string) $this->seriB->id)
            ->call('saveRubricScope', $kosong->id);

        $this->assertSame($this->seriB->id, (int) $kosong->fresh()->competition_series_id);
    }
}
