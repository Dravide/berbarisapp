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

    /**
     * Sekolah di luar jajaran juara tetap dapat sertifikat — gelarnya PESERTA,
     * bukan 404.
     */
    public function test_magic_link_certificate_for_non_winner_school_is_peserta()
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

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * Peringkat & skor tidak dikosongkan sembarangan: sertifikat PESERTA tidak
     * menampilkan angka peringkat/skor sama sekali.
     */
    public function test_peserta_certificate_has_no_rank_or_score()
    {
        [$user, $eventner, $template, $championCat, $compCat] = $this->setupCertificateAssets();

        $reg = Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $compCat->id,
            'nama_sekolah' => 'Sekolah Bukan Juara',
        ]);

        $pages = app(\App\Services\ChampionCalculator::class)->participantPages($reg);

        $this->assertCount(1, $pages);
        $this->assertSame('PESERTA', $pages[0]['title']);
        $this->assertNull($pages[0]['rank']);
        $this->assertNull($pages[0]['total']);

        $this->assertSame('PESERTA', $reg->resolveCertificateField('gelar_juara', [
            'winner' => $pages[0],
            'championCategory' => $championCat,
        ]));
        $this->assertSame('', $reg->resolveCertificateField('peringkat', ['winner' => $pages[0]]));
    }

    /**
     * Halaman editor sertifikat: sekolah non-juara di-preview sebagai PESERTA
     * (bukan pesan error "Belum ada juara dari sekolah terpilih").
     */
    public function test_editor_preview_falls_back_to_peserta_for_non_winner_school()
    {
        [$user, $eventner, $template, $championCat, $compCat] = $this->setupCertificateAssets();

        $reg = Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $compCat->id,
            'nama_sekolah' => 'Sekolah Bukan Juara',
        ]);

        // Field gelar juara di canvas — mail-merge harus menghasilkan "PESERTA".
        CertificateTextField::create([
            'certificate_template_id' => $template->id,
            'field_key' => 'gelar_juara',
            'label' => 'Gelar Juara',
            'x' => 148.5,
            'y' => 60,
            'font_size' => 24,
            'font_color' => '#000000',
            'text_align' => 'center',
            'font_weight' => 'bold',
        ]);

        $this->actingAs($user);

        \Livewire\Livewire::test(\App\Livewire\Eventner\Certificate\Editor::class, ['template' => $template->id])
            ->set('showPreview', true)
            ->set('previewChampionCategoryId', $championCat->id)
            ->set('previewCompetitionCategoryId', $compCat->id)
            ->set('previewSchool', $reg->npsn ?: mb_strtolower(trim($reg->nama_sekolah)))
            ->assertSet('showPreview', true)
            ->assertSee('Sekolah Bukan Juara')
            ->assertSee('PESERTA');
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

        // Kategori lomba pasukan kalah → tidak ada juara di mata lomba itu,
        // jadi sertifikatnya terbit sebagai PESERTA (bukan 404).
        $this->get(route('magic.link.certificate.category', [
            $loser->magic_token,
            $otherCat->id,
        ]))->assertStatus(200)->assertHeader('Content-Type', 'application/pdf');
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
     * Kartu sertifikat tampil di SEMUA tab sekolah ini: tab juara bergelar
     * juara, tab pasukan kalah bergelar PESERTA (sertifikat keikutsertaan).
     */
    public function test_magic_link_certificate_card_on_every_tab()
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

        // Tab pasukan kalah → kartu PESERTA dengan tombol unduh.
        // Tab pasukan juara → Sertifikat Juara + keterangan juara keberapanya
        // (Sekolah Test 2 skor 82 = peringkat 1, rank title 'Juara 1').
        \Livewire\Livewire::test(\App\Livewire\Public\MagicLink\Registration::class, [
            'token' => $loser->magic_token,
        ])
            ->assertSee('Sertifikat Peserta')
            ->assertSee('PESERTA')
            ->assertSee('Unduh Sertifikat')
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

    /**
     * Tanpa template sertifikat aktif, kartu sertifikat tidak dirender sama
     * sekali (fitur belum disiapkan eventner).
     */
    public function test_magic_link_certificate_card_hidden_without_active_template()
    {
        [$user, $eventner, $template, $championCat, $compCat] = $this->setupCertificateAssets();

        $template->update(['is_active' => false]);

        $reg = Registration::where('eventner_id', $eventner->id)
            ->where('nama_sekolah', 'Sekolah Test 2')
            ->first();

        \Livewire\Livewire::test(\App\Livewire\Public\MagicLink\Registration::class, [
            'token' => $reg->magic_token,
        ])
            ->assertDontSee('Sertifikat Juara')
            ->assertDontSee('Sertifikat Peserta');
    }

    /**
     * Peringkat juara dihitung per mata lomba. Dengan pool gabungan lintas
     * mata lomba, pasukan sekolah yang sama saling menyalip: gelar juara
     * pasukan di mata lomba A tertukar dengan pasukan di mata lomba B.
     */
    public function test_champion_ranking_is_scoped_per_competition_category()
    {
        [$user, $eventner, $template, $championCat, $compCat, $criteria] = $this->setupCertificateAssets();

        // Mata lomba kedua: satu pasukan skor jauh lebih tinggi.
        $catB = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => $compCat->parent_id,
        ]);

        $topInB = Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $catB->id,
            'nama_sekolah' => 'Sekolah Test 0',
            'npsn' => Registration::where('eventner_id', $eventner->id)
                ->where('nama_sekolah', 'Sekolah Test 0')->first()->npsn,
        ]);

        $judge = Judge::create(['eventner_id' => $eventner->id, 'name' => 'Juri B']);
        AssessmentScore::create([
            'eventner_id' => $eventner->id,
            'judge_id' => $judge->id,
            'registration_id' => $topInB->id,
            'assessment_criteria_id' => $criteria->id,
            'score' => 99,
        ]);

        $calc = app(\App\Services\ChampionCalculator::class);

        // Papan gabungan: Sekolah Test 0 (99) menyalip semua di mata lomba A.
        $combined = $calc->winners($championCat->fresh())[2];
        $this->assertSame('Sekolah Test 0', $combined[0]['registration']->nama_sekolah);

        // Per mata lomba: Sekolah Test 2 (82) tetap juara 1 di mata lomba A.
        $scoped = $calc->winners($championCat->fresh(), $compCat->id)[2];
        $this->assertSame('Sekolah Test 2', $scoped[0]['registration']->nama_sekolah);
        $this->assertSame(1, $scoped[0]['rank']);

        // Kartu portal pakai peringkat per mata lomba → gelar juara benar.
        $reg = Registration::where('eventner_id', $eventner->id)
            ->where('nama_sekolah', 'Sekolah Test 2')->first();

        \Livewire\Livewire::test(\App\Livewire\Public\MagicLink\Registration::class, [
            'token' => $reg->magic_token,
        ])->assertSee('Juara Umum — Juara 1');
    }

    /**
     * Peserta tanpa nilai tidak boleh menggeser peringkat juara. Peringkat
     * dihitung SETELAH peserta berskor 0 dibuang (sebelumnya rank diambil
     * dari index array mentah, sehingga juara bergeser satu nomor).
     */
    public function test_zero_score_participants_do_not_shift_champion_ranks()
    {
        [$user, $eventner, $template, $championCat, $compCat] = $this->setupCertificateAssets();

        // Pasukan tanpa skor sama sekali, namanya urut paling awal sehingga
        // berada di indeks 0 pool peserta (orderBy nama_sekolah).
        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $compCat->id,
            'nama_sekolah' => 'AAA Tanpa Nilai',
        ]);

        [, , $winners] = app(\App\Services\ChampionCalculator::class)
            ->winners($championCat->fresh(), $compCat->id);

        // Juara 1 tetap Sekolah Test 2 (skor 82), bukan bergeser ke peringkat 2.
        $this->assertSame('Sekolah Test 2', $winners[0]['registration']->nama_sekolah);
        $this->assertSame(1, $winners[0]['rank']);
        $this->assertSame('Juara 1', $winners[0]['title']);

        // Pasukan tanpa nilai tidak masuk jajaran juara sama sekali.
        $this->assertNotContains(
            'AAA Tanpa Nilai',
            array_map(fn ($w) => $w['registration']->nama_sekolah, $winners)
        );
    }

    // ── Komentar pendukung di Rekap Jumlah Vote (portal) ────────────────

    /**
     * Komentar voter tampil di kartu Rekap Jumlah Vote, terbaru dulu, dan
     * hanya transaksi PAID yang berkomentar.
     */
    public function test_vote_recap_shows_voter_comments()
    {
        [$user, $eventner, $template, $championCat, $compCat] = $this->setupCertificateAssets();

        $eventner->update(['vote_active' => true]);

        $reg = Registration::where('eventner_id', $eventner->id)
            ->where('nama_sekolah', 'Sekolah Test 2')->first();

        // Kartu rekap nilai & vote hanya dirender untuk berkas Terverifikasi.
        $reg->update(['status_berkas' => 'Terverifikasi']);

        $seq = new \stdClass;
        $seq->n = 0;
        $make = function (array $attrs) use ($eventner, $reg, $seq) {
            $n = ++$seq->n;

            return \App\Models\VoteTransaction::create(array_merge([
                'eventner_id' => $eventner->id,
                'registration_id' => $reg->id,
                'autogopay_transaction_id' => 'TRX-' . $n,
                'qr_url' => 'https://example.test/qr/' . $n,
                'amount' => 10000,
                'voter_email' => 'voter' . $n . '@example.test',
                'votes_earned' => 10,
                'status' => 'PAID',
                'paid_at' => now(),
            ], $attrs));
        };

        $make(['voter_name' => 'Budi', 'comment' => 'Semangat Rukibra!', 'paid_at' => now()->subMinutes(5)]);
        $make(['voter_name' => 'Sari', 'comment' => 'Keren sekali!', 'paid_at' => now()]);
        // Tidak berkomentar → tidak tampil.
        $make(['voter_name' => 'Tanpa Komentar', 'comment' => null]);
        // Belum bayar → tidak tampil walau ada komentar.
        $make(['voter_name' => 'Pending', 'comment' => 'Belum bayar', 'status' => 'PENDING']);

        \Livewire\Livewire::test(\App\Livewire\Public\MagicLink\Registration::class, [
            'token' => $reg->magic_token,
        ])
            ->assertSee('Rekap Jumlah Vote')
            ->assertSee('Komentar Pendukung')
            ->assertSee('Semangat Rukibra!')
            ->assertSee('Keren sekali!')
            ->assertSee('Budi')
            ->assertSee('Sari')
            ->assertDontSee('Tanpa Komentar')
            ->assertDontSee('Belum bayar');
    }
}
