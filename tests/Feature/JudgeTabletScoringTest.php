<?php

namespace Tests\Feature;

use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JudgeTabletScoringTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;
    private CompetitionCategory $category;
    private Registration $registration;
    private Judge $judge;

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

        // Halaman juri disajikan dari host entry tetap (bukan host request).
        config(['app.entry_host' => 'entry.berbaris.test']);
    }

    private function makeRubric(int $criteriaCount = 1, ?int $competitionCategoryId = null): array
    {
        $category = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Penilaian Umum',
            'competition_category_id' => $competitionCategoryId,
        ]);
        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $category->id,
            'name' => 'Sub',
        ]);

        $this->judge->assessmentCategories()->attach($category->id);

        $criteria = [];
        for ($i = 1; $i <= $criteriaCount; $i++) {
            $criteria[] = AssessmentCriteria::create([
                'assessment_sub_category_id' => $sub->id,
                'name' => 'Kriteria ' . $i,
                'score_options' => [['score' => 10], ['score' => 20]],
            ]);
        }

        return $criteria;
    }

    public function test_token_dibuat_otomatis_saat_juri_dibuat()
    {
        $this->assertNotEmpty($this->judge->access_token);
        $this->assertSame(16, strlen($this->judge->access_token));
    }

    /**
     * Request di host entry milik platform (config app.entry_host).
     *
     * Harus URL absolut: SymfonyRequest::create menimpa HTTP_HOST dari host URL,
     * jadi withServerVariables() tidak cukup untuk pindah host.
     */
    private function tablet(string $path)
    {
        return $this->get('http://' . judge_entry_host() . $path);
    }

    public function test_token_salah_menghasilkan_404()
    {
        $this->tablet('/juri/token-ngawur')->assertNotFound();
    }

    public function test_halaman_tablet_terbuka_dengan_token_valid()
    {
        $this->tablet('/juri/' . $this->judge->access_token)->assertOk();
    }

    public function test_halaman_tablet_tidak_diindeks_mesin_pencari()
    {
        $this->tablet('/juri/' . $this->judge->access_token)
            ->assertOk()
            ->assertSee('noindex, nofollow');
    }

    /**
     * Token ada di URL. Tanpa no-referrer, browser mengirim URL penuh itu ke
     * fonts.googleapis.com/jsdelivr lewat header Referer saat halaman memuat
     * resource dari sana — token nyangkut di access log pihak ketiga.
     */
    public function test_halaman_tablet_tidak_membocorkan_token_lewat_referer()
    {
        $this->tablet('/juri/' . $this->judge->access_token)
            ->assertOk()
            ->assertSee('name="referrer" content="no-referrer"', false);
    }

    /** Aset pihak ketiga di-pin ke versi persis, bukan "@latest". */
    public function test_aset_pihak_ketiga_di_pin_versinya()
    {
        $html = $this->tablet('/juri/' . $this->judge->access_token)->assertOk()->getContent();

        $this->assertStringNotContainsString('@latest', $html);
    }

    /** Layout khusus juri — bukan layout frontend dengan nav + footer event. */
    public function test_halaman_tablet_memakai_layout_juri_tanpa_navigasi_event()
    {
        $response = $this->tablet('/juri/' . $this->judge->access_token)->assertOk();

        // Footer ringkas khas layout juri.
        $response->assertSee('Nilai tersimpan otomatis', false);

        // Navigasi event / footer marketing tidak boleh ikut.
        $response->assertDontSee('Hak cipta dilindungi', false);
        $response->assertDontSee('Daftar Eventner', false);
        $response->assertDontSee('google-adsense-account', false);
    }

    public function test_alamat_juri_lama_redirect_permanen_ke_host_entry()
    {
        config(['app.entry_host' => 'entry.berbaris.test']);

        $this->get('/juri/' . $this->judge->access_token)
            ->assertStatus(301)
            ->assertRedirect('http://entry.berbaris.test/juri/' . $this->judge->access_token);

        // Varian ber-slug dinormalkan ke /juri/{token} — host entry tidak punya /event/{slug}.
        $this->get('/event/lomba-abc/juri/' . $this->judge->access_token)
            ->assertStatus(301)
            ->assertRedirect('http://entry.berbaris.test/juri/' . $this->judge->access_token);

        $this->tablet('/juri/' . $this->judge->access_token)->assertOk();
    }

    public function test_tanpa_rubrik_juri_tidak_melihat_kategori_apa_pun()
    {
        Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judge->access_token])
            ->assertSet('view', 'categories')
            ->assertViewHas('categories', fn ($cats) => $cats->isEmpty());
    }

    public function test_rubrik_khusus_tingkat_lain_tidak_membuka_kategori_ini()
    {
        // Rubrik diikat ke tingkat lomba lain → kategori kita tidak muncul.
        $otherParent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
        ]);
        $otherCategory = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $otherParent->id,
        ]);
        $this->makeRubric(1, $otherCategory->id);

        Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judge->access_token])
            ->assertViewHas('categories', fn ($cats) => !$cats->pluck('id')->contains($this->category->id));
    }

    public function test_peserta_diurutkan_sesuai_urutan_tampil()
    {
        $this->makeRubric();

        $this->registration->update(['urutan_tampil' => 3]);

        $second = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->category->id,
            'nama_sekolah' => 'SD Negeri 2',
            'urutan_tampil' => 2,
        ]);
        $first = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->category->id,
            'nama_sekolah' => 'SD Negeri 3',
            'urutan_tampil' => 1,
        ]);

        Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judge->access_token])
            ->call('selectCategory', $this->category->id)
            ->assertViewHas('participants', fn ($list) => $list->pluck('id')->all() === [$first->id, $second->id, $this->registration->id]);
    }

    public function test_ketuk_nilai_langsung_tersimpan_ke_database()
    {
        $criteria = $this->makeRubric()[0];

        Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judge->access_token])
            ->call('selectCategory', $this->category->id)
            ->call('selectParticipant', $this->registration->id)
            ->call('setScore', $criteria->id, 20);

        $this->assertDatabaseHas('assessment_scores', [
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->registration->id,
            'assessment_criteria_id' => $criteria->id,
            'judge_id' => $this->judge->id,
            'score' => 20,
        ]);
    }

    /** Mode satu-per-satu: ketuk nilai → otomatis ke kriteria berikutnya. */
    public function test_mode_satu_per_satu_maju_otomatis_setelah_menilai()
    {
        $criteria = $this->makeRubric(3);

        Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judge->access_token])
            ->call('selectCategory', $this->category->id)
            ->call('selectParticipant', $this->registration->id)
            ->assertSet('criteriaMode', 'satu-satu')
            ->assertSet('currentCriteriaIndex', 0)
            ->call('setScore', $criteria[0]->id, 10)
            ->assertSet('currentCriteriaIndex', 1)
            ->call('setScore', $criteria[1]->id, 20)
            ->assertSet('currentCriteriaIndex', 2);
    }

    /** Maju otomatis berhenti di kriteria terakhir — tidak melewati batas. */
    public function test_mode_satu_per_satu_tidak_melewati_kriteria_terakhir()
    {
        $criteria = $this->makeRubric(2);

        Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judge->access_token])
            ->call('selectCategory', $this->category->id)
            ->call('selectParticipant', $this->registration->id)
            ->call('setScore', $criteria[0]->id, 10)
            ->call('setScore', $criteria[1]->id, 20)
            ->assertSet('currentCriteriaIndex', 1);
    }

    /** Mode "semua" tidak memindahkan posisi saat nilai diketuk. */
    public function test_mode_semua_tidak_memindahkan_kriteria_aktif()
    {
        $criteria = $this->makeRubric(2);

        Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judge->access_token])
            ->call('selectCategory', $this->category->id)
            ->call('selectParticipant', $this->registration->id)
            ->call('setCriteriaMode', 'semua')
            ->assertSet('criteriaMode', 'semua')
            ->call('goToCriteria', 0)
            ->call('setScore', $criteria[0]->id, 10)
            ->assertSet('currentCriteriaIndex', 0)
            ->assertSet('scores.' . $criteria[0]->id, 10);
    }

    /** Menyentuh peserta membuka kriteria kosong pertama, bukan selalu nomor 1. */
    public function test_kriteria_kosong_pertama_dipilih_saat_membuka_peserta()
    {
        $criteria = $this->makeRubric(3);

        // Kriteria 2 sudah dinilai sebelumnya (mis. juri sempat menutup aplikasi).
        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->registration->id,
            'assessment_criteria_id' => $criteria[0]->id,
            'judge_id' => $this->judge->id,
            'score' => 10,
        ]);

        Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judge->access_token])
            ->call('selectCategory', $this->category->id)
            ->call('selectParticipant', $this->registration->id)
            ->assertSet('currentCriteriaIndex', 1);
    }

    public function test_kriteria_di_luar_rubrik_juri_ditolak()    {
        $this->makeRubric();

        // Rubrik milik juri lain
        $otherJudge = Judge::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Juri Lain',
        ]);
        $otherCat = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Rubrik Juri Lain',
            'competition_category_id' => null,
        ]);
        $otherSub = AssessmentSubCategory::create([
            'assessment_category_id' => $otherCat->id,
            'name' => 'Sub Lain',
        ]);
        $foreignCriteria = AssessmentCriteria::create([
            'assessment_sub_category_id' => $otherSub->id,
            'name' => 'Kriteria Asing',
            'score_options' => [['score' => 99]],
        ]);
        $otherJudge->assessmentCategories()->attach($otherCat->id);

        Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judge->access_token])
            ->call('selectCategory', $this->category->id)
            ->call('selectParticipant', $this->registration->id)
            ->call('setScore', $foreignCriteria->id, 99)
            ->assertStatus(403);
    }

    public function test_finalisasi_ditolak_bila_masih_ada_kriteria_kosong()
    {
        $this->makeRubric(2);

        Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judge->access_token])
            ->call('selectCategory', $this->category->id)
            ->call('selectParticipant', $this->registration->id)
            ->call('finalize')
            ->assertSet('isFinalized', false)
            ->assertSet('saveStatus', 'error');
    }

    public function test_finalisasi_mengunci_nilai_dan_menolak_ubah_berikutnya()
    {
        $criteria = $this->makeRubric(2);

        $component = Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judge->access_token])
            ->call('selectCategory', $this->category->id)
            ->call('selectParticipant', $this->registration->id)
            ->call('setScore', $criteria[0]->id, 10)
            ->call('setScore', $criteria[1]->id, 20)
            ->call('finalize')
            ->assertSet('isFinalized', true);

        $this->assertSame(2, AssessmentScore::where('registration_id', $this->registration->id)
            ->where('judge_id', $this->judge->id)
            ->where('is_finalized', true)
            ->count());

        // Percobaan ubah setelah terkunci diabaikan
        $component->call('setScore', $criteria[0]->id, 20);

        $this->assertSame('10', (string) AssessmentScore::where('registration_id', $this->registration->id)
            ->where('assessment_criteria_id', $criteria[0]->id)
            ->where('judge_id', $this->judge->id)
            ->value('score'));
    }

    public function test_juri_lain_tidak_terpengaruh_finalisasi_juri_ini()
    {
        $criteria = $this->makeRubric(1);

        $otherJudge = Judge::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Juri Kedua',
        ]);

        AssessmentScore::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->registration->id,
            'assessment_criteria_id' => $criteria[0]->id,
            'judge_id' => $otherJudge->id,
            'score' => 10,
        ]);

        Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judge->access_token])
            ->call('selectCategory', $this->category->id)
            ->call('selectParticipant', $this->registration->id)
            ->call('setScore', $criteria[0]->id, 20)
            ->call('finalize');

        $this->assertSame('10', (string) AssessmentScore::where('judge_id', $otherJudge->id)->value('score'));
        $this->assertFalse((bool) AssessmentScore::where('judge_id', $otherJudge->id)->value('is_finalized'));
    }

    public function test_regenerate_token_mencabut_akses_lama()
    {
        $this->makeRubric();
        $oldToken = $this->judge->access_token;

        $user = User::where('eventner_id', $this->eventner->id)->first()
            ?? User::factory()->eventner()->create(['is_active' => true]);

        Livewire::actingAs($this->eventner->user ?? $user)
            ->test(\App\Livewire\Eventner\Judge\Index::class)
            ->call('regenerateAccessToken', $this->judge->id);

        $newToken = $this->judge->fresh()->access_token;

        $this->assertNotSame($oldToken, $newToken);
        $this->tablet('/juri/' . $oldToken)->assertNotFound();
        $this->tablet('/juri/' . $newToken)->assertOk();
    }

    public function test_qr_dan_link_panitia_menunjuk_ke_host_entry()
    {
        $user = $this->eventner->user;

        Livewire::actingAs($user)
            ->test(\App\Livewire\Eventner\Judge\Index::class)
            ->call('openTabletModal', $this->judge->id)
            ->assertSee('http://entry.berbaris.test/juri/' . $this->judge->access_token);
    }

    /**
     * QR di modal panitia harus PNG. Di chillerlan/php-qrcode v6 propertinya
     * bernama outputInterface; kunci 'outputType' pada array QROptions
     * diabaikan diam-diam sehingga QR keluar SVG.
     */
    public function test_qr_modal_panitia_dirender_sebagai_png()
    {
        Livewire::actingAs($this->eventner->user)
            ->test(\App\Livewire\Eventner\Judge\Index::class)
            ->call('openTabletModal', $this->judge->id)
            ->assertSee('data:image/png;base64,', false)
            ->assertDontSee('data:image/svg+xml', false);
    }
}
