<?php

namespace Tests\Feature;

use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\CompetitionSeries;
use App\Models\CompetitionRoundRegistration;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Pemisahan babak pada penyimpanan & penguncian nilai.
 *
 * Kunci `assessment_scores` UNIQUE (registration_id, assessment_criteria_id,
 * judge_id) berarti satu registrasi boleh menyimpan nilai penyisihan DAN final
 * sekaligus, asal tiap babak punya baris kriteria sendiri. Yang wajib dijaga
 * adalah sebaliknya: finalisasi satu babak tidak boleh ikut mengunci babak
 * yang lain, dan menampilkan satu babak tidak boleh memuat nilai babak lain.
 */
class GroupScoringIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $level;

    private CompetitionGroup $groupA;

    private CompetitionGroup $groupB;

    private CompetitionSeries $seriA;

    private CompetitionSeries $seriB;

    private CompetitionRound $penyisihan;

    private CompetitionRound $final;

    private Judge $juri;

    private Registration $reg;

    /** Kriteria rubrik per babak. */
    private AssessmentCriteria $kriteriaPenyisihan;

    private AssessmentCriteria $kriteriaFinal;

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
        ]);
        $this->groupB = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup B',
        ]);

        $this->seriA = CompetitionSeries::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Seri A',
        ]);
        $this->seriB = CompetitionSeries::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Seri B',
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

        $this->juri = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri Umum']);

        // Penugasan juri menempel di GRUP (bukan di rubriknya). Grup A adalah
        // grup peserta utama, jadi juri umum tercentang di sana — sama seperti
        // hasil backfill pada data yang sudah berjalan.
        CompetitionGroup::syncJudges($this->level->id, CompetitionGroup::SCOPE_GROUP, $this->groupA->id, [$this->juri->id]);

        $this->kriteriaPenyisihan = $this->makeRubric('PBB Penyisihan', $this->penyisihan, null, $this->juri);
        $this->kriteriaFinal = $this->makeRubric('PBB Final', $this->final, null, $this->juri);

        $this->reg = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $this->groupA->id,
            'competition_series_id' => $this->seriA->id,
            'nama_sekolah' => 'SMPN 1',
        ]);

        $this->actingAs($this->eventner->user);
    }

    private function makeRubric(string $name, ?CompetitionRound $round, ?CompetitionSeries $series, ?Judge $judge = null): AssessmentCriteria
    {
        $category = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'competition_series_id' => $series?->id,
            'competition_round_id' => $round?->id,
            'name' => $name,
            'sort_order' => 1,
        ]);

        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $category->id,
            'name' => 'Sub ' . $name,
        ]);

        if ($judge) {
            $judge->assessmentCategories()->attach($category->id);
        }

        return AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Kriteria ' . $name,
            'score_options' => [['score' => 10], ['score' => 20]],
            'weight' => 1,
            'sort_order' => 1,
        ]);
    }

    private function panel()
    {
        return Livewire::test(\App\Livewire\Eventner\Scoring\Index::class)
            ->call('selectCategory', $this->level->id)
            ->call('selectParticipant', $this->reg->id)
            ->set('selectedJudgeId', $this->juri->id);
    }

    /**
     * Panel tanpa menyaring finalis — untuk tes yang membuka peserta biasa
     * lalu memindah babaknya sendiri.
     */
    private function panelBebas()
    {
        return Livewire::test(\App\Livewire\Eventner\Scoring\Index::class)
            ->call('selectCategory', $this->level->id)
            ->call('selectParticipant', $this->reg->id)
            ->set('selectedJudgeId', $this->juri->id);
    }

    /** Panel dengan chip grup A aktif — babaknya otomatis penyisihan. */
    private function panelPenyisihan()
    {
        $panel = $this->panel()->call('selectScope', (string) $this->groupA->id);

        $this->assertSame((int) $this->penyisihan->id, (int) $panel->get('selectedRoundId'),
            'Chip grup tidak membuka babak penyisihan.');

        return $panel;
    }

    /**
     * Layar INPUT NILAI dengan chip Grup A aktif.
     *
     * Berbeda dari panelPenyisihan(): di sana view berakhir di 'participants'
     * karena chip dipilih setelah peserta dibuka. Di alur nyata urutannya
     * terbalik — pilih grup, baru buka peserta — jadi pemilih juri memang
     * dirender. Tanpa helper ini label juri ikut kosong bersama view-nya, dan
     * tesnya lulus karena tak memeriksa apa pun.
     */
    private function panelScoringGrupA()
    {
        return Livewire::test(\App\Livewire\Eventner\Scoring\Index::class)
            ->call('selectCategory', $this->level->id)
            ->call('selectScope', (string) $this->groupA->id)
            ->call('selectParticipant', $this->reg->id)
            ->set('selectedJudgeId', $this->juri->id);
    }

    /**
     * Panel dengan chip "Final" aktif.
     *
     * Pesertanya harus tercatat lolos lebih dulu: daftar di scope final memang
     * disaring ke finalis, jadi membuka peserta yang tidak lolos akan
     * mengembalikan panel ke daftar peserta.
     */
    private function panelFinal()
    {
        CompetitionRoundRegistration::firstOrCreate([
            'competition_round_id' => $this->final->id,
            'registration_id' => $this->reg->id,
        ], [
            'eventner_id' => $this->eventner->id,
            'competition_group_id' => $this->groupA->id,
        ]);

        return $this->panel()->call('selectScope', 'final');
    }

    public function test_satu_registrasi_menyimpan_nilai_penyisihan_dan_final_bersamaan()
    {
        $this->panelPenyisihan()
            ->set('scores.' . $this->kriteriaPenyisihan->id, 20)
            ->call('saveScores');

        $this->panelFinal()
            ->set('scores.' . $this->kriteriaFinal->id, 10)
            ->call('saveScores');

        $this->assertDatabaseHas('assessment_scores', [
            'registration_id' => $this->reg->id,
            'assessment_criteria_id' => $this->kriteriaPenyisihan->id,
            'judge_id' => $this->juri->id,
            'score' => 20,
        ]);
        $this->assertDatabaseHas('assessment_scores', [
            'registration_id' => $this->reg->id,
            'assessment_criteria_id' => $this->kriteriaFinal->id,
            'judge_id' => $this->juri->id,
            'score' => 10,
        ]);
    }

    public function test_finalisasi_penyisihan_tidak_mengunci_nilai_final()
    {
        $this->panelPenyisihan()
            ->set('scores.' . $this->kriteriaPenyisihan->id, 20)
            ->call('finalizeScores');

        $this->panelFinal()
            ->set('scores.' . $this->kriteriaFinal->id, 10)
            ->call('saveScores');

        $finalisasiPenyisihan = AssessmentScore::where('registration_id', $this->reg->id)
            ->where('assessment_criteria_id', $this->kriteriaPenyisihan->id)
            ->value('is_finalized');
        $finalisasiFinal = AssessmentScore::where('registration_id', $this->reg->id)
            ->where('assessment_criteria_id', $this->kriteriaFinal->id)
            ->value('is_finalized');

        $this->assertTrue((bool) $finalisasiPenyisihan, 'Nilai penyisihan harus terkunci.');
        $this->assertFalse((bool) $finalisasiFinal, 'Finalisasi penyisihan ikut mengunci babak final.');

        // Dan nilai final masih bisa diubah setelah itu.
        $this->panelFinal()
            ->set('scores.' . $this->kriteriaFinal->id, 20)
            ->call('saveScores');

        $this->assertSame(20, (int) AssessmentScore::where('registration_id', $this->reg->id)
            ->where('assessment_criteria_id', $this->kriteriaFinal->id)
            ->value('score'));
    }

    public function test_penyisihan_terkunci_tidak_membekukan_babak_final_di_layar()
    {
        $this->panelPenyisihan()
            ->set('scores.' . $this->kriteriaPenyisihan->id, 20)
            ->call('finalizeScores');

        // Membuka babak final harus tampil belum terkunci.
        $this->panelFinal()
            ->assertSet('isFinalized', false);

        // Dan sebaliknya: membuka penyisihan lagi tampil terkunci.
        $this->panelPenyisihan()
            ->assertSet('isFinalized', true);
    }

    public function test_memuat_satu_babak_tidak_menarik_nilai_babak_lain()
    {
        $this->panelPenyisihan()
            ->set('scores.' . $this->kriteriaPenyisihan->id, 20)
            ->call('saveScores');

        $this->panelFinal()
            ->assertSet('scores.' . $this->kriteriaFinal->id, null)
            ->assertSet('scores.' . $this->kriteriaPenyisihan->id, null);
    }

    public function test_reset_penyisihan_tidak_menghapus_nilai_final()
    {
        $this->panelPenyisihan()
            ->set('scores.' . $this->kriteriaPenyisihan->id, 20)
            ->call('saveScores');

        $this->panelFinal()
            ->set('scores.' . $this->kriteriaFinal->id, 10)
            ->call('saveScores');

        $this->panelPenyisihan()
            ->call('resetScores');

        $this->assertDatabaseMissing('assessment_scores', [
            'registration_id' => $this->reg->id,
            'assessment_criteria_id' => $this->kriteriaPenyisihan->id,
        ]);
        $this->assertDatabaseHas('assessment_scores', [
            'registration_id' => $this->reg->id,
            'assessment_criteria_id' => $this->kriteriaFinal->id,
            'judge_id' => $this->juri->id,
        ]);
    }

    /** Babak datang dari DOM pada jalur lain — babak tenant lain wajib ditolak. */
    public function test_babak_milik_event_lain_ditolak()
    {
        $lain = Eventner::factory()->create(['status' => 'approved']);
        $babakLain = CompetitionRound::create([
            'eventner_id' => $lain->id,
            'competition_category_id' => CompetitionCategory::factory()->create([
                'eventner_id' => $lain->id,
                'parent_id' => null,
            ])->id,
            'name' => 'Final',
            'type' => CompetitionRound::TYPE_FINAL,
        ]);

        $this->panelBebas()
            ->set('selectedRoundId', $babakLain->id)
            ->assertSet('selectedRoundId', null);
    }

    /** Chip grup tenant lain tidak boleh menggeser saringan. */
    public function test_grup_milik_event_lain_ditolak_di_chip()
    {
        $lain = Eventner::factory()->create(['status' => 'approved']);
        $grupLain = CompetitionGroup::create([
            'eventner_id' => $lain->id,
            'competition_category_id' => CompetitionCategory::factory()->create([
                'eventner_id' => $lain->id,
                'parent_id' => null,
            ])->id,
            'name' => 'Grup Lain',
        ]);

        $this->panel()->call('selectScope', (string) $grupLain->id)
            ->assertSet('selectedGroupId', null);
    }

    /**
     * Membuka peserta di scope yang salah tidak dibiarkan: peserta yang tidak
     * masuk daftar (grup lain, atau bukan finalis) dikembalikan ke daftar
     * peserta, bukan dinilai diam-diam di luar yang terlihat di layar.
     */
    public function test_peserta_di_luar_scope_dikembalikan_ke_daftar()
    {
        $this->panelPenyisihan()
            ->call('selectScope', (string) $this->groupB->id)
            ->assertSet('view', 'participants');
    }

    /**
     * finalizeAllForCategory() dibatasi ke kriteria babak terpilih: finalisasi
     * massal penyisihan tidak boleh mengunci final sebelum digelar.
     */
    public function test_finalisasi_massal_terbatas_pada_babak_terpilih()
    {
        // Nilai penyisihan DAN final sudah tersimpan, keduanya belum terkunci.
        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'assessment_criteria_id' => $this->kriteriaPenyisihan->id,
            'judge_id' => $this->juri->id,
            'score' => 20,
        ]);
        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'assessment_criteria_id' => $this->kriteriaFinal->id,
            'judge_id' => $this->juri->id,
            'score' => 10,
        ]);

        $this->panelPenyisihan()
            ->call('finalizeAllForCategory');

        $this->assertTrue((bool) AssessmentScore::where('registration_id', $this->reg->id)
            ->where('assessment_criteria_id', $this->kriteriaPenyisihan->id)
            ->value('is_finalized'));
        $this->assertFalse((bool) AssessmentScore::where('registration_id', $this->reg->id)
            ->where('assessment_criteria_id', $this->kriteriaFinal->id)
            ->value('is_finalized'), 'Finalisasi massal penyisihan ikut mengunci babak final.');
    }

    public function test_finalisasi_massal_babak_final_hanya_mengunci_kriteria_final()
    {
        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'assessment_criteria_id' => $this->kriteriaPenyisihan->id,
            'judge_id' => $this->juri->id,
            'score' => 20,
        ]);
        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->reg->id,
            'assessment_criteria_id' => $this->kriteriaFinal->id,
            'judge_id' => $this->juri->id,
            'score' => 10,
        ]);

        $this->panelFinal()
            ->call('finalizeAllForCategory');

        $this->assertTrue((bool) AssessmentScore::where('registration_id', $this->reg->id)
            ->where('assessment_criteria_id', $this->kriteriaFinal->id)
            ->value('is_finalized'));
        $this->assertFalse((bool) AssessmentScore::where('registration_id', $this->reg->id)
            ->where('assessment_criteria_id', $this->kriteriaPenyisihan->id)
            ->value('is_finalized'));
    }

    /**
     * Panel panitia menampilkan juri sesuai PENUGASAN GRUP, bukan serinya.
     *
     * Ini inti keputusan "grup menggantikan seri": juri Seri B yang ditugaskan
     * ke Grup A tetap muncul saat panitia membuka peserta Grup A — yang
     * menentukan cuma centang di modal Kelola Grup. Lembar nilainya menyusul
     * dari seri masing-masing peserta.
     */
    public function test_daftar_juri_panel_mengikuti_grup_peserta()
    {
        $juriB = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri Seri B']);
        $this->makeRubric('PBB Seri B', $this->penyisihan, $this->seriB, $juriB);

        // Juri Umum tercentang di Grup A; juri Seri B memang belum ditugaskan
        // ke grup mana pun, jadi ia belum muncul.
        CompetitionGroup::syncJudges($this->level->id, CompetitionGroup::SCOPE_GROUP, $this->groupA->id, [$this->juri->id]);
        $this->panel()->assertSee('Juri Umum')->assertDontSee('Juri Seri B');

        // Begitu ia dicentang untuk Grup A, ia muncul di lembar Grup A —
        // walau rubrik yang dipegangnya berseri B.
        CompetitionGroup::syncJudges($this->level->id, CompetitionGroup::SCOPE_GROUP, $this->groupA->id, [$this->juri->id, $juriB->id]);
        $this->panel()->assertSee('Juri Seri B');
    }

    /** Juri yang hanya tercentang di Grup B tidak muncul di lembar Grup A. */
    public function test_juri_grup_lain_tidak_muncul_di_panel_grup_ini()
    {
        $juriB = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri Grup B']);
        CompetitionGroup::syncJudges($this->level->id, CompetitionGroup::SCOPE_GROUP, $this->groupB->id, [$juriB->id]);

        $this->panel()->assertDontSee('Juri Grup B');
    }

    /**
     * Kartu grup menyebut seri apa saja yang dihuni grup itu.
     *
     * Grup dan seri dua sumbu bebas: satu grup boleh dihuni lebih dari satu
     * seri. Tanpa pecahan ini kartu grup cuma berbunyi "2 Peserta" dan panitia
     * tak punya cara tahu lembar nilai mana yang menunggu di dalamnya.
     */
    public function test_kartu_grup_menampilkan_ringkasan_seri()
    {
        Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $this->groupA->id,
            'competition_series_id' => $this->seriB->id,
            'nama_sekolah' => 'SMPN 2',
        ]);

        $panel = Livewire::test(\App\Livewire\Eventner\Scoring\Index::class)
            ->call('selectCategory', $this->level->id)
            ->assertViewHas('groupSeriesCounts', function (array $counts) {
                $grupA = collect($counts[$this->groupA->id] ?? []);

                return $grupA->firstWhere('nama', 'Seri A')['jumlah'] === 1
                    && $grupA->firstWhere('nama', 'Seri B')['jumlah'] === 1;
            });

        // Terbaca di layar, bukan cuma ada di view data.
        $panel->assertSee('Seri A 1')->assertSee('Seri B 1');
    }

    /** Peserta yang belum dapat seri dihitung terpisah, bukan hilang dari kartu. */
    public function test_kartu_grup_menandai_peserta_tanpa_seri()
    {
        Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $this->groupA->id,
            'competition_series_id' => null,
            'nama_sekolah' => 'SMPN 2',
        ]);

        Livewire::test(\App\Livewire\Eventner\Scoring\Index::class)
            ->call('selectCategory', $this->level->id)
            ->assertViewHas('groupSeriesCounts', function (array $counts) {
                $grupA = collect($counts[$this->groupA->id] ?? []);

                $tanpaSeri = $grupA->firstWhere('tanpa_seri', true);

                return $tanpaSeri !== null
                    && $tanpaSeri['nama'] === 'Tanpa Seri'
                    && $tanpaSeri['jumlah'] === 1
                    // Selalu paling akhir supaya chipnya tidak menyela di
                    // antara seri bernama.
                    && $grupA->last()['tanpa_seri'] === true;
            });
    }

    /**
     * Kartu grup membawa regu jurinya, dan grup tanpa juri ditandai.
     *
     * Inilah jawaban pertanyaan "juri Grup A siapa saja" tanpa harus membuka
     * modal: panitia melihat pembagiannya langsung di kartu, dan grup yang
     * belum dicentang siapa pun tampak mencolok — peserta di dalamnya memang
     * tak bisa dinilai siapa pun kalau dibiarkan begitu.
     */
    public function test_kartu_grup_menampilkan_juri_yang_bertugas()
    {
        $juriB = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri B']);
        CompetitionGroup::syncJudges($this->level->id, CompetitionGroup::SCOPE_GROUP, $this->groupB->id, [$juriB->id]);

        $grupC = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup C',
        ]);

        $panel = Livewire::test(\App\Livewire\Eventner\Scoring\Index::class)
            ->call('selectCategory', $this->level->id)
            ->assertViewHas('groupJudgeNames', function (array $nama) use ($grupC) {
                return $nama['group:' . $this->groupA->id] === ['Juri Umum']
                    && $nama['group:' . $this->groupB->id] === ['Juri B']
                    && ! isset($nama['group:' . $grupC->id]);
            });

        // Terbaca di kartunya, bukan cuma ada di view data.
        $panel->assertSee('Juri Umum')->assertSee('Juri B')->assertSee('Belum ada juri');
    }

    /**
     * Label juri menyebut BARIS PENUGASANNYA, bukan rubrik yang dipegang.
     *
     * Deretan nama juri telanjang terbaca seperti kebocoran saringan.
     * "Juri Umum · Grup A" langsung menjelaskan kenapa ia ada di lembar ini.
     */
    public function test_tombol_juri_menampilkan_grup_yang_dipegang()
    {
        $juriA = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri Seri A']);
        $this->makeRubric('PBB Seri A', $this->penyisihan, $this->seriA, $juriA);

        $this->panelScoringGrupA()
            ->set('selectedJudgeId', null)
            ->call('loadJudges')
            ->assertViewHas('judgeGroupLabels', function (array $label) {
                // Peserta ada di Grup A, jadi seluruh jurinya berlabel Grup A —
                // bukan nama seri rubrik yang mereka pegang.
                return $label !== [] && collect($label)->every(fn ($v) => $v === 'Grup A');
            });
    }

    /**
     * Label juri ikut baris penugasan yang sedang berlaku: saat panel membuka
     * babak Final dengan penugasan `final` tersendiri, labelnya "Final".
     */
    public function test_label_juri_mengikuti_baris_penugasan_babak()
    {
        CompetitionGroup::syncJudges($this->level->id, CompetitionGroup::SCOPE_FINAL, null, [$this->juri->id]);

        $this->panelFinal()
            ->set('selectedJudgeId', null)
            ->call('loadJudges')
            ->assertViewHas('judgeGroupLabels', fn (array $label) => collect($label)->every(fn ($v) => $v === 'Final'));
    }

    /**
     * Panel ikut disaring per babak lewat baris `final` tersendiri.
     *
     * Juri yang cuma tercentang di baris Final tidak muncul saat panitia
     * menilai penyisihan — dan sebaliknya ia muncul begitu panel dibuka pada
     * scope Final, karena peserta finalis pindah ke baris `final` itu.
     *
     * Begitu ada satu juri di baris final, SELURUH peserta finalis pindah ke
     * sana — termasuk bagi juri grup yang tak ditugaskan ke final.
     */
    public function test_daftar_juri_panel_mengikuti_babak()
    {
        $juriFinal = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri Final']);
        $this->makeRubric('PBB Final Saja', $this->final, null, $juriFinal);

        CompetitionGroup::syncJudges($this->level->id, CompetitionGroup::SCOPE_FINAL, null, [$juriFinal->id]);

        $this->panelPenyisihan()->assertSee('Juri Umum')->assertDontSee('Juri Final');

        $this->panelFinal()->assertSee('Juri Final')->assertDontSee('Juri Umum');
    }

    /**
     * Panel juri yang kosong menjelaskan baris mana yang belum diisi, dan
     * menawarkan jalan keluar.
     *
     * Panel kosong tanpa sebab membuat tombol Simpan tampak rusak: operator
     * melihat lembar nilai tanpa satu pun juri, tanpa tahu apa yang harus
     * dibereskan di mana.
     */
    public function test_panel_kosong_menyebut_baris_penugasan_yang_belum_diisi()
    {
        // Grup B belum punya penugasan sendiri; peserta di sana jatuh ke baris
        // "Belum Bergrup" yang juga belum diisi siapa pun.
        $diGrupB = $this->pesertaGrupB();

        Livewire::test(\App\Livewire\Eventner\Scoring\Index::class)
            ->call('selectCategory', $this->level->id)
            ->call('selectParticipant', $diGrupB->id)
            ->assertViewHas('barisPenugasan', 'Grup B')
            ->assertSee('belum ada')
            ->assertSee('Buka Kategori Lomba');
    }

    private function pesertaGrupB(): Registration
    {
        return Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $this->groupB->id,
            'nama_sekolah' => 'SMPN 2',
        ]);
    }

    /** Chip grup menyaring daftar ke sekolah grup itu saja. */
    public function test_chip_grup_menyaring_daftar_peserta()
    {
        $this->pesertaGrupB();

        $this->panel()->call('selectScope', (string) $this->groupA->id)
            ->assertViewHas('participants', fn ($p) => $p->pluck('nama_sekolah')->all() === ['SMPN 1']);

        $this->panel()->call('selectScope', (string) $this->groupB->id)
            ->assertViewHas('participants', fn ($p) => $p->pluck('nama_sekolah')->all() === ['SMPN 2']);
    }

    /**
     * Chip "Semua" tidak menyaring grup, dan tetap membuka babak penyisihan:
     * scope non-final berarti penyisihan, bukan "seluruh babak sekaligus" —
     * rubrik final hanya relevan untuk peserta yang lolos.
     */
    public function test_chip_semua_menampilkan_seluruh_peserta()
    {
        $this->pesertaGrupB();

        $this->panel()->call('selectScope', 'all')
            ->assertViewHas('participants', fn ($p) => $p->count() === 2)
            ->assertSet('selectedGroupId', null)
            ->assertSet('selectedRoundId', $this->penyisihan->id);
    }

    /**
     * Chip Final menyaring ke peserta yang tercatat lolos saja, dan memuat
     * rubrik babak final.
     */
    public function test_chip_final_hanya_menampilkan_finalis()
    {
        $this->pesertaGrupB();

        CompetitionRoundRegistration::create([
            'eventner_id' => $this->eventner->id,
            'competition_round_id' => $this->final->id,
            'registration_id' => $this->reg->id,
            'competition_group_id' => $this->groupA->id,
        ]);

        $this->panel()->call('selectScope', 'final')
            ->assertViewHas('participants', fn ($p) => $p->pluck('nama_sekolah')->all() === ['SMPN 1'])
            ->assertSet('selectedRoundId', $this->final->id);
    }

    /**
     * Tingkat tanpa grup dan tanpa babak tidak menampilkan layar pemilih apa
     * pun — 13 event lama harus berperilaku persis seperti sebelum fitur ini.
     */
    public function test_tingkat_polos_tanpa_chip()
    {
        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
        ]);
        $polos = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
        ]);

        Livewire::test(\App\Livewire\Eventner\Scoring\Index::class)
            ->call('selectCategory', $polos->id)
            ->assertViewHas('groups', fn ($g) => $g->isEmpty())
            ->assertViewHas('rounds', fn ($r) => $r->isEmpty())
            ->assertSet('view', 'participants');
    }

    /**
     * Tingkat bergrup mampir dulu ke layar pemilih grup — bukan langsung ke
     * daftar gabungan seluruh sekolah.
     */
    public function test_tingkat_bergrup_mampir_ke_layar_grup_dulu()
    {
        Livewire::test(\App\Livewire\Eventner\Scoring\Index::class)
            ->call('selectCategory', $this->level->id)
            ->assertSet('view', 'groups')
            // Nama grup harus benar-benar tercetak: state PHP yang benar tapi
            // blade tanpa cabang 'groups' akan merender halaman kosong diam-diam.
            ->assertSee('Grup A')
            ->assertSee('Grup B')
            ->assertViewHas('groupCounts', fn ($c) => (int) ($c[$this->groupA->id] ?? 0) === 1);
    }

    /** Tingkat tanpa grup tapi berbabak final juga perlu layar perantara. */
    public function test_tingkat_tanpa_grup_tapi_ada_final_tetap_mampir()
    {
        $finalSaja = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => CompetitionCategory::factory()->create([
                'eventner_id' => $this->eventner->id,
                'parent_id' => null,
            ])->id,
        ]);
        CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $finalSaja->id,
            'name' => 'Final',
            'type' => CompetitionRound::TYPE_FINAL,
        ]);

        Livewire::test(\App\Livewire\Eventner\Scoring\Index::class)
            ->call('selectCategory', $finalSaja->id)
            ->assertSet('view', 'groups');
    }

    /** Mengklik kartu grup di layar perantara membuka daftar sekolah grup itu. */
    public function test_pilih_grup_dari_layar_grup_membuka_daftar_grupnya()
    {
        $this->pesertaGrupB();

        Livewire::test(\App\Livewire\Eventner\Scoring\Index::class)
            ->call('selectCategory', $this->level->id)
            ->call('selectGroupScope', (string) $this->groupA->id)
            ->assertSet('view', 'participants')
            ->assertSet('selectedGroupId', $this->groupA->id)
            ->assertViewHas('participants', fn ($p) => $p->pluck('nama_sekolah')->all() === ['SMPN 1']);
    }

    /**
     * Peserta yang belum dibagi grup tidak muncul di kartu grup mana pun.
     * Tanpa jalur sendiri mereka lenyap dari jangkauan panitia.
     */
    public function test_peserta_belum_bergrup_punya_jalur_sendiri()
    {
        Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => null,
            'nama_sekolah' => 'SMPN 9',
        ]);

        Livewire::test(\App\Livewire\Eventner\Scoring\Index::class)
            ->call('selectCategory', $this->level->id)
            ->assertViewHas('ungroupedCount', fn ($n) => $n === 1)
            ->call('selectGroupScope', 'ungrouped')
            ->assertSet('view', 'participants')
            ->assertViewHas('participants', fn ($p) => $p->pluck('nama_sekolah')->all() === ['SMPN 9']);
    }

    /**
     * Hanya grupnya sendiri yang tampil — peserta di grup lain tidak ikut
     * terbawa ke jalur "belum bergrup", dan sebaliknya.
     */
    public function test_jalur_belum_bergrup_tidak_membawa_peserta_bergrup()
    {
        $this->pesertaGrupB();

        $this->panel()->call('selectScope', 'ungrouped')
            ->assertSet('ungroupedOnly', true)
            ->assertSet('selectedGroupId', null)
            ->assertViewHas('participants', fn ($p) => $p->isEmpty());
    }

    /** Kembali dari daftar peserta mendarat di layar pemilih grup, bukan daftar kategori. */
    public function test_kembali_dari_daftar_peserta_ke_layar_grup()
    {
        $this->panelPenyisihan()
            ->call('backFromParticipants')
            ->assertSet('view', 'groups')
            ->assertSet('selectedGroupId', null)
            ->assertSet('selectedRoundId', null);
    }

    /**
     * Tautan PDF di panel penilaian membawa babak yang sedang dibuka.
     *
     * Ini keluhan yang dilaporkan: saat panel dibuka pada fase grup, lembar
     * yang tercetak justru final — karena controller menebak dari daftar
     * finalis, bukan dari babak yang dilihat operator.
     */
    public function test_tautan_pdf_membawa_babak_yang_sedang_dibuka()
    {
        $this->panelFinal()
            ->assertSee('round_id=' . $this->final->id, false);

        $this->panelPenyisihan()
            ->assertSee('round_id=' . $this->penyisihan->id, false)
            ->assertDontSee('round_id=' . $this->final->id, false);
    }

    /** Tingkat tanpa babak: tautan lama tanpa round_id tetap utuh. */
    public function test_tautan_pdf_tanpa_babak_tidak_membawa_round_id()
    {
        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
        ]);
        $polos = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
        ]);
        $reg = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $polos->id,
            'nama_sekolah' => 'SMPN 9',
        ]);

        Livewire::test(\App\Livewire\Eventner\Scoring\Index::class)
            ->call('selectCategory', $polos->id)
            ->call('selectParticipant', $reg->id)
            ->assertDontSee('round_id=', false);
    }
}
