<?php

namespace Tests\Feature;

use App\Livewire\Eventner\Scoring\Index as ScoringIndex;
use App\Livewire\Public\JudgeScoring\Index as JudgeScoringIndex;
use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentSubCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\CompetitionRoundRegistration;
use App\Models\CompetitionSeries;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Seri diabaikan di babak FINAL.
 *
 * Babak final adalah satu pool se-tingkat: tanpa pembagian grup, tanpa
 * pembagian seri. Rubrik final yang dibuat panitia pun sengaja TIDAK bertanda
 * seri (lihat ScoringController::assessmentCategoriesFor). Selama forEntry()
 * masih menyaring seri, finalis hanya melihat rubrik berseri miliknya sendiri —
 * jadi begitu panitia menandai rubrik final dengan satu seri, finalis dari seri
 * lain mendapat lembar KOSONG tanpa pesan apa pun.
 *
 * Yang dijaga di sini karena itu bukan cuma tampilannya:
 *
 *  1. Di babak final, seluruh rubrik babak itu terbuka untuk semua finalis,
 *     apa pun serinya — termasuk finalis yang belum dibagi seri.
 *  2. Di babak penyisihan, saringan seri tetap berlaku seperti sebelumnya.
 *     Kalau tidak, pemisahan seri kehilangan artinya.
 *  3. Panel panitia dan yang dituntut finalisasi memakai himpunan kriteria yang
 *     sama — syarat mutlak, sebab satu kriteria lebih banyak di sisi penuntut
 *     membuat tombol Finalisasi mustahil ditekan tanpa pesan kesalahan.
 */
class FinalRoundSeriesIgnoredTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $level;

    private CompetitionSeries $seriA;

    private CompetitionSeries $seriB;

    private CompetitionRound $penyisihan;

    private CompetitionRound $final;

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
            'name' => 'Final',
            'type' => CompetitionRound::TYPE_FINAL,
            'sort_order' => 2,
        ]);
    }

    /** Rubrik + satu kriteria bernama unik, supaya bisa dicari di HTML. */
    private function rubrik(string $name, ?CompetitionSeries $series, CompetitionRound $round): AssessmentCategory
    {
        $category = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'competition_series_id' => $series?->id,
            'competition_round_id' => $round->id,
            'name' => $name,
            'sort_order' => 1,
        ]);

        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $category->id,
            'name' => 'Sub ' . $name,
        ]);

        AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Kriteria ' . $name,
            'score_options' => [['score' => 10], ['score' => 20]],
            'weight' => 1,
            'sort_order' => 1,
        ]);

        return $category;
    }

    private function peserta(string $sekolah, ?CompetitionSeries $seri): Registration
    {
        return Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_series_id' => $seri?->id,
            'nama_sekolah' => $sekolah,
        ]);
    }

    /** Catat peserta sebagai finalis — satu-satunya sumber daftar babak final. */
    private function loloskan(Registration $reg): void
    {
        CompetitionRoundRegistration::create([
            'eventner_id' => $this->eventner->id,
            'competition_round_id' => $this->final->id,
            'registration_id' => $reg->id,
        ]);
    }

    /** Panel Input Nilai dengan chip Final terbuka, peserta sudah dipilih. */
    private function panelFinal(Registration $peserta)
    {
        return Livewire::test(ScoringIndex::class, ['selectedCategoryId' => $this->level->id])
            ->call('selectScope', 'final')
            ->call('selectParticipant', $peserta->id);
    }

    /**
     * Kasus utama: rubrik final bertanda Seri A tetap terbuka untuk finalis
     * Seri B. Sebelum ini lembarnya kosong — finalis hanya melihat rubrik
     * berseri dirinya sendiri.
     */
    public function test_rubrik_final_berseri_terbuka_untuk_finalis_seri_lain()
    {
        $this->rubrik('Rubrik Final Seri A', $this->seriA, $this->final);

        $finalis = $this->peserta('SMA BRAVO', $this->seriB);
        $this->loloskan($finalis);

        $this->panelFinal($finalis)->assertSee('Kriteria Rubrik Final Seri A');
    }

    /**
     * Finalis yang belum dibagi seri pun mendapat lembar final yang sama.
     * Dulu peserta tanpa seri hanya melihat rubrik TANPA seri, jadi rubrik
     * final berseri lenyap untuknya.
     */
    public function test_finalis_tanpa_seri_melihat_rubrik_final_berseri()
    {
        $this->rubrik('Rubrik Final Seri A', $this->seriA, $this->final);

        $finalis = $this->peserta('SMA POLOS', null);
        $this->loloskan($finalis);

        $this->panelFinal($finalis)->assertSee('Kriteria Rubrik Final Seri A');
    }

    /** Penjaga regresi: di penyisihan saringan seri TIDAK dilonggarkan. */
    public function test_rubrik_penyisihan_masih_menyaring_seri()
    {
        $this->rubrik('Rubrik Penyisihan Seri A', $this->seriA, $this->penyisihan);
        $this->rubrik('Rubrik Penyisihan Seri B', $this->seriB, $this->penyisihan);

        $pesertaB = $this->peserta('SMA BRAVO', $this->seriB);

        Livewire::test(ScoringIndex::class, ['selectedCategoryId' => $this->level->id])
            ->call('selectScope', 'all')
            ->call('selectParticipant', $pesertaB->id)
            ->assertSee('Kriteria Rubrik Penyisihan Seri B')
            ->assertDontSee('Kriteria Rubrik Penyisihan Seri A');
    }

    /**
     * Panel tidak boleh meminta satu kriteria lebih banyak daripada yang
     * dituntut finalize(). finalize() menolak selama ada kriteria kosong, jadi
     * selisih sekecil apa pun membuat tombol Finalisasi mustahil ditekan —
     * tanpa satu pun pesan yang menjelaskan sebabnya.
     */
    public function test_kriteria_panel_final_sama_dengan_yang_dituntut_finalisasi()
    {
        $juri = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri Final']);
        $rubrik = $this->rubrik('Rubrik Final Seri A', $this->seriA, $this->final);
        $rubrik->syncRubricJudges([$juri->id]);

        $finalis = $this->peserta('SMA BRAVO', $this->seriB);
        $this->loloskan($finalis);

        $dariFinalisasi = AssessmentCategory::rubrikUntukPeserta(
            $this->eventner->id,
            $this->level->id,
            $finalis->competition_series_id,
            $this->final->id,
            $juri->id,
        )->get()
            ->flatMap(fn ($cat) => $cat->subCategories->flatMap(fn ($sub) => $sub->criterias->pluck('id')))
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        $html = $this->panelFinal($finalis)->html();

        // Tiap kriteria yang dituntut benar-benar tampil di panel.
        foreach ($dariFinalisasi as $criteriaId) {
            $nama = AssessmentCriteria::find($criteriaId)->name;
            $this->assertStringContainsString($nama, $html);
        }

        // Dan sebaliknya: panel tidak memuat kriteria di luar daftar itu.
        $kriteriaDiLuarDaftar = AssessmentCriteria::whereHas(
            'subCategory.category',
            fn ($q) => $q->where('eventner_id', $this->eventner->id)
        )->whereNotIn('id', $dariFinalisasi)->pluck('name');

        foreach ($kriteriaDiLuarDaftar as $nama) {
            $this->assertStringNotContainsString($nama, $html);
        }
    }

    /**
     * Tablet juri: di babak final penanda seri tidak lagi tampil, dan di
     * penyisihan tetap tampil seperti semula.
     */
    public function test_tablet_tidak_menandai_seri_di_babak_final()
    {
        $juriFinal = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri Final']);
        CompetitionGroup::syncJudges($this->level->id, CompetitionGroup::SCOPE_FINAL, null, [$juriFinal->id]);

        $this->rubrik('Rubrik Final Seri A', $this->seriA, $this->final);

        $finalis = $this->peserta('SMA BRAVO', $this->seriB);
        $this->loloskan($finalis);

        Livewire::test(JudgeScoringIndex::class, ['token' => $juriFinal->access_token])
            ->call('selectCategory', $this->level->id)
            ->call('switchRound', $this->final->id)
            ->assertSee('SMA BRAVO')
            ->assertDontSee('Seri B')
            // Lembar finalnya tetap terbuka walau rubriknya bertanda seri lain.
            ->call('selectParticipant', $finalis->id)
            ->assertSee('Kriteria Rubrik Final Seri A');
    }

    /** Babak penyisihan tidak ikut berubah: penanda serinya masih ada. */
    public function test_tablet_masih_menandai_seri_di_babak_penyisihan()
    {
        $grup = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup 1',
        ]);

        $juriGrup = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri Grup']);
        CompetitionGroup::syncJudges($this->level->id, CompetitionGroup::SCOPE_GROUP, $grup->id, [$juriGrup->id]);

        $pesertaB = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $grup->id,
            'competition_series_id' => $this->seriB->id,
            'nama_sekolah' => 'SMA CHARLIE',
        ]);

        Livewire::test(JudgeScoringIndex::class, ['token' => $juriGrup->access_token])
            ->call('selectCategory', $this->level->id)
            ->call('switchRound', $this->penyisihan->id)
            ->assertSee($pesertaB->nama_sekolah)
            ->assertSee('Seri B');
    }

    /**
     * Penjaga regresi yang tak ada hubungannya dengan seri, tapi ketahuan
     * bersamaan: tombol babak tablet hidup di daftar PESERTA — tepat saat belum
     * ada peserta terpilih — dan switchRound() dulu memuat kriteria di situ juga.
     * Pembacaan registration yang belum ada itu melempar 500 hanya karena juri
     * mengetuk pilihan babaknya.
     */
    public function test_tablet_bisa_pindah_babak_sebelum_memilih_peserta()
    {
        $juri = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri Babak']);
        CompetitionGroup::syncJudges($this->level->id, CompetitionGroup::SCOPE_LEVEL, null, [$juri->id]);

        Livewire::test(JudgeScoringIndex::class, ['token' => $juri->access_token])
            ->call('selectCategory', $this->level->id)
            ->call('switchRound', $this->final->id)
            ->assertSet('selectedRoundId', $this->final->id)
            ->call('switchRound', $this->penyisihan->id)
            ->assertSet('selectedRoundId', $this->penyisihan->id);
    }
}
