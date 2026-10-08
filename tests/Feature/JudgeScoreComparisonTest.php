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
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\ScoreDeduction;
use App\Models\User;
use App\Services\JudgeScoreComparison;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Perbandingan nilai antar juri.
 *
 * Yang dijaga di sini bukan sekadar "halamannya terbuka", tapi bahwa angka
 * yang keluar dari JudgeScoreComparison memang berarti:
 *
 *   - satuan analisisnya sel (peserta × kriteria), bukan total juri — total
 *     per juri tidak setara sejak rubrik boleh dibagi;
 *   - skala kriteria yang berbeda disetarakan sebelum dibandingkan;
 *   - bobot kriteria tidak ikut campur, karena ia sifat kriteria bukan juri;
 *   - dua juri tanpa sel beririsan TIDAK dipaksa dibandingkan;
 *   - babak, grup, dan seri tetap terpisah seperti di ScoreRecapBuilder.
 */
class JudgeScoreComparisonTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $level;

    private CompetitionGroup $grupA;

    private CompetitionGroup $grupB;

    private CompetitionRound $penyisihan;

    private CompetitionRound $final;

    private CompetitionSeries $seriA;

    private Judge $juriA;

    private Judge $juriB;

    private Judge $juriC;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->eventner()->create(['is_active' => true]);
        $this->eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        $induk = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
        ]);
        $this->level = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $induk->id,
        ]);

        $this->grupA = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup A',
        ]);
        $this->grupB = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup B',
        ]);

        $this->seriA = CompetitionSeries::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Seri A',
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

        $this->juriA = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri A']);
        $this->juriB = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri B']);
        $this->juriC = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri C']);

        // Ketiga juri dipekerjakan pada grup yang sama; pemisahan tugas nanti
        // murni dari centangan rubrik, bukan dari penugasan grup.
        CompetitionGroup::syncJudges(
            $this->level->id,
            CompetitionGroup::SCOPE_GROUP,
            $this->grupA->id,
            [$this->juriA->id, $this->juriB->id, $this->juriC->id],
        );

        $this->actingAs($this->eventner->user);
    }

    // ── Perkakas ────────────────────────────────────────────────────────

    /**
     * Peserta tanpa seri secara default.
     *
     * Rubrik berseri hanya terbuka bagi peserta berseri itu (lihat
     * scopeForEntry), jadi peserta yang serinya tidak disebut justru melihat
     * SEMUA rubrik tingkat. Itu yang diinginkan di hampir semua uji di sini:
     * yang sedang diuji perbandingan juri, bukan penyaringan seri.
     */
    private function peserta(string $nama, ?CompetitionGroup $grup = null, ?CompetitionSeries $seri = null): Registration
    {
        return Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => ($grup ?? $this->grupA)->id,
            'competition_series_id' => $seri?->id,
            'nama_sekolah' => $nama,
        ]);
    }

    /**
     * Satu kriteria dalam rubriknya sendiri.
     *
     * $pengisi kosong = rubrik belum dicentang, jadi terbuka untuk semua juri
     * baris penugasan (perilaku sebelum pembagian rubrik ada).
     *
     * @param  array<int, int|float|string>  $opsi
     */
    private function rubrik(
        string $nama,
        array $opsi,
        int $weight = 1,
        ?CompetitionRound $babak = null,
        ?Judge $pengisi = null,
        ?CompetitionSeries $seri = null,
    ): AssessmentCriteria {
        $kategori = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'competition_round_id' => ($babak ?? $this->penyisihan)->id,
            // Default null = berlaku semua seri. Rubrik berseri hanya terbuka
            // untuk peserta berseri itu — uji yang tak berniat menyaring seri
            // harus membiarkannya kosong.
            'competition_series_id' => $seri?->id,
            'name' => $nama,
            'sort_order' => 1,
        ]);

        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $kategori->id,
            'name' => 'Umum',
            'sort_order' => 1,
        ]);

        $criteria = AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => $nama,
            'score_options' => array_map(fn ($v) => ['score' => $v], $opsi),
            'weight' => $weight,
            'sort_order' => 1,
        ]);

        if ($pengisi) {
            $kategori->syncRubricJudges([$pengisi->id]);
        }

        return $criteria;
    }

    /**
     * Banyak peserta sekaligus.
     *
     * Ambang MIN_SEL = 5 membuat uji yang benar-benar memeriksa angka bias
     * butuh lebih dari empat sel pembanding. Satu peserta = satu sel per
     * kriteria, jadi uji semacam itu memakai beberapa peserta.
     *
     * @return array<int, Registration>
     */
    private function pesertaBanyak(int $n, ?CompetitionGroup $grup = null, ?CompetitionSeries $seri = null): array
    {
        $daftar = [];

        for ($i = 1; $i <= $n; $i++) {
            $daftar[] = $this->peserta("Sekolah {$i}", $grup, $seri);
        }

        return $daftar;
    }

    private function nilai(Registration $reg, AssessmentCriteria $crit, Judge $juri, $skor): void
    {
        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $reg->id,
            'assessment_criteria_id' => $crit->id,
            'judge_id' => $juri->id,
            'score' => $skor,
        ]);
    }

    private function bangun(?int $babak = null, $grup = null): array
    {
        return (new JudgeScoreComparison)->build(
            $this->eventner,
            $this->level->id,
            $babak,
            $grup,
        );
    }

    /** @return array<string, mixed> baris statistik satu juri */
    private function juri(array $hasil, Judge $juri): array
    {
        foreach ($hasil['juri'] as $baris) {
            if ($baris['judge']->id === $juri->id) {
                return $baris;
            }
        }

        $this->fail("Juri {$juri->name} tidak ada di hasil perbandingan.");
    }

    // ── Uji ─────────────────────────────────────────────────────────────

    /**
     * Dua juri menilai kriteria yang sama; juri B selalu 20% skala lebih
     * tinggi. Itu kecenderungan yang bisa dikoreksi dengan sadar — bias besar,
     * sebaran kecil.
     */
    public function test_bias_terdeteksi_saat_dua_juri_menilai_kriteria_yang_sama()
    {
        $crit = $this->rubrik('Kriteria', [0, 50, 100]);

        for ($i = 0; $i < 6; $i++) {
            $reg = $this->peserta("Sekolah {$i}");
            $this->nilai($reg, $crit, $this->juriA, 50);
            $this->nilai($reg, $crit, $this->juriB, 90);
        }

        $hasil = $this->bangun();

        $a = $this->juri($hasil, $this->juriA);
        $b = $this->juri($hasil, $this->juriB);

        $this->assertSame(JudgeScoreComparison::KELAS_TINGGI, $b['kelas']);
        $this->assertEqualsWithDelta(0.20, $b['bias'], 1e-9);
        $this->assertEqualsWithDelta(0.0, $b['sigma'], 1e-9);
        $this->assertTrue($b['konsisten'], 'Selalu 20% lebih tinggi itu bias yang konsisten.');

        $this->assertSame(JudgeScoreComparison::KELAS_RENDAH, $a['kelas']);
        $this->assertEqualsWithDelta(-0.20, $a['bias'], 1e-9);
        $this->assertSame(6, $b['pembanding']);
    }

    /**
     * Rubrik dibagi tanpa irisan: juri A hanya memegang satu kriteria, juri B
     * hanya kriteria lain. Tak ada satu sel pun yang mereka isi bersama, jadi
     * perbandingannya memang tidak bisa dilakukan — dan itu harus dikatakan,
     * bukan ditampilkan sebagai 0.
     */
    public function test_juri_tanpa_kriteria_bersama_tidak_dibandingkan()
    {
        $critA = $this->rubrik('Pegang A', [0, 100], pengisi: $this->juriA);
        $critB = $this->rubrik('Pegang B', [0, 100], pengisi: $this->juriB);

        $reg = $this->peserta('Sekolah 1');
        $this->nilai($reg, $critA, $this->juriA, 80);
        $this->nilai($reg, $critB, $this->juriB, 40);

        $hasil = $this->bangun();

        $a = $this->juri($hasil, $this->juriA);

        $this->assertSame(JudgeScoreComparison::KELAS_TAK_BISA, $a['kelas']);
        $this->assertNull($a['bias'], 'Tanpa sel pembanding tidak ada angka bias.');
        $this->assertNull($a['sigma']);
        $this->assertSame(0, $a['pembanding']);

        // Dan halaman mengatakannya, bukan menyembunyikannya.
        $this->assertStringContainsString('Tidak dapat dibandingkan', $this->halaman());
    }

    /**
     * Nilai babak final hidup di baris kriteria berbeda. Kalau keduanya
     * digabung, selisih yang muncul tidak pernah terjadi di lapangan.
     */
    public function test_babak_penyisihan_dan_final_tidak_tercampur()
    {
        $critPenyisihan = $this->rubrik('Penyisihan', [0, 100], babak: $this->penyisihan);
        $critFinal = $this->rubrik('Final', [0, 100], babak: $this->final);

        // Penyisihan: seimbang. Final: juri B 20% skala lebih tinggi.
        foreach ($this->pesertaBanyak(5) as $reg) {
            $this->nilai($reg, $critPenyisihan, $this->juriA, 50);
            $this->nilai($reg, $critPenyisihan, $this->juriB, 50);
            $this->nilai($reg, $critFinal, $this->juriA, 50);
            $this->nilai($reg, $critFinal, $this->juriB, 90);
        }

        // Grup A punya penugasan grup, jadi babak final butuh penugasan final
        // tingkat tersendiri supaya jurinya benar-benar dinilai di sana.
        CompetitionGroup::syncJudges(
            $this->level->id,
            CompetitionGroup::SCOPE_FINAL,
            null,
            [$this->juriA->id, $this->juriB->id],
        );

        $penyisihan = $this->bangun($this->penyisihan->id);
        $final = $this->bangun($this->final->id);

        // Penyisihan hanya memuat kriteria penyisihan → seimbang.
        $this->assertEqualsWithDelta(0.0, $this->juri($penyisihan, $this->juriB)['bias'], 1e-9);
        $this->assertSame(5, $this->juri($penyisihan, $this->juriB)['pembanding']);
        $this->assertSame(JudgeScoreComparison::KELAS_SEIMBANG, $this->juri($penyisihan, $this->juriB)['kelas']);

        // Final hanya memuat kriteria final → juri B jelas lebih tinggi.
        $this->assertEqualsWithDelta(0.20, $this->juri($final, $this->juriB)['bias'], 1e-9);
        $this->assertSame(5, $this->juri($final, $this->juriB)['pembanding']);
        $this->assertSame(JudgeScoreComparison::KELAS_TINGGI, $this->juri($final, $this->juriB)['kelas']);

        // Kriteria satu babak tidak pernah muncul di analisis babak lain.
        $this->assertCount(1, $penyisihan['kriteria']);
        $this->assertSame($critPenyisihan->id, $penyisihan['kriteria'][0]['criteria']->id);
        $this->assertSame($critFinal->id, $final['kriteria'][0]['criteria']->id);
    }

    /** 10 lawan 25 pada skala 25 = 60% skala: sel ditandai, dan juri yang menjauh dicatat. */
    public function test_sel_dengan_selisih_besar_ditandai()
    {
        $crit = $this->rubrik('Kriteria', [0, 25]);

        $reg = $this->peserta('Sekolah 1');
        $this->nilai($reg, $crit, $this->juriA, 10);
        $this->nilai($reg, $crit, $this->juriB, 25);

        $hasil = $this->bangun();

        $this->assertCount(1, $hasil['sel']);

        $sel = $hasil['sel'][0];
        $this->assertTrue($sel['flag']);
        $this->assertEqualsWithDelta(0.60, $sel['rentang'], 1e-9);
        $this->assertNull($sel['menyimpang'], '10 dan 25 sama jauhnya dari rerata 17,5 — tak ada yang menonjol.');
        $this->assertSame(1, $hasil['ringkas']['sel_dibandingkan']);
        $this->assertSame(1, $hasil['ringkas']['sel_ditandai']);
    }

    /**
     * Kriteria 0–10 dan 0–100 dengan penyimpangan setara harus menghasilkan
     * bias yang sama. Tanpa normalisasi, kriteria berskala besar mendominasi
     * seluruh statistik.
     */
    public function test_kriteria_berskala_berbeda_dinormalkan()
    {
        $kecil = $this->rubrik('Skala 10', [0, 10]);
        $besar = $this->rubrik('Skala 100', [0, 100]);

        for ($i = 0; $i < 3; $i++) {
            $reg = $this->peserta("Sekolah {$i}");
            // Juri A di posisi tengah, juri B 20% skala lebih tinggi — di
            // kedua kriteria, walau poinnya 2 vs 20.
            $this->nilai($reg, $kecil, $this->juriA, 5);
            $this->nilai($reg, $kecil, $this->juriB, 9);
            $this->nilai($reg, $besar, $this->juriA, 50);
            $this->nilai($reg, $besar, $this->juriB, 90);
        }

        $hasil = $this->bangun();
        $b = $this->juri($hasil, $this->juriB);

        // 6 sel pembanding: 3 sel skala 10 + 3 sel skala 100.
        $this->assertSame(6, $b['pembanding']);
        $this->assertEqualsWithDelta(0.20, $b['bias'], 1e-9, 'Dua skala berbeda, satu bias.');
    }

    /**
     * Bobot adalah sifat kriteria, bukan juri. Dua kriteria dengan nilai
     * identik tapi bobot 1 dan 5 harus memberi bias yang sama — kalau bobot
     * ikut, juri pemegang kriteria berbobot besar tampak "lebih tinggi"
     * padahal ia menilai persis sama.
     */
    public function test_bobot_kriteria_tidak_mempengaruhi_bias()
    {
        $ringan = $this->rubrik('Bobot 1', [0, 100], weight: 1);
        $berat = $this->rubrik('Bobot 5', [0, 100], weight: 5);

        foreach ($this->pesertaBanyak(3) as $reg) {
            $this->nilai($reg, $ringan, $this->juriA, 40);
            $this->nilai($reg, $ringan, $this->juriB, 60);
            $this->nilai($reg, $berat, $this->juriA, 40);
            $this->nilai($reg, $berat, $this->juriB, 60);
        }

        $bias = $this->juri($this->bangun(), $this->juriB)['bias'];

        $this->assertEqualsWithDelta(0.10, $bias, 1e-9);

        // Dan bobotnya memang tercatat berbeda — jadi uji ini benar-benar
        // menguji bobot, bukan kriteria yang kebetulan sama.
        $this->assertNotEquals((float) $ringan->weight, (float) $berat->weight);
    }

    /** Juri hanya dibandingkan dengan rekannya di grup yang sama. */
    public function test_grup_lain_tidak_jadi_pembanding()
    {
        // Juri A merangkap di Grup B; juri B tetap hanya di Grup A.
        CompetitionGroup::syncJudges(
            $this->level->id,
            CompetitionGroup::SCOPE_GROUP,
            $this->grupB->id,
            [$this->juriA->id],
        );

        $crit = $this->rubrik('Kriteria', [0, 100]);

        foreach ($this->pesertaBanyak(5, $this->grupA) as $diA) {
            $this->nilai($diA, $crit, $this->juriA, 50);
            $this->nilai($diA, $crit, $this->juriB, 90);
        }

        // Peserta Grup B: juri A saja, nilainya sengaja ekstrem. Kalau
        // penyaringan grup bocor, juri A akan tampak menyimpang.
        $diB = $this->peserta('Sekolah Grup B', $this->grupB);
        $this->nilai($diB, $crit, $this->juriA, 10);

        $hasil = $this->bangun(null, $this->grupA->id);

        $this->assertCount(5, $hasil['sel'], 'Hanya peserta Grup A yang masuk analisis Grup A.');
        $this->assertEqualsWithDelta(0.20, $this->juri($hasil, $this->juriB)['bias'], 1e-9);

        // Juri A dinilai hanya di Grup A. Nilai 10 milik peserta Grup B tak
        // boleh ikut: kalau bocor, biasnya jauh dari -0,20 dan pembandingnya 6.
        $this->assertSame(5, $this->juri($hasil, $this->juriA)['pembanding']);
        $this->assertEqualsWithDelta(-0.20, $this->juri($hasil, $this->juriA)['bias'], 1e-9);
    }

    /** Di bawah lima sel pembanding tidak ada angka bias — itu derau. */
    public function test_data_belum_cukup_bila_sel_pembanding_kurang_dari_lima()
    {
        $crit = $this->rubrik('Kriteria', [0, 100]);

        foreach (['Sekolah 1', 'Sekolah 2'] as $nama) {
            $reg = $this->peserta($nama);
            $this->nilai($reg, $crit, $this->juriA, 60);
            $this->nilai($reg, $crit, $this->juriB, 80);
        }

        $hasil = $this->bangun();
        $b = $this->juri($hasil, $this->juriB);

        $this->assertSame(2, $b['pembanding']);
        $this->assertSame(JudgeScoreComparison::KELAS_KURANG, $b['kelas']);
        $this->assertNull($b['bias'], 'Dua sel belum cukup untuk menyimpulkan arah.');
        $this->assertNull($b['sigma']);
        $this->assertNull($b['selisih_ekstrem']);
    }

    /** Halaman menolak tingkat milik event lain — tidak ada nilai yang bocor. */
    public function test_halaman_menolak_tingkat_milik_eventner_lain()
    {
        $lain = User::factory()->eventner()->create(['is_active' => true]);
        $eventLain = Eventner::factory()->create(['user_id' => $lain->id, 'status' => 'approved']);
        $indukLain = CompetitionCategory::factory()->create(['eventner_id' => $eventLain->id, 'parent_id' => null]);
        $levelLain = CompetitionCategory::factory()->create([
            'eventner_id' => $eventLain->id,
            'parent_id' => $indukLain->id,
        ]);

        $this->get('/eventner/scoring/perbandingan?selectedCategoryId=' . $levelLain->id)
            ->assertNotFound();
    }

    /**
     * Halaman ini hanya membaca. Membukanya tidak boleh menyentuh satu baris
     * nilai pun — kalau tidak, "lihat-lihat" jadi perubahan data.
     */
    public function test_halaman_tidak_menulis_apa_pun()
    {
        $crit = $this->rubrik('Kriteria', [0, 100]);

        for ($i = 0; $i < 6; $i++) {
            $reg = $this->peserta("Sekolah {$i}");
            $this->nilai($reg, $crit, $this->juriA, 50 + $i);
            $this->nilai($reg, $crit, $this->juriB, 60 + $i);
        }

        $deduksiKategori = \App\Models\DeductionCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Pelanggaran',
            'sort_order' => 1,
        ]);
        $deduksiKriteria = \App\Models\DeductionCriteria::create([
            'deduction_category_id' => $deduksiKategori->id,
            'name' => 'Terlambat',
            'deduction_options' => [['amount' => 5]],
            'sort_order' => 1,
        ]);

        ScoreDeduction::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->peserta('Sekolah Deduksi')->id,
            'deduction_criteria_id' => $deduksiKriteria->id,
            'amount' => 5,
            'note' => 'Terlambat',
        ]);

        $sebelum = [
            'skor' => DB::table('assessment_scores')->orderBy('id')->get()->toArray(),
            'deduksi' => DB::table('score_deductions')->orderBy('id')->get()->toArray(),
        ];

        $this->get('/eventner/scoring/perbandingan?selectedCategoryId=' . $this->level->id)
            ->assertOk();

        $sesudah = [
            'skor' => DB::table('assessment_scores')->orderBy('id')->get()->toArray(),
            'deduksi' => DB::table('score_deductions')->orderBy('id')->get()->toArray(),
        ];

        $this->assertEquals($sebelum, $sesudah, 'Membuka halaman perbandingan tidak boleh mengubah data.');
    }

    /** Tombol menuju perbandingan ada di halaman Input Nilai dan membawa tingkatnya. */
    public function test_tombol_perbandingan_ada_di_halaman_input_nilai()
    {
        $crit = $this->rubrik('Kriteria', [0, 100]);
        $reg = $this->peserta('Sekolah 1');
        $this->nilai($reg, $crit, $this->juriA, 50);

        $response = $this->get('/eventner/scoring?selectedCategoryId=' . $this->level->id);

        $response->assertOk();
        $response->assertSee('scoring/perbandingan', false);
        $response->assertSee('selectedCategoryId=' . $this->level->id, false);
    }

    /** Tanpa tingkat, tidak ada yang bisa dibandingkan — tombolnya tidak muncul. */
    public function test_tombol_perbandingan_tersembunyi_tanpa_tingkat()
    {
        $response = $this->get('/eventner/scoring');

        $response->assertOk();
        $response->assertDontSee('scoring/perbandingan', false);
    }

    /** Tingkat tanpa nilai sama sekali: halaman terbuka, dengan pesan yang tepat. */
    public function test_halaman_menampilkan_keadaan_belum_ada_nilai()
    {
        // Rubriknya ada dan jurinya sudah ditugaskan — yang belum cuma
        // nilainya. Pesannya harus menunjuk ke sana, bukan ke rubrik atau juri.
        $this->rubrik('Kriteria', [0, 100]);
        $this->peserta('Sekolah 1');

        $html = $this->halaman();

        $this->assertStringContainsString('Belum ada nilai', $html);
    }

    /** Peserta dan juri ada, tapi rubriknya belum dibuat. */
    public function test_halaman_menampilkan_keadaan_belum_ada_rubrik()
    {
        $this->peserta('Sekolah 1');

        $html = $this->halaman();

        $this->assertStringContainsString('Belum ada rubrik', $html);
    }

    /** Tingkat tanpa peserta. */
    public function test_halaman_menampilkan_keadaan_belum_ada_peserta()
    {
        $html = $this->halaman();

        $this->assertStringContainsString('Belum ada peserta', $html);
    }

    /** Nilai lengkap tapi tiap juri memegang kriteria sendiri → bukan "belum ada data". */
    public function test_halaman_membedakan_belum_ada_nilai_dari_tanpa_sel_pembanding()
    {
        $critA = $this->rubrik('Pegang A', [0, 100], pengisi: $this->juriA);
        $critB = $this->rubrik('Pegang B', [0, 100], pengisi: $this->juriB);

        $reg = $this->peserta('Sekolah 1');
        $this->nilai($reg, $critA, $this->juriA, 80);
        $this->nilai($reg, $critB, $this->juriB, 40);

        $html = $this->halaman();

        $this->assertStringContainsString('Tidak dapat dibandingkan', $html);
        $this->assertStringNotContainsString('Belum ada nilai', $html);
    }

    /** Halaman mengikuti grup yang diminta lewat query string. */
    public function test_halaman_menyaring_per_grup()
    {
        $crit = $this->rubrik('Kriteria', [0, 100]);

        // Grup A: selisih besar, jadi namanya muncul di daftar sel berselisih.
        // Grup B: seimbang, jadi namanya memang tak muncul di sana.
        $diA = $this->peserta('Sekolah Grup A', $this->grupA);
        $this->nilai($diA, $crit, $this->juriA, 10);
        $this->nilai($diA, $crit, $this->juriB, 90);

        $diB = $this->peserta('Sekolah Grup B', $this->grupB);
        $this->nilai($diB, $crit, $this->juriA, 50);
        $this->nilai($diB, $crit, $this->juriB, 50);

        $html = $this->halaman(grup: $this->grupA->id);

        $this->assertStringContainsString('Sekolah Grup A', $html);
        $this->assertStringNotContainsString('Sekolah Grup B', $html);
    }

    /** Halaman perbandingan terbuka walau nilai tingkat itu sudah dikunci. */
    public function test_halaman_terbuka_saat_nilai_sudah_final()
    {
        $crit = $this->rubrik('Kriteria', [0, 100]);
        $reg = $this->peserta('Sekolah 1');
        $this->nilai($reg, $crit, $this->juriA, 50);

        DB::table('assessment_scores')->update(['is_finalized' => true]);

        $this->get('/eventner/scoring/perbandingan?selectedCategoryId=' . $this->level->id)
            ->assertOk();
    }

    /** Tautan dari Input Nilai mendarat di halaman yang benar. */
    public function test_halaman_terbuka_dari_tautan_input_nilai()
    {
        $crit = $this->rubrik('Kriteria', [0, 100]);
        $reg = $this->peserta('Sekolah 1');
        $this->nilai($reg, $crit, $this->juriA, 50);

        $url = '/eventner/scoring/perbandingan?selectedCategoryId=' . $this->level->id;

        $this->get($url)->assertOk()->assertSee('Perbandingan Nilai Juri', false);
    }

    /** Mengganti tingkat melepas grup milik tingkat lama. */
    public function test_grup_dilepas_saat_tingkat_berganti()
    {
        $crit = $this->rubrik('Kriteria', [0, 100]);
        $reg = $this->peserta('Sekolah 1');
        $this->nilai($reg, $crit, $this->juriA, 50);
        $this->nilai($reg, $crit, $this->juriB, 60);

        $indukLain = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
        ]);
        $tingkatLain = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $indukLain->id,
        ]);

        $html = $this->get('/eventner/scoring/perbandingan?selectedCategoryId=' . $tingkatLain->id
            . '&selectedGroupId=' . $this->grupA->id)->assertOk();

        // Grup A milik tingkat lain, jadi tak boleh tersaring di sini.
        $this->assertStringNotContainsString('value="' . $this->grupA->id . '" selected', $html->getContent());
    }

    /** Tingkat dengan peserta tapi tanpa penugasan juri tidak error, dan mengatakannya. */
    public function test_tingkat_tanpa_penugasan_juri_tidak_error()
    {
        $induk = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
        ]);
        $sepi = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $induk->id,
        ]);

        Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $sepi->id,
            'nama_sekolah' => 'Sekolah Sepi',
        ]);

        $html = $this->get('/eventner/scoring/perbandingan?selectedCategoryId=' . $sepi->id)
            ->assertOk()->getContent();

        $this->assertStringContainsString('Belum ada juri', $html);
    }

    /** Perangkat uji: buka halaman perbandingan dan kembalikan HTML-nya. */
    private function halaman(?int $grup = null): string
    {
        $url = '/eventner/scoring/perbandingan?selectedCategoryId=' . $this->level->id;

        if ($grup !== null) {
            $url .= '&selectedGroupId=' . $grup;
        }

        return $this->get($url)->assertOk()->getContent();
    }
}
