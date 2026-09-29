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
 * Batas pandang juri: juri dikurung GRUP, bukan seri.
 *
 * Dua sumbu yang sengaja dipisah, dan seluruh berkas ini menjaga pemisahan itu:
 *
 *   Grup   -> siapa menilai siapa. Panitia mencentang juri per baris penugasan
 *             di modal Kelola Grup, dan juri Grup A menilai SELURUH peserta
 *             Grup A, apa pun serinya.
 *   Seri   -> lembar nilai mana yang terbuka. Rubriknya menyusul dari seri
 *             masing-masing peserta, ditetapkan panitia saat daftar ulang.
 *
 * Karena itu juri boleh memegang lebih dari satu seri sekaligus: satu grup
 * biasanya memang memuat peserta dari beberapa seri. Yang tidak boleh terjadi
 * adalah sebaliknya — juri Grup A menilai peserta Grup B.
 *
 * Model lama mengikat juri ke rubrik (assessment_category_judge). Pivot itu
 * sekarang tinggal jejak audit: rubrik tak lagi menentukan siapa yang menilai.
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

        // Bentuk data yang diminta panitia: Juri A khusus Grup A, Juri B khusus
        // Grup B. Rubriknya tidak ikut menentukan apa pun di sini.
        CompetitionGroup::syncJudges($this->level->id, CompetitionGroup::SCOPE_GROUP, $this->groupA->id, [$this->judgeA->id]);
        CompetitionGroup::syncJudges($this->level->id, CompetitionGroup::SCOPE_GROUP, $this->groupB->id, [$this->judgeB->id]);

        config(['app.entry_host' => 'entry.berbaris.test']);
    }

    /** Rubrik + satu kriteria, menempel pada seri (lembar nilai manusianya). */
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

    /** Peserta dengan seri & grup terpisah — justru bentuk yang ditangani di sini. */
    private function makeParticipant(string $school, ?CompetitionSeries $series, ?CompetitionGroup $group = null): Registration
    {
        return Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $group?->id,
            'competition_series_id' => $series?->id,
            'nama_sekolah' => $school,
        ]);
    }

    private function tablet(Judge $judge)
    {
        return Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $judge->access_token])
            ->call('selectCategory', $this->level->id);
    }

    /** ID peserta yang benar-benar tampil di daftar juri ini. */
    private function idsDiDaftar($component): \Illuminate\Support\Collection
    {
        return collect($component->instance()->render()->getData()['participants'])->pluck('id');
    }

    // ---------- inti keputusan: grup jadi sumbu penugasan ----------

    /**
     * Juri Grup A melihat SELURUH peserta Grup A, apa pun serinya.
     *
     * Inilah keluhan aslinya: "juri A dan B khusus grup A, juri C dan D grup B,
     * dengan kondisi juri tersebut dapat menilai seri A dan B, tergantung
     * sekolah dapat seri berapa ketika daftar ulang". Seri tidak mengurung
     * siapa pun lagi.
     */
    public function test_juri_grup_a_melihat_seluruh_peserta_grup_a_apa_pun_serinya()
    {
        $seriAdiGrupA = $this->makeParticipant('SMPN 1', $this->seriA, $this->groupA);
        $seriBdiGrupA = $this->makeParticipant('SMPN 2', $this->seriB, $this->groupA);

        $ids = $this->idsDiDaftar($this->tablet($this->judgeA));

        $this->assertTrue($ids->contains($seriAdiGrupA->id), 'Peserta Seri A di Grup A hilang dari juri Grup A.');
        $this->assertTrue($ids->contains($seriBdiGrupA->id), 'Peserta Seri B di Grup A hilang dari juri Grup A.');
    }

    /**
     * Lembar nilainya mengikuti SERI peserta, bukan grup jurinya.
     *
     * Satu juri Grup A membuka dua peserta berseri berbeda dan mendapat dua
     * rubric set yang berbeda pula — kalau rubriknya ikut grup, juri itu cuma
     * bisa menilai satu seri dan peserta seri lain tampil kosong.
     */
    public function test_juri_grup_a_menilai_rubrik_seri_peserta_bukan_rubrik_grupnya()
    {
        $kriteriaA = $this->makeRubric('PBB Seri A', $this->seriA);
        $kriteriaB = $this->makeRubric('PBB Seri B', $this->seriB);

        $pesertaSeriA = $this->makeParticipant('SMPN 1', $this->seriA, $this->groupA);
        $pesertaSeriB = $this->makeParticipant('SMPN 2', $this->seriB, $this->groupA);

        $component = $this->tablet($this->judgeA);

        $component->call('selectParticipant', $pesertaSeriA->id)
            ->assertSet('allowedCriteriaIds', [(int) $kriteriaA->id]);

        $component->call('selectParticipant', $pesertaSeriB->id)
            ->assertSet('allowedCriteriaIds', [(int) $kriteriaB->id]);
    }

    /** Juri Grup B tidak melihat peserta Grup A — dan tak bisa membukanya lewat ID. */
    public function test_juri_grup_b_tidak_melihat_peserta_grup_a()
    {
        $pesertaA = $this->makeParticipant('SMPN 1', $this->seriA, $this->groupA);
        $pesertaB = $this->makeParticipant('SMPN 2', $this->seriB, $this->groupB);

        $component = $this->tablet($this->judgeB);
        $ids = $this->idsDiDaftar($component);

        $this->assertTrue($ids->contains($pesertaB->id));
        $this->assertFalse($ids->contains($pesertaA->id), 'Peserta Grup A muncul di daftar juri Grup B.');

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $component->call('selectParticipant', $pesertaA->id);
    }

    /**
     * Peserta yang belum bergrup punya barisnya sendiri.
     *
     * Tanpa baris `ungrouped`, peserta yang telat dibagi grup akan hilang dari
     * semua daftar juri grup — tak ada satu pun juri yang bisa menilainya.
     */
    public function test_peserta_belum_bergrup_dinilai_juri_baris_ungrouped()
    {
        $juriCadangan = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri Cadangan']);
        CompetitionGroup::syncJudges($this->level->id, CompetitionGroup::SCOPE_UNGROUPED, null, [$juriCadangan->id]);

        $this->makeRubric('PBB Umum', $this->seriA);

        $belumBergrup = $this->makeParticipant('SMPN 9', $this->seriA, null);
        $this->makeParticipant('SMPN 1', $this->seriA, $this->groupA);

        $ids = $this->idsDiDaftar($this->tablet($juriCadangan));

        $this->assertSame([$belumBergrup->id], $ids->values()->all());
    }

    /** Sebaliknya: juri grup tidak ikut melihat peserta tanpa grup. */
    public function test_peserta_tanpa_grup_tidak_muncul_di_juri_grup()
    {
        $bergrup = $this->makeParticipant('SMPN 1', $this->seriA, $this->groupA);
        $this->makeParticipant('SMPN 9', $this->seriA, null);

        $ids = $this->idsDiDaftar($this->tablet($this->judgeA));

        $this->assertSame([$bergrup->id], $ids->values()->all());
    }

    /**
     * Juri final hidup di baris `final`, terpisah dari grup.
     *
     * Begitu ada penugasan final di tingkat itu, SELURUH finalis berpindah ke
     * baris final — termasuk bagi juri grup, yang memang lalu tak melihat siapa
     * pun saat babak final dibuka.
     */
    public function test_juri_final_hanya_melihat_finalis_dan_juri_grup_tidak()
    {
        $babakFinal = CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Final',
            'type' => CompetitionRound::TYPE_FINAL,
            'sort_order' => 2,
        ]);

        $juriFinal = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri Final']);
        CompetitionGroup::syncJudges($this->level->id, CompetitionGroup::SCOPE_FINAL, null, [$juriFinal->id]);

        $lolos = $this->makeParticipant('SMPN 1', $this->seriA, $this->groupA);
        $this->makeParticipant('SMPN 2', $this->seriB, $this->groupA);

        CompetitionRoundRegistration::create([
            'eventner_id' => $this->eventner->id,
            'competition_round_id' => $babakFinal->id,
            'registration_id' => $lolos->id,
            'competition_group_id' => $this->groupA->id,
        ]);

        $component = $this->tablet($juriFinal);

        $this->assertSame([$lolos->id], $this->idsDiDaftar($component)->values()->all());

        // Juri Grup A tak ditugaskan ke baris final, jadi daftarnya kosong.
        $componentGrup = $this->tablet($this->judgeA);

        $this->assertSame([], $this->idsDiDaftar($componentGrup)->values()->all());
    }

    /**
     * Juri tanpa penugasan apa pun tak punya tingkat untuk dibuka.
     *
     * Kartunya kosong sejak layar pertama, dan tingkat mana pun ditolak 403 —
     * tidak ada jalan masuk yang tersisa, apalagi untuk menyimpan nilai.
     */
    public function test_juri_tanpa_penugasan_bahkan_tak_punya_tingkat_untuk_dibuka()
    {
        $this->makeRubric('PBB Seri A', $this->seriA);
        $this->makeParticipant('SMPN 1', $this->seriA, $this->groupA);

        $juriLepas = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri Lepas']);

        Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $juriLepas->access_token])
            ->assertViewHas('categories', fn ($c) => $c->isEmpty())
            ->call('selectCategory', $this->level->id)
            ->assertStatus(403);
    }

    /** Nilai di luar rubrik peserta tetap ditolak, walau jurinya ditugaskan. */
    public function test_set_score_di_luar_rubrik_peserta_ditolak()
    {
        $this->makeRubric('PBB Seri A', $this->seriA);
        $asing = $this->makeRubric('PBB Seri B', $this->seriB);

        $peserta = $this->makeParticipant('SMPN 1', $this->seriA, $this->groupA);

        $this->tablet($this->judgeA)
            ->call('selectParticipant', $peserta->id)
            ->call('setScore', $asing->id, 10)
            ->assertStatus(403);
    }

    /**
     * Tingkat tanpa grup (tiga belas event lama) memakai baris `level`.
     *
     * Layar centang rubrik sudah dihapus, jadi tanpa jalur ini panel juri event
     * lama kosong seluruhnya dan pesertanya tak bisa dinilai siapa pun.
     */
    public function test_tingkat_tanpa_grup_memakai_baris_level()
    {
        $induk = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
        ]);
        $polos = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $induk->id,
        ]);

        $kriteria = $this->makeRubric('PBB Umum', null, $polos->id);
        CompetitionGroup::syncJudges($polos->id, CompetitionGroup::SCOPE_LEVEL, null, [$this->judgeA->id]);

        $peserta = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $polos->id,
            'competition_group_id' => null,
            'competition_series_id' => null,
            'nama_sekolah' => 'SMPN 3',
        ]);

        $component = Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judgeA->access_token])
            ->call('selectCategory', $polos->id);

        $this->assertSame([$peserta->id], $this->idsDiDaftar($component)->values()->all());

        $component->call('selectParticipant', $peserta->id)
            ->assertSet('allowedCriteriaIds', [(int) $kriteria->id]);
    }

    // ---------- angka di kartu tingkat harus sama dengan isi daftarnya ----------

    /**
     * Kartu tingkat menjanjikan jumlah yang berbeda dari daftar.
     *
     * withCount('registrations') menghitung seluruh pendaftar tingkat — tak
     * peduli grup. Juri Grup A membaca "3 peserta", membuka daftarnya, dan
     * menemukan 1; angka yang salah lebih buruk daripada tidak ada angka karena
     * juri mengira ada peserta yang hilang.
     */
    public function test_angka_kartu_tingkat_sama_dengan_isi_daftar_untuk_juri_grup()
    {
        $this->makeParticipant('SMPN 1', $this->seriA, $this->groupA);
        $this->makeParticipant('SMPN 2', $this->seriB, $this->groupB);
        $this->makeParticipant('SMPN 3', $this->seriB, $this->groupB);

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
     * Memakai jumlah pendaftar tingkat membuat kartunya menulis "3 peserta"
     * sementara hanya 2 yang benar-benar tampil.
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

        $juriFinal = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri Final']);
        CompetitionGroup::syncJudges($this->level->id, CompetitionGroup::SCOPE_FINAL, null, [$juriFinal->id]);

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

        $component = Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $juriFinal->access_token]);

        $this->assertSame(
            2,
            $component->viewData('jumlahPeserta')[$this->level->id],
            'Kartu juri final menghitung seluruh pendaftar tingkat, bukan finalis.'
        );

        $component->call('selectCategory', $this->level->id);

        $this->assertCount(2, $component->viewData('participants'));
    }

    /** Pemilih babak muncul begitu tingkatnya punya babak, apa pun rubriknya. */
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

        $this->assertSame(
            [$babak->id],
            $this->tablet($this->judgeA)->viewData('rounds')->pluck('id')->all()
        );
    }
}
