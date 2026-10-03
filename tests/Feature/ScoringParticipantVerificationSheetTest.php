<?php

namespace Tests\Feature;

use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentSubCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionSeries;
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
        $this->reg->load('competitionCategory', 'competitionGroup', 'competitionSeries');

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

    /**
     * Seri peserta ikut tercetak di kop lembar penilaian.
     *
     * Dua seri di tingkat yang sama boleh memakai nama kategori lomba yang
     * sama persis, jadi lembar Seri A dan Seri B tak bisa dibedakan setelah
     * ditumpuk tanpa baris ini.
     */
    public function test_seri_peserta_tercetak_di_kop()
    {
        $seri = CompetitionSeries::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Seri A',
        ]);
        $this->reg->update(['competition_series_id' => $seri->id]);

        $this->assertStringContainsString('Seri A', $this->lembar());
    }

    /** Peserta tanpa seri tetap mendapat barisnya, ditulis apa adanya. */
    public function test_peserta_tanpa_seri_ditulis_tanpa_seri()
    {
        $this->assertStringContainsString('Tanpa Seri', $this->lembar());
    }

    // ---------- tanda tangan pelatih ----------

    /**
     * Pelatih menandatangani di lembar nilainya sendiri, bukan cuma di arsip.
     *
     * Halaman pertama dulu hanya memuat kolom Ketua Panitia, dan tanda tangan
     * pelatih baru ada di Lembar Verifikasi halaman kedua. Lembar penilaian
     * itulah yang dipegang pelatih saat mencocokkan nilai, jadi tanda
     * tangannya harus ada di lembar yang benar-benar ia baca — bukan di
     * halaman yang baru dibuka panitia belakangan.
     */
    public function test_halaman_pertama_memuat_tanda_tangan_pelatih()
    {
        $html = $this->lembarPenuh();
        $halamanSatu = explode('page-break-before', $html)[0];

        // Kolom tanda tangannya, bukan sekadar label "Pelatih" di tabel info.
        $this->assertMatchesRegularExpression('/class="role"[^>]*>Pelatih</', $halamanSatu);
        $this->assertStringContainsString('<span class="line"></span>', $halamanSatu);

        // Kolom Ketua Panitia tetap ada, berikut QR-nya.
        $this->assertStringContainsString('Ketua Panitia', $halamanSatu);
        $this->assertStringContainsString('data:image/png;base64,', $halamanSatu);
    }

    /** Nama pelatih tercetak di kop dan kolom tanda tangan kedua halaman. */
    public function test_nama_pelatih_tercetak_di_kop_dan_kolom_tanda_tangan()
    {
        $html = $this->lembarPenuh();

        $verifikasi = explode('page-break-before', $html);
        $this->assertCount(2, $verifikasi, 'Lembar verifikasi tidak lagi jadi halaman kedua.');

        // Identitas pelatih di kop kedua halaman.
        $this->assertStringContainsString('Budi Santoso', $verifikasi[0]);
        $this->assertStringContainsString('Budi Santoso', $verifikasi[1]);

        // Empat kali: baris identitas + kolom tanda tangan, di kedua halaman.
        $this->assertSame(
            4,
            substr_count($html, 'Budi Santoso'),
            'Nama pelatih terhitung empat kali: dua baris identitas dan dua kolom tanda tangan.'
        );

        // Tiga kolom tanda tangan: pelatih di halaman pertama, lalu pelatih dan
        // panitia penerima di lembar verifikasi.
        $this->assertSame(3, substr_count($html, '<span class="line"></span>'), 'Jumlah kolom tanda tangan berubah.');
    }

    /** Pelatih dan Ketua Panitia berdampingan di halaman pertama. */
    public function test_halaman_pertama_kolom_pelatih_dan_ketua_panitia()
    {
        $html = $this->lembarPenuh();
        $halamanSatu = explode('page-break-before', $html)[0];

        $this->assertStringContainsString('Ketua Panitia', $halamanSatu);
        $this->assertStringNotContainsString('Pernyataan Persetujuan', $halamanSatu);

        // Urutannya pelatih lalu panitia, sama seperti lembar verifikasi:
        // pelatih di kolom kiri pada kedua halaman.
        $this->assertLessThan(
            strpos($halamanSatu, 'Ketua Panitia'),
            strpos($halamanSatu, '>Pelatih</div>'),
        );
    }
}
