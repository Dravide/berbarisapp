<?php

namespace Tests\Feature;

use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentSubCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\User;
use App\Services\ScoreFinalizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Pembagian rubrik antar juri — satu rubrik diisi satu juri.
 *
 * Aturan yang dijaga di sini:
 *   - Rubrik yang DICENTANG ke juri tertentu hanya terbuka untuk juri itu.
 *   - Rubrik yang BELUM dicentang terbuka untuk semua juri dari baris
 *     penugasan yang berlaku (kompatibilitas mundur: acara yang belum dibagi
 *     berperilaku persis seperti sebelum fitur ini ada).
 *   - Himpunan kriteria tablet, panel panitia, dan finalisasi WAJIB sama;
 *     beda satu kriteria saja membuat tombol finalisasi terkunci selamanya.
 */
class RubricJudgeSplitTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $level;

    private CompetitionGroup $group;

    private CompetitionRound $penyisihan;

    private Judge $juriA;

    private Judge $juriB;

    private Registration $reg;

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

        $this->group = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup A',
        ]);

        $seri = \App\Models\CompetitionSeries::create([
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

        $this->juriA = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri A']);
        $this->juriB = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri B']);

        // Keduanya dinilai satu grup yang sama: pemisahannya murni dari
        // centangan rubrik, bukan dari penugasan grup.
        CompetitionGroup::syncJudges(
            $this->level->id,
            CompetitionGroup::SCOPE_GROUP,
            $this->group->id,
            [$this->juriA->id, $this->juriB->id],
        );

        $this->reg = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $this->group->id,
            'competition_series_id' => $seri->id,
            'nama_sekolah' => 'SMPN 1',
        ]);

        config(['app.entry_host' => 'entry.berbaris.test']);

        $this->actingAs($this->eventner->user);
    }

    /** Rubrik + satu kriteria. $pengisi = null berarti belum dicentang ke siapa pun. */
    private function makeRubrik(string $nama, ?Judge $pengisi = null): AssessmentCategory
    {
        $category = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'competition_series_id' => $this->reg->competition_series_id,
            'competition_round_id' => $this->penyisihan->id,
            'name' => $nama,
        ]);

        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $category->id,
            'name' => 'Sub ' . $nama,
        ]);

        AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Kriteria ' . $nama,
            'score_options' => [['score' => 10], ['score' => 20]],
            'weight' => 1,
        ]);

        if ($pengisi) {
            $category->syncRubricJudges([$pengisi->id]);
        }

        return $category;
    }

    private function kriteriaDari(AssessmentCategory $category): int
    {
        return (int) $category->fresh()->subCategories->first()->criterias->first()->id;
    }

    private function tablet(Judge $juri)
    {
        return Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $juri->access_token])
            ->call('selectCategory', $this->level->id)
            ->call('selectParticipant', $this->reg->id);
    }

    private function panel()
    {
        return Livewire::test(\App\Livewire\Eventner\Scoring\Index::class)
            ->call('selectCategory', $this->level->id);
    }

    /** Panel dengan peserta dibuka, tapi jurinya belum dipilih. */
    private function panelTanpaJuri()
    {
        return $this->panel()
            ->call('selectParticipant', $this->reg->id)
            ->set('selectedJudgeId', null);
    }

    // ── Tablet juri ────────────────────────────────────────────────────

    public function test_tablet_juri_a_hanya_melihat_rubrik_yang_dicentang_ke_dirinya()
    {
        $pbb = $this->makeRubrik('PBB', $this->juriA);
        $danton = $this->makeRubrik('DANTON', $this->juriA);
        $variasi = $this->makeRubrik('VARIASI', $this->juriB);

        $tabletA = $this->tablet($this->juriA)
            ->assertSet('allowedCriteriaIds', [
                $this->kriteriaDari($pbb),
                $this->kriteriaDari($danton),
            ]);

        // `assessmentCategories` di layout juri bukan computed Laravel, jadi
        // dibaca lewat properti komponennya — bukan assertViewHas.
        $ids = $tabletA->get('assessmentCategories')->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->assertContains($pbb->id, $ids);
        $this->assertContains($danton->id, $ids);
        $this->assertNotContains($variasi->id, $ids, 'Rubrik milik juri lain bocor ke tablet juri ini.');

        $this->tablet($this->juriB)
            ->assertSet('allowedCriteriaIds', [$this->kriteriaDari($variasi)]);
    }

    public function test_tablet_menolak_nilai_atas_rubrik_juri_lain()
    {
        $this->makeRubrik('PBB', $this->juriA);
        $variasi = $this->makeRubrik('VARIASI', $this->juriB);

        $this->tablet($this->juriA)
            ->call('setScore', $this->kriteriaDari($variasi), 10)
            ->assertStatus(403);

        $this->assertDatabaseMissing('assessment_scores', [
            'registration_id' => $this->reg->id,
            'assessment_criteria_id' => $this->kriteriaDari($variasi),
            'judge_id' => $this->juriA->id,
        ]);
    }

    /**
     * Keputusan yang dikunci: rubrik TANPA centang boleh diisi semua juri dari
     * baris penugasan peserta itu. Kalau ini pecah, seluruh acara yang sudah
     * berjalan kehilangan rubriknya begitu fitur ini rilis.
     */
    public function test_rubrik_tanpa_centang_terbuka_untuk_semua_juri_penugasan()
    {
        $tanpaCentang = $this->makeRubrik('Belum Dibagi');

        $this->tablet($this->juriA)
            ->assertSet('allowedCriteriaIds', [$this->kriteriaDari($tanpaCentang)]);

        $this->tablet($this->juriB)
            ->assertSet('allowedCriteriaIds', [$this->kriteriaDari($tanpaCentang)]);

        // Dan nilainya benar-benar bisa masuk dari kedua juri.
        $this->tablet($this->juriA)->call('setScore', $this->kriteriaDari($tanpaCentang), 10);
        $this->tablet($this->juriB)->call('setScore', $this->kriteriaDari($tanpaCentang), 20);

        $this->assertDatabaseHas('assessment_scores', [
            'assessment_criteria_id' => $this->kriteriaDari($tanpaCentang),
            'judge_id' => $this->juriA->id,
            'score' => '10',
        ]);
        $this->assertDatabaseHas('assessment_scores', [
            'assessment_criteria_id' => $this->kriteriaDari($tanpaCentang),
            'judge_id' => $this->juriB->id,
            'score' => '20',
        ]);
    }

    // ── Penjaga drift ──────────────────────────────────────────────────

    /**
     * Himpunan yang dituntut finalisasi harus persis yang dirender tablet.
     * finalize() menolak selama ada satu kriteria kosong; satu kriteria lebih
     * banyak di sisi finalisasi membuat tombolnya mustahil ditekan.
     */
    public function test_kriteria_tablet_sama_dengan_yang_dituntut_finalisasi()
    {
        $this->makeRubrik('PBB', $this->juriA);
        $this->makeRubrik('DANTON', $this->juriA);
        $this->makeRubrik('VARIASI', $this->juriB);
        $this->makeRubrik('Belum Dibagi');

        $component = $this->tablet($this->juriA);
        $tabletIds = $component->get('allowedCriteriaIds');

        $dariFinalisasi = AssessmentCategory::rubrikUntukPeserta(
            $this->eventner->id,
            $this->level->id,
            $this->reg->competition_series_id,
            $this->penyisihan->id,
            $this->juriA->id,
        )->get()
            ->flatMap(fn ($cat) => $cat->subCategories->flatMap(fn ($sub) => $sub->criterias->pluck('id')))
            ->map(fn ($id) => (int) $id)
            ->all();

        sort($tabletIds);
        sort($dariFinalisasi);

        $this->assertSame($dariFinalisasi, $tabletIds);

        // Bukti tak cuma bentuk: setelah tablet mengisi SELURUH yang ia lihat,
        // finalisasi benar-benar bisa ditekan.
        foreach ($tabletIds as $criteriaId) {
            $component->call('setScore', $criteriaId, 10);
        }

        $hasil = app(ScoreFinalizationService::class)
            ->finalize($this->eventner->id, $this->reg->id, $this->juriA->id, [], $this->penyisihan->id);

        $this->assertFalse($hasil['missing'], 'Finalisasi menuntut kriteria yang tak dirender tablet.');
        $this->assertTrue($hasil['ok']);
    }

    // ── Notifikasi nilai final ─────────────────────────────────────────

    /**
     * Juri yang seluruh rubriknya dipegang juri lain tak pernah menulis satu
     * baris nilai, jadi ia tak mungkin terlihat "sudah final" — kalau ia tetap
     * dituntut, nota "nilai selesai" tak akan pernah terkirim.
     */
    public function test_juri_tanpa_rubrik_tidak_menahan_notifikasi_nilai_final()
    {
        $pbb = $this->makeRubrik('PBB', $this->juriA);
        $kriteria = $this->kriteriaDari($pbb);

        // Nilai disimpan lebih dulu (lewat tablet, seperti di lapangan) —
        // finalize() mengunci baris yang SUDAH ada, jadi tanpa langkah ini tak
        // ada satu pun baris yang berubah jadi final.
        $this->tablet($this->juriA)->call('setScore', $kriteria, 10);

        $service = app(ScoreFinalizationService::class);
        $hasil = $service->finalize($this->eventner->id, $this->reg->id, $this->juriA->id, [], $this->penyisihan->id);

        $this->assertTrue($hasil['ok'], 'Finalisasi juri A gagal: ' . json_encode($hasil));
        $this->assertSame(1, $hasil['updated']);

        // Juri B tak memegang satu rubrik pun, jadi ia tak boleh ikut dituntut.
        // Kalau ia dituntut, notanya tak akan pernah terkirim.
        $fcm = $this->mock(\App\Services\FcmService::class);
        $fcm->shouldReceive('sendToModel')->once()->andReturn(1);

        $service->notifyIfComplete($this->eventner->id, $this->reg, $this->penyisihan->id);
    }

    public function test_juri_berubrik_yang_belum_final_tetap_menahan_notifikasi()
    {
        $pbb = $this->makeRubrik('PBB', $this->juriA);
        $this->makeRubrik('VARIASI', $this->juriB);
        $kriteria = $this->kriteriaDari($pbb);

        $this->tablet($this->juriA)->call('setScore', $kriteria, 10);

        $service = app(ScoreFinalizationService::class);
        $service->finalize($this->eventner->id, $this->reg->id, $this->juriA->id, [], $this->penyisihan->id);

        // Juri B memegang rubrik sendiri dan belum final — notanya harus ditahan.
        $fcm = $this->mock(\App\Services\FcmService::class);
        $fcm->shouldReceive('sendToModel')->never();

        $service->notifyIfComplete($this->eventner->id, $this->reg, $this->penyisihan->id);
    }

    // ── Panel panitia ──────────────────────────────────────────────────

    public function test_panel_tanpa_juri_terpilih_menampilkan_semua_rubrik()
    {
        $pbb = $this->makeRubrik('PBB', $this->juriA);
        $variasi = $this->makeRubrik('VARIASI', $this->juriB);

        $this->panelTanpaJuri()
            ->assertViewHas('assessmentCategories', function ($cats) use ($pbb, $variasi) {
                $ids = $cats->pluck('id')->map(fn ($id) => (int) $id)->all();

                return in_array($pbb->id, $ids, true) && in_array($variasi->id, $ids, true);
            });
    }

    public function test_panel_dengan_juri_terpilih_hanya_menampilkan_rubrik_juri_itu()
    {
        $pbb = $this->makeRubrik('PBB', $this->juriA);
        $variasi = $this->makeRubrik('VARIASI', $this->juriB);

        $this->panel()
            ->call('selectParticipant', $this->reg->id)
            ->set('selectedJudgeId', $this->juriA->id)
            ->assertViewHas('assessmentCategories', function ($cats) use ($pbb, $variasi) {
                $ids = $cats->pluck('id')->map(fn ($id) => (int) $id)->all();

                return in_array($pbb->id, $ids, true) && ! in_array($variasi->id, $ids, true);
            });
    }

    /** Form bisa memuat sisa state juri sebelumnya — panel wajib menolaknya. */
    public function test_panel_menolak_menyimpan_nilai_di_luar_rubrik_juri_terpilih()
    {
        $pbb = $this->makeRubrik('PBB', $this->juriA);
        $variasi = $this->makeRubrik('VARIASI', $this->juriB);

        $this->panel()
            ->call('selectParticipant', $this->reg->id)
            ->set('selectedJudgeId', $this->juriA->id)
            ->set('scores', [
                $this->kriteriaDari($pbb) => 10,
                $this->kriteriaDari($variasi) => 20,
            ])
            ->call('saveScores');

        $this->assertDatabaseHas('assessment_scores', [
            'assessment_criteria_id' => $this->kriteriaDari($pbb),
            'judge_id' => $this->juriA->id,
            'score' => '10',
        ]);
        $this->assertDatabaseMissing('assessment_scores', [
            'assessment_criteria_id' => $this->kriteriaDari($variasi),
            'judge_id' => $this->juriA->id,
        ]);
    }

    // ── Layar centang di Builder ───────────────────────────────────────

    public function test_builder_mencentang_dan_melepas_juri_sebuah_rubrik()
    {
        $rubrik = $this->makeRubrik('PBB');

        $builder = Livewire::test(\App\Livewire\Eventner\FormatNilai\Builder::class);

        $builder->call('toggleRubricJudge', $rubrik->id, $this->juriA->id, true);
        $this->assertSame([$this->juriA->id], $rubrik->rubricJudgeIds());

        $builder->call('toggleRubricJudge', $rubrik->id, $this->juriB->id, true);
        $this->assertEqualsCanonicalizing(
            [$this->juriA->id, $this->juriB->id],
            $rubrik->rubricJudgeIds(),
        );

        $builder->call('toggleRubricJudge', $rubrik->id, $this->juriA->id, false);
        $this->assertSame([$this->juriB->id], $rubrik->rubricJudgeIds());
    }

    public function test_builder_menandai_rubrik_yang_jurinya_tak_bertugas_di_tingkat_itu()
    {
        $rubrik = $this->makeRubrik('PBB');

        // Juri C tak punya baris penugasan di tingkat ini.
        $juriC = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri C']);

        Livewire::test(\App\Livewire\Eventner\FormatNilai\Builder::class)
            ->call('toggleRubricJudge', $rubrik->id, $juriC->id, true)
            ->assertSet('rubrikTanpaJuriReachable', fn ($daftar) => in_array($rubrik->id, $daftar, true));

        // Begitu dicentang ke juri yang memang bertugas, peringatannya hilang.
        Livewire::test(\App\Livewire\Eventner\FormatNilai\Builder::class)
            ->call('toggleRubricJudge', $rubrik->id, $this->juriA->id, true)
            ->assertSet('rubrikTanpaJuriReachable', fn ($daftar) => ! in_array($rubrik->id, $daftar, true));
    }

    /**
     * Rubrik yang belum dicentang TIDAK ditandai.
     *
     * Kosong berarti "semua juri boleh mengisi" — tak ada centang yang perlu
     * dijangkau. Kalau rubrik kosong ikut ditandai, setiap rubrik di acara yang
     * belum dibagi jadi merah, dan justru keadaan yang fitur ini jaga supaya
     * tidak berubah yang dilaporkan sebagai masalah.
     */
    public function test_builder_tidak_menandai_rubrik_yang_belum_dibagi()
    {
        $rubrik = $this->makeRubrik('PBB');

        Livewire::test(\App\Livewire\Eventner\FormatNilai\Builder::class)
            ->assertSet('rubrikTanpaJuriReachable', fn ($daftar) => $daftar === []);
    }

    /**
     * Tingkat tanpa baris penugasan sama sekali tak menandai apa pun.
     *
     * Di situ setiap centang memang tak terjangkau, tapi penyebabnya bukan
     * centangnya: layar centangnya sendiri sudah jatuh ke "seluruh juri event"
     * dan panelnya menyatakan belum ada juri di tingkat ini.
     */
    public function test_builder_tidak_menandai_saat_tingkat_belum_punya_penugasan()
    {
        $rubrik = $this->makeRubrik('PBB');
        $rubrik->syncRubricJudges([$this->juriA->id]);

        DB::table('competition_group_judge')
            ->where('competition_category_id', $this->level->id)
            ->delete();

        Livewire::test(\App\Livewire\Eventner\FormatNilai\Builder::class)
            ->assertSet('rubrikTanpaJuriReachable', fn ($daftar) => $daftar === []);
    }

    public function test_duplikat_rubrik_menyalin_centangannya()
    {
        $rubrik = $this->makeRubrik('PBB');
        $rubrik->syncRubricJudges([$this->juriA->id]);

        Livewire::test(\App\Livewire\Eventner\FormatNilai\Builder::class)
            ->call('startDuplicateCategory', $rubrik->id)
            ->set('duplicateCategoryName', 'PBB Final')
            ->call('confirmDuplicateCategory');

        $salinan = AssessmentCategory::where('eventner_id', $this->eventner->id)
            ->where('name', 'PBB Final')
            ->firstOrFail();

        $this->assertNotSame($rubrik->id, $salinan->id);
        $this->assertSame([$this->juriA->id], $salinan->rubricJudgeIds());
    }

    // ── Pintu lama yang kini menyaring ─────────────────────────────────

    /**
     * Tanpa tingkat, "semua tingkat" — bukan "hanya rubrik global".
     *
     * Kartu akses juri memanggil pintu ini tanpa tingkat begitu jurinya
     * memegang lebih dari satu tingkat. Kalau null diperlakukan sebagai nilai
     * yang dibandingkan, syaratnya menyusut jadi `competition_category_id IS
     * NULL`, seluruh rubrik bertingkat hilang, dan kartunya tercetak "Belum
     * ada tugas — hubungi panitia".
     */
    public function test_rubrik_untuk_tingkat_tanpa_tingkat_mengembalikan_semua_tingkat()
    {
        $pbb = $this->makeRubrik('PBB');

        // Rubrik global ikut juga.
        $global = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Rubrik Global',
            'competition_category_id' => null,
        ]);

        $ids = AssessmentCategory::rubrikUntukTingkat($this->eventner->id, null, $this->juriA->id)
            ->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->assertContains($pbb->id, $ids, 'Rubrik bertingkat hilang saat tingkat tidak disebut.');
        $this->assertContains($global->id, $ids);
    }

    /**
     * Kartu akses juri menyebut nama rubriknya. Sesudah migrasi mengosongkan
     * pivot, pembacaan relasi mentah `assessmentCategories` mengosongkan kolom
     * "Tugas Penilaian" di setiap kartu — tanpa satu pun galat.
     */
    public function test_kartu_akses_masih_menyebut_tugas_penilaian_juri()
    {
        $this->makeRubrik('PBB');

        $response = $this->get(route('eventner.judges.kartu-akses', $this->juriA->id));
        $response->assertOk();

        // renderCard() di JudgeAccessCardTest membaca HTML sebelum dompdf —
        // di sini cukup membuktikan viewnya tak kosong tanpa galat.
        $html = view('eventner.judge.pdf_kartu_akses', [
            'eventner' => $this->eventner,
            'judges' => Judge::where('eventner_id', $this->eventner->id)->get(),
        ])->render();

        $this->assertStringContainsString('PBB', $html);
        $this->assertStringNotContainsString('Belum ada tugas', $html);
    }

    /**
     * Dulu `whereHas('judges')` mentah membuang setiap rubrik yang pivotnya
     * kosong — yaitu hampir semuanya. Unduhan format nilai harus memakai pintu
     * baru supaya rubrik yang belum dibagi tetap tercetak.
     */
    public function test_unduhan_format_nilai_ikut_membawa_rubrik_yang_belum_dibagi()
    {
        $tanpaCentang = $this->makeRubrik('Belum Dibagi');
        $milikB = $this->makeRubrik('VARIASI', $this->juriB);

        $download = new \App\Livewire\Eventner\FormatNilai\Download();
        $download->eventnerId = $this->eventner->id;
        $download->selectedLevelId = $this->level->id;
        $download->selectedJudgeId = $this->juriA->id;

        $ids = $download->categories->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->assertContains($tanpaCentang->id, $ids);
        $this->assertNotContains($milikB->id, $ids);
    }

    // ── Migrasi pivot ──────────────────────────────────────────────────

    /**
     * Migrasi menjalankan hapusKembar() lebih dulu: sisakan satu baris per
     * pasangan, supaya pemasangan unique index tak gagal karena data lama.
     */
    public function test_migrasi_merapikan_baris_kembar_sebelum_memasang_unique_index()
    {
        $rubrik = $this->makeRubrik('PBB');
        $kriteriaId = $this->kriteriaDari($rubrik);

        $index = DB::select("PRAGMA index_list('assessment_category_judge')");
        $namaIndex = collect($index)->pluck('name')->all();
        $this->assertContains('assessment_category_judge_unik', $namaIndex);

        // Susun ulang keadaan "data lama berkembar": unique index dilepas dulu,
        // karena dengan index terpasang baris kembar memang tak bisa ada.
        DB::statement('DROP INDEX assessment_category_judge_unik');
        $now = now();
        DB::table('assessment_category_judge')->insert([
            ['judge_id' => $this->juriA->id, 'assessment_category_id' => $rubrik->id, 'created_at' => $now, 'updated_at' => $now],
            ['judge_id' => $this->juriA->id, 'assessment_category_id' => $rubrik->id, 'created_at' => $now, 'updated_at' => $now],
            ['judge_id' => $this->juriB->id, 'assessment_category_id' => $rubrik->id, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Method privat migrasi — sengaja dipanggil langsung supaya yang diuji
        // dedupenya, bukan efek samping "hapus semua baris" milik up().
        $migrasi = require database_path('migrations/2026_09_30_000001_normalize_assessment_category_judge.php');
        $hapusKembar = new \ReflectionMethod($migrasi, 'hapusKembar');
        $hapusKembar->setAccessible(true);
        $hapusKembar->invoke($migrasi);

        $this->assertSame(1, DB::table('assessment_category_judge')
            ->where('assessment_category_id', $rubrik->id)
            ->where('judge_id', $this->juriA->id)
            ->count(), 'Baris kembar tidak dirapikan.');

        // Juri lain di rubrik yang sama tak ikut terhapus.
        $this->assertSame(1, DB::table('assessment_category_judge')
            ->where('assessment_category_id', $rubrik->id)
            ->where('judge_id', $this->juriB->id)
            ->count());

        // Dan pemasangan index-nya berhasil di atas data yang sudah bersih.
        $migrasi->up();

        $this->assertContains(
            'assessment_category_judge_unik',
            collect(DB::select("PRAGMA index_list('assessment_category_judge')"))->pluck('name')->all(),
        );

        // Rubrik tanpa baris tetap terbuka untuk semua juri penugasan.
        $this->tablet($this->juriA)->assertSet('allowedCriteriaIds', [$kriteriaId]);
        $this->tablet($this->juriB)->assertSet('allowedCriteriaIds', [$kriteriaId]);
    }

    /**
     * Migrasi juga mengosongkan baris lama — itu yang membuat acara yang sudah
     * berjalan tak kehilangan rubrik. Setelah dikosongkan, semua juri kembali
     * melihat rubrik yang sama.
     */
    public function test_baris_pivot_lama_dikosongkan_sehingga_semua_juri_melihat_rubrik_yang_sama()
    {
        $rubrik = $this->makeRubrik('PBB');
        $kriteriaId = $this->kriteriaDari($rubrik);

        // Keadaan lama: hanya sebagian juri tercatat di pivot.
        $rubrik->syncRubricJudges([$this->juriA->id]);

        $this->tablet($this->juriA)->assertSet('allowedCriteriaIds', [$kriteriaId]);
        $this->tablet($this->juriB)->assertSet('allowedCriteriaIds', []);

        DB::table('assessment_category_judge')->delete();

        $this->tablet($this->juriA)->assertSet('allowedCriteriaIds', [$kriteriaId]);
        $this->tablet($this->juriB)->assertSet('allowedCriteriaIds', [$kriteriaId]);
    }
}
