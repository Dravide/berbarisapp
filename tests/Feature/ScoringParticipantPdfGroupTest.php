<?php

namespace Tests\Feature;

use App\Http\Controllers\Eventner\ScoringController;
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
use Tests\TestCase;

/**
 * Lembar penilaian per peserta (PDF) pada tingkat berseri.
 *
 * Dua kebocoran yang dijaga di sini, keduanya berasal dari daftar yang tidak
 * disaring per seri:
 *
 *  1. Rubrik seri lain ikut tercetak di lembar peserta ini, lalu subtotalnya
 *     mengotori nilai akhir dengan kriteria yang tak pernah dinilai.
 *  2. Juri yang cuma memegang rubrik seri lain muncul sebagai kolom penilai,
 *     padahal ia tidak pernah menilai peserta ini.
 *
 * Grup tetap dipakai di beberapa tes di bawah sebagai bentuk data nyata LOBB —
 * satu seri per grup — tapi yang disaring sekarang adalah serinya, bukan
 * grupnya. Grup dan seri dua sumbu bebas: satu grup boleh memuat dua seri.
 *
 * PDF-nya sendiri tidak dirender di sini — yang diuji adalah daftar apa yang
 * diserahkan ke view, karena di situlah letak bug-nya.
 */
class ScoringParticipantPdfGroupTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $level;

    private CompetitionGroup $groupA;

    private CompetitionGroup $groupB;

    private CompetitionSeries $seriA;

    private CompetitionSeries $seriB;

    private Judge $juriA;

    private Judge $juriB;

    private Registration $regA;

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

        // Seri sejajar dengan grup, bukan di bawahnya: peserta memilih seri
        // lewat kolomnya sendiri, jadi satu grup boleh memuat dua seri.
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

        $this->juriA = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri Seri A']);
        $this->juriB = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri Seri B']);

        // Babak: rubrik seri milik Penyisihan, rubrik final sengaja TANPA seri —
        // persis bentuk data nyata yang membuat juri final bocor ke lembar
        // penyisihan lewat klausa "seri NULL".
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

        $this->rubrik('Rubrik Seri A', $this->seriA, $this->juriA, $this->penyisihan);
        $this->rubrik('Rubrik Seri B', $this->seriB, $this->juriB, $this->penyisihan);

        $this->regA = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $this->groupA->id,
            'competition_series_id' => $this->seriA->id,
            'nama_sekolah' => 'SMPN 1',
        ]);
    }

    private function rubrik(string $name, ?CompetitionSeries $series, Judge $judge, ?CompetitionRound $round = null): AssessmentCategory
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

        $judge->assessmentCategories()->attach($category->id);

        AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Kriteria ' . $name,
            'score_options' => [['score' => 10], ['score' => 20]],
            'weight' => 1,
            'sort_order' => 1,
        ]);

        return $category;
    }

    /**
     * Rubrik yang tercetak di lembar peserta ini.
     *
     * Diverifikasi lewat metode controller, bukan HTTP: dompdf menutup
     * viewData-nya, jadi daftar yang diserahkan ke view tidak bisa dibaca dari
     * response. Yang diuji tetap keputusan yang sama — metode inilah yang
     * dipanggil downloadParticipantPdf().
     */
    private function rubrikPdf(int $registrationId, ?int $roundId = null): array
    {
        $reg = Registration::where('eventner_id', $this->eventner->id)->findOrFail($registrationId);

        return array_values(app(ScoringController::class)->assessmentCategoriesFor($reg, $roundId)->pluck('name')->all());
    }

    /** Nama juri yang tercetak sebagai kolom penilai di lembar peserta ini. */
    private function juriPdf(int $registrationId, ?int $roundId = null): array
    {
        $reg = Registration::where('eventner_id', $this->eventner->id)->findOrFail($registrationId);

        return array_values(app(ScoringController::class)->judgesFor($reg, $roundId)->pluck('name')->all());
    }

    /** Dan rutenya sendiri tetap menghasilkan PDF — bukan 500 gara-gara saringan baru. */
    public function test_route_tetap_menghasilkan_pdf()
    {
        $this->get(route('eventner.scoring.pdf-participant', [
            'registration_id' => $this->regA->id,
        ]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        // Lembar babak final juga bisa diminta eksplisit lewat ?round_id=.
        $this->get(route('eventner.scoring.pdf-participant', [
            'registration_id' => $this->regA->id,
            'round_id' => $this->final->id,
        ]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_rubrik_seri_lain_tidak_ikut_di_lembar_peserta()
    {
        $this->assertSame(['Rubrik Seri A'], $this->rubrikPdf($this->regA->id));
    }

    /** Inilah keluhan yang dilaporkan: juri seri lain muncul di lembar peserta. */
    public function test_juri_seri_lain_tidak_muncul_di_lembar_peserta()
    {
        $this->assertSame(['Juri Seri A'], $this->juriPdf($this->regA->id));
    }

    /** Peserta Seri B mendapat kebalikannya — saringannya bukan sekadar "buang Seri B". */
    public function test_peserta_seri_b_dapat_rubrik_dan_jurinya()
    {
        $regB = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $this->groupB->id,
            'competition_series_id' => $this->seriB->id,
            'nama_sekolah' => 'SMPN 2',
        ]);

        $this->assertSame(['Rubrik Seri B'], $this->rubrikPdf($regB->id));
        $this->assertSame(['Juri Seri B'], $this->juriPdf($regB->id));
    }

    /**
     * Peserta yang belum dibagi seri hanya melihat rubrik dan juri TANPA seri.
     *
     * Rubrik berseri sengaja tidak ikut: kalau ikut, pemisahan seri tak berarti
     * — peserta yang tak masuk seri mana pun tetap melihat kolom penilai Seri A
     * dan Seri B. Konsekuensinya tingkat yang rubriknya sudah ditandai per seri
     * tetapi pesertanya belum dibagi tampil kosong, dan itu memang yang
     * diinginkan: bagi serinya lebih dulu.
     */
    public function test_peserta_tanpa_seri_hanya_melihat_rubrik_tanpa_seri()
    {
        $juriUmum = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri Umum']);
        $rubrikUmum = $this->rubrik('Rubrik Umum', $this->seriA, $juriUmum);
        $rubrikUmum->update(['competition_series_id' => null]);

        $polos = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => null,
            'competition_series_id' => null,
            'nama_sekolah' => 'SMPN 9',
        ]);

        $this->assertSame(['Rubrik Umum'], $this->rubrikPdf($polos->id));
        $this->assertSame(['Juri Umum'], $this->juriPdf($polos->id));
    }

    /** Tingkat yang rubriknya seluruhnya berseri tampil kosong untuk peserta belum-berseri. */
    public function test_peserta_tanpa_seri_dan_tanpa_rubrik_umum_tampil_kosong()
    {
        $polos = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => null,
            'competition_series_id' => null,
            'nama_sekolah' => 'SMPN 9',
        ]);

        $this->assertSame([], $this->rubrikPdf($polos->id));
        $this->assertSame([], $this->juriPdf($polos->id));
    }

    /**
     * INI keluhan kedua yang dilaporkan: juri final muncul di lembar peserta
     * yang sedang dinilai di fase grup.
     *
     * Bentuk datanya yang bikin bocor: rubrik babak final sengaja TIDAK berseri
     * (babaknya berlaku untuk semua finalis), jadi klausa "seri NULL" ikut
     * meloloskannya selama babak tidak disaring.
     */
    public function test_juri_babak_final_tidak_muncul_di_lembar_penyisihan()
    {
        $juriFinal = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri Final']);
        $this->rubrik('Rubrik Final', null, $juriFinal, $this->final);

        $this->assertSame(['Juri Seri A'], $this->juriPdf($this->regA->id));
        $this->assertSame(['Rubrik Seri A'], $this->rubrikPdf($this->regA->id));

        // Rubrik final pun tidak boleh tercetak sebagai kolom di lembar ini.
        $this->assertNotContains('Rubrik Final', $this->rubrikPdf($this->regA->id));
    }

    /**
     * INI keluhan ketiga: sekolah yang sudah lolos final dan nilainya sudah
     * diinput tetap tercetak dengan lembar fase grup saat diunduh dari daftar
     * peserta (tanpa ?round_id=).
     *
     * Sekolah finalis berdiri pada tingkat lomba yang sama dengan peserta
     * penyisihan, jadi identitasnya sendiri tidak bisa dipakai menebak babak —
     * yang menentukan adalah baris kelolosannya.
     */
    public function test_finalis_diunduh_tanpa_round_id_mendapat_lembar_final()
    {
        $juriFinal = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri Final']);
        $this->rubrik('Rubrik Final', null, $juriFinal, $this->final);

        CompetitionRoundRegistration::create([
            'eventner_id' => $this->eventner->id,
            'competition_round_id' => $this->final->id,
            'registration_id' => $this->regA->id,
            'competition_group_id' => $this->groupA->id,
        ]);

        // Lembar bawaan = final, bukan fase grup.
        $this->assertSame(['Rubrik Final'], $this->rubrikPdf($this->regA->id));
        $this->assertSame(['Juri Final'], $this->juriPdf($this->regA->id));

        // Babak lama masih bisa diminta eksplisit — koreksi nilai tetap mungkin.
        $this->assertSame(['Rubrik Seri A'], $this->rubrikPdf($this->regA->id, $this->penyisihan->id));
    }

    /** Bukan finalis: lembar bawaan tetap fase grup, perilaku lama tak berubah. */
    public function test_peserta_bukan_finalis_tetap_dapat_lembar_penyisihan()
    {
        $juriFinal = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri Final']);
        $this->rubrik('Rubrik Final', null, $juriFinal, $this->final);

        $this->assertSame(['Rubrik Seri A'], $this->rubrikPdf($this->regA->id));
    }

    /** Tingkat tanpa babak final: tak ada kelolosan yang bisa menebak babak. */
    public function test_tingkat_tanpa_babak_final_tidak_terpengaruh()

    {
        $this->final->delete();

        $this->assertSame(['Rubrik Seri A'], $this->rubrikPdf($this->regA->id));
    }

    /** Lembar babak final dicetak sendiri, dan di situ juri final memang muncul. */
    public function test_lembar_babak_final_memuat_rubrik_dan_juri_final()
    {
        $juriFinal = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri Final']);
        $this->rubrik('Rubrik Final', null, $juriFinal, $this->final);

        $this->assertSame(['Rubrik Final'], $this->rubrikPdf($this->regA->id, $this->final->id));
        $this->assertSame(['Juri Final'], $this->juriPdf($this->regA->id, $this->final->id));
    }

    /** Tingkat tanpa baris babak tetap tanpa saringan babak — perilaku lama. */
    public function test_tingkat_tanpa_babak_memakai_rubrik_apa_adanya()
    {
        $induk = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
        ]);
        $polos = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $induk->id,
        ]);

        $juri = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri Polos']);
        $this->rubrikUntukTingkat('Rubrik Polos', $polos, $juri);

        $reg = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $polos->id,
            'competition_group_id' => null,
            'nama_sekolah' => 'SMPN 3',
        ]);

        $this->assertSame(['Rubrik Polos'], $this->rubrikPdf($reg->id));
        $this->assertSame(['Juri Polos'], $this->juriPdf($reg->id));
    }

    /** Tingkat tanpa seri berperilaku persis seperti sebelum fitur seri. */
    public function test_tingkat_tanpa_seri_tidak_berubah()
    {
        $induk = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
        ]);
        $polos = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $induk->id,
        ]);

        $juri = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri Polos']);
        $this->rubrikUntukTingkat('Rubrik Polos', $polos, $juri);

        $reg = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $polos->id,
            'competition_group_id' => null,
            'competition_series_id' => null,
            'nama_sekolah' => 'SMPN 3',
        ]);

        $this->assertSame(['Rubrik Polos'], $this->rubrikPdf($reg->id));
        $this->assertSame(['Juri Polos'], $this->juriPdf($reg->id));
    }

    /** Rubrik tingkat (tanpa seri) tetap berlaku di tingkat berseri. */
    private function rubrikUntukTingkat(string $name, CompetitionCategory $level, Judge $judge): AssessmentCategory
    {
        $category = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $level->id,
            'competition_series_id' => null,
            'name' => $name,
            'sort_order' => 1,
        ]);

        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $category->id,
            'name' => 'Sub ' . $name,
        ]);

        $judge->assessmentCategories()->attach($category->id);

        AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Kriteria ' . $name,
            'score_options' => [['score' => 10]],
            'weight' => 1,
            'sort_order' => 1,
        ]);

        return $category;
    }
}
