<?php

namespace Tests\Feature;

use App\Livewire\Eventner\ChampionCategory\Index;
use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Dropdown lingkup juara di /eventner/champion-categories.
 *
 * Isinya "juara siapa" — Grup A, Grup B, Final — bukan "babak mana". Babak
 * penyisihan tidak berdiri sendiri sebagai pilihan karena lingkupnya sudah
 * terwakili grup: juara grup memang dihitung dari nilai penyisihan.
 */
class ChampionScopeFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Eventner $eventner;

    private CompetitionCategory $level;

    private CompetitionGroup $groupA;

    private CompetitionGroup $groupB;

    private CompetitionRound $penyisihan;

    private CompetitionRound $final;

    /** @var array{0: AssessmentSubCategory, 1: AssessmentCriteria} */
    private array $rubrikGrupA = [];

    /** @var array{0: AssessmentSubCategory, 1: AssessmentCriteria} */
    private array $rubrikGrupB = [];

    /** @var array{0: AssessmentSubCategory, 1: AssessmentCriteria} */
    private array $rubrikFinal = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->eventner()->create(['is_active' => true]);
        $this->eventner = Eventner::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'approved',
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

        $this->rubrikGrupA = $this->makeRubrik('PBB Grup A', $this->groupA->id, $this->penyisihan->id);
        $this->rubrikGrupB = $this->makeRubrik('PBB Grup B', $this->groupB->id, $this->penyisihan->id);
        $this->rubrikFinal = $this->makeRubrik('PBB Final', null, $this->final->id);
    }

    /** @return array{0: AssessmentSubCategory, 1: AssessmentCriteria} */
    private function makeRubrik(string $name, ?int $groupId, ?int $roundId, ?int $seriesId = null): array
    {
        $cat = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $groupId,
            'competition_round_id' => $roundId,
            'competition_series_id' => $seriesId,
            'name' => $name,
            'sort_order' => 1,
        ]);
        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $cat->id,
            'name' => 'Sub '.$name,
            'sort_order' => 1,
        ]);
        $crit = AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Kriteria '.$name,
            'score_options' => [['score' => 10]],
            'weight' => 1,
            'sort_order' => 1,
        ]);

        return [$sub, $crit];
    }

    private function makeChampion(string $name, AssessmentSubCategory $sub): ChampionCategory
    {
        $champion = ChampionCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => $name,
            'quantity' => 3,
        ]);
        $champion->assessmentSubCategories()->sync([$sub->id]);

        return $champion;
    }

    private function makeParticipant(string $school, ?CompetitionGroup $group): Registration
    {
        return Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $group?->id,
            'nama_sekolah' => $school,
        ]);
    }

    private function score(Registration $reg, AssessmentCriteria $criteria, int $score): void
    {
        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $reg->id,
            'assessment_criteria_id' => $criteria->id,
            'judge_id' => Judge::create([
                'eventner_id' => $this->eventner->id,
                'name' => 'Juri '.$reg->id,
            ])->id,
            'score' => $score,
            'is_finalized' => true,
        ]);
    }

    protected function panel()
    {
        return Livewire::actingAs($this->user)
            ->test(Index::class)
            ->set('selectedCompetitionCategoryId', (string) $this->level->id);
    }

    public function test_daftar_lingkup_berisi_grup_dan_final_saja()
    {
        $scopes = $this->panel()->viewData('scopes');

        $this->assertSame(
            ['Grup A', 'Grup B', 'Final Stage'],
            $scopes->pluck('label')->all(),
            'Babak penyisihan tidak berdiri sendiri: lingkupnya sudah diwakili grup.'
        );
    }

    public function test_lingkup_grup_memakai_babak_penyisihan()
    {
        $scopes = $this->panel()->viewData('scopes');

        // Juara grup dihitung dari nilai penyisihan, jadi pasangan (grup,
        // babak) yang dibawa tiap pilihan grup harus babak penyisihan.
        $this->assertSame(
            (string) $this->penyisihan->id,
            $scopes->firstWhere('label', 'Grup A')['round_id']
        );
        $this->assertSame(
            (string) $this->final->id,
            $scopes->firstWhere('label', 'Final Stage')['round_id']
        );
    }

    public function test_lingkup_grup_hanya_memeringkat_anggota_grup_itu()
    {
        $champion = $this->makeChampion('Juara Grup', $this->rubrikGrupA[0]);

        $a1 = $this->makeParticipant('SMPN 1', $this->groupA);
        $b1 = $this->makeParticipant('SMPN 2', $this->groupB);
        $this->score($a1, $this->rubrikGrupA[1], 30);
        $this->score($b1, $this->rubrikGrupB[1], 99);

        $rankings = $this->panel()
            ->set('selectedScopeId', 'grup-'.$this->groupA->id)
            ->viewData('rankings');

        $this->assertSame(
            ['SMPN 1'],
            $rankings[$champion->id]->pluck('participant.nama_sekolah')->all(),
            'Peserta Grup B tidak boleh masuk peringkat Grup A walau nilainya lebih tinggi.'
        );
    }

    public function test_lingkup_final_hanya_memeringkat_finalis_dengan_nilai_final()
    {
        $champion = $this->makeChampion('Juara Final', $this->rubrikFinal[0]);

        $lolos = $this->makeParticipant('SMPN 1', $this->groupA);
        $gugur = $this->makeParticipant('SMPN 2', $this->groupB);
        $this->score($lolos, $this->rubrikFinal[1], 40);
        $this->score($gugur, $this->rubrikGrupB[1], 99);

        \App\Models\CompetitionRoundRegistration::create([
            'eventner_id' => $this->eventner->id,
            'competition_round_id' => $this->final->id,
            'registration_id' => $lolos->id,
            'competition_group_id' => $this->groupA->id,
        ]);

        $rankings = $this->panel()
            ->set('selectedScopeId', 'babak-'.$this->final->id)
            ->viewData('rankings');

        $this->assertSame(
            ['SMPN 1'],
            $rankings[$champion->id]->pluck('participant.nama_sekolah')->all()
        );
    }

    public function test_lingkup_final_tidak_memakai_nilai_penyisihan()
    {
        $champion = $this->makeChampion('Juara Final', $this->rubrikFinal[0]);

        $reg = $this->makeParticipant('SMPN 1', $this->groupA);
        $this->score($reg, $this->rubrikGrupA[1], 50);

        \App\Models\CompetitionRoundRegistration::create([
            'eventner_id' => $this->eventner->id,
            'competition_round_id' => $this->final->id,
            'registration_id' => $reg->id,
            'competition_group_id' => $this->groupA->id,
        ]);

        $rankings = $this->panel()
            ->set('selectedScopeId', 'babak-'.$this->final->id)
            ->viewData('rankings');

        $this->assertCount(
            0,
            $rankings[$champion->id],
            'Nilai penyisihan bukan penentu juara final — tanpa nilai final, peserta bukan juara.'
        );
    }

    public function test_lingkup_babak_tenant_lain_ditolak()
    {
        $lain = Eventner::factory()->create(['status' => 'approved']);
        $levelLain = CompetitionCategory::factory()->create(['eventner_id' => $lain->id]);
        $babakLain = CompetitionRound::create([
            'eventner_id' => $lain->id,
            'competition_category_id' => $levelLain->id,
            'name' => 'Final',
            'type' => CompetitionRound::TYPE_FINAL,
        ]);

        $this->panel()
            ->set('selectedScopeId', 'babak-'.$babakLain->id)
            ->assertSet('selectedScopeId', '');
    }

    /** Nama rubrik yang tampil di checklist pada lingkup terpilih. */
    private function rubrikTampil(?string $scopeKey): array
    {
        $component = $this->panel();

        if ($scopeKey !== null) {
            $component->set('selectedScopeId', $scopeKey);
        }

        return collect($component->viewData('rubrikByLevel'))
            ->flatMap(fn ($levelGroup) => $levelGroup['sections'])
            ->flatMap(fn ($section) => $section['categories'])
            ->pluck('name')
            ->all();
    }

    /**
     * Lingkup grup TIDAK menyaring rubrik lagi — hanya babaknya.
     *
     * Dulu lingkup "Grup A" menyembunyikan rubrik Grup B, karena rubrik memang
     * ditandai per grup. Sejak lembar nilai ditentukan SERI dan satu grup boleh
     * memuat beberapa seri, menyembunyikannya justru menyembunyikan rubrik yang
     * benar-benar dipakai peserta Grup A yang berseri B — dan peta bobotnya
     * ikut kehilangan kriteria itu, sehingga total antar-serinya tak sebanding.
     */
    public function test_lingkup_grup_tidak_lagi_menyaring_rubrik()
    {
        $this->assertSame(
            ['PBB Grup A', 'PBB Grup B'],
            $this->rubrikTampil('grup-'.$this->groupA->id),
            'Rubrik sesama babak penyisihan harus tetap tampil di lingkup grup mana pun.'
        );
    }

    public function test_lingkup_final_tidak_menampilkan_rubrik_grup()
    {
        $this->assertSame(
            ['PBB Final'],
            $this->rubrikTampil('babak-'.$this->final->id)
        );
    }

    public function test_tanpa_lingkup_seluruh_rubrik_tingkat_tampil()
    {
        $this->assertSame(
            ['PBB Grup A', 'PBB Grup B', 'PBB Final'],
            $this->rubrikTampil(null)
        );
    }

    /** Lingkup grup B pun memuat rubrik grup A — lihat tes di atas. */
    public function test_lingkup_grup_b_juga_memuat_rubrik_grup_a()
    {
        $this->assertSame(
            ['PBB Grup A', 'PBB Grup B'],
            $this->rubrikTampil('grup-'.$this->groupB->id)
        );
    }

    /**
     * Peta bobot satu grup memuat rubrik SEMUA seri yang ada di grup itu.
     *
     * Inilah akibat langsung dari "seri bebas dari grup": satu grup boleh
     * memuat dua seri, dan tiap seri punya lembarnya sendiri. Kalau peta bobot
     * masih disaring per grup (atau per seri), kriteria pasukan seri lain
     * hilang dari peta — dan totalnya di tabel grup itu tak lagi sebanding
     * dengan pasukan yang serinya kebetulan sama dengan lingkup.
     */
    public function test_peta_bobot_lingkup_grup_memuat_semua_seri_di_grup_itu()
    {
        $seriA = \App\Models\CompetitionSeries::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Seri A',
            'sort_order' => 1,
        ]);
        $seriB = \App\Models\CompetitionSeries::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Seri B',
            'sort_order' => 2,
        ]);

        // Dua rubrik di grup yang SAMA, beda seri.
        [, $kriteriaA] = $this->makeRubrik('PBB', $this->groupA->id, $this->penyisihan->id, $seriA->id);
        [, $kriteriaB] = $this->makeRubrik('PBB', $this->groupA->id, $this->penyisihan->id, $seriB->id);

        $champion = ChampionCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Juara Grup A',
            'quantity' => 3,
        ]);
        $champion->criterias()->sync([$kriteriaA->id, $kriteriaB->id]);
        $champion->load(['assessmentSubCategories.criterias', 'criterias']);

        $bobot = $champion->scoringCriteriaWeights($this->penyisihan->id, $this->groupA->id);

        $this->assertArrayHasKey($kriteriaA->id, $bobot, 'Kriteria seri A hilang dari peta bobot.');
        $this->assertArrayHasKey(
            $kriteriaB->id,
            $bobot,
            'Kriteria seri B hilang: peta bobot masih tersaring per grup/seri.'
        );

        // Babak TETAP menyaring — hanya grup/seri yang tidak lagi.
        $this->assertEmpty(
            $champion->scoringCriteriaWeights($this->final->id, $this->groupA->id),
            'Rubrik babak penyisihan bocor ke peta bobot babak final.'
        );
    }

    public function test_lingkup_menyempit_tidak_mengembalikan_seluruh_rubrik_saat_kosong()
    {
        // Lingkup babak yang belum punya rubrik sendiri: daftar harus kosong,
        // bukan jatuh ke "tampilkan semua" — fallback itu akan memasukkan
        // kembali rubrik babak lain yang baru saja disembunyikan.
        $level = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->level->parent_id,
            'name' => 'Regu Kosong',
        ]);
        $babakKosong = CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $level->id,
            'name' => 'Penyisihan',
            'type' => CompetitionRound::TYPE_PRELIMINARY,
            'sort_order' => 1,
        ]);
        // Rubrik milik tingkat lain — inilah yang tidak boleh bocor kembali
        // lewat fallback "tampilkan semua".
        $this->assertSame(
            [],
            $this->rubrikTampilUntuk($level->id, 'babak-'.$babakKosong->id)
        );
    }

    /** Nama rubrik yang tampil di checklist untuk satu tingkat + lingkup. */
    private function rubrikTampilUntuk(int $levelId, ?string $scopeKey): array
    {
        $component = $this->panel()->set('selectedCompetitionCategoryId', (string) $levelId);

        if ($scopeKey !== null) {
            $component->set('selectedScopeId', $scopeKey);
        }

        return collect($component->viewData('rubrikByLevel'))
            ->flatMap(fn ($levelGroup) => $levelGroup['sections'])
            ->flatMap(fn ($section) => $section['categories'])
            ->pluck('name')
            ->all();
    }

    public function test_tingkat_tanpa_grup_hanya_menampilkan_babak_non_final()
    {
        // Tingkat tanpa grup: babak penyisihan tetap muncul sendiri, karena
        // tidak ada grup yang mewakilinya.
        $level = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->level->parent_id,
            'name' => 'Regu Cadangan',
        ]);
        $round = CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $level->id,
            'name' => 'Penyisihan',
            'type' => CompetitionRound::TYPE_PRELIMINARY,
        ]);

        $scopes = $this->panel()
            ->set('selectedCompetitionCategoryId', (string) $level->id)
            ->viewData('scopes');

        $this->assertSame(['Penyisihan'], $scopes->pluck('label')->all());
        $this->assertSame((string) $round->id, $scopes->first()['round_id']);
    }

    public function test_tingkat_tanpa_grup_dan_babak_tidak_punya_daftar_lingkup()
    {
        $level = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->level->parent_id,
            'name' => 'Regu Polos',
        ]);

        $this->assertCount(
            0,
            $this->panel()->set('selectedCompetitionCategoryId', (string) $level->id)
                ->viewData('scopes')
        );
    }
}
