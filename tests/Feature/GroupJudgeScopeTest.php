<?php

namespace Tests\Feature;

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
 * Batas pandang juri saat satu tingkat dipecah jadi beberapa seri.
 *
 * Rubrik adalah satu-satunya pengikat juri ke pekerjaannya, jadi "juri berbeda
 * per seri" berarti "rubrik berbeda per seri". Yang paling mudah bocor adalah
 * cabang fallback "rubrik kosong → semua rubrik tingkat": tanpa klausa seri di
 * dalam $base, juri Seri B yang belum punya rubrik akan diberi rubrik Seri A.
 *
 * Grup sengaja TIDAK jadi sumbu saringan lagi. Grup dan seri dua sumbu bebas:
 * grup menyusun tabel peringkat dan nomor undian, seri menentukan lembar nilai.
 * Karena itu tes di bawah memakai dua seri yang tersebar di dua grup — juri
 * Seri B memang harus melihat peserta Seri B di grup mana pun ia berada.
 */
class GroupJudgeScopeTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $level;

    private CompetitionGroup $groupA;

    private CompetitionGroup $groupB;

    private CompetitionSeries $seriA;

    private CompetitionSeries $seriB;

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

        $this->judgeA = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri A']);
        $this->judgeB = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri B']);

        config(['app.entry_host' => 'entry.berbaris.test']);
    }

    /** Rubrik + satu kriteria; juri yang ditugaskan diattach dari luar. */
    private function makeRubric(string $name, ?CompetitionSeries $series = null, ?int $levelId = null): AssessmentCriteria
    {
        $category = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $levelId ?? $this->level->id,
            'competition_series_id' => $series?->id,
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

    /**
     * Peserta dengan seri & grup terpisah — justru bentuk yang bikin grup tidak
     * bisa dipakai sebagai pengganti seri.
     */
    private function makeParticipant(string $school, ?CompetitionSeries $series, ?CompetitionGroup $group = null): Registration
    {
        return Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $group?->id,
            'competition_series_id' => $series?->id,
            'nama_sekolah' => $school,
        ]);
    }

    public function test_juri_seri_a_hanya_melihat_kriteria_seri_a()
    {
        $kriteriaA = $this->makeRubric('PBB Seri A', $this->seriA);
        $kriteriaB = $this->makeRubric('PBB Seri B', $this->seriB);

        $this->judgeA->assessmentCategories()->attach(
            AssessmentCategory::where('name', 'PBB Seri A')->first()->id
        );
        $this->judgeB->assessmentCategories()->attach(
            AssessmentCategory::where('name', 'PBB Seri B')->first()->id
        );

        $pesertaA = $this->makeParticipant('SMPN 1', $this->seriA, $this->groupA);

        Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judgeA->access_token])
            ->call('selectCategory', $this->level->id)
            ->call('selectParticipant', $pesertaA->id)
            ->assertSet('allowedCriteriaIds', [(int) $kriteriaA->id])
            ->assertNotSet('allowedCriteriaIds', [(int) $kriteriaB->id]);
    }

    /**
     * Kasus paling berbahaya: peserta Seri B, tak ada rubrik Seri B sama sekali,
     * dan hanya rubrik Seri A yang ada. Juri pemegang rubrik Seri A TIDAK boleh
     * dapat jalan masuk ke peserta Seri B — baik lewat daftar maupun lewat ID
     * langsung, dan rubrik Seri A tak boleh muncul untuk peserta Seri B.
     */
    public function test_peserta_seri_b_tanpa_rubrik_seri_b_tidak_mewarisi_rubrik_seri_a()
    {
        $kriteriaA = $this->makeRubric('PBB Seri A', $this->seriA);
        $this->makeRubric('Umum Tingkat', null);

        // Juri B ditugaskan ke rubrik Seri A — justru supaya cabang
        // whereHas('judges') tidak menyelamatkan; yang diuji adalah $base-nya.
        $this->judgeB->assessmentCategories()->attach(
            AssessmentCategory::where('name', 'PBB Seri A')->first()->id
        );

        $pesertaB = $this->makeParticipant('SMPN 2', $this->seriB, $this->groupB);

        $component = Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judgeB->access_token])
            ->call('selectCategory', $this->level->id);

        $ids = collect($component->instance()->render()->getData()['participants'])->pluck('id');
        $this->assertFalse($ids->contains($pesertaB->id), 'Peserta Seri B muncul di daftar juri Seri A.');

        // Lewat ID langsung pun ditolak, jadi $allowedCriteriaIds tidak pernah
        // sempat memuat rubrik Seri A untuk peserta Seri B.
        try {
            $component->call('selectParticipant', $pesertaB->id);
            $this->fail('Juri Seri A berhasil memilih peserta Seri B.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->assertTrue(true);
        }

        $this->assertNotContains(
            (int) $kriteriaA->id,
            $component->get('allowedCriteriaIds'),
            'Rubrik Seri A bocor ke peserta Seri B.'
        );
    }

    /**
     * Rubrik tanpa seri tidak boleh jadi celah: juri yang HANYA punya rubrik
     * Seri A tetap tak melihat peserta Seri B, sekalipun peserta Seri B punya
     * rubrik tanpa seri yang bisa dinilai.
     */
    public function test_rubrik_tanpa_seri_tidak_membuka_peserta_seri_lain_ke_juri_seri_a()
    {
        $this->makeRubric('Umum Tingkat', null);
        $this->makeRubric('PBB Seri A', $this->seriA);

        // Juri A hanya dipekerjakan pada rubrik Seri A.
        $this->judgeA->assessmentCategories()->attach(
            AssessmentCategory::where('name', 'PBB Seri A')->first()->id
        );

        $pesertaB = $this->makeParticipant('SMPN 2', $this->seriB, $this->groupB);

        $component = Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judgeA->access_token])
            ->call('selectCategory', $this->level->id);

        $ids = collect($component->instance()->render()->getData()['participants'])->pluck('id');
        $this->assertFalse($ids->contains($pesertaB->id));

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $component->call('selectParticipant', $pesertaB->id);
    }

    public function test_rubrik_tanpa_seri_membuat_juri_menilai_seluruh_peserta_tingkat()
    {
        $kriteriaUmum = $this->makeRubric('Umum Tingkat', null);

        $this->judgeA->assessmentCategories()->attach(
            AssessmentCategory::where('name', 'Umum Tingkat')->first()->id
        );

        $pesertaA = $this->makeParticipant('SMPN 1', $this->seriA, $this->groupA);
        $pesertaB = $this->makeParticipant('SMPN 2', $this->seriB, $this->groupB);

        $component = Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judgeA->access_token])
            ->call('selectCategory', $this->level->id);

        $ids = collect($component->instance()->render()->getData()['participants'])->pluck('id');

        $this->assertTrue($ids->contains($pesertaA->id));
        $this->assertTrue($ids->contains($pesertaB->id));

        $component->call('selectParticipant', $pesertaB->id)
            ->assertSet('allowedCriteriaIds', [(int) $kriteriaUmum->id]);
    }

    public function test_juri_seri_a_tidak_melihat_peserta_seri_b_di_daftar()
    {
        $this->makeRubric('PBB Seri A', $this->seriA);
        $this->judgeA->assessmentCategories()->attach(
            AssessmentCategory::where('name', 'PBB Seri A')->first()->id
        );

        $pesertaA = $this->makeParticipant('SMPN 1', $this->seriA, $this->groupA);
        $pesertaB = $this->makeParticipant('SMPN 2', $this->seriB, $this->groupB);

        $component = Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judgeA->access_token])
            ->call('selectCategory', $this->level->id);

        $ids = collect($component->instance()->render()->getData()['participants'])->pluck('id');

        $this->assertTrue($ids->contains($pesertaA->id));
        $this->assertFalse($ids->contains($pesertaB->id), 'Peserta Seri B muncul di daftar juri Seri A.');

        // Bahkan lewat ID langsung pun ditolak.
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $component->call('selectParticipant', $pesertaB->id);
    }

    public function test_juri_tetap_dibatasi_meski_punya_rubrik_seri_lain_di_tingkat_yang_sama()
    {
        $this->makeRubric('PBB Seri B', $this->seriB);
        $this->judgeA->assessmentCategories()->attach(
            AssessmentCategory::where('name', 'PBB Seri B')->first()->id
        );

        $pesertaB = $this->makeParticipant('SMPN 2', $this->seriB, $this->groupB);

        Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judgeA->access_token])
            ->call('selectCategory', $this->level->id)
            ->call('selectParticipant', $pesertaB->id)
            ->assertOk();
    }

    // ---------- inti permintaan aslinya: seri bebas dari grup ----------

    /**
     * Satu seri boleh tersebar di beberapa grup, dan jurinya melihat semuanya.
     *
     * Inilah alasan grup tidak bisa lagi jadi sumbu lembar nilai: kalau juri
     * dikurung ke grupnya, juri Seri B di Grup A tak melihat peserta Seri B di
     * Grup B — padahal keduanya dinilai dengan lembar nilai yang sama.
     */
    public function test_juri_seri_b_melihat_peserta_seri_b_dari_dua_grup()
    {
        $this->makeRubric('PBB Seri B', $this->seriB);
        $this->judgeB->assessmentCategories()->attach(
            AssessmentCategory::where('name', 'PBB Seri B')->first()->id
        );

        $seriBdiGrupA = $this->makeParticipant('SMPN 1', $this->seriB, $this->groupA);
        $seriBdiGrupB = $this->makeParticipant('SMPN 2', $this->seriB, $this->groupB);
        $seriAdiGrupA = $this->makeParticipant('SMPN 3', $this->seriA, $this->groupA);

        $component = Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judgeB->access_token])
            ->call('selectCategory', $this->level->id);

        $ids = collect($component->instance()->render()->getData()['participants'])->pluck('id');

        $this->assertTrue($ids->contains($seriBdiGrupA->id), 'Seri B di Grup A hilang dari daftar juri Seri B.');
        $this->assertTrue($ids->contains($seriBdiGrupB->id), 'Seri B di Grup B hilang dari daftar juri Seri B.');
        $this->assertFalse($ids->contains($seriAdiGrupA->id), 'Peserta Seri A muncul di daftar juri Seri B.');
    }

    // ---------- angka di kartu tingkat harus sama dengan isi daftarnya ----------

    /**
     * Kartu tingkat menjanjikan jumlah yang berbeda dari daftar.
     *
     * Sebelumnya kartu memakai `withCount('registrations')` — seluruh pendaftar
     * tingkat, tak peduli seri maupun babak. Juri Seri A membaca "2 peserta",
     * membuka daftarnya, dan menemukan 1; angka yang salah lebih buruk daripada
     * tidak ada angka karena juri mengira ada peserta yang hilang.
     */
    public function test_angka_kartu_tingkat_sama_dengan_isi_daftar_untuk_juri_seri()
    {
        $this->makeRubric('PBB Seri A', $this->seriA);
        $this->judgeA->assessmentCategories()->attach(
            AssessmentCategory::where('name', 'PBB Seri A')->first()->id
        );

        $this->makeParticipant('SMPN 1', $this->seriA, $this->groupA);
        $this->makeParticipant('SMPN 2', $this->seriB, $this->groupB);
        $this->makeParticipant('SMPN 3', $this->seriB, $this->groupB);

        $component = Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judgeA->access_token]);

        $this->assertSame(
            1,
            $component->viewData('jumlahPeserta')[$this->level->id],
            'Juri Seri A dijanjikan 3 peserta padahal daftarnya 1.'
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

        $lolos = $this->makeParticipant('SMPN 1', $this->seriA, $this->groupA);
        $this->makeParticipant('SMPN 2', $this->seriB, $this->groupB); // tak lolos
        $lolosDua = $this->makeParticipant('SMPN 3', $this->seriB, $this->groupB);

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
     * Pemilih babak muncul sebelum peserta dipilih, walau rubriknya berseri.
     *
     * `forEntry(null seri)` berarti "rubrik TANPA seri saja", jadi juri yang
     * seluruh rubriknya berseri mendapat daftar babak kosong dan pemilihnya
     * hilang dari layar — juri tak bisa berpindah babak sama sekali.
     */
    public function test_pemilih_babak_muncul_walau_rubrik_juri_berseri()
    {
        $babak = CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Fase Grup',
            'type' => CompetitionRound::TYPE_PRELIMINARY,
            'sort_order' => 1,
        ]);

        $this->makeRubric('PBB Seri A', $this->seriA);
        AssessmentCategory::where('name', 'PBB Seri A')->update(['competition_round_id' => $babak->id]);

        $this->judgeA->assessmentCategories()->attach(
            AssessmentCategory::where('name', 'PBB Seri A')->first()->id
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
