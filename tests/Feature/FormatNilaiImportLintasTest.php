<?php

namespace Tests\Feature;

use App\Livewire\Eventner\FormatNilai\Builder;
use App\Livewire\Eventner\FormatNilai\ImportLintas;
use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentSubCategory;
use App\Models\CompetitionCategory;
use App\Models\DeductionCategory;
use App\Models\DeductionCriteria;
use App\Models\Eventner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Import rubrik lintas event lewat kode berbagi.
 *
 * Tiga hal yang dijaga di sini, ketiganya kegagalan yang tampak seperti
 * keberhasilan:
 *
 * 1. Kode gerbang bocor. Daftar event sumber diambil dengan `get()` polos; ikut
 *    menyeleksi `format_share_code` akan mengirim kode berbagi semua event ke
 *    browser, dan gerbangnya jadi hiasan.
 * 2. Kode mati di tengah jalan. Antara pratinjau dibuka dan tombol simpan
 *    ditekan, panitia sumber bisa mencabut kodenya. Tanpa pemeriksaan ulang,
 *    salinan tetap masuk lewat sesi yang sudah basi.
 * 3. Pengurangan tingkat hilang diam-diam. Kelompok ber-scope 'global' tidak
 *    menempel pada kategori mana pun; menyalinnya dengan pola `rubrik_index`
 *    membuatnya terbuang tanpa satu pun pesan.
 */
class FormatNilaiImportLintasTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Eventner $sendiri;

    private Eventner $sumber;

    private CompetitionCategory $tingkatSendiri;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->eventner()->create(['is_active' => true]);
        $this->sendiri = Eventner::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'approved',
            'nama_event' => 'Event Sendiri',
        ]);
        $this->actingAs($this->user);

        $induk = CompetitionCategory::factory()->create([
            'eventner_id' => $this->sendiri->id,
            'parent_id' => null,
        ]);
        $this->tingkatSendiri = CompetitionCategory::factory()->create([
            'eventner_id' => $this->sendiri->id,
            'parent_id' => $induk->id,
            'name' => 'SMP',
        ]);

        $userSumber = User::factory()->eventner()->create(['is_active' => true]);
        $this->sumber = Eventner::factory()->create([
            'user_id' => $userSumber->id,
            'status' => 'approved',
            'nama_event' => 'Lomba PBB se-Jawa Barat 2026',
            'subdomain' => 'pbb2026',
            'format_share_code' => 'PBB26-SMP-4KQ9',
        ]);
    }

    /** Bangun satu rubrik lengkap di event sumber: kategori → sub → kriteria. */
    private function buatRubrikSumber(string $nama = 'Ketepatan Formasi', int $jumlahKriteria = 1): AssessmentCategory
    {
        $induk = CompetitionCategory::factory()->create([
            'eventner_id' => $this->sumber->id,
            'parent_id' => null,
        ]);
        $tingkat = CompetitionCategory::factory()->create([
            'eventner_id' => $this->sumber->id,
            'parent_id' => $induk->id,
            'name' => 'SMP',
        ]);

        $cat = AssessmentCategory::create([
            'eventner_id' => $this->sumber->id,
            'competition_category_id' => $tingkat->id,
            'name' => $nama,
            'sort_order' => 1,
        ]);

        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $cat->id,
            'name' => 'Gerakan',
            'sort_order' => 1,
        ]);

        for ($i = 1; $i <= $jumlahKriteria; $i++) {
            AssessmentCriteria::create([
                'assessment_sub_category_id' => $sub->id,
                'name' => "Kriteria {$i}",
                'score_options' => [1, 2, 3],
                'weight' => 1,
                'sort_order' => $i,
            ]);
        }

        return $cat;
    }

    private function komponen()
    {
        return Livewire::test(ImportLintas::class)->set('activeTab', (string) $this->tingkatSendiri->id);
    }

    // ── Gerbang kode ────────────────────────────────────────────────

    public function test_kode_benar_membuka_pratinjau_tanpa_menulis_apa_pun(): void
    {
        $this->buatRubrikSumber();

        $komponen = $this->komponen()
            ->set('sourceEventnerId', (string) $this->sumber->id)
            ->set('shareCode', 'PBB26-SMP-4KQ9')
            ->call('verifyAndPreview')
            ->assertHasNoErrors()
            ->assertSet('step', 2);

        // Belum satu baris pun masuk ke event sendiri.
        $this->assertSame(
            0,
            AssessmentCategory::where('eventner_id', $this->sendiri->id)->count()
        );

        $this->assertSame(1, $komponen->get('previewMeta')['rubrikCount']);
    }

    public function test_kode_salah_ditolak_saat_submit_bukan_saat_mengetik(): void
    {
        $this->buatRubrikSumber();

        // Mengetik kode salah TIDAK boleh memunculkan pesan apa pun — pesan yang
        // muncul per ketikan mengubah isian ini jadi oracle tebak-kode.
        $this->komponen()
            ->set('sourceEventnerId', (string) $this->sumber->id)
            ->set('shareCode', 'SALAH')
            ->assertHasNoErrors();

        $this->komponen()
            ->set('sourceEventnerId', (string) $this->sumber->id)
            ->set('shareCode', 'SALAH')
            ->call('verifyAndPreview')
            ->assertHasErrors(['shareCode' => ImportLintas::MSG_KODE_SALAH]);
    }

    public function test_belum_dibagikan_dan_dicabut_punya_kalimat_berbeda(): void
    {
        $this->sumber->update(['format_share_code' => null, 'format_share_revoked_at' => null]);

        $this->komponen()
            ->set('sourceEventnerId', (string) $this->sumber->id)
            ->set('shareCode', 'APA-SAJA')
            ->call('verifyAndPreview')
            ->assertHasErrors(['shareCode' => ImportLintas::MSG_BELUM_DIBAGIKAN]);

        $this->sumber->update(['format_share_revoked_at' => now()]);

        $this->komponen()
            ->set('sourceEventnerId', (string) $this->sumber->id)
            ->set('shareCode', 'APA-SAJA')
            ->call('verifyAndPreview')
            ->assertHasErrors(['shareCode' => ImportLintas::MSG_DICABUT]);
    }

    public function test_event_belum_disetujui_ditolak_sebelum_kode_dinilai(): void
    {
        $this->sumber->update(['status' => 'pending']);

        $this->komponen()
            ->set('sourceEventnerId', (string) $this->sumber->id)
            ->set('shareCode', 'PBB26-SMP-4KQ9')
            ->call('verifyAndPreview')
            ->assertHasErrors(['shareCode' => ImportLintas::MSG_BELUM_DISETUJUI]);
    }

    public function test_event_sendiri_tidak_bisa_jadi_sumber(): void
    {
        $this->komponen()
            ->set('sourceEventnerId', (string) $this->sendiri->id)
            ->set('shareCode', 'PBB26-SMP-4KQ9')
            ->call('verifyAndPreview')
            ->assertHasErrors(['sourceEventnerId']);
    }

    /**
     * Daftar event sumber tidak boleh membawa kode berbagi ke browser.
     *
     * Halaman publik mana pun bisa dibuka tanpa kode; kalau kolomnya ikut
     * terseleksi, gerbang ini hanya menyembunyikan rubrik — kodenya sendiri
     * sudah ada di HTML.
     */
    public function test_daftar_event_sumber_tidak_membocorkan_kode(): void
    {
        $this->buatRubrikSumber();

        $daftar = $this->komponen()->instance()->sumberEvents();

        $this->assertSame(1, $daftar->count());
        $this->assertSame(
            ['id', 'nama_event', 'subdomain'],
            array_keys($daftar->first()->getAttributes())
        );
    }

    public function test_pratinjau_tidak_membocorkan_apa_pun_sebelum_kode_benar(): void
    {
        $this->buatRubrikSumber('Ketepatan Formasi');

        $this->komponen()
            ->set('sourceEventnerId', (string) $this->sumber->id)
            ->set('shareCode', 'SALAH')
            ->call('verifyAndPreview')
            ->assertSet('step', 1)
            ->assertDontSee('Ketepatan Formasi');
    }

    // ── Kode mati di tengah jalan ───────────────────────────────────

    public function test_kode_dicabut_saat_pratinjau_terbuka_membatalkan_simpan(): void
    {
        $this->buatRubrikSumber();

        $komponen = $this->komponen()
            ->set('sourceEventnerId', (string) $this->sumber->id)
            ->set('shareCode', 'PBB26-SMP-4KQ9')
            ->call('verifyAndPreview')
            ->assertSet('step', 2);

        // Panitia sumber mencabut kodenya setelah pratinjau dibuka.
        $this->sumber->update(['format_share_code' => null, 'format_share_revoked_at' => now()]);

        $komponen->call('confirmImport')
            ->assertHasErrors(['shareCode' => ImportLintas::MSG_DICABUT]);

        $this->assertSame(
            0,
            AssessmentCategory::where('eventner_id', $this->sendiri->id)->count(),
            'Rubrik tidak boleh tersalin setelah kodenya dicabut.'
        );

        // Pratinjau lama dibuang: panitia harus mulai dari langkah 1.
        $this->assertSame(1, $komponen->get('step'));
    }

    // ── Penulisan ───────────────────────────────────────────────────

    public function test_salin_menempel_ke_event_sendiri_bukan_event_sumber(): void
    {
        $this->buatRubrikSumber('Ketepatan Formasi', 3);

        $this->komponen()
            ->set('sourceEventnerId', (string) $this->sumber->id)
            ->set('shareCode', 'PBB26-SMP-4KQ9')
            ->call('verifyAndPreview')
            ->call('lanjutPilihTingkat')
            ->assertSet('step', 3)
            ->call('confirmImport')
            ->assertHasNoErrors();

        $baru = AssessmentCategory::where('eventner_id', $this->sendiri->id)->get();

        $this->assertCount(1, $baru);
        $this->assertSame('Ketepatan Formasi', $baru->first()->name);
        $this->assertSame($this->tingkatSendiri->id, $baru->first()->competition_category_id);

        // Rubrik event sumber tetap utuh dan tetap milik event sumber — salinan
        // tidak memindahkan barisnya, hanya menggandakan definisinya.
        $this->assertTrue(
            AssessmentCategory::where('eventner_id', $this->sumber->id)->exists()
        );

        $this->assertSame(
            3,
            AssessmentCriteria::whereHas(
                'subCategory.category',
                fn ($q) => $q->where('eventner_id', $this->sendiri->id)
            )->count()
        );
    }

    public function test_pengurangan_tingkat_ikut_tersalin(): void
    {
        $cat = $this->buatRubrikSumber();

        // Kelompok pengurangan bers-scope 'global': menempel ke TINGKAT, bukan ke
        // kategori. Pola `rubrik_index` tidak bisa menyatakannya, jadi tanpa
        // lingkar khusus ia terbuang tanpa satu pun pesan.
        $global = DeductionCategory::create([
            'eventner_id' => $this->sumber->id,
            'assessment_category_id' => null,
            'competition_category_id' => $cat->competition_category_id,
            'scope' => DeductionCategory::SCOPE_GLOBAL,
            'name' => 'Pengurangan Nilai Tingkat',
            'sort_order' => 1,
        ]);
        DeductionCriteria::create([
            'deduction_category_id' => $global->id,
            'name' => 'Terlambat masuk lapangan',
            'deduction_options' => [-5, -10],
            'sort_order' => 1,
        ]);

        $this->komponen()
            ->set('sourceEventnerId', (string) $this->sumber->id)
            ->set('shareCode', 'PBB26-SMP-4KQ9')
            ->call('verifyAndPreview')
            ->call('lanjutPilihTingkat')
            ->call('confirmImport')
            ->assertHasNoErrors();

        $disalin = DeductionCategory::where('eventner_id', $this->sendiri->id)
            ->where('scope', DeductionCategory::SCOPE_GLOBAL)
            ->first();

        $this->assertNotNull($disalin, 'Pengurangan tingkat milik sumber harus ikut tersalin.');
        $this->assertSame('Pengurangan Nilai Tingkat', $disalin->name);
        $this->assertSame($this->tingkatSendiri->id, $disalin->competition_category_id);
        $this->assertNull($disalin->assessment_category_id);
        $this->assertSame(1, $disalin->criterias()->count());
    }

    public function test_butuh_tingkat_tujuan(): void
    {
        $this->buatRubrikSumber();

        Livewire::test(ImportLintas::class)
            ->set('activeTab', '')
            ->set('sourceEventnerId', (string) $this->sumber->id)
            ->set('shareCode', 'PBB26-SMP-4KQ9')
            ->call('verifyAndPreview')
            ->call('lanjutPilihTingkat')
            ->call('confirmImport')
            ->assertHasErrors('activeTab');

        $this->assertSame(
            0,
            AssessmentCategory::where('eventner_id', $this->sendiri->id)->count()
        );
    }

    public function test_tingkat_tujuan_milik_event_lain_ditolak(): void
    {
        $this->buatRubrikSumber();

        $indukLain = CompetitionCategory::factory()->create([
            'eventner_id' => $this->sumber->id,
            'parent_id' => null,
        ]);
        $tingkatLain = CompetitionCategory::factory()->create([
            'eventner_id' => $this->sumber->id,
            'parent_id' => $indukLain->id,
        ]);

        Livewire::test(ImportLintas::class)
            ->set('activeTab', (string) $tingkatLain->id)
            ->set('sourceEventnerId', (string) $this->sumber->id)
            ->set('shareCode', 'PBB26-SMP-4KQ9')
            ->call('verifyAndPreview')
            ->call('lanjutPilihTingkat')
            ->call('confirmImport')
            ->assertHasErrors('activeTab');

        $this->assertSame(
            0,
            AssessmentCategory::where('eventner_id', $this->sendiri->id)->count()
        );
    }

    public function test_sumber_tanpa_rubrik_tidak_bisa_dilanjutkan(): void
    {
        $this->komponen()
            ->set('sourceEventnerId', (string) $this->sumber->id)
            ->set('shareCode', 'PBB26-SMP-4KQ9')
            ->call('verifyAndPreview')
            ->assertHasNoErrors()
            ->assertSet('step', 2)
            ->call('lanjutPilihTingkat')
            ->call('confirmImport')
            ->assertHasErrors('shareCode');
    }

    public function test_kategori_senama_ditandai_bukan_ditolak(): void
    {
        $this->buatRubrikSumber('Ketepatan Formasi');

        AssessmentCategory::create([
            'eventner_id' => $this->sendiri->id,
            'competition_category_id' => $this->tingkatSendiri->id,
            'name' => 'Ketepatan Formasi',
            'sort_order' => 1,
        ]);

        $komponen = $this->komponen()
            ->set('sourceEventnerId', (string) $this->sumber->id)
            ->set('shareCode', 'PBB26-SMP-4KQ9')
            ->call('verifyAndPreview');

        $this->assertContains('Ketepatan Formasi', $komponen->get('previewMeta')['senama']);

        // Tetap boleh disimpan — panitia yang tahu mana yang harus dihapus.
        $komponen->call('lanjutPilihTingkat')->call('confirmImport')->assertHasNoErrors();

        $this->assertSame(
            2,
            AssessmentCategory::where('eventner_id', $this->sendiri->id)->count()
        );
    }

    public function test_selesai_mengirim_param_bernama(): void
    {
        $this->buatRubrikSumber();

        $komponen = $this->komponen()
            ->set('sourceEventnerId', (string) $this->sumber->id)
            ->set('shareCode', 'PBB26-SMP-4KQ9')
            ->call('verifyAndPreview')
            ->call('lanjutPilihTingkat')
            ->call('confirmImport');

        $dispatches = collect($komponen->effects['dispatches'] ?? [])
            ->firstWhere('name', 'import:done');

        $this->assertNotNull($dispatches, 'import:done harus dikirim agar Builder tersegarkan.');
        $this->assertIsArray($dispatches['params'] ?? null);
        $this->assertArrayHasKey('message', $dispatches['params']);
        $this->assertStringContainsString('Lomba PBB se-Jawa Barat 2026', $dispatches['params']['message']);
    }

    /**
     * Builder harus ikut tersegarkan lewat event yang sama dengan jalur Excel —
     * tanpa itu, kategori hasil salinan baru muncul setelah halaman dimuat ulang.
     */
    public function test_builder_menyegarkan_diri_setelah_import_done(): void
    {
        $this->buatRubrikSumber();

        Livewire::test(Builder::class)
            ->call('$refresh')
            ->dispatch('import:done', message: 'selesai')
            ->assertHasNoErrors();
    }

    // ── Sisi sumber: kode berbagi ───────────────────────────────────

    public function test_buat_kode_berbagi_membuat_kode_acak_dan_mematikan_yang_lama(): void
    {
        $this->sendiri->update(['format_share_code' => 'LAMA-1234']);

        Livewire::test(Builder::class)
            ->call('buatKodeBerbagi')
            ->assertHasNoErrors();

        $baru = $this->sendiri->fresh()->format_share_code;

        $this->assertNotSame('LAMA-1234', $baru);
        $this->assertSame(12, strlen($baru));

        // Kode lama langsung mati: gerbang dibaca dari nilai hidup, bukan salinan.
        $this->komponen()
            ->set('sourceEventnerId', (string) $this->sendiri->id)
            ->set('shareCode', 'LAMA-1234')
            ->call('verifyAndPreview')
            ->assertHasErrors();
    }

    public function test_cabut_kode_mengosongkan_kode_dan_mencatat_jejak(): void
    {
        $this->sendiri->update(['format_share_code' => 'PBB26-SMP-4KQ9']);

        Livewire::test(Builder::class)
            ->call('cabutKodeBerbagi')
            ->assertHasNoErrors();

        $segar = $this->sendiri->fresh();

        $this->assertNull($segar->format_share_code);
        $this->assertNotNull($segar->format_share_revoked_at);
    }

    /**
     * Mencabut izin membaca tidak menghapus hasil bacaan yang sudah masuk.
     */
    public function test_salinan_yang_sudah_masuk_tetap_utuh_setelah_kode_dicabut(): void
    {
        $this->buatRubrikSumber('Ketepatan Formasi');

        $this->komponen()
            ->set('sourceEventnerId', (string) $this->sumber->id)
            ->set('shareCode', 'PBB26-SMP-4KQ9')
            ->call('verifyAndPreview')
            ->call('lanjutPilihTingkat')
            ->call('confirmImport');

        $this->sumber->update(['format_share_code' => null, 'format_share_revoked_at' => now()]);

        $this->assertSame(
            1,
            AssessmentCategory::where('eventner_id', $this->sendiri->id)->count()
        );
    }

    // ── Tampilan Builder ────────────────────────────────────────────

    /**
     * AssertSee menjaga modal benar-benar dirender di dalam root komponen.
     * Modal Livewire yang ditulis setelah `</div>` root tidak pernah tampil,
     * sedangkan assertSee tetap lulus — karena itu yang diperiksa di sini
     * adalah dua hal sekaligus: item dropdownnya ada, dan modalnya ikut
     * ter-render di halaman yang sama.
     */
    public function test_builder_menampilkan_kartu_bagikan_dan_pintu_import(): void
    {
        Livewire::test(Builder::class)
            ->assertSee('Bagikan Format Penilaian')
            ->assertSee('Buat kode berbagi')
            ->assertSee('Import dari Event Lain')
            ->assertSee('importLintasModal')
            ->assertHasNoErrors();
    }

    /**
     * Tanpa tingkat lomba tidak ada sasaran tempel: pintunya disembunyikan
     * daripada membuka modal yang pasti berakhir di penolakan langkah 3.
     */
    public function test_pintu_import_disembunyikan_bila_belum_punya_tingkat(): void
    {
        CompetitionCategory::where('eventner_id', $this->sendiri->id)->delete();

        Livewire::test(Builder::class)
            ->assertDontSee('Import dari Event Lain')
            ->assertHasNoErrors();
    }
}
