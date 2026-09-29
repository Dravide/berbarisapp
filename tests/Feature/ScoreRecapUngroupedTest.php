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
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Rekap tingkat bergrup yang pesertanya BELUM dibagi ke grup mana pun.
 *
 * Keluhan lapangan: "sudah ada grupnya, tapi filternya tidak memunculkan
 * data — cuma 'Belum Ada Data'. Yang tanpa grup justru muncul."
 *
 * Yang dijaga di sini: peserta ber-grup null tetap punya tabel sendiri
 * (jalur "Belum Bergrup"), tidak ikut dibuang saat tingkatnya bergrup.
 */
class ScoreRecapUngroupedTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $level;

    private CompetitionGroup $groupA;

    private Judge $juri;

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

        $this->juri = Judge::create(['eventner_id' => $this->eventner->id, 'name' => 'Juri Umum']);
    }

    private function rubrik(string $name, ?CompetitionGroup $group, ?CompetitionRound $round = null, ?CompetitionSeries $series = null): AssessmentCriteria
    {
        $category = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $group?->id,
            'competition_round_id' => $round?->id,
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
            'score_options' => [['score' => 10]],
            'weight' => 1,
            'sort_order' => 1,
        ]);
    }

    private function peserta(string $nama, ?CompetitionGroup $group): Registration
    {
        return Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $group?->id,
            'nama_sekolah' => $nama,
        ]);
    }

    private function nilai(Registration $reg, AssessmentCriteria $criteria, int $score): void
    {
        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'judge_id' => $this->juri->id,
            'registration_id' => $reg->id,
            'assessment_criteria_id' => $criteria->id,
            'score' => $score,
        ]);
    }

    private function html(int $levelId, string $groupFilter = ''): string
    {
        return Livewire::test(\App\Livewire\Eventner\ScoreRecap\Index::class, [
            'selectedCategoryId' => $levelId,
            'selectedGroupId' => $groupFilter,
        ])->html();
    }

    /**
     * Tingkat bergrup + rubrik TANPA grup + semua peserta belum dibagi grup.
     *
     * Semua peserta ber-grup null, jadi hanya jalur "Belum Bergrup" yang
     * terisi. Inilah bentuk yang dilaporkan: datanya ada, tapi tak muncul.
     */
    public function test_peserta_tanpa_grup_muncul_di_tingkat_bergrup()
    {
        $kriteria = $this->rubrik('PBB Umum', null);

        $reg = $this->peserta('SMPN Tanpa Grup', null);
        $this->nilai($reg, $kriteria, 80);

        $html = $this->html($this->level->id);

        $this->assertStringContainsString('SMPN Tanpa Grup', $html);
        $this->assertStringNotContainsString('Belum Ada Data', $html);
        $this->assertStringContainsString('Belum Bergrup', $html);
    }

    /** Sebagian peserta bergrup, sebagian belum — keduanya harus tampil. */
    public function test_grup_dan_belum_bergrup_tampil_bersama()
    {
        $kriteriaA = $this->rubrik('PBB Grup A', $this->groupA);
        $kriteriaUmum = $this->rubrik('PBB Umum', null);

        $diGrup = $this->peserta('SMPN Grup A', $this->groupA);
        $belum = $this->peserta('SMPN Belum', null);

        $this->nilai($diGrup, $kriteriaA, 90);
        $this->nilai($belum, $kriteriaUmum, 70);

        $html = $this->html($this->level->id);

        $this->assertStringContainsString('SMPN Grup A', $html);
        $this->assertStringContainsString('SMPN Belum', $html);
        $this->assertStringContainsString('Grup A', $html);
        $this->assertStringContainsString('Belum Bergrup', $html);
    }

    /**
     * Filter "Seluruh Tingkat" tidak boleh mengosongkan rekap.
     *
     * Peserta ber-grup null tidak cocok dengan saringan grup mana pun, jadi
     * kalau filter itu diterapkan ke daftar peserta di jalur ini, seluruh
     * rekap jadi kosong.
     */
    public function test_filter_seluruh_tingkat_tidak_membuang_peserta_tanpa_grup()
    {
        $kriteria = $this->rubrik('PBB Umum', null);

        $diGrup = $this->peserta('SMPN Grup A', $this->groupA);
        $belum = $this->peserta('SMPN Belum', null);

        $this->nilai($diGrup, $kriteria, 90);
        $this->nilai($belum, $kriteria, 70);

        $html = $this->html($this->level->id, '');

        $this->assertStringContainsString('SMPN Grup A', $html);
        $this->assertStringContainsString('SMPN Belum', $html);
        $this->assertStringNotContainsString('Belum Ada Data', $html);
    }

    /** Tingkat bergrup + rubrik bergrup, tapi belum ada peserta sama sekali. */
    public function test_tingkat_bergrup_tanpa_peserta_tetap_kosong()
    {
        $this->rubrik('PBB Grup A', $this->groupA);

        $html = $this->html($this->level->id);

        $this->assertStringContainsString('Belum Ada Data', $html);
    }

    /**
     * Tingkat sudah bergrup dan rubriknya menempel ke seri, tapi pesertanya
     * belum dapat seri.
     *
     * Bentuk inilah yang paling cocok dengan keluhan lapangan: grupnya ada,
     * nilainya ada, tapi tidak satu pun peserta punya competition_group_id.
     * Sejak lembar nilai ditentukan SERI, keadaan yang setara adalah peserta
     * yang belum dapat seri — dan peserta itu tetap wajib tampil.
     */
    public function test_peserta_belum_dapat_seri_saat_rubriknya_berseri()
    {
        $seriA = CompetitionSeries::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Seri A',
            'sort_order' => 1,
        ]);

        $this->rubrik('PBB Seri A', $this->groupA, null, $seriA);
        $kriteriaUmum = $this->rubrik('PBB Umum', null);

        $belum = $this->peserta('SMPN Belum Dibagi', null);
        $this->nilai($belum, $kriteriaUmum, 70);

        $html = $this->html($this->level->id);

        $this->assertStringContainsString('SMPN Belum Dibagi', $html, 'Peserta tanpa seri hilang dari rekap.');
        $this->assertStringNotContainsString('Belum Ada Data', $html);
    }

    /**
     * Peserta tanpa seri hanya melihat rubrik tanpa seri.
     *
     * Yang menyaring kolom sekarang adalah SERI, bukan penanda grup pada rubrik
     * — dua pasukan satu grup boleh berbeda seri, jadi penanda grup tak lagi
     * bisa dipakai menyembunyikan kolom. Rubrik berseri tetap wajib absen di
     * sini: peserta ini tak menghuni seri mana pun.
     */
    public function test_peserta_tanpa_seri_hanya_melihat_rubrik_tanpa_seri()
    {
        $seriA = CompetitionSeries::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Seri A',
            'sort_order' => 1,
        ]);

        // Dua rubrik polos: satu bertanda grup, satu tidak. Keduanya tanpa seri,
        // jadi keduanya berlaku di mana saja — termasuk di bagian ini.
        $this->rubrik('PBB Grup A', $this->groupA);
        $this->rubrik('PBB Umum', null);
        $this->rubrik('PBB Seri A', $this->groupA, null, $seriA);

        $this->peserta('SMPN Belum Dibagi', null);

        $html = $this->html($this->level->id);

        $this->assertSame(1, substr_count($html, 'PBB Umum'));
        $this->assertSame(1, substr_count($html, 'PBB Grup A'));
        $this->assertSame(
            0,
            substr_count($html, 'PBB Seri A'),
            'Rubrik berseri tampil di bagian peserta tanpa seri.'
        );
    }

    /**
     * Penilaian yang benar-benar tersimpan harus terbaca, bukan hanya namanya.
     *
     * Nilai hanya muncul kalau kriteria yang dinilai termasuk rubrik yang
     * diambil rekap. Kalau rekap mengambil rubrik dengan lemparan yang lebih
     * sempit, baris pesertanya tetap ada tapi seluruh kolomnya nol.
     */
    public function test_nilai_peserta_belum_bergrup_terbaca()
    {
        $kriteriaUmum = $this->rubrik('PBB Umum', null);

        $belum = $this->peserta('SMPN Belum Dibagi', null);
        $this->nilai($belum, $kriteriaUmum, 77);

        $html = $this->html($this->level->id);

        $this->assertStringContainsString('77', $html, 'Nilai tersimpan tidak terbaca di rekap.');
    }

    /**
     * Peserta yang grupnya bukan milik tingkat ini tetap harus tampil.
     *
     * Inilah bentuk yang paling cocok dengan keluhan: grupnya ada (dropdown
     * terisi), tapi competition_group_id peserta menunjuk grup tingkat lain —
     * mis. grup dibuat di bawah kategori INDUK, atau peserta dipindah setelah
     * grupnya dibuat ulang. Loop pembagian bucket hanya memasukkan peserta yang
     * cocok dengan salah satu grup tingkat ini; yang tidak cocok DIBUANG
     * diam-diam, sehingga rekap tampak kosong padahal nilainya ada. Peserta
     * ber-grup null tidak terkena karena punya jalur "Belum Bergrup" sendiri —
     * persis seperti yang dilaporkan.
     */
    public function test_peserta_bergrup_lain_tidak_dibuang_diam_diam()
    {
        $induk = CompetitionCategory::find($this->level->parent_id);

        $grupInduk = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $induk->id,
            'name' => 'Grup Induk',
        ]);

        $kriteria = $this->rubrik('PBB Umum', null);

        $nyasar = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $grupInduk->id,
            'nama_sekolah' => 'SMPN Grup Asing',
        ]);
        $this->nilai($nyasar, $kriteria, 88);

        $html = $this->html($this->level->id);

        $this->assertStringContainsString('SMPN Grup Asing', $html, 'Peserta bergrup asing hilang dari rekap.');
        $this->assertStringNotContainsString('Belum Ada Data', $html);
    }

    /**
     * selectedGroupId dari URL wajib divalidasi saat mount.
     *
     * selectedGroupId ada di $queryString, tapi yang menjaganya cuma hook
     * updatedSelectedGroupId() — hook itu TIDAK jalan saat hidrasi awal. Jadi
     * URL dengan grup milik tingkat lain (sisa tautan lama, atau grup yang
     * sejak itu dihapus) menyaring seluruh peserta tanpa satu pun pesan, dan
     * hasilnya "Belum Ada Data" padahal datanya ada.
     */
    public function test_grup_asing_dari_url_tidak_mengosongkan_rekap()
    {
        $lain = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->level->parent_id,
        ]);
        $grupLain = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $lain->id,
            'name' => 'Grup Tingkat Lain',
        ]);

        $kriteria = $this->rubrik('PBB Umum', null);
        $reg = $this->peserta('SMPN Satu', $this->groupA);
        $this->nilai($reg, $kriteria, 90);

        // Filter grup asing: harus diabaikan (kembali ke seluruh tingkat),
        // bukan menyaring habis seluruh peserta.
        $html = $this->html($this->level->id, (string) $grupLain->id);

        $this->assertStringContainsString('SMPN Satu', $html, 'Filter grup asing mengosongkan rekap.');
        $this->assertStringNotContainsString('Belum Ada Data', $html);
    }

    /**
     * Grup berpeserta yang rubriknya belum dibuat tidak boleh digugurkan.
     *
     * sectionsPerGroup() melewati bagian yang rubriknya kosong selama tingkat
     * ini PUNYA rubrik lain — niatnya menghindari tabel tanpa kolom. Tapi
     * efeknya pesertanya ikut hilang, dan kalau itu terjadi di seluruh grup,
     * seluruh rekap jadi "Belum Ada Data" padahal pesertanya ada.
     */
    public function test_grup_berpeserta_tanpa_rubrik_tidak_digugurkan()
    {
        $this->rubrik('Rubrik Grup B', CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup B',
        ]));

        $this->peserta('SMPN Grup A', $this->groupA);

        $html = $this->html($this->level->id);

        $this->assertStringContainsString('SMPN Grup A', $html, 'Peserta grup tanpa rubrik hilang dari rekap.');
        $this->assertStringNotContainsString('Belum Ada Data', $html);
    }

    /**
     * Tingkat berbabak yang rubriknya TIDAK menempel ke babak mana pun.
     *
     * sectionsPerRound() mengambil rubrik per competition_round_id, lalu
     * melewati (continue) setiap babak yang daftarnya kosong. Rubrik tanpa
     * babak tergrup di bawah key kosong dan tak pernah terbaca, jadi kalau
     * TIDAK SATU PUN rubrik menempel ke babak, semua babak dilewati dan
     * seluruh rekap kosong — padahal pesertanya ada, nilainya ada, dan
     * babaknya cuma dekorasi.
     *
     * Bentuk ini yang paling cocok dengan laporan: grup sudah dibagi, babak
     * sudah dibuat, tapi rubriknya dibuat sebelum babak ada.
     */
    public function test_tingkat_berbabak_tanpa_rubrik_berbabak_tetap_tampil()
    {
        CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Penyisihan',
            'type' => CompetitionRound::TYPE_PRELIMINARY,
            'sort_order' => 1,
        ]);

        // Rubriknya sengaja tanpa competition_round_id.
        $kriteria = $this->rubrik('PBB Umum', null);
        $reg = $this->peserta('SMPN Satu', $this->groupA);
        $this->nilai($reg, $kriteria, 85);

        $html = $this->html($this->level->id);

        $this->assertStringContainsString('SMPN Satu', $html, 'Rekap kosong padahal rubrik tanpa babak ada.');
        $this->assertStringNotContainsString('Belum Ada Data', $html);
    }

    /**
     * Tingkat berbabak + bergrup yang rubriknya belum dibuat sama sekali.
     *
     * Bentuk inilah yang dilaporkan dari lapangan (kategori 28): dua grup,
     * dua babak, 20 peserta terbagi rata, tapi rubriknya kosong. Dulu babaknya
     * langsung dibuang sehingga seluruh rekap jadi "Belum Ada Data" — padahal
     * tingkat TANPA babak dengan keadaan yang sama tetap menampilkan
     * pesertanya. Kosongnya rubrik itu kekurangan konfigurasi, bukan alasan
     * menyembunyikan peserta.
     */
    public function test_tingkat_berbabak_tanpa_rubrik_sama_sekali_tetap_tampil()
    {
        CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup',
            'type' => CompetitionRound::TYPE_PRELIMINARY,
            'sort_order' => 1,
        ]);
        CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Final',
            'type' => CompetitionRound::TYPE_FINAL,
            'sort_order' => 2,
        ]);

        $this->peserta('SMPN Grup A', $this->groupA);
        $this->peserta('SMPN Belum Dibagi', null);

        $html = $this->html($this->level->id);

        $this->assertStringContainsString('SMPN Grup A', $html, 'Peserta hilang saat rubriknya belum dibuat.');
        $this->assertStringContainsString('SMPN Belum Dibagi', $html);
        $this->assertStringNotContainsString('Belum Ada Data', $html);

        // Ketiadaan rubriknya harus terbaca sebagai kekurangan konfigurasi.
        $this->assertStringContainsString('belum punya format penilaian', $html);
    }

    /** Tingkat berbabak tanpa peserta tetap kosong — tidak ada yang ditampilkan. */
    public function test_tingkat_berbabak_tanpa_peserta_tanpa_rubrik_tetap_kosong()
    {
        CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup',
            'type' => CompetitionRound::TYPE_PRELIMINARY,
            'sort_order' => 1,
        ]);

        $html = $this->html($this->level->id);

        $this->assertStringContainsString('Belum Ada Data', $html);
    }
}
