<?php

namespace Tests\Feature;

use App\Livewire\Eventner\FormatNilai\Builder;
use App\Livewire\Eventner\FormatNilai\Import;
use App\Models\AssessmentCategory;
use App\Models\CompetitionCategory;
use App\Models\DeductionCategory;
use App\Models\Eventner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Rubrik penilaian tidak boleh ditempelkan ke kategori induk ber-anak.
 *
 * competitions_category_id pada assessment_categories/deduction_categories
 * ditulis dari nilai yang berasal dari klien (activeTab, target salin, target
 * import). Pemeriksaan lama hanya memastikan kepemilikan eventner — id induk
 * ber-anak lolos. Barisnya tersimpan, tapi halaman penilaian hanya memilih
 * tingkat dari daftar yang menyaring induk ber-anak, jadi rubrik itu tidak
 * pernah tampil di tab mana pun: data hilang tanpa pesan.
 */
class FormatNilaiCategoryScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Eventner $eventner;
    private CompetitionCategory $induk;
    private CompetitionCategory $anak;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->eventner()->create(['is_active' => true]);

        $this->eventner = Eventner::factory()->create([
            'user_id' => $this->user->id,
            'slug' => 'format-nilai-scope',
            'status' => 'approved',
        ]);
        $this->user->update(['eventner_id' => $this->eventner->id]);

        $this->induk = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
            'name' => 'PBB Putra',
        ]);
        $this->anak = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->induk->id,
            'name' => 'Regu Inti',
        ]);
    }

    private function rubrik(?CompetitionCategory $kategori = null): AssessmentCategory
    {
        return AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $kategori?->id,
            'name' => 'Rubrik Uji',
            'sort_order' => AssessmentCategory::where('eventner_id', $this->eventner->id)->max('sort_order') + 1,
        ]);
    }

    // ---- Salin rubrik antar tingkat (controller) --------------------------

    /** POST langsung dengan id induk ber-anak ditolak; tidak ada rubrik baru. */
    public function test_salin_menolak_induk_beranak()
    {
        $sumber = $this->rubrik($this->anak);

        $this->actingAs($this->user)
            ->post(route('eventner.format-nilai.copy-execute', $sumber->id), [
                'target_competition_category_id' => $this->induk->id,
            ])
            ->assertSessionHasErrors('target_competition_category_id');

        $this->assertSame(
            0,
            AssessmentCategory::where('competition_category_id', $this->induk->id)->count(),
            'Rubrik tidak boleh menempel di induk ber-anak.'
        );
    }

    /** Daftar tujuan salin juga tidak menawarkan induk ber-anak. */
    public function test_form_salin_tidak_menawarkan_induk_beranak()
    {
        $sumber = $this->rubrik($this->anak);

        $this->actingAs($this->user)
            ->get(route('eventner.format-nilai.copy-form', $sumber->id))
            ->assertOk()
            ->assertViewHas('targets', function ($targets) {
                $ids = collect($targets)->pluck('id')->all();

                return ! in_array($this->induk->id, $ids, true);
            });
    }

    /** Tingkat lomba tetap bisa jadi tujuan salin. */
    public function test_salin_menerima_tingkat_lomba()
    {
        $sumber = $this->rubrik($this->anak);
        $lain = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $this->induk->id,
            'name' => 'Regu Cadangan',
        ]);

        $this->actingAs($this->user)->post(
            route('eventner.format-nilai.copy-execute', $sumber->id),
            ['target_competition_category_id' => $lain->id]
        );

        $this->assertSame(1, AssessmentCategory::where('competition_category_id', $lain->id)->count());
    }

    /** Kategori event lain tetap ditolak (regresi guard tenant yang lama). */
    public function test_salin_menolak_kategori_event_lain()
    {
        $userLain = User::factory()->eventner()->create(['is_active' => true]);
        $eventLain = Eventner::factory()->create([
            'user_id' => $userLain->id,
            'status' => 'approved',
        ]);
        $kategoriLain = CompetitionCategory::factory()->child()->create([
            'eventner_id' => $eventLain->id,
        ]);

        $sumber = $this->rubrik($this->anak);

        $this->actingAs($this->user)->post(
            route('eventner.format-nilai.copy-execute', $sumber->id),
            ['target_competition_category_id' => $kategoriLain->id]
        );

        $this->assertSame(0, AssessmentCategory::where('competition_category_id', $kategoriLain->id)->count());
    }

    // ---- Builder ----------------------------------------------------------

    /** selectTab(induk) tidak mengisi activeTab. */
    public function test_builder_select_tab_menolak_induk_beranak()
    {
        Livewire::actingAs($this->user)
            ->test(Builder::class)
            ->call('selectTab', $this->induk->id)
            ->assertSet('activeTab', '');
    }

    /** addCategory tidak menempelkan rubrik ke induk walau activeTab dipaksa. */
    public function test_builder_tambah_kategori_tidak_menempel_ke_induk()
    {
        Livewire::actingAs($this->user)
            ->test(Builder::class)
            ->set('activeTab', (string) $this->induk->id)
            ->set('newCategoryName', 'Rubrik Baru')
            ->call('addCategory');

        $this->assertSame(
            0,
            AssessmentCategory::where('competition_category_id', $this->induk->id)->count(),
            'Rubrik tidak boleh menempel di induk ber-anak.'
        );
    }

    /** Pengurangan global juga tidak menempel ke induk. */
    public function test_builder_pengurangan_global_tidak_menempel_ke_induk()
    {
        Livewire::actingAs($this->user)
            ->test(Builder::class)
            ->set('activeTab', (string) $this->induk->id)
            ->set('newGlobalDeductionCategoryName', 'Pengurangan Baru')
            ->call('addGlobalDeductionCategory');

        $this->assertSame(
            0,
            DeductionCategory::where('competition_category_id', $this->induk->id)->count()
        );
    }

    // ---- Import -----------------------------------------------------------

    /** setActiveTab(induk) jatuh ke global (''), bukan menyimpan id induk. */
    public function test_import_menolak_induk_beranak()
    {
        Livewire::actingAs($this->user)
            ->test(Import::class)
            ->call('setActiveTab', $this->induk->id)
            ->assertSet('activeTab', '');
    }

    /** Daftar tingkat di halaman import tidak memuat induk ber-anak. */
    public function test_import_tidak_menawarkan_induk_beranak()
    {
        $ids = Livewire::actingAs($this->user)
            ->test(Import::class)
            ->instance()
            ->competitionCategories()
            ->pluck('id')
            ->all();

        $this->assertContains($this->anak->id, $ids);
        $this->assertNotContains($this->induk->id, $ids);
    }
}
