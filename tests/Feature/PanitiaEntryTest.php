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
}
