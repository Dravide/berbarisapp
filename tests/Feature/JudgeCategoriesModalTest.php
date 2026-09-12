<?php

namespace Tests\Feature;

use App\Models\AssessmentCategory;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JudgeCategoriesModalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Eventner $eventner;
    private Judge $judge;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->eventner()->create(['is_active' => true]);
        $this->eventner = Eventner::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'approved',
        ]);
        $this->judge = Judge::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Budi Santoso',
        ]);
    }

    /** Satu tingkat lomba (child) + satu kategori penilaian di dalamnya. */
    private function makeAssignment(string $parentName, string $levelName, string $categoryName): AssessmentCategory
    {
        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
            'name' => $parentName,
        ]);
        $level = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
            'name' => $levelName,
        ]);

        $category = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => $categoryName,
            'competition_category_id' => $level->id,
        ]);
        $this->judge->assessmentCategories()->attach($category->id);

        return $category;
    }

    private function judgeComponent()
    {
        return Livewire::actingAs($this->user)->test(\App\Livewire\Eventner\Judge\Index::class);
    }

    public function test_kolom_tabel_menampilkan_tombol_bukan_badge_kategori()
    {
        $this->makeAssignment('LOBB', 'U13', 'Kekompakan');

        // Kategori tidak lagi dijejal di kolom — hanya jumlahnya.
        $this->judgeComponent()
            ->assertSee('1 Kategori Penilaian')
            ->assertDontSee('badge bg-success-subtle text-success');
    }

    public function test_modal_terbuka_lewat_tombol_kategori()
    {
        $this->makeAssignment('LOBB', 'U13', 'Kekompakan');

        $this->judgeComponent()
            ->assertSet('selectedCategoriesJudgeId', null)
            ->call('openCategoriesModal', $this->judge->id)
            ->assertSet('selectedCategoriesJudgeId', $this->judge->id)
            ->assertSee('Kategori Penilaian Juri')
            ->assertSee('Kekompakan');
    }

    /** Isi modal dipisah per tingkat lomba, bukan satu daftar rata. */
    public function test_kategori_dikelompokkan_per_tingkat_lomba()
    {
        $this->makeAssignment('LOBB', 'U13', 'Kekompakan U13');
        $this->makeAssignment('LOBB', 'U16', 'Kekompakan U16');

        $component = $this->judgeComponent();
        $component->call('openCategoriesModal', $this->judge->id);

        $groups = $component->instance()->categoriesJudgeGrouped;

        $this->assertSame(['LOBB — U13', 'LOBB — U16'], $groups->pluck('name')->all());
        $this->assertSame(
            ['Kekompakan U13'],
            collect($groups->firstWhere('name', 'LOBB — U13')['items'])->pluck('name')->all()
        );
        $this->assertSame(
            ['Kekompakan U16'],
            collect($groups->firstWhere('name', 'LOBB — U16')['items'])->pluck('name')->all()
        );

        $component->assertSee('LOBB — U13')->assertSee('LOBB — U16');
    }

    public function test_juri_tanpa_tugas_menampilkan_pesan_kosong()
    {
        $this->judgeComponent()
            ->call('openCategoriesModal', $this->judge->id)
            ->assertSee('belum ditugaskan ke kategori penilaian mana pun');
    }

    public function test_modal_ditutup_lewat_tombol_tutup()
    {
        $this->makeAssignment('LOBB', 'U13', 'Kekompakan');

        $this->judgeComponent()
            ->call('openCategoriesModal', $this->judge->id)
            ->call('closeCategoriesModal')
            ->assertSet('selectedCategoriesJudgeId', null);
    }

    /** Klik "Ubah Tugas" menutup modal rincian lalu membuka form edit. */
    public function test_ubah_tugas_menutup_modal_rincian()
    {
        $this->makeAssignment('LOBB', 'U13', 'Kekompakan');

        $this->judgeComponent()
            ->call('openCategoriesModal', $this->judge->id)
            ->call('edit', $this->judge->id)
            ->assertSet('selectedCategoriesJudgeId', null)
            ->assertSet('isEditMode', true)
            ->assertSet('editingId', $this->judge->id);
    }

    public function test_modal_terpisah_antar_juri()
    {
        $this->makeAssignment('LOBB', 'U13', 'Kekompakan');

        $other = Judge::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Citra Dewi',
        ]);
        $otherCat = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Ketepatan',
            'competition_category_id' => null,
        ]);
        $other->assessmentCategories()->attach($otherCat->id);

        $component = $this->judgeComponent();

        $component->call('openCategoriesModal', $other->id);
        $otherGroups = $component->instance()->categoriesJudgeGrouped;
        $components = $component->instance()->categoriesJudge;

        $this->assertSame('Citra Dewi', $components->name);
        $this->assertSame(
            ['Ketepatan'],
            collect($otherGroups->firstWhere('name', 'Lainnya')['items'])->pluck('name')->all()
        );

        $component->call('openCategoriesModal', $this->judge->id);
        $judgeGroups = $component->instance()->categoriesJudgeGrouped;

        $this->assertSame(
            ['Kekompakan'],
            collect($judgeGroups->firstWhere('name', 'LOBB — U13')['items'])->pluck('name')->all()
        );
    }
}
