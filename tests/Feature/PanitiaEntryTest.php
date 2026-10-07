<?php

namespace Tests\Feature;

use App\Livewire\Eventner\Judge\Index as JudgeIndex;
use App\Livewire\Public\PanitiaScoring\Index;
use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Entry nilai panitia: host entry, /panitia/{token}, gerbang PIN event.
 *
 * Halaman juri (/juri/{token}) sengaja tidak disentuh sama sekali — kalau
 * perubahan di sini merusaknya, JudgeTabletScoringTest yang akan berbunyi.
 */
class PanitiaEntryTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;
    private CompetitionCategory $category;
    private Registration $registration;
    private Judge $judge;
    private AssessmentCriteria $criteria;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->eventner()->create(['is_active' => true]);

        $this->eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'panitia_token' => 'tok-panitia-40',
            'panitia_pin' => '123456',
        ]);

        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
        ]);
        $this->category = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
        ]);

        $this->registration = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->category->id,
            'nama_sekolah' => 'SD Negeri 1',
            'urutan_tampil' => 1,
        ]);

        $this->judge = Judge::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Budi Santoso',
        ]);

        // Penugasan tingkat: satu-satunya jalur yang membuat juri muncul di
        // pemilih juri halaman panitia.
        CompetitionGroup::syncJudges($this->category->id, CompetitionGroup::SCOPE_LEVEL, null, [$this->judge->id]);

        $rubrik = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Penilaian Umum',
            'competition_category_id' => $this->category->id,
        ]);
        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $rubrik->id,
            'name' => 'Sub',
        ]);

        $this->judge->assessmentCategories()->attach($rubrik->id);

        $this->criteria = AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Kriteria 1',
            'score_options' => [['score' => 10], ['score' => 20]],
        ]);

        // Host entry ditentukan ENTRY_HOST di phpunit.xml, bukan config() di
        // sini: grup route entry didaftarkan saat aplikasi di-boot, jadi
        // menimpa config di setUp sudah terlambat untuk urusan domain.
    }

    private function url(string $token = 'tok-panitia-40'): string
    {
        // withServerVariables() tidak cukup untuk pindah host.
        return 'http://' . judge_entry_host() . '/panitia/' . $token;
    }

    /** Komponen dalam keadaan sudah lolos PIN. */
    private function terbuka()
    {
        $this->withSession(['panitia_entry.' . $this->eventner->id => true]);

        return Livewire::test(Index::class, ['token' => 'tok-panitia-40']);
    }

    public function test_token_salah_404()
    {
        $this->get($this->url('token-ngawur'))->assertStatus(404);
    }

    public function test_event_belum_disetujui_404()
    {
        $this->eventner->update(['status' => 'pending']);

        $this->get($this->url())->assertStatus(404);
    }

    public function test_layar_pin_tampil_sebelum_dibuka()
    {
        $this->get($this->url())
            ->assertOk()
            ->assertSee('Masukkan PIN entry panitia')
            ->assertDontSee('Pilih Tingkat Lomba');
    }

    public function test_pin_salah_tetap_terkunci()
    {
        Livewire::test(Index::class, ['token' => 'tok-panitia-40'])
            ->set('pinInput', '000000')
            ->call('bukaPin')
            ->assertSet('terbuka', false)
            ->assertSet('view', 'pin');

        $this->assertFalse((bool) session('panitia_entry.' . $this->eventner->id));
    }

    public function test_pin_benar_membuka_dan_bertahan_setelah_refresh()
    {
        Livewire::test(Index::class, ['token' => 'tok-panitia-40'])
            ->set('pinInput', '123456')
            ->call('bukaPin')
            ->assertSet('terbuka', true)
            ->assertSet('view', 'categories');

        $this->assertTrue((bool) session('panitia_entry.' . $this->eventner->id));

        // Permintaan berikutnya (refresh) tidak boleh meminta PIN lagi.
        $this->terbuka()->assertSet('terbuka', true);
    }

    public function test_set_score_menulis_baris_dengan_juri_terpilih()
    {
        $this->terbuka()
            ->call('selectCategory', $this->category->id)
            ->call('selectParticipant', $this->registration->id)
            ->call('selectJudge', $this->judge->id)
            ->call('setScore', $this->criteria->id, 20);

        $this->assertDatabaseHas('assessment_scores', [
            'registration_id' => $this->registration->id,
            'assessment_criteria_id' => $this->criteria->id,
            'judge_id' => $this->judge->id,
            'score' => 20,
        ]);
    }

    /** Guard IDOR: kriteria di luar rubrik juri tidak boleh bisa disimpan. */
    public function test_kriteria_di_luar_rubrik_juri_ditolak()
    {
        // Rubrik milik juri LAIN. Rubrik tanpa centang juri mana pun justru
        // terbuka untuk semua juri (scopeBolehDinilaiOleh), jadi harus
        // dicentang ke juri lain supaya benar-benar di luar jangkauan.
        $rubrikLain = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Rubrik Juri Lain',
            'competition_category_id' => $this->category->id,
        ]);
        $subLain = AssessmentSubCategory::create([
            'assessment_category_id' => $rubrikLain->id,
            'name' => 'Sub Lain',
        ]);
        $lain = AssessmentCriteria::create([
            'assessment_sub_category_id' => $subLain->id,
            'name' => 'Kriteria Asing',
            'score_options' => [['score' => 10]],
        ]);

        $juriLain = Judge::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Juri Lain',
        ]);
        $juriLain->assessmentCategories()->attach($rubrikLain->id);

        $this->terbuka()
            ->call('selectCategory', $this->category->id)
            ->call('selectParticipant', $this->registration->id)
            ->call('selectJudge', $this->judge->id)
            ->call('setScore', $lain->id, 10)
            ->assertStatus(403);

        $this->assertDatabaseMissing('assessment_scores', [
            'assessment_criteria_id' => $lain->id,
        ]);
    }

    public function test_tanpa_pin_tidak_bisa_menyimpan()
    {
        Livewire::test(Index::class, ['token' => 'tok-panitia-40'])
            ->call('selectCategory', $this->category->id)
            ->assertStatus(403);

        $this->assertDatabaseCount('assessment_scores', 0);
    }

    public function test_finalize_mengunci_nilai()
    {
        $this->terbuka()
            ->call('selectCategory', $this->category->id)
            ->call('selectParticipant', $this->registration->id)
            ->call('selectJudge', $this->judge->id)
            ->call('setScore', $this->criteria->id, 20)
            ->call('finalize')
            ->assertSet('isFinalized', true);

        $this->assertDatabaseHas('assessment_scores', [
            'registration_id' => $this->registration->id,
            'judge_id' => $this->judge->id,
            'is_finalized' => true,
        ]);
    }

    public function test_komponen_punya_satu_root_element()
    {
        $html = $this->terbuka()->html();

        $dom = new \DOMDocument();
        $dom->loadHTML($html, LIBXML_NOERROR);
        $body = $dom->getElementsByTagName('body')->item(0);

        $roots = 0;
        foreach ($body->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $roots++;
            }
        }

        $this->assertSame(1, $roots, 'Komponen Livewire harus punya tepat satu root element.');

        // Root itu yang dipegang Livewire, dan layar PIN benar-benar di dalamnya
        // — bukan saudara di luarnya (yang tak pernah sampai ke browser).
        $root = null;
        foreach ($body->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $root = $child;
            }
        }

        $this->assertNotNull($root);
        $this->assertStringContainsString('Pilih Tingkat Lomba', $html);
        $this->assertStringContainsString('Pilih Tingkat Lomba', $root->ownerDocument->saveHTML($root));
    }

    public function test_halaman_judges_menampilkan_link_dan_pin()
    {
        Livewire::actingAs($this->eventner->user)
            ->test(JudgeIndex::class)
            ->call('openPanitiaModal')
            ->assertSet('showPanitiaModal', true)
            ->assertSee('http://entry.berbaris.test/panitia/tok-panitia-40')
            ->assertSee('123456');
    }

    /** Panitia event lain tidak boleh melihat token event ini. */
    public function test_pemilik_event_lain_tidak_melihat_token()
    {
        $lain = Eventner::factory()->create([
            'user_id' => User::factory()->eventner()->create(['is_active' => true])->id,
            'status' => 'approved',
            'panitia_token' => 'tok-panitia-lain',
        ]);

        Livewire::actingAs($lain->user)
            ->test(JudgeIndex::class)
            ->call('openPanitiaModal')
            ->assertDontSee('tok-panitia-40');
    }

    public function test_helper_url_memakai_host_entry()
    {
        $this->assertSame(
            'http://entry.berbaris.test/panitia/tok-panitia-40',
            $this->eventner->panitiaEntryUrl(),
        );

        $this->assertNull(Eventner::factory()->create(['panitia_token' => null])->panitiaEntryUrl());
    }

    /**
     * Link yang masa berlakunya habis harus DIKATAKAN di modal.
     *
     * Tanpa ini, "Buat Akses" pada event yang tanggalnya sudah lewat
     * menghasilkan link yang 404 begitu dibuka, dan pemilik event tak punya
     * cara tahu sebabnya dari dashboard.
     */
    public function test_modal_memperingatkan_link_kedaluwarsa()
    {
        $this->eventner->update(['tanggal' => now()->subMonths(3)->toDateString(), 'tanggal_akhir' => null]);

        Livewire::actingAs($this->eventner->user)
            ->test(JudgeIndex::class)
            ->call('openPanitiaModal')
            ->assertSee('Link ini tidak bisa dibuka');
    }

    public function test_modal_tidak_memperingatkan_saat_masih_berlaku()
    {
        Livewire::actingAs($this->eventner->user)
            ->test(JudgeIndex::class)
            ->call('openPanitiaModal')
            ->assertDontSee('Link ini tidak bisa dibuka');
    }

    /**
     * Kartu akses panitia: link + QR + PIN dalam satu PDF.
     *
     * Isinya diperiksa lewat render view, bukan byte PDF — dompdf memampatkan
     * aliran teksnya, jadi string match ke berkas PDF akan gagal walau isinya
     * benar (pola yang sama dengan JudgeAccessCardTest).
     */
    public function test_kartu_panitia_memuat_link_qr_dan_pin()
    {
        // Tanggal event dipatok: masa berlaku hanya ikut tercetak bila event
        // punya tanggal, dan asersi di bawah menuntut tanggal itu ada.
        $this->eventner->update(['tanggal' => '2026-10-18', 'tanggal_akhir' => null]);

        $this->actingAs($this->eventner->user)
            ->get(route('eventner.judges.kartu-panitia'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $html = view('eventner.panitia.pdf_kartu_akses', [
            'eventner' => $this->eventner,
            'url' => $this->eventner->panitiaEntryUrl(),
            'qrImage' => qr_data_uri($this->eventner->panitiaEntryUrl(), 12),
        ])->render();

        $this->assertStringContainsString('Kartu Akses Entry Nilai Panitia', $html);
        $this->assertStringContainsString('entry.berbaris.test/panitia/tok-panitia-40', $html);
        $this->assertStringContainsString('123456', $html);

        // Harus PNG: dompdf membuang SVG diam-diam, dan kartu tanpa QR tetap
        // terunduh tanpa keluhan — panitia baru sadar saat mencetaknya.
        $this->assertStringContainsString('data:image/png;base64,', $html);

        // Data-URI dilepas dulu sebelum mencari "svg": alfabet base64 PNG
        // memuat huruf s, v, g, jadi string "svg" bisa muncul acak di dalam
        // gambar yang sah dan tes gagal sesekali tanpa ada yang berubah.
        $tanpaDataUri = preg_replace('#data:image/[a-z+]+;base64,[A-Za-z0-9+/=]+#', '', $html);

        $this->assertStringNotContainsString('<svg', strtolower($tanpaDataUri));
        $this->assertStringNotContainsString('image/svg', strtolower($tanpaDataUri));

        // Masa berlaku ikut tercetak: halaman entry 404 sesudahnya, dan kartu
        // tanpa tanggal membuat panitia mengira PIN-nya yang salah.
        // (Dulu blok "Masa Berlaku" sendiri; sekarang satu langkah di daftar
        // "Cara Membuka" supaya kartu tetap muat satu lembar.)
        $this->assertStringContainsString('Halaman menolak dibuka?', $html);
        $this->assertStringContainsString('18 Oktober 2026', $html);
    }

    /**
     * Kartu harus SATU lembar — termasuk saat nama event & penyelenggara panjang.
     *
     * Kartu ini dicetak lalu diserahkan ke meja; lembar kedua yang cuma berisi
     * ekor peringatan mudah terlewat, dan di situlah PIN-nya berada. Batasnya
     * pernah kesentuh (nama panjang + banyak tempat = 2 halaman), jadi angkanya
     * diuji, bukan dikira-kira.
     */
    public function test_kartu_panitia_satu_lembar_walau_nama_event_panjang()
    {
        $this->eventner->update([
            'nama_event' => 'Kejuaraan Nasional Baris Berbaris, Ketangkasan, dan Variasi Formasi '
                . 'Antar Sekolah Menengah Atas Serta Madrasah Aliyah Se-Indonesia Tahun Anggaran 2026',
            'diselenggarakan_oleh' => 'Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi '
                . 'Direktorat Jenderal Pendidikan Anak Usia Dini, Pendidikan Dasar, dan Pendidikan Menengah',
            'tanggal' => '2026-10-18',
            'tanggal_akhir' => '2026-10-21',
            'venue' => 'GOR Padjadjaran / Stadion Si Jalak Harupat / Lapangan Gasibu',
        ]);

        $html = view('eventner.panitia.pdf_kartu_akses', [
            'eventner' => $this->eventner,
            'url' => $this->eventner->panitiaEntryUrl(),
            'qrImage' => qr_data_uri($this->eventner->panitiaEntryUrl(), 12),
        ])->render();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->output();

        // Dompdf menulis /Type /Page untuk tiap halaman dan /Type /Pages untuk
        // katalognya — polanya harus mengecualikan yang kedua.
        $halaman = preg_match_all('#/Type\s*/Page[^s]#', $pdf);

        $this->assertSame(1, $halaman, 'Kartu akses panitia harus muat satu lembar A4.');
    }

    /** Nama berkas ikut nama event, dan isinya bukan milik event lain. */
    public function test_kartu_panitia_terpisah_per_event()
    {
        $lain = Eventner::factory()->create([
            'user_id' => User::factory()->eventner()->create(['is_active' => true])->id,
            'status' => 'approved',
            'panitia_token' => 'tok-panitia-lain',
            'panitia_pin' => '999999',
        ]);

        $this->actingAs($lain->user)
            ->get(route('eventner.judges.kartu-panitia'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $html = view('eventner.panitia.pdf_kartu_akses', [
            'eventner' => $lain,
            'url' => $lain->panitiaEntryUrl(),
            'qrImage' => qr_data_uri($lain->panitiaEntryUrl(), 12),
        ])->render();

        // Token & PIN event lain tidak boleh bocor ke kartu ini.
        $this->assertStringNotContainsString('tok-panitia-40', $html);
        $this->assertStringNotContainsString('123456', $html);
        $this->assertStringContainsString('tok-panitia-lain', $html);
        $this->assertStringContainsString('999999', $html);
    }

    /** Belum ada akses = tidak ada yang bisa dicetak; 404, bukan kartu kosong. */
    public function test_kartu_panitia_404_saat_akses_belum_dibuat()
    {
        $this->eventner->update(['panitia_token' => null, 'panitia_pin' => null]);

        $this->actingAs($this->eventner->user)
            ->get(route('eventner.judges.kartu-panitia'))
            ->assertStatus(404);
    }

    /** Tamu tanpa event tidak boleh mengunduh kartu milik siapa pun. */
    public function test_kartu_panitia_menolak_pengguna_tanpa_event()
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('eventner.judges.kartu-panitia'))
            ->assertStatus(403);
    }

    public function test_modal_menawarkan_unduh_kartu_saat_akses_ada()
    {
        Livewire::actingAs($this->eventner->user)
            ->test(JudgeIndex::class)
            ->call('openPanitiaModal')
            ->assertSee('Unduh Kartu Akses');
    }
}
