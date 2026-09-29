<?php

namespace Tests\Feature;

use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\User;
use App\Services\ChampionCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Peringkat per grup dan per babak.
 *
 * Dua daftar juara yang berbeda dari satu tingkat yang sama:
 *  - juara grup  = peserta Grup A saja (rubriknya sendiri ditentukan serinya);
 *  - juara final = rubrik babak final saja, peserta yang lolos saja.
 *
 * Cakupan grup tetap bekerja — tapi sebagai saringan PESERTA, bukan rubrik.
 * Sejak lembar nilai ditentukan SERI dan satu grup boleh memuat beberapa seri,
 * menyaring rubrik per grup justru membuang kriteria yang dipakai peserta grup
 * itu sendiri.
 *
 * Logika sort-nya sendiri TIDAK disentuh fitur ini (audit #48 sudah
 * membaikannya) — yang diuji di sini adalah cakupan pesertanya.
 */
class GroupRankingTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $level;

    private CompetitionGroup $groupA;

    private CompetitionGroup $groupB;

    private CompetitionRound $final;

    private CompetitionRound $penyisihan;

    /** @var array{0: AssessmentSubCategory, 1: AssessmentCriteria} rubrik penyisihan tingkat */
    private array $rubrikPenyisihan;

    private ChampionCalculator $calc;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->eventner()->create(['is_active' => true]);
        $this->eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

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

        $this->rubrikPenyisihan = $this->makeRubrik('PBB Penyisihan', null, 1);
        $this->calc = app(ChampionCalculator::class);
    }

    /**
     * @return array{0: AssessmentSubCategory, 1: AssessmentCriteria}
     */
    private function makeRubrik(string $name, ?CompetitionRound $round, int $weight = 1): array
    {
        $category = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'competition_round_id' => $round?->id,
            'name' => $name,
            'sort_order' => 1,
        ]);
        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $category->id,
            'name' => 'Sub ' . $name,
            'sort_order' => 1,
        ]);
        $criteria = AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Kriteria ' . $name,
            'score_options' => [['score' => 10], ['score' => 20]],
            'weight' => $weight,
            'sort_order' => 1,
        ]);

        return [$sub, $criteria];
    }

    private function makeChampion(string $name, AssessmentSubCategory $sub, int $quantity = 3): ChampionCategory
    {
        $champion = ChampionCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => $name,
            'quantity' => $quantity,
            'is_public' => true,
        ]);
        $champion->assessmentSubCategories()->sync([$sub->id]);

        return $champion;
    }

    private function makeParticipant(string $school, ?CompetitionGroup $group, ?int $urutan = null): Registration
    {
        return Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $group?->id,
            'nama_sekolah' => $school,
            'urutan_tampil' => $urutan,
        ]);
    }

    private function score(Registration $reg, AssessmentCriteria $criteria, int $score): void
    {
        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $reg->id,
            'assessment_criteria_id' => $criteria->id,
            'judge_id' => \App\Models\Judge::create([
                'eventner_id' => $this->eventner->id,
                'name' => 'Juri ' . $reg->id,
            ])->id,
            'score' => $score,
            'is_finalized' => true,
        ]);
    }

    public function test_peringkat_grup_hanya_memuat_peserta_grup_itu()
    {
        [, $kriteria] = $this->rubrikPenyisihan;
        $champion = $this->makeChampion('Juara Grup A', $this->rubrikPenyisihan[0]);

        $a1 = $this->makeParticipant('SMPN 1', $this->groupA);
        $a2 = $this->makeParticipant('SMPN 2', $this->groupA);
        $b1 = $this->makeParticipant('SMPN 7', $this->groupB);

        $this->score($a1, $kriteria, 20);
        $this->score($a2, $kriteria, 10);
        // Nilai tertinggi justru di Grup B — harus tetap tidak muncul.
        $this->score($b1, $kriteria, 20);

        [, , $rankingsA] = $this->calc->rankings($champion, $this->level->id, $this->groupA->id);

        $this->assertCount(2, $rankingsA);
        $this->assertSame(
            [$a1->id, $a2->id],
            array_map(fn ($r) => $r['registration']->id, $rankingsA)
        );

        [, , $rankingsB] = $this->calc->rankings($champion, $this->level->id, $this->groupB->id);
        $this->assertCount(1, $rankingsB);
        $this->assertSame($b1->id, $rankingsB[0]['registration']->id);
    }

    public function test_tanpa_filter_grup_menggabungkan_seluruh_peserta()
    {
        [, $kriteria] = $this->rubrikPenyisihan;
        $champion = $this->makeChampion('Juara Umum', $this->rubrikPenyisihan[0]);

        $a1 = $this->makeParticipant('SMPN 1', $this->groupA);
        $b1 = $this->makeParticipant('SMPN 7', $this->groupB);
        $tanpaGrup = $this->makeParticipant('SMPN 9', null);

        $this->score($a1, $kriteria, 20);
        $this->score($b1, $kriteria, 10);
        $this->score($tanpaGrup, $kriteria, 10);

        [, , $rankings] = $this->calc->rankings($champion, $this->level->id);

        $this->assertCount(3, $rankings);
    }

    public function test_peserta_belum_bergrup_tidak_ikut_saat_filter_grup_aktif()
    {
        [, $kriteria] = $this->rubrikPenyisihan;
        $champion = $this->makeChampion('Juara Grup A', $this->rubrikPenyisihan[0]);

        $a1 = $this->makeParticipant('SMPN 1', $this->groupA);
        $belum = $this->makeParticipant('SMPN 9', null);

        $this->score($a1, $kriteria, 10);
        $this->score($belum, $kriteria, 20);

        [, , $rankings] = $this->calc->rankings($champion, $this->level->id, $this->groupA->id);

        $this->assertCount(1, $rankings);
        $this->assertSame($a1->id, $rankings[0]['registration']->id);
    }

    public function test_urutan_tampil_tetap_jadi_pemecah_nilai_sama_terakhir()
    {
        [, $kriteria] = $this->rubrikPenyisihan;
        $champion = $this->makeChampion('Juara Grup A', $this->rubrikPenyisihan[0]);

        // Nilai identik → tiebreak jatuh ke urutan_tampil.
        $kedua = $this->makeParticipant('SMPN 2', $this->groupA, 2);
        $pertama = $this->makeParticipant('SMPN 1', $this->groupA, 1);

        $this->score($kedua, $kriteria, 20);
        $this->score($pertama, $kriteria, 20);

        [, , $rankings] = $this->calc->rankings($champion, $this->level->id, $this->groupA->id);

        $this->assertSame($pertama->id, $rankings[0]['registration']->id);
        // Nilai identik → peringkat nilai sama, bukan 1 dan 2.
        $this->assertSame(1, $rankings[0]['rank']);
        $this->assertSame(1, $rankings[1]['rank']);
    }

    public function test_kuota_juara_dipotong_per_grup()
    {
        [, $kriteria] = $this->rubrikPenyisihan;
        $champion = $this->makeChampion('Juara Grup A', $this->rubrikPenyisihan[0], 2);

        foreach (range(1, 4) as $i) {
            $reg = $this->makeParticipant('SMPN A' . $i, $this->groupA);
            $this->score($reg, $kriteria, 20 - $i);
        }

        [, , $winners] = $this->calc->winners($champion, $this->level->id, $this->groupA->id);

        $this->assertCount(2, $winners);
        $this->assertSame([1, 2], array_column($winners, 'rank'));
    }

    public function test_juara_grup_dan_juara_final_adalah_dua_daftar_berbeda()
    {
        // Penyisihan menempatkan B1 di puncak; final menempatkan A1 di puncak.
        [$subPenyisihan, $kriteriaPenyisihan] = $this->rubrikPenyisihan;
        [$subFinal, $kriteriaFinal] = $this->makeRubrik('PBB Final', $this->final);
        $kriteriaFinal->update(['weight' => 5]);

        $juaraGrupA = $this->makeChampion('Juara Grup A', $subPenyisihan);
        $juaraFinal = $this->makeChampion('Juara Final', $subFinal);

        $a1 = $this->makeParticipant('SMPN 1', $this->groupA);
        $b1 = $this->makeParticipant('SMPN 7', $this->groupB);

        $this->score($a1, $kriteriaPenyisihan, 10);
        $this->score($b1, $kriteriaPenyisihan, 20);

        // Hanya A1 yang lolos dan dinilai di final.
        $this->score($a1, $kriteriaFinal, 20);
        \App\Models\CompetitionRoundRegistration::create([
            'eventner_id' => $this->eventner->id,
            'competition_round_id' => $this->final->id,
            'registration_id' => $a1->id,
            'competition_group_id' => $this->groupA->id,
            'seed' => 1,
            'preliminary_total' => 10,
        ]);

        [, , $peringkatGrupA] = $this->calc->rankings($juaraGrupA, $this->level->id, $this->groupA->id);
        [, , $peringkatFinal] = $this->calc->rankings(
            $juaraFinal,
            $this->level->id,
            null,
            // Peserta final = yang tercatat lolos.
        );

        $this->assertSame($a1->id, $peringkatGrupA[0]['registration']->id);

        // Juara final dari rubrik final: B1 tidak punya nilai final sama sekali,
        // jadi tidak muncul (total 0 dibuang).
        $finalIds = array_map(fn ($r) => $r['registration']->id, $peringkatFinal);
        $this->assertContains($a1->id, $finalIds);
        $this->assertNotContains($b1->id, $finalIds);
    }

    /**
     * Penanda grup pada rubrik tidak lagi menyembunyikan kategori juara.
     *
     * Dulu "Juara Grup A" hanya tampil di Grup A karena rubriknya bertanda Grup
     * A. Sejak lembar nilai ditentukan SERI, tanda grup di rubrik tidak lagi
     * membatasi siapa yang boleh dinilai — peserta Grup B yang berseri A memang
     * dinilai rubrik itu. Menyembunyikan kategorinya membuat juara sah hilang
     * dari layar. Cakupan grup tetap bekerja, tapi sebagai saringan PESERTA.
     */
    public function test_penanda_grup_pada_rubrik_tidak_lagi_menyembunyikan_kategori_juara()
    {
        [$subA] = $this->makeRubrik('PBB Grup A', null);
        // Ubah rubrik itu jadi milik Grup A.
        AssessmentSubCategory::where('name', 'Sub PBB Grup A')
            ->first()
            ->category
            ->update(['competition_group_id' => $this->groupA->id]);

        $championA = ChampionCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Juara Grup A',
            'quantity' => 3,
        ]);
        $championA->assessmentSubCategories()->sync([$subA->id]);
        $championA->load('assessmentSubCategories.category');

        $this->assertTrue($championA->isVisibleFor($this->level->id, $this->groupA->id));
        $this->assertTrue(
            $championA->isVisibleFor($this->level->id, $this->groupB->id),
            'Tanda grup di rubrik tidak lagi membatasi — lihat docblock tes.'
        );
    }

    /** Babak tetap menyaring: itulah yang memisahkan juara penyisihan dari final. */
    public function test_penanda_babak_tetap_menyembunyikan_kategori_juara()
    {
        [$subFinal] = $this->makeRubrik('PBB Final', null);
        AssessmentSubCategory::where('name', 'Sub PBB Final')
            ->first()
            ->category
            ->update(['competition_round_id' => $this->final->id]);

        $championFinal = ChampionCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Juara Final',
            'quantity' => 3,
        ]);
        $championFinal->assessmentSubCategories()->sync([$subFinal->id]);
        $championFinal->load('assessmentSubCategories.category');

        $this->assertTrue($championFinal->isVisibleFor($this->level->id, null, $this->final->id));
        $this->assertFalse(
            $championFinal->isVisibleFor($this->level->id, null, $this->penyisihan->id),
            'Rubrik babak Final tampil di lingkup penyisihan.'
        );
    }

    public function test_kategori_juara_rubrik_tanpa_grup_tampil_di_semua_grup()
    {
        [$sub] = $this->makeRubrik('PBB Umum', null);

        $champion = ChampionCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Juara Umum',
            'quantity' => 3,
        ]);
        $champion->assessmentSubCategories()->sync([$sub->id]);
        $champion->load('assessmentSubCategories.category');

        $this->assertTrue($champion->isVisibleFor($this->level->id, $this->groupA->id));
        $this->assertTrue($champion->isVisibleFor($this->level->id, $this->groupB->id));
        $this->assertTrue($champion->isVisibleFor($this->level->id));
    }
}
