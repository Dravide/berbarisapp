<?php

namespace Tests\Feature;

use App\Livewire\Eventner\ChampionCategory\Index;
use App\Livewire\Eventner\FormatNilai\Builder;
use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentSubCategory;
use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Checklist rubrik juara sampai level Kriteria Penilaian.
 */
class ChampionCriteriaChecklistTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Eventner, 2: AssessmentSubCategory, 3: AssessmentCriteria, 4: AssessmentCriteria}
     */
    private function makeRubrik(): array
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        $eventner = Eventner::factory()->create(['user_id' => $user->id, 'status' => 'approved']);
        $level = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id]);

        $ac = AssessmentCategory::create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $level->id,
            'name' => 'PBB',
            'sort_order' => 1,
        ]);
        $sub = AssessmentSubCategory::create(['assessment_category_id' => $ac->id, 'name' => 'Gerakan Ditempat', 'sort_order' => 1]);

        $sikap = AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Sikap',
            'score_options' => [['score' => 10]],
            'weight' => 3,
            'sort_order' => 1,
        ]);
        $ketepatan = AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Ketepatan',
            'score_options' => [['score' => 10]],
            'weight' => 2,
            'sort_order' => 2,
        ]);

        return [$user, $eventner, $sub, $sikap, $ketepatan];
    }

    private function makeChampion(Eventner $eventner, string $name = 'Juara Umum'): ChampionCategory
    {
        return ChampionCategory::create([
            'eventner_id' => $eventner->id,
            'name' => $name,
            'quantity' => 3,
        ]);
    }

    private function checkedIn(string $html, string $inputId): bool
    {
        return (bool) preg_match(
            '/id="'.preg_quote($inputId, '/').'"(?:\s+checked|\s*)\s*checked/i',
            $html
        );
    }

    // ── Resolver bobot ─────────────────────────────────────────────────

    public function test_kriteria_eksplisit_menang_atas_seluruh_kriteria_sub()
    {
        [, $eventner, $sub, $sikap, $ketepatan] = $this->makeRubrik();
        $champion = $this->makeChampion($eventner);

        $champion->assessmentSubCategories()->sync([$sub->id]);
        $champion->criterias()->sync([$sikap->id]);
        $champion->load(['assessmentSubCategories.criterias', 'criterias']);

        // Hanya kriteria yang dicentang eksplisit yang dihitung.
        $this->assertSame([$sikap->id => '3.00'], $champion->scoringCriteriaWeights());
        $this->assertArrayNotHasKey($ketepatan->id, $champion->scoringCriteriaWeights());
    }

    public function test_tanpa_kriteria_eksplisit_pakai_seluruh_kriteria_sub()
    {
        [, $eventner, $sub, $sikap, $ketepatan] = $this->makeRubrik();
        $champion = $this->makeChampion($eventner);

        $champion->assessmentSubCategories()->sync([$sub->id]);
        $champion->load(['assessmentSubCategories.criterias', 'criterias']);

        // Kategori juara lama: belum ada baris pivot kriteria sama sekali.
        $this->assertEmpty($champion->criterias);
        $weights = $champion->scoringCriteriaWeights();
        $this->assertSame(['3.00', '2.00'], array_values($weights));
        $this->assertArrayHasKey($sikap->id, $weights);
        $this->assertArrayHasKey($ketepatan->id, $weights);
    }

    public function test_tiebreak_punya_resolver_terpisah()
    {
        [, $eventner, $sub, $sikap, $ketepatan] = $this->makeRubrik();
        $champion = $this->makeChampion($eventner);

        $champion->assessmentSubCategories()->sync([$sub->id]);
        $champion->criterias()->sync([$sikap->id, $ketepatan->id]);
        $champion->tiebreakSubCategories()->sync([$sub->id]);
        $champion->tiebreakCriterias()->sync([$ketepatan->id]);
        $champion->load([
            'assessmentSubCategories.criterias', 'criterias',
            'tiebreakSubCategories.criterias', 'tiebreakCriterias',
        ]);

        $this->assertSame([$ketepatan->id => '2.00'], $champion->tiebreakCriteriaWeights());
        $this->assertCount(2, $champion->scoringCriteriaWeights());
    }

    // ── Pre-check di modal ─────────────────────────────────────────────

    public function test_edit_modal_pre_check_kriteria_parsial_dan_buka_subnya()
    {
        [$user, $eventner, $sub, $sikap, $ketepatan] = $this->makeRubrik();
        $champion = $this->makeChampion($eventner);

        $champion->assessmentSubCategories()->sync([$sub->id]);
        $champion->criterias()->sync([$sikap->id]);

        $resp = Livewire::actingAs($user)->test(Index::class)->call('edit', $champion->id);
        $html = $resp->html();

        $this->assertTrue($this->checkedIn($html, 'crt_'.$sikap->id), 'kriteria terpilih harus checked');
        $this->assertFalse($this->checkedIn($html, 'crt_'.$ketepatan->id), 'kriteria lain tidak boleh checked');
        // Sub centang parsial harus terbuka, kalau tidak centangnya tersembunyi.
        $this->assertSame([(string) $sub->id], $resp->get('expandedSubIds'));
        $this->assertStringContainsString('Sikap', $html);
        $this->assertStringContainsString('Ketepatan', $html);
    }

    public function test_edit_modal_kategori_juara_lama_mencentang_seluruh_kriteria_sub()
    {
        [$user, $eventner, $sub, $sikap, $ketepatan] = $this->makeRubrik();
        $champion = $this->makeChampion($eventner);

        // Data sebelum fitur ini: hanya pivot sub-kategori yang terisi.
        $champion->assessmentSubCategories()->sync([$sub->id]);

        $resp = Livewire::actingAs($user)->test(Index::class)->call('edit', $champion->id);
        $html = $resp->html();

        $this->assertTrue($this->checkedIn($html, 'asc_'.$sub->id));
        // Semua tercentang = tidak parsial, jadi sub-nya tetap terlipat; daftar
        // kriteria baru dirender setelah dibuka.
        $this->assertSame([], $resp->get('expandedSubIds'));
        $this->assertEqualsCanonicalizing(
            [(string) $sikap->id, (string) $ketepatan->id],
            $resp->get('selectedCriteria')
        );

        $resp->call('toggleSubExpand', $sub->id);
        $html = $resp->html();
        $this->assertTrue($this->checkedIn($html, 'crt_'.$sikap->id));
        $this->assertTrue($this->checkedIn($html, 'crt_'.$ketepatan->id));
    }

    public function test_rubrik_nama_sama_dipisah_judul_seri_dan_babak()
    {
        [$user, $eventner, $sub, , ] = $this->makeRubrik();
        $level = CompetitionCategory::find($sub->category->competition_category_id);

        // Tiga rubrik bernama sama pada satu tingkat — sah sejak seri & babak
        // ada. Sebelum dipisah, ketiganya duduk di satu daftar rata, dan itu
        // berbahaya: rubrik Seri A tampak sederajat dengan rubrik babak Final,
        // padahal himpunan pesertanya berbeda.
        $seriA = \App\Models\CompetitionSeries::create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $level->id,
            'name' => 'Seri A',
            'sort_order' => 1,
        ]);
        $seriB = \App\Models\CompetitionSeries::create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $level->id,
            'name' => 'Seri B',
            'sort_order' => 2,
        ]);
        $final = \App\Models\CompetitionRound::create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $level->id,
            'name' => 'Final Stage',
            'type' => \App\Models\CompetitionRound::TYPE_FINAL,
            'sort_order' => 1,
        ]);

        $ids = [];
        foreach ([['Seri A', $seriA->id, null], ['Seri B', $seriB->id, null], ['Final', null, $final->id]] as [$label, $seriesId, $roundId]) {
            $ids[$label] = AssessmentCategory::create([
                'eventner_id' => $eventner->id,
                'competition_category_id' => $level->id,
                'competition_series_id' => $seriesId,
                'competition_round_id' => $roundId,
                'name' => 'PBB',
                'sort_order' => 1,
            ])->id;
        }

        $component = Livewire::actingAs($user)
            ->test(Index::class)
            ->set('selectedCompetitionCategoryId', (string) $level->id)
            ->call('create');

        $groups = $component->viewData('rubrikByLevel')
            ->firstWhere('id', (string) $level->id)['sections'];

        // Seri dan babak jadi judul bagian tersendiri, bukan satu daftar rata.
        // Rubrik polos dari fixture (tanpa seri & babak) menyusul di akhir tanpa
        // judul — rubrik spesifik dulu, rubrik umum terakhir.
        $this->assertSame(
            ['Seri A', 'Seri B', 'Babak Final Stage', ''],
            $groups->pluck('section_name')->all()
        );

        $final = $groups->firstWhere('section_name', 'Babak Final Stage');
        $this->assertTrue($final['is_final']);
        $this->assertSame([$ids['Final']], $final['categories']->pluck('id')->all());

        // Tiap bagian disaring ke rubriknya sendiri — inti kekhawatirannya:
        // rubrik Seri A tidak boleh muncul di bagian babak Final.
        $this->assertSame(
            [$ids['Seri A']],
            $groups->firstWhere('section_name', 'Seri A')['categories']->pluck('id')->all()
        );
    }

    public function test_tingkat_tanpa_seri_babak_tidak_punya_judul_bagian()
    {
        [$user, $eventner] = $this->makeRubrik();

        $sections = Livewire::actingAs($user)
            ->test(Index::class)
            ->call('create')
            ->viewData('rubrikByLevel')
            ->flatMap(fn ($levelGroup) => $levelGroup['sections']);

        // Perilaku lama dipertahankan: satu bagian, tanpa judul.
        $this->assertSame([''], $sections->pluck('section_name')->unique()->values()->all());
    }

    // ── Toggle ─────────────────────────────────────────────────────────

    public function test_toggle_kriteria_menyeret_status_sub()
    {
        [$user, $eventner, $sub, $sikap, $ketepatan] = $this->makeRubrik();
        $champion = $this->makeChampion($eventner);

        $resp = Livewire::actingAs($user)->test(Index::class)
            ->call('create')
            ->call('toggleCriteria', $sikap->id);

        $resp->assertSet('selectedCriteria', [(string) $sikap->id]);
        $resp->assertSet('selectedSubCategories', [(string) $sub->id]);

        // Menambah kriteria kedua: sub tetap satu, tidak terduplikasi.
        $resp->call('toggleCriteria', $ketepatan->id);
        $resp->assertCount('selectedCriteria', 2);
        $resp->assertSet('selectedSubCategories', [(string) $sub->id]);
    }

    public function test_toggle_sub_mencentang_seluruh_kriterianya()
    {
        [$user, $eventner, $sub, $sikap, $ketepatan] = $this->makeRubrik();

        $resp = Livewire::actingAs($user)->test(Index::class)
            ->call('create')
            ->call('toggleCriteriaSub', $sub->id);

        $selected = $resp->get('selectedCriteria');
        $this->assertContains((string) $sikap->id, $selected);
        $this->assertContains((string) $ketepatan->id, $selected);

        // Klik kedua kalinya menghapus semuanya.
        $resp->call('toggleCriteriaSub', $sub->id);
        $resp->assertSet('selectedCriteria', []);
        $resp->assertSet('selectedSubCategories', []);
    }

    public function test_toggle_kriteria_tidak_bisa_menyentuh_rubrik_tenant_lain()
    {
        [$user] = $this->makeRubrik();

        // Eventner lain beserta kriterianya.
        $lain = User::factory()->eventner()->create(['is_active' => true]);
        $eventnerLain = Eventner::factory()->create(['user_id' => $lain->id, 'status' => 'approved']);
        $levelLain = CompetitionCategory::factory()->create(['eventner_id' => $eventnerLain->id]);
        $acLain = AssessmentCategory::create([
            'eventner_id' => $eventnerLain->id,
            'competition_category_id' => $levelLain->id,
            'name' => 'Rahasia',
            'sort_order' => 1,
        ]);
        $subLain = AssessmentSubCategory::create(['assessment_category_id' => $acLain->id, 'name' => 'Sub Rahasia', 'sort_order' => 1]);
        $critLain = AssessmentCriteria::create([
            'assessment_sub_category_id' => $subLain->id,
            'name' => 'Kriteria Rahasia',
            'score_options' => [['score' => 10]],
            'weight' => 1,
            'sort_order' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('create')
            ->call('toggleCriteria', $critLain->id)
            ->assertSet('selectedCriteria', []);
    }

    // ── Save ───────────────────────────────────────────────────────────

    public function test_save_menulis_pivot_kriteria_dan_menurunkan_sub()
    {
        [$user, $eventner, $sub, $sikap] = $this->makeRubrik();

        Livewire::actingAs($user)->test(Index::class)
            ->call('create')
            ->set('name', 'Juara Terbaik')
            ->set('quantity', 3)
            ->call('toggleCriteria', $sikap->id)
            ->call('save');

        $champion = ChampionCategory::where('eventner_id', $eventner->id)->firstOrFail();
        $this->assertSame([$sikap->id], $champion->criterias()->pluck('assessment_criterias.id')->all());
        // Pivot sub tetap diisi supaya isVisibleFor() bekerja.
        $this->assertSame([$sub->id], $champion->assessmentSubCategories()->pluck('assessment_sub_categories.id')->all());
    }

    // ── Guard hapus kriteria ───────────────────────────────────────────

    /**
     * Flash-nya tidak dibaca di sini: harness Livewire membuang nilai flash
     * setelah aksi (sama seperti FormatNilaiBuilderTest). Yang dibuktikan
     * adalah invariannya — kriteria tetap ada, dan pivotnya masih menempel.
     */
    public function test_hapus_kriteria_diblokir_saat_dipakai_kategori_juara()
    {
        [$user, $eventner, , $sikap] = $this->makeRubrik();
        $champion = $this->makeChampion($eventner, 'Juara Umum');
        $champion->criterias()->sync([$sikap->id]);

        Livewire::actingAs($user)
            ->test(Builder::class)
            ->call('deleteCriteria', $sikap->id);

        $this->assertNotNull(
            AssessmentCriteria::find($sikap->id),
            'Kriteria berhak tetap ada selama dicentang di kategori juara.'
        );
        $this->assertTrue(
            $champion->fresh()->criterias->contains($sikap->id),
            'Pivot kriteria kategori juara tidak boleh ikut terhapus.'
        );
    }

    /**
     * Jalur kedua: kriteria ikut terpakai karena sub-kategorinya dicentang,
     * meski tidak ada baris pivot kriteria eksplisit.
     */
    public function test_hapus_kriteria_diblokir_lewat_pivot_sub_kategori()
    {
        [$user, $eventner, $sub, $sikap] = $this->makeRubrik();
        $champion = $this->makeChampion($eventner, 'Juara Umum');
        $champion->assessmentSubCategories()->sync([$sub->id]);

        Livewire::actingAs($user)
            ->test(Builder::class)
            ->call('deleteCriteria', $sikap->id);

        $this->assertNotNull(AssessmentCriteria::find($sikap->id));
        $this->assertTrue(
            $champion->fresh()->assessmentSubCategories->contains($sub->id),
            'Pivot sub-kategori tidak boleh ikut terhapus.'
        );
    }

    public function test_hapus_kriteria_tanpa_kategori_juara_tetap_bisa()
    {
        [$user, , , $sikap] = $this->makeRubrik();

        Livewire::actingAs($user)
            ->test(Builder::class)
            ->call('deleteCriteria', $sikap->id);

        $this->assertDatabaseMissing('assessment_criterias', ['id' => $sikap->id]);
    }
}
