<?php

namespace Tests\Feature;

use App\Livewire\Eventner\FormatNilai\Builder;
use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentSubCategory;
use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\DeductionCategory;
use App\Models\DeductionCriteria;
use App\Models\Eventner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Temuan audit #30, #32 — opsi skor dan penghapusan kategori rubrik.
 *
 * #30: preview badge di blade memisah pada entitas HTML '&ndash;' sementara
 *      Builder memisah pada karakter ' – ' asli, jadi preset "0 – 25" tampil
 *      satu badge tapi tersimpan dua nilai.
 * #32: deleteCategory() tidak menjaga champion_assessment (cascade mencabut
 *      rubrik dari kategori juara diam-diam) dan meninggalkan
 *      deduction_categories yatim (nullOnDelete).
 */
class FormatNilaiBuilderTest extends TestCase
{
    use RefreshDatabase;

    private function buatEventner(): Eventner
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);

        return Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'plan' => 'free',
            'trial_ends_at' => now()->addYear(),
        ]);
    }

    private function tingkat(Eventner $eventner): CompetitionCategory
    {
        $parent = CompetitionCategory::factory()->for($eventner, 'eventner')->create();

        return CompetitionCategory::factory()->child($parent)
            ->for($eventner, 'eventner')
            ->create(['name' => 'PBB Beregu']);
    }

    private function subKategori(Eventner $eventner, CompetitionCategory $tingkat, string $nama): AssessmentSubCategory
    {
        $format = AssessmentCategory::create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $tingkat->id,
            'name' => 'Format ' . $nama,
            'sort_order' => 1,
        ]);

        return AssessmentSubCategory::create([
            'assessment_category_id' => $format->id,
            'name' => $nama,
            'sort_order' => 1,
        ]);
    }

    /**
     * #30 — rentang "0 – 25" adalah SATU nilai; preview dan hasil simpan
     * harus menampilkan hal yang sama.
     */
    public function test_rentang_skor_tersimpan_sebagai_satu_nilai()
    {
        $eventner = $this->buatEventner();
        $tingkat = $this->tingkat($eventner);
        $sub = $this->subKategori($eventner, $tingkat, 'Ketepatan');

        $this->actingAs($eventner->user);

        $komponen = Livewire::test(Builder::class)
            ->call('openCriteriaModal', $sub->id)
            ->call('fillLabelPreset');

        // Preview yang dilihat operator: empat badge, bukan delapan.
        $this->assertSame(
            ['0 – 25 (Kurang)', '26 – 50 (Cukup)', '51 – 75 (Baik)', '76 – 100 (Sangat Baik)'],
            $komponen->get('scoreOptionPreview'),
            'Preview badge tidak cocok dengan preset.'
        );

        $komponen->set('criteriaModalName', 'Ketepatan')->call('saveCriteriaModal');

        $kriteria = AssessmentCriteria::where('assessment_sub_category_id', $sub->id)->firstOrFail();

        $this->assertCount(4, $kriteria->score_options, 'Rentang tidak boleh terpecah jadi dua nilai.');
        $this->assertSame('0 – 25', $kriteria->score_options[0]['score']);
        $this->assertSame('Kurang', $kriteria->score_options[0]['label']);
    }

    /** Pemisah koma tetap memisah banyak nilai dalam satu label. */
    public function test_koma_tetap_memisah_banyak_nilai()
    {
        $eventner = $this->buatEventner();
        $tingkat = $this->tingkat($eventner);
        $sub = $this->subKategori($eventner, $tingkat, 'Ketepatan');

        $this->actingAs($eventner->user);

        Livewire::test(Builder::class)
            ->call('openCriteriaModal', $sub->id)
            ->set('labelGroups', [['label' => 'Baik', 'scores' => '70, 80; 90']])
            ->set('criteriaModalName', 'Ketepatan')
            ->call('saveCriteriaModal');

        $kriteria = AssessmentCriteria::where('assessment_sub_category_id', $sub->id)->firstOrFail();

        $this->assertSame(['70', '80', '90'], $kriteria->getScoreOptionsFlatAttribute());
    }

    /**
     * #32 — kategori rubrik yang dipakai kategori juara tidak boleh dihapus
     * diam-diam, karena champion_assessment cascade.
     */
    public function test_kategori_rubrik_yang_dipakai_juara_tidak_bisa_dihapus()
    {
        $eventner = $this->buatEventner();
        $tingkat = $this->tingkat($eventner);
        $sub = $this->subKategori($eventner, $tingkat, 'Ketepatan');

        $juara = ChampionCategory::create([
            'eventner_id' => $eventner->id,
            'name' => 'Juara Umum',
            'quantity' => 3,
            'sort_order' => 1,
        ]);
        $juara->assessmentSubCategories()->attach($sub->id);

        $this->actingAs($eventner->user);

        Livewire::test(Builder::class)->call('deleteCategory', $sub->assessment_category_id);

        // Flash-nya tidak dibaca di sini: harness Livewire membuang nilai
        // flash setelah aksi. Yang dibuktikan adalah invariannya — kategori
        // tetap ada, dan rubriknya masih menempel di kategori juara.
        $this->assertNotNull(
            AssessmentCategory::find($sub->assessment_category_id),
            'Kategori berhak tetap ada selama rubriknya dipakai kategori juara.'
        );
        $this->assertTrue(
            $juara->fresh()->assessmentSubCategories->contains($sub->id),
            'Pivot rubrik kategori juara tidak boleh ikut terhapus.'
        );
    }

    /**
     * #32 — kategori pengurangan menunjuk assessment_categories dengan
     * nullOnDelete, jadi ia harus ikut terhapus, bukan jadi yatim.
     */
    public function test_kategori_pengurangan_ikut_terhapus_bersama_kategori_rubrik()
    {
        $eventner = $this->buatEventner();
        $tingkat = $this->tingkat($eventner);
        $sub = $this->subKategori($eventner, $tingkat, 'Ketepatan');

        $pengurangan = DeductionCategory::create([
            'eventner_id' => $eventner->id,
            'assessment_category_id' => $sub->assessment_category_id,
            'name' => 'Pelanggaran',
            'sort_order' => 1,
        ]);
        DeductionCriteria::create([
            'deduction_category_id' => $pengurangan->id,
            'name' => 'Terlambat',
            'deduction_options' => [5],
            'sort_order' => 1,
        ]);

        $this->actingAs($eventner->user);

        Livewire::test(Builder::class)->call('deleteCategory', $sub->assessment_category_id);

        $this->assertNull(
            DeductionCategory::find($pengurangan->id),
            'Kategori pengurangan tidak boleh jadi yatim tanpa format nilai.'
        );
    }
}
