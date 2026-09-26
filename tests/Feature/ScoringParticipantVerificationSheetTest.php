<?php

namespace Tests\Feature;

use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentSubCategory;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lembar verifikasi (halaman kedua) pada PDF nilai peserta.
 *
 * Lembar nilai dipegang pelatih; arsip panitia butuh bukti bahwa nilai itu
 * sudah dicek pelatihnya. Karena itu lembar ini ikut tercetak sebagai halaman
 * kedua di berkas yang sama — bukan unduhan terpisah yang baru dibuka kalau
 * panitia ingat.
 *
 * Yang diuji di sini isi rendernya, bukan PDF-nya: dompdf membuang viewData,
 * jadi halaman render langsung adalah satu-satunya cara memeriksa teksnya.
 */
class ScoringParticipantVerificationSheetTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $level;

    private Registration $reg;

    protected function setUp(): void
    {
        parent::setUp();

        $user = \App\Models\User::factory()->eventner()->create(['is_active' => true]);
        $this->eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'nama_event' => 'Lomba PBB 2026',
            'diselenggarakan_oleh' => 'Yayasan Berbaris',
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

        $this->reg = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'nama_sekolah' => 'SMPN 1',
            'npsn' => '20202020',
            'nama_pelatih' => 'Budi Santoso',
            'urutan_tampil' => 7,
        ]);
    }

    /**
     * Rubrik tunggal + kriterianya, mengembalikan kategori penilaiannya.
     *
     * Dipakai ulang antar pemanggilan (bukan dibuat baru tiap kali) supaya tes
     * yang perlu tahu id kategorinya sebelum merender tidak berakhir dengan
     * dua rubrik berbeda di satu halaman.
     */
    private function rubrik(string $name): AssessmentCategory
    {
        $ada = AssessmentCategory::where('eventner_id', $this->eventner->id)
            ->where('name', $name)
            ->first();

        if ($ada) {
            return $ada;
        }

        $cat = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => $name,
            'sort_order' => 1,
        ]);
        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $cat->id,
            'name' => 'Sub ' . $name,
        ]);
        AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Kriteria ' . $name,
            'score_options' => [['score' => 10]],
            'weight' => 1,
            'sort_order' => 1,
        ]);

        return $cat;
    }

    private function data(array $override = []): array
    {
        $cat = $this->rubrik('PBB');
        $this->reg->load('competitionCategory', 'competitionGroup');

        return array_merge([
            'eventner' => $this->eventner,
            'registration' => $this->reg,
            'roundName' => 'Fase Grup',
            'roundIsFinal' => false,
            'assessmentCategories' => collect([$cat]),
            'criteriaTotals' => [],
            'categoryTotals' => [$cat->id => 85],
            'categoryDeductions' => [],
            'grandTotal' => 85,
            'judges' => collect(),
            'judgeScores' => [],
            'judgeCategoryTotals' => [],
            'deductionCategories' => collect(),
            'scoreDeductions' => collect(),
            'totalDeduction' => 0,
            'finalScore' => 85,
        ], $override);
    }

    /** Lembar verifikasi saja — bukan PDF-nya. */
    private function lembar(array $override = []): string
    {
        return view('eventner.scoring.pdf_participant_verifikasi', $this->data($override))->render();
    }

    /** Seluruh berkas nilai peserta, termasuk halaman verifikasi di kakinya. */
    private function lembarPenuh(array $override = []): string
    {
        return view('eventner.scoring.pdf_participant', $this->data($override))->render();
    }

    /**
     * Halaman kedua benar-benar ikut di berkas yang sama.
     *
     * Inilah inti permintaannya: satu berkas untuk pelatih, satu bagian untuk
     * panitia. Kalau lembar ini jadi unduhan terpisah, ia tidak akan tercetak
     * pada saat yang tepat.
     */
    public function test_lembar_verifikasi_ikut_di_pdf_nilai_peserta()
    {
        $html = $this->lembarPenuh();

        $this->assertStringContainsString('Lembar Verifikasi Nilai', $html);
        $this->assertStringContainsString('page-break-before', $html);
        // Halaman pertama tetap ada — yang ditambahkan bukan pengganti.
        $this->assertStringContainsString('Lembar Penilaian', $html);
    }

    public function test_lembar_verifikasi_bertanda_tangan_pelatih()
    {
        $html = $this->lembar();

        $this->assertStringContainsString('Pernyataan Pelatih', $html);
        $this->assertStringContainsString('Budi Santoso', $html);
        $this->assertStringContainsString('Panitia Penerima', $html);
        // Ruang catatan: keberatan pelatih ditulis di sini, bukan di formulir
        // terpisah yang baru diisi setelah ia pulang.
        $this->assertStringContainsString('Catatan / keberatan', $html);
    }

    public function test_nomor_undian_tampil_di_lembar_verifikasi()
    {
        $html = $this->lembar();

        $this->assertStringContainsString('No. Undian', $html);
        $this->assertMatchesRegularExpression('/No\. Undian.*?7/s', $html);
    }

    /** Nomor undian juga tampil di lembar yang dipegang pelatih. */
    public function test_nomor_undian_tampil_di_lembar_penilaian()
    {
        $html = $this->lembarPenuh();

        $this->assertMatchesRegularExpression('/No\. Undian.*?>7</s', $html);
    }

    /** Peserta yang belum diundi tetap tercetak — kolomnya kosong, bukan hilang. */
    public function test_peserta_belum_diundi_tetap_punya_kolom_nomor()
    {
        $this->reg->update(['urutan_tampil' => null]);

        $html = $this->lembar();

        $this->assertStringContainsString('No. Undian', $html);
        $this->assertStringContainsString('—', $html);
    }

    /** Rekapitulasi memuat nilai kategori dan nilai akhir yang sama dengan halaman pertama. */
    public function test_rekapitulasi_memuat_nilai_kategori_dan_nilai_akhir()
    {
        $html = $this->lembar();

        $this->assertStringContainsString('Rekapitulasi Nilai', $html);
        $this->assertStringContainsString('Total Nilai Juri', $html);
        // 85 = categoryTotals dan finalScore pada data uji ini.
        $this->assertGreaterThanOrEqual(2, substr_count($html, '85'));
    }

    public function test_pengurangan_kategori_ikut_tercetak()
    {
        $cat = $this->rubrik('PBB');

        $html = $this->lembar([
            'categoryDeductions' => [$cat->id => -5],
            'totalDeduction' => -5,
            'finalScore' => 80,
        ]);

        $this->assertStringContainsString('Pengurangan', $html);
        $this->assertStringContainsString('-5', $html);
        $this->assertStringContainsString('80', $html);
    }

    public function test_identitas_kontingen_dan_babak_tercetak()
    {
        $html = $this->lembar();

        $this->assertStringContainsString('SMPN 1', $html);
        $this->assertStringContainsString('20202020', $html);
        $this->assertStringContainsString('Fase Grup', $html);
        // Tingkat lomba ditulis lengkap (parent - anak).
        $this->assertStringContainsString('PBB Putra', $html);
    }

    // ---------- tanda tangan pelatih hanya sekali ----------

    /**
     * Pelatih menandatangani satu kali saja, di lembar verifikasi.
     *
     * Halaman pertama dulu memuat kolom "Pelatih" kedua, padahal halaman itu
     * murni arsip panitia dan tak seorang pun menandatanganinya di sana. Dua
     * kolom tanda tangan dengan nama yang sama membuat pelatih mengira harus
     * menandatangani keduanya, dan yang kedua selalu kosong.
     */
    /** Nama pelatih hanya dicetak sekali, di kolom tanda tangannya. */
    public function test_nama_pelatih_hanya_muncul_di_lembar_verifikasi()
    {
        $html = $this->lembarPenuh();

        $verifikasi = explode('page-break-before', $html);
        $this->assertCount(2, $verifikasi, 'Lembar verifikasi tidak lagi jadi halaman kedua.');

        // Identitas pelatih tetap tercetak di kop halaman pertama — yang
        // dibuang cuma kolom tanda tangannya.
        $this->assertStringContainsString('Budi Santoso', $verifikasi[0]);
        $this->assertStringContainsString('Budi Santoso', $verifikasi[1]);

        // Kop halaman 1, kop lembar verifikasi, dan kolom tanda tangannya.
        $this->assertSame(
            3,
            substr_count($html, 'Budi Santoso'),
            'Nama pelatih tercetak tiga kali: dua kop dan satu kolom tanda tangan.'
        );
        // Dua kolom tanda tangan di lembar verifikasi (Pelatih dan Panitia
        // Penerima); halaman pertama tak lagi menyumbang satu pun.
        $this->assertSame(2, substr_count($html, '<span class="line"></span>'), 'Jumlah kolom tanda tangan berubah.');
    }

    /** Halaman pertama tinggal satu tanda tangan: Ketua Panitia. */
    public function test_halaman_pertama_hanya_ketua_panitia()
    {
        $html = $this->lembarPenuh();
        $halamanSatu = explode('page-break-before', $html)[0];

        $this->assertStringContainsString('Ketua Panitia', $halamanSatu);
        $this->assertStringNotContainsString('Pernyataan Persetujuan', $halamanSatu);
    }
}
