<?php

namespace Tests\Feature;

use App\Http\Controllers\Eventner\FormatNilaiController;
use App\Livewire\Eventner\FormatNilai\Download;
use App\Livewire\Eventner\Scoring\Index as ScoringIndex;
use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
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
use Illuminate\Http\Request;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Seri harus terbaca, bukan hanya benar.
 *
 * Panel Input Nilai sudah memuat rubrik seri peserta dengan benar, tetapi dua
 * seri boleh memakai nama kategori yang SAMA PERSIS ("PBB", "KOMANDAN PLETON")
 * dan hanya kriterianya yang berbeda ("Lari Maju A" vs "Lari Maju B"). Tanpa
 * penanda seri di layar, operator tidak punya cara tahu lembar mana yang sedang
 * terbuka — itu yang dilaporkan sebagai "seri B tapi muncul format seri A".
 *
 * Dua hal yang dijaga di sini:
 *  1. Seri benar-benar terlihat di panel (header peserta + daftar peserta).
 *  2. Lembar cetak per peserta TIDAK memuat rubrik seri lain — dulu controller
 *     unduh menyaring tingkat dan juri saja, tanpa dimensi seri.
 */
class SeriesScoringVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;

    private CompetitionCategory $level;

    private CompetitionGroup $group;

    private CompetitionSeries $seriA;

    private CompetitionSeries $seriB;

    private CompetitionRound $babak;

    private Registration $pesertaA;

    private Registration $pesertaB;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->eventner()->create(['is_active' => true]);
        $this->eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'logo_event' => null,
        ]);
        $this->actingAs($user);

        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
        ]);
        $this->level = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
            'name' => 'Kelas 9',
        ]);

        $this->babak = CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Fase Grup',
            'type' => CompetitionRound::TYPE_PRELIMINARY,
            'sort_order' => 1,
        ]);

        // Satu grup memuat KEDUA seri — justru itu inti fitur seri.
        $this->group = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Grup 1',
            'sort_order' => 1,
        ]);

        $this->seriA = CompetitionSeries::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Seri A',
            'sort_order' => 1,
        ]);
        $this->seriB = CompetitionSeries::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'name' => 'Seri B',
            'sort_order' => 2,
        ]);

        // Nama kategori SENGAJA identik; hanya kriteria yang membedakan.
        $this->rubrik('PBB', $this->seriA, 'Lari Maju A', 'JURI-ALFA');
        $this->rubrik('PBB', $this->seriB, 'Lari Maju B', 'JURI-BRAVO');

        $this->pesertaA = $this->peserta('SMA ALFA', $this->seriA);
        $this->pesertaB = $this->peserta('SMA BRAVO', $this->seriB);
    }

    private function rubrik(string $name, CompetitionSeries $seri, string $kriteria, string $juri): void
    {
        $category = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'competition_round_id' => $this->babak->id,
            'competition_series_id' => $seri->id,
            'name' => $name,
            'sort_order' => $seri->sort_order,
        ]);

        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $category->id,
            'name' => 'Sub ' . $seri->name,
            'sort_order' => 1,
        ]);

        AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => $kriteria,
            'score_options' => [['score' => 10], ['score' => 20]],
            'weight' => 1,
            'sort_order' => 1,
        ]);

        Judge::create([
            'eventner_id' => $this->eventner->id,
            'name' => $juri,
        ])->assessmentCategories()->attach($category->id);
    }

    private function peserta(string $sekolah, CompetitionSeries $seri): Registration
    {
        return Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $this->group->id,
            'competition_series_id' => $seri->id,
            'nama_sekolah' => $sekolah,
        ]);
    }

    private function panel(Registration $peserta)
    {
        return Livewire::test(ScoringIndex::class, ['selectedCategoryId' => $this->level->id])
            ->call('selectGroupScope', (string) $this->group->id)
            ->call('selectParticipant', $peserta->id);
    }

    /** Panel memuat rubrik SERI peserta, bukan seri tetangga di grup yang sama. */
    public function test_panel_hanya_memuat_kriteria_seri_peserta()
    {
        $htmlB = $this->panel($this->pesertaB)->html();

        $this->assertStringContainsString('Lari Maju B', $htmlB);
        $this->assertStringNotContainsString('Lari Maju A', $htmlB);
        $this->assertStringNotContainsString('JURI-ALFA', $htmlB);

        $htmlA = $this->panel($this->pesertaA)->html();

        $this->assertStringContainsString('Lari Maju A', $htmlA);
        $this->assertStringNotContainsString('Lari Maju B', $htmlA);
    }

    /** Nama seri terbaca di panel — tanpa ini nama kategori yang sama tak bisa dibedakan. */
    public function test_panel_menampilkan_nama_seri_peserta()
    {
        $this->panel($this->pesertaB)->assertSee('Seri B');
    }

    /** Daftar peserta juga menandai seri tiap baris. */
    public function test_daftar_peserta_menandai_seri_tiap_baris()
    {
        Livewire::test(ScoringIndex::class, ['selectedCategoryId' => $this->level->id])
            ->call('selectGroupScope', (string) $this->group->id)
            ->assertSee('Seri A')
            ->assertSee('Seri B');
    }

    /** Lembar peserta yang tak berseri hanya memuat rubrik tanpa seri. */
    public function test_peserta_tanpa_seri_tidak_melihat_rubrik_berseri()
    {
        $polos = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'competition_group_id' => $this->group->id,
            'nama_sekolah' => 'SMA POLOS',
        ]);

        $html = $this->panel($polos)->html();

        $this->assertStringNotContainsString('Lari Maju A', $html);
        $this->assertStringNotContainsString('Lari Maju B', $html);
    }

    /** Pemilih peserta di layar unduh menampilkan seri, bukan hanya tingkatnya. */
    public function test_pemilih_peserta_unduh_menampilkan_seri()
    {
        Livewire::test(Download::class)
            ->set('selectedLevelId', (string) $this->level->id)
            ->set('mode', 'peserta')
            ->assertSee('Seri B')
            ->assertSee('SMA BRAVO');
    }

    /** Layar unduh menyaring rubrik mengikuti seri peserta yang dipilih. */
    public function test_layar_unduh_menyaring_rubrik_per_seri_peserta()
    {
        $komponen = Livewire::test(Download::class)
            ->set('selectedLevelId', (string) $this->level->id)
            ->set('mode', 'peserta')
            ->set('selectedRegistrationId', (string) $this->pesertaB->id);

        $nama = collect($komponen->instance()->categories)->pluck('name')->all();

        $this->assertContains('PBB', $nama);
        $this->assertSame(1, count($nama), 'Hanya rubrik Seri B yang boleh ikut.');

        $subs = collect($komponen->instance()->categories)->flatMap->subCategories->pluck('name')->all();
        $this->assertSame(['Sub Seri B'], $subs);
    }

    /**
     * PDF per peserta: rubrik seri lain tidak boleh tercetak di lembarnya.
     *
     * Yang diuji keputusan yang diserahkan ke view (dompdf menutup viewData),
     * sama seperti ChampionPdfRoundTest.
     */
    public function test_pdf_peserta_tidak_memuat_rubrik_seri_lain()
    {
        $data = $this->unduhPdfData($this->pesertaB, 'Seri B', 'Lari Maju B');

        $this->assertSame(1, $data['categories']->count());
        $this->assertSame(
            ['Lari Maju B'],
            $data['categories']->flatMap->subCategories->flatMap->criterias->pluck('name')->all()
        );

        // Arah sebaliknya juga: peserta Seri A tidak melihat kriteria Seri B.
        $dataA = $this->unduhPdfData($this->pesertaA, 'Seri A', 'Lari Maju A');
        $this->assertSame(
            ['Lari Maju A'],
            $dataA['categories']->flatMap->subCategories->flatMap->criterias->pluck('name')->all()
        );
    }

    /**
     * Keputusan rubrik lembar cetak, diambil dari jalur yang sama dengan
     * controller — bukan query yang ditulis ulang di tes.
     *
     * @return array{categories: \Illuminate\Support\Collection<int, AssessmentCategory>}
     */
    private function unduhPdfData(Registration $peserta, string $seri, string $kriteria): array
    {
        $request = Request::create('/eventner/format-nilai/unduh/pdf');
        $request->query->set('mode', 'peserta');
        $request->query->set('level_id', $this->level->id);
        $request->query->set('registration_id', $peserta->id);

        $controller = app(FormatNilaiController::class);

        $categories = $controller->rubricCategoriesFor(
            $this->eventner,
            $this->level->id,
            null,
            Registration::with('competitionSeries')->findOrFail($peserta->id),
        );

        // Rutenya sendiri tetap menghasilkan PDF.
        $this->get(route('eventner.format-nilai.download-pdf', [
            'mode' => 'peserta',
            'level_id' => $this->level->id,
            'registration_id' => $peserta->id,
        ]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertNotNull($request);
        $this->assertTrue($categories->isNotEmpty(), "Rubrik $seri harus ikut.");
        $this->assertTrue(
            $categories->flatMap->subCategories->flatMap->criterias->pluck('name')->contains($kriteria),
            "Kriteria $kriteria milik $seri harus ada."
        );

        return ['categories' => $categories];
    }
}
