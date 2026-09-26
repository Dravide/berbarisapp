<?php

namespace Tests\Feature;

use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentSubCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\CompetitionRoundRegistration;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Batas pandang juri saat satu tingkat dipecah jadi beberapa grup.
 *
 * Rubrik adalah satu-satunya pengikat juri ke pekerjaannya, jadi "juri berbeda
 * per grup" berarti "rubrik berbeda per grup". Yang paling mudah bocor adalah
 * cabang fallback "rubrik kosong → semua rubrik tingkat": tanpa klausa grup di
 * dalam $base, juri Grup B yang belum punya rubrik akan diberi rubrik Grup A.
 */
class GroupJudgeScopeTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $level;

    private CompetitionGroup $groupA;

    private CompetitionGroup $groupB;

    private Judge $judgeA;

    private Judge $judgeB;

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

        $this->judgeA = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri A']);
        $this->judgeB = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri B']);

        config(['app.entry_host' => 'entry.berbaris.test']);
    }

    /** Rubrik + satu kriteria; juri yang ditugaskan diattach dari luar. */
    private function makeRubric(string $name, ?CompetitionGroup $group = null, ?int $levelId = null): AssessmentCriteria
    {
        $category = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $levelId ?? $this->level->id,
            'competition_group_id' => $group?->id,
            'name' => $name,
            'sort_order' => 1,
        ]);

        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $category->id,
            'name' => 'Sub ' . $name,
        ]);

        return AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Kriteria ' . $name,
            'score_options' => [['score' => 10], ['score' => 20]],
        ]);
    }

    private function makeParticipant(string $school, ?CompetitionGroup $group): Registration
    {
        return Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $group?->id,
            'nama_sekolah' => $school,
        ]);
    }

    public function test_juri_grup_a_hanya_melihat_kriteria_grup_a()
    {
        $kriteriaA = $this->makeRubric('PBB Grup A', $this->groupA);
        $kriteriaB = $this->makeRubric('PBB Grup B', $this->groupB);

        $this->judgeA->assessmentCategories()->attach(
            AssessmentCategory::where('name', 'PBB Grup A')->first()->id
        );
        $this->judgeB->assessmentCategories()->attach(
            AssessmentCategory::where('name', 'PBB Grup B')->first()->id
        );

        $pesertaA = $this->makeParticipant('SMPN 1', $this->groupA);

        Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judgeA->access_token])
            ->call('selectCategory', $this->level->id)
            ->call('selectParticipant', $pesertaA->id)
            ->assertSet('allowedCriteriaIds', [(int) $kriteriaA->id])
            ->assertNotSet('allowedCriteriaIds', [(int) $kriteriaB->id]);
    }

    /**
     * Kasus paling berbahaya: peserta Grup B, tak ada rubrik Grup B sama sekali,
     * dan hanya rubrik Grup A yang ada. Juri pemegang rubrik Grup A TIDAK boleh
     * dapat jalan masuk ke peserta Grup B — baik lewat daftar maupun lewat ID
     * langsung, dan rubrik Grup A tak boleh muncul untuk peserta Grup B.
     */
    public function test_peserta_grup_b_tanpa_rubrik_grup_b_tidak_mewarisi_rubrik_grup_a()
    {
        $kriteriaA = $this->makeRubric('PBB Grup A', $this->groupA);
        $this->makeRubric('Umum Tingkat', null);

        // Juri B ditugaskan ke rubrik Grup A — justru supaya cabang
        // whereHas('judges') tidak menyelamatkan; yang diuji adalah $base-nya.
        $this->judgeB->assessmentCategories()->attach(
            AssessmentCategory::where('name', 'PBB Grup A')->first()->id
        );

        $pesertaB = $this->makeParticipant('SMPN 2', $this->groupB);

        $component = Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judgeB->access_token])
            ->call('selectCategory', $this->level->id);

        $ids = collect($component->instance()->render()->getData()['participants'])->pluck('id');
        $this->assertFalse($ids->contains($pesertaB->id), 'Peserta Grup B muncul di daftar juri Grup A.');

        // Lewat ID langsung pun ditolak, jadi $allowedCriteriaIds tidak pernah
        // sempat memuat rubrik Grup A untuk peserta Grup B.
        try {
            $component->call('selectParticipant', $pesertaB->id);
            $this->fail('Juri Grup A berhasil memilih peserta Grup B.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->assertTrue(true);
        }

        $this->assertNotContains(
            (int) $kriteriaA->id,
            $component->get('allowedCriteriaIds'),
            'Rubrik Grup A bocor ke peserta Grup B.'
        );
    }

    /**
     * Rubrik tanpa grup tidak boleh jadi celah: juri yang HANYA punya rubrik
     * Grup A tetap tak melihat peserta Grup B, sekalipun peserta Grup B punya
     * rubrik tanpa grup yang bisa dinilai.
     */
    public function test_rubrik_tanpa_grup_tidak_membuka_peserta_grup_lain_ke_juri_grup_a()
    {
        $this->makeRubric('Umum Tingkat', null);
        $this->makeRubric('PBB Grup A', $this->groupA);

        // Juri A hanya dipekerjakan pada rubrik Grup A.
        $this->judgeA->assessmentCategories()->attach(
            AssessmentCategory::where('name', 'PBB Grup A')->first()->id
        );

        $pesertaB = $this->makeParticipant('SMPN 2', $this->groupB);

        $component = Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judgeA->access_token])
            ->call('selectCategory', $this->level->id);

        $ids = collect($component->instance()->render()->getData()['participants'])->pluck('id');
        $this->assertFalse($ids->contains($pesertaB->id));

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $component->call('selectParticipant', $pesertaB->id);
    }

    public function test_rubrik_tanpa_grup_membuat_juri_menilai_seluruh_peserta_tingkat()
    {
        $kriteriaUmum = $this->makeRubric('Umum Tingkat', null);

        $this->judgeA->assessmentCategories()->attach(
            AssessmentCategory::where('name', 'Umum Tingkat')->first()->id
        );

        $pesertaA = $this->makeParticipant('SMPN 1', $this->groupA);
        $pesertaB = $this->makeParticipant('SMPN 2', $this->groupB);

        $component = Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judgeA->access_token])
            ->call('selectCategory', $this->level->id);

        $ids = collect($component->instance()->render()->getData()['participants'])->pluck('id');

        $this->assertTrue($ids->contains($pesertaA->id));
        $this->assertTrue($ids->contains($pesertaB->id));

        $component->call('selectParticipant', $pesertaB->id)
            ->assertSet('allowedCriteriaIds', [(int) $kriteriaUmum->id]);
    }

    public function test_juri_grup_a_tidak_melihat_peserta_grup_b_di_daftar()
    {
        $this->makeRubric('PBB Grup A', $this->groupA);
        $this->judgeA->assessmentCategories()->attach(
            AssessmentCategory::where('name', 'PBB Grup A')->first()->id
        );

        $pesertaA = $this->makeParticipant('SMPN 1', $this->groupA);
        $pesertaB = $this->makeParticipant('SMPN 2', $this->groupB);

        $component = Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judgeA->access_token])
            ->call('selectCategory', $this->level->id);

        $ids = collect($component->instance()->render()->getData()['participants'])->pluck('id');

        $this->assertTrue($ids->contains($pesertaA->id));
        $this->assertFalse($ids->contains($pesertaB->id), 'Peserta Grup B muncul di daftar juri Grup A.');

        // Bahkan lewat ID langsung pun ditolak.
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $component->call('selectParticipant', $pesertaB->id);
    }

    public function test_juri_tetap_dibatasi_meski_punya_rubrik_grup_lain_di_tingkat_yang_sama()
    {
        $this->makeRubric('PBB Grup B', $this->groupB);
        $this->judgeA->assessmentCategories()->attach(
            AssessmentCategory::where('name', 'PBB Grup B')->first()->id
        );

        $pesertaB = $this->makeParticipant('SMPN 2', $this->groupB);

        Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judgeA->access_token])
            ->call('selectCategory', $this->level->id)
            ->call('selectParticipant', $pesertaB->id)
            ->assertOk();
    }

    // ---------- angka di kartu tingkat harus sama dengan isi daftarnya ----------

    /**
     * Kartu tingkat menjanjikan jumlah yang berbeda dari daftar.
     *
     * Sebelumnya kartu memakai `withCount('registrations')` — seluruh pendaftar
     * tingkat, tak peduli grup maupun babak. Juri Grup A membaca "2 peserta",
     * membuka daftarnya, dan menemukan 1; angka yang salah lebih buruk daripada
     * tidak ada angka karena juri mengira ada peserta yang hilang.
     */
    public function test_angka_kartu_tingkat_sama_dengan_isi_daftar_untuk_juri_grup()
    {
        $this->makeRubric('PBB Grup A', $this->groupA);
        $this->judgeA->assessmentCategories()->attach(
            AssessmentCategory::where('name', 'PBB Grup A')->first()->id
        );

        $this->makeParticipant('SMPN 1', $this->groupA);
        $this->makeParticipant('SMPN 2', $this->groupB);
        $this->makeParticipant('SMPN 3', $this->groupB);

        $component = Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judgeA->access_token]);

        $this->assertSame(
            1,
            $component->viewData('jumlahPeserta')[$this->level->id],
            'Juri Grup A dijanjikan 3 peserta padahal daftarnya 1.'
        );

        $component->call('selectCategory', $this->level->id);

        $this->assertCount(1, $component->viewData('participants'));
    }

    /**
     * Juri yang menilai babak final hanya melihat finalis, di kartu maupun daftar.
     *
     * Tingkat ini punya babak penyisihan dan final; juri memegang rubrik final
     * saja. Memakai jumlah pendaftar tingkat membuat kartunya menulis "3
     * peserta" sementara hanya 2 yang benar-benar tampil.
     */
    public function test_angka_kartu_tingkat_untuk_juri_final_hanya_finalis()
    {
        $babakFinal = CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Final',
            'type' => CompetitionRound::TYPE_FINAL,
            'sort_order' => 2,
        ]);

        $this->makeRubric('PBB Final', null);
        AssessmentCategory::where('name', 'PBB Final')->update(['competition_round_id' => $babakFinal->id]);

        $this->judgeA->assessmentCategories()->attach(
            AssessmentCategory::where('name', 'PBB Final')->first()->id
        );

        $lolos = $this->makeParticipant('SMPN 1', $this->groupA);
        $this->makeParticipant('SMPN 2', $this->groupB); // tak lolos
        $lolosDua = $this->makeParticipant('SMPN 3', $this->groupB);

        foreach ([$lolos, $lolosDua] as $p) {
            CompetitionRoundRegistration::create([
                'eventner_id' => $this->eventner->id,
                'competition_round_id' => $babakFinal->id,
                'registration_id' => $p->id,
                'competition_group_id' => $p->competition_group_id,
            ]);
        }

        $component = Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judgeA->access_token]);

        $this->assertSame(
            2,
            $component->viewData('jumlahPeserta')[$this->level->id],
            'Kartu juri final menghitung seluruh pendaftar tingkat, bukan finalis.'
        );

        $component->call('selectCategory', $this->level->id);

        $this->assertCount(2, $component->viewData('participants'));
    }

    /**
     * Pemilih babak muncul sebelum peserta dipilih, walau rubriknya bergrup.
     *
     * `forEntry(null grup)` berarti "rubrik TANPA grup saja", jadi juri yang
     * seluruh rubriknya bergrup mendapat daftar babak kosong dan pemilihnya
     * hilang dari layar — juri tak bisa berpindah babak sama sekali.
     */
    public function test_pemilih_babak_muncul_walau_rubrik_juri_bergrup()
    {
        $babak = CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Fase Grup',
            'type' => CompetitionRound::TYPE_PRELIMINARY,
            'sort_order' => 1,
        ]);

        $this->makeRubric('PBB Grup A', $this->groupA);
        AssessmentCategory::where('name', 'PBB Grup A')->update(['competition_round_id' => $babak->id]);

        $this->judgeA->assessmentCategories()->attach(
            AssessmentCategory::where('name', 'PBB Grup A')->first()->id
        );

        $this->assertSame(
            [$babak->id],
            Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judgeA->access_token])
                ->call('selectCategory', $this->level->id)
                ->viewData('rounds')
                ->pluck('id')
                ->all()
        );
    }
}
