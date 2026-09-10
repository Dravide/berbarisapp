<?php

namespace Tests\Feature;

use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\CertificateTemplate;
use App\Models\CertificateTextField;
use App\Models\ChampionCategory;
use App\Models\ChampionRankTitle;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure storage link exists for testing
        if (!is_dir(public_path('storage'))) {
            \Illuminate\Support\Facades\Artisan::call('storage:link');
        }
    }

    public function test_certificate_page_requires_auth()
    {
        $response = $this->get(route('eventner.certificate.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_certificate_download_requires_params()
    {
        $user = User::factory()->create(['role' => 'Eventner']);
        $eventner = Eventner::factory()->create(['user_id' => $user->id, 'status' => 'approved', 'plan' => 'paid']);
        $this->actingAs($user);

        $response = $this->get(route('eventner.certificate.pdf'));
        $response->assertStatus(422);
    }

    /**
     * Setup lengkap: template + field, struktur penilaian, kategori juara,
     * 3 pasukan dengan skor. Return [$user, $eventner, $template, $championCat, $compCat, $criteria].
     */
    private function setupCertificateAssets(): array
    {
        $user = User::factory()->create(['role' => 'Eventner']);
        $eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'plan' => 'paid',
            'tanggal' => '2026-08-15',
        ]);

        // Create assets
        $template = CertificateTemplate::factory()->create([
            'eventner_id' => $eventner->id,
            'width' => 297,
            'height' => 210,
        ]);

        // Add a text field
        CertificateTextField::create([
            'certificate_template_id' => $template->id,
            'field_key' => 'nama_sekolah',
            'label' => 'Nama Sekolah',
            'x' => 148.5,
            'y' => 80,
            'font_size' => 24,
            'font_color' => '#000000',
            'text_align' => 'center',
            'font_weight' => 'bold',
        ]);

        $parentCat = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id, 'parent_id' => null]);
        $compCat = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id, 'parent_id' => $parentCat->id]);

        // Create assessment structure
        $assessmentCat = AssessmentCategory::create(['eventner_id' => $eventner->id, 'name' => 'Penilaian']);
        $subCat = AssessmentSubCategory::create(['assessment_category_id' => $assessmentCat->id, 'name' => 'Sub A']);
        $criteria = AssessmentCriteria::create([
            'assessment_sub_category_id' => $subCat->id,
            'name' => 'Kriteria A',
            'score_options' => json_encode([['label' => 'Buruk', 'value' => 10], ['label' => 'Baik', 'value' => 100]]),
            'weight' => 1,
        ]);

        $championCat = ChampionCategory::create([
            'eventner_id' => $eventner->id,
            'name' => 'Juara Umum',
            'quantity' => 3,
        ]);
        $championCat->assessmentSubCategories()->sync([$subCat->id]);

        ChampionRankTitle::create([
            'champion_category_id' => $championCat->id,
            'title' => 'Juara 1',
            'rank_start' => 1,
            'rank_end' => 1,
            'sort_order' => 1,
        ]);

        // Create registrations with scores
        for ($i = 0; $i < 3; $i++) {
            $reg = Registration::factory()->create([
                'eventner_id' => $eventner->id,
                'competition_category_id' => $compCat->id,
                'nama_sekolah' => "Sekolah Test {$i}",
            ]);

            $judge = Judge::create(['eventner_id' => $eventner->id, 'name' => "Juri {$i}"]);

            AssessmentScore::create([
                'eventner_id' => $eventner->id,
                'judge_id' => $judge->id,
                'registration_id' => $reg->id,
                'assessment_criteria_id' => $criteria->id,
                'score' => 80 + $i,
            ]);
        }

        return [$user, $eventner, $template, $championCat, $compCat, $criteria];
    }

    public function test_certificate_download_returns_pdf()
    {
        [$user, $eventner, $template, $championCat, $compCat] = $this->setupCertificateAssets();

        $this->actingAs($user);

        $response = $this->get(route('eventner.certificate.pdf', [
            'template_id' => $template->id,
            'champion_category_id' => $championCat->id,
            'competition_category_id' => $compCat->id,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_certificate_school_filter_returns_pdf()
    {
        [$user, $eventner, $template, $championCat, $compCat] = $this->setupCertificateAssets();

        $this->actingAs($user);

        // Sekolah Test 2 = peringkat 2 (skor 81) — filter by nama sekolah
        // (registrasi helper pakai npsn unik, jadi key-nya npsn).
        $reg = Registration::where('eventner_id', $eventner->id)
            ->where('nama_sekolah', 'Sekolah Test 2')
            ->first();

        $response = $this->get(route('eventner.certificate.pdf', [
            'template_id' => $template->id,
            'champion_category_id' => $championCat->id,
            'competition_category_id' => $compCat->id,
            'school' => $reg->npsn,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_certificate_school_filter_unknown_school_404()
    {
        [$user, $eventner, $template, $championCat, $compCat] = $this->setupCertificateAssets();

        $this->actingAs($user);

        $response = $this->get(route('eventner.certificate.pdf', [
            'template_id' => $template->id,
            'champion_category_id' => $championCat->id,
            'competition_category_id' => $compCat->id,
            'school' => '99999999',
        ]));

        $response->assertStatus(404);
    }

    // ── Unduh via magic link peserta (/reg/{token}/certificate) ────────

    public function test_magic_link_certificate_returns_pdf_for_winner_school()
    {
        [$user, $eventner, $template, $championCat, $compCat] = $this->setupCertificateAssets();

        // Sekolah Test 2 = peringkat 2 (skor 81) — milik pasukan pemenang
        $reg = Registration::where('eventner_id', $eventner->id)
            ->where('nama_sekolah', 'Sekolah Test 2')
            ->first();

        $response = $this->get(route('magic.link.certificate', $reg->magic_token));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_magic_link_certificate_404_for_non_winner_school()
    {
        [$user, $eventner, $template, $championCat, $compCat] = $this->setupCertificateAssets();

        // Sekolah di luar jajaran juara (quantity 3, hanya 3 pasukan → semua
        // juara). Buat pasukan baru dengan skor 0 agar tidak masuk juara.
        $reg = Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $compCat->id,
            'nama_sekolah' => 'Sekolah Bukan Juara',
        ]);

        $response = $this->get(route('magic.link.certificate', $reg->magic_token));

        $response->assertStatus(404);
    }

    public function test_magic_link_certificate_forbidden_when_no_active_template()
    {
        [$user, $eventner, $template, $championCat, $compCat] = $this->setupCertificateAssets();

        $template->update(['is_active' => false]);

        $reg = Registration::where('eventner_id', $eventner->id)
            ->where('nama_sekolah', 'Sekolah Test 2')
            ->first();

        $response = $this->get(route('magic.link.certificate', $reg->magic_token));

        $response->assertStatus(404);
    }

    // ── Unduh per mata lomba (/reg/{token}/{competitionCategory}/certificate) ──

    /**
     * Sekolah 2 pasukan: pasukan kalah tetap bisa unduh sertifikat pasukan
     * yang menang (pasukan lain) via kategori lomba pemenang.
     */
    public function test_magic_link_certificate_by_category_for_sibling_winner()
    {
        [$user, $eventner, $template, $championCat, $compCat] = $this->setupCertificateAssets();

        $winner = Registration::where('eventner_id', $eventner->id)
            ->where('nama_sekolah', 'Sekolah Test 2')
            ->first();

        $otherCat = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => $compCat->parent_id,
        ]);

        // Pasukan kedua sekolah yang sama, mata lomba lain, tanpa skor → kalah.
        $loser = Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $otherCat->id,
            'nama_sekolah' => 'Sekolah Test 2 (Regu B)',
            'npsn' => $winner->npsn,
        ]);

        // Token pasukan kalah + kategori lomba pasukan menang → PDF.
        $response = $this->get(route('magic.link.certificate.category', [
            $loser->magic_token,
            $winner->competition_category_id,
        ]));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');

        // Kategori lomba pasukan kalah → jajaran juara event ini tetap sama
        // (ChampionCalculator lintas mata lomba), jadi sertifikat pasukan
        // sekolah yang menang tetap terbit — bukan 404.
        $this->get(route('magic.link.certificate.category', [
            $loser->magic_token,
            $otherCat->id,
        ]))->assertStatus(200);
    }

    public function test_magic_link_certificate_by_category_404_for_other_event()
    {
        [$user, $eventner, $template, $championCat, $compCat] = $this->setupCertificateAssets();

        $reg = Registration::where('eventner_id', $eventner->id)->first();

        $otherEvent = Eventner::factory()->create([
            'user_id' => User::factory()->create(['role' => 'Eventner'])->id,
            'status' => 'approved',
            'plan' => 'paid',
        ]);
        $foreignCat = CompetitionCategory::factory()->create(['eventner_id' => $otherEvent->id]);

        $this->get(route('magic.link.certificate.category', [$reg->magic_token, $foreignCat->id]))
            ->assertStatus(404);
    }

    /**
     * Kartu "Sertifikat Juara" hanya dirender di tab pasukan yang menang —
     * tab pasukan yang kalah tidak menampilkannya.
     */
    public function test_magic_link_certificate_card_only_on_winning_tab()
    {
        [$user, $eventner, $template, $championCat, $compCat] = $this->setupCertificateAssets();

        $winner = Registration::where('eventner_id', $eventner->id)
            ->where('nama_sekolah', 'Sekolah Test 2')
            ->first();

        $otherCat = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => $compCat->parent_id,
        ]);

        $loser = Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $otherCat->id,
            'nama_sekolah' => 'Sekolah Test 2 (Regu B)',
            'npsn' => $winner->npsn,
        ]);

        // Tab pasukan kalah → kartu tidak tampil.
        // Tab pasukan juara → kartu + keterangan juara keberapanya
        // (Sekolah Test 2 skor 82 = peringkat 1, rank title 'Juara 1').
        \Livewire\Livewire::test(\App\Livewire\Public\MagicLink\Registration::class, [
            'token' => $loser->magic_token,
        ])
            ->assertDontSee('Sertifikat Juara')
            ->call('switchRegistration', $winner->id)
            ->assertSee('Sertifikat Juara')
            ->assertSee('Juara Umum — Juara 1');

        // Rank title yang meng-cover 1–3 → gelar diberi nomor posisi dalam grup.
        ChampionRankTitle::where('champion_category_id', $championCat->id)
            ->update(['title' => 'Juara Harapan', 'rank_start' => 1, 'rank_end' => 3]);

        \Livewire\Livewire::test(\App\Livewire\Public\MagicLink\Registration::class, [
            'token' => $winner->magic_token,
        ])->assertSee('Juara Umum — Juara Harapan 1');
    }
}
