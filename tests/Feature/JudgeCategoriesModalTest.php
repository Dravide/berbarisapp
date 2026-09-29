<?php

namespace Tests\Feature;

use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Modal rincian tugas juri — dulu "Kategori Penilaian Juri" (centang rubrik),
 * sekarang "Tugas Penilaian Juri": daftar baris penugasan grup yang dipegang
 * juri itu.
 *
 * Layar centang rubriknya dihapus bersama keputusan "grup menggantikan seri":
 * juri diikat ke grup, dan rubriknya menyusul dari seri peserta.
 */
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

    /** Satu tingkat lomba (child) + satu grup di dalamnya, tercentang ke juri. */
    private function makeAssignment(string $parentName, string $levelName, string $groupName): CompetitionGroup
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

        $group = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $level->id,
            'name' => $groupName,
        ]);

        CompetitionGroup::syncJudges($level->id, CompetitionGroup::SCOPE_GROUP, $group->id, [$this->judge->id]);

        return $group;
    }

    private function judgeComponent()
    {
        return Livewire::actingAs($this->user)->test(\App\Livewire\Eventner\Judge\Index::class);
    }

    public function test_kolom_tabel_menampilkan_tombol_bukan_badge_kategori()
    {
        $this->makeAssignment('LOBB', 'U13', 'Grup A');

        // Penugasan tidak lagi dijejal di kolom — hanya jumlahnya.
        $this->judgeComponent()
            ->assertSee('1 Penugasan')
            ->assertDontSee('badge bg-success-subtle text-success');
    }

    public function test_modal_terbuka_lewat_tombol_penugasan()
    {
        $this->makeAssignment('LOBB', 'U13', 'Grup A');

        $this->judgeComponent()
            ->assertSet('selectedCategoriesJudgeId', null)
            ->call('openCategoriesModal', $this->judge->id)
            ->assertSet('selectedCategoriesJudgeId', $this->judge->id)
            ->assertSee('Tugas Penilaian Juri')
            ->assertSee('Grup A');
    }

    /** Isi modal dipisah per tingkat lomba, bukan satu daftar rata. */
    public function test_penugasan_dikelompokkan_per_tingkat_lomba()
    {
        $this->makeAssignment('LOBB', 'U13', 'Grup A');
        $this->makeAssignment('LOBB', 'U16', 'Grup B');

        $component = $this->judgeComponent();
        $component->call('openCategoriesModal', $this->judge->id);

        $groups = $component->instance()->categoriesJudgeGrouped;

        $this->assertSame(['LOBB — U13', 'LOBB — U16'], $groups->pluck('name')->all());
        $this->assertSame(
            ['Grup A'],
            $groups->firstWhere('name', 'LOBB — U13')['items']->all()
        );
        $this->assertSame(
            ['Grup B'],
            $groups->firstWhere('name', 'LOBB — U16')['items']->all()
        );

        $component->assertSee('LOBB — U13')->assertSee('LOBB — U16');
    }

    /** Baris tanpa grup muncul dengan namanya, bukan nama grup kosong. */
    public function test_baris_final_dan_belum_bergrup_memakai_label_scope()
    {
        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
            'name' => 'LOBB',
        ]);
        $level = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
            'name' => 'U13',
        ]);

        CompetitionRound::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $level->id,
            'name' => 'Final',
            'type' => CompetitionRound::TYPE_FINAL,
        ]);

        CompetitionGroup::syncJudges($level->id, CompetitionGroup::SCOPE_FINAL, null, [$this->judge->id]);
        CompetitionGroup::syncJudges($level->id, CompetitionGroup::SCOPE_UNGROUPED, null, [$this->judge->id]);

        $component = $this->judgeComponent();
        $component->call('openCategoriesModal', $this->judge->id);

        $items = $component->instance()->categoriesJudgeGrouped->firstWhere('name', 'LOBB — U13')['items'];

        $this->assertEqualsCanonicalizing(['Final', 'Belum Bergrup'], $items->all());
        $component->assertSee('Final')->assertSee('Belum Bergrup');
    }

    public function test_juri_tanpa_tugas_menampilkan_pesan_kosong()
    {
        $this->judgeComponent()
            ->call('openCategoriesModal', $this->judge->id)
            ->assertSee('belum ditugaskan ke grup mana pun');
    }

    public function test_modal_ditutup_lewat_tombol_tutup()
    {
        $this->makeAssignment('LOBB', 'U13', 'Grup A');

        $this->judgeComponent()
            ->call('openCategoriesModal', $this->judge->id)
            ->call('closeCategoriesModal')
            ->assertSet('selectedCategoriesJudgeId', null);
    }

    /** Klik "Ubah Tugas" menutup modal rincian lalu membuka form edit. */
    public function test_ubah_tugas_menutup_modal_rincian()
    {
        $this->makeAssignment('LOBB', 'U13', 'Grup A');

        $this->judgeComponent()
            ->call('openCategoriesModal', $this->judge->id)
            ->call('edit', $this->judge->id)
            ->assertSet('selectedCategoriesJudgeId', null)
            ->assertSet('isEditMode', true)
            ->assertSet('editingId', $this->judge->id);
    }

    public function test_modal_terpisah_antar_juri()
    {
        $this->makeAssignment('LOBB', 'U13', 'Grup A');

        $other = Judge::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Citra Dewi',
        ]);

        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
            'name' => 'PBB',
        ]);
        $level = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
            'name' => 'U16',
        ]);
        $otherGroup = CompetitionGroup::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $level->id,
            'name' => 'Grup Z',
        ]);
        CompetitionGroup::syncJudges($level->id, CompetitionGroup::SCOPE_GROUP, $otherGroup->id, [$other->id]);

        $component = $this->judgeComponent();

        $component->call('openCategoriesModal', $other->id);
        $otherGroups = $component->instance()->categoriesJudgeGrouped;

        $this->assertSame('Citra Dewi', $component->instance()->categoriesJudge->name);
        $this->assertSame(
            ['Grup Z'],
            $otherGroups->firstWhere('name', 'PBB — U16')['items']->all()
        );

        $component->call('openCategoriesModal', $this->judge->id);

        $this->assertSame(
            ['Grup A'],
            $component->instance()->categoriesJudgeGrouped->firstWhere('name', 'LOBB — U13')['items']->all()
        );
    }

    /** Juri di grup tanpa peserta tetap tampil — penugasan tak butuh pendaftar. */
    public function test_penugasan_grup_tanpa_peserta_tetap_terbaca()
    {
        $this->makeAssignment('LOBB', 'U13', 'Grup Kosong');

        $component = $this->judgeComponent();
        $component->call('openCategoriesModal', $this->judge->id);

        $this->assertSame(
            ['Grup Kosong'],
            $component->instance()->categoriesJudgeGrouped->firstWhere('name', 'LOBB — U13')['items']->all()
        );
    }

    /** Filter PDF per juri membaca tingkat dari penugasan, bukan dari rubrik. */
    public function test_filter_pdf_memakai_tingkat_dari_penugasan()
    {
        $this->makeAssignment('LOBB', 'U13', 'Grup A');

        $component = $this->judgeComponent();
        $component->call('selectJudgeForPdf', $this->judge->id);

        $this->assertSame(
            ['LOBB — U13'],
            $component->instance()->judgePdfLevels->pluck('full_name')->all()
        );
    }

    /** Peserta yang menempel di grup tidak mengubah daftar penugasan jurinya. */
    public function test_peserta_di_grup_tidak_menggandakan_baris_penugasan()
    {
        $group = $this->makeAssignment('LOBB', 'U13', 'Grup A');

        Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $group->competition_category_id,
            'competition_group_id' => $group->id,
            'nama_sekolah' => 'SD Negeri 1',
        ]);

        $component = $this->judgeComponent();
        $component->call('openCategoriesModal', $this->judge->id);

        $this->assertSame(
            ['Grup A'],
            $component->instance()->categoriesJudgeGrouped->firstWhere('name', 'LOBB — U13')['items']->all()
        );
    }
}
