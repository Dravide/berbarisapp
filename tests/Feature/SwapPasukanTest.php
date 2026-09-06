<?php

namespace Tests\Feature;

use App\Models\AssessmentScore;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Participant;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SwapPasukanTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Registration $regA;
    private Registration $regB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->eventner()->create(['is_active' => true]);
        $eventner = Eventner::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'approved',
        ]);
        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => null,
        ]);
        $category = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => $parent->id,
        ]);

        // 2 pasukan dari satu sekolah (NPSN sama, kategori sama)
        $this->regA = Registration::factory()->pasukan('A')->for($eventner, 'eventner')->create([
            'competition_category_id' => $category->id,
            'npsn' => '12345678',
            'danton_nama' => 'Danton A',
            'danton_nisn' => '1111',
        ]);
        $this->regB = Registration::factory()->pasukan('B')->for($eventner, 'eventner')->create([
            'competition_category_id' => $category->id,
            'npsn' => '12345678',
            'danton_nama' => 'Danton B',
            'danton_nisn' => '2222',
        ]);

        Participant::create(['registration_id' => $this->regA->id, 'nama' => 'Anggota A1']);
        Participant::create(['registration_id' => $this->regA->id, 'nama' => 'Anggota A2']);
        Participant::create(['registration_id' => $this->regB->id, 'nama' => 'Anggota B1']);
    }

    public function test_tukar_pasukan_menukar_anggota_dan_danton()
    {
        Livewire::actingAs($this->user)
            ->test(\App\Livewire\Eventner\Participant\Index::class)
            ->call('openSwapModal', $this->regA->id)
            ->assertSet('showSwapModal', true)
            ->call('swapPasukan', $this->regB->id);

        // Anggota bertukar
        $namesA = $this->regA->fresh()->participants->pluck('nama')->sort()->values()->all();
        $namesB = $this->regB->fresh()->participants->pluck('nama')->sort()->values()->all();
        $this->assertSame(['Anggota B1'], $namesA);
        $this->assertSame(['Anggota A1', 'Anggota A2'], $namesB);

        // Danton bertukar
        $this->assertSame('Danton B', $this->regA->fresh()->danton_nama);
        $this->assertSame('Danton A', $this->regB->fresh()->danton_nama);
        $this->assertSame('2222', $this->regA->fresh()->danton_nisn);
        $this->assertSame('1111', $this->regB->fresh()->danton_nisn);
    }

    public function test_tukar_pasukan_diblokir_kalau_sudah_ada_nilai_juri()
    {
        $criteria = \App\Models\AssessmentCriteria::create([
            'assessment_sub_category_id' => \App\Models\AssessmentSubCategory::create([
                'assessment_category_id' => \App\Models\AssessmentCategory::create([
                    'eventner_id' => $this->regA->eventner_id,
                    'name' => 'Penilaian',
                ])->id,
                'name' => 'Sub',
            ])->id,
            'name' => 'Teknik',
            'score_options' => [['score' => 10]],
        ]);

        AssessmentScore::create([
            'eventner_id' => $this->regA->eventner_id,
            'registration_id' => $this->regA->id,
            'assessment_criteria_id' => $criteria->id,
            'score' => 10,
        ]);

        Livewire::actingAs($this->user)
            ->test(\App\Livewire\Eventner\Participant\Index::class)
            ->call('openSwapModal', $this->regA->id)
            ->call('swapPasukan', $this->regB->id);

        // Tidak ada yang berubah
        $namesA = $this->regA->fresh()->participants->pluck('nama')->sort()->values()->all();
        $this->assertSame(['Anggota A1', 'Anggota A2'], $namesA);
        $this->assertSame('Danton A', $this->regA->fresh()->danton_nama);
    }

    public function test_tukar_pasukan_satu_sekolah_saja_tidak_bisa_tukar_antar_sekolah()
    {
        $eventner = $this->regA->eventner;
        $category = CompetitionCategory::find($this->regA->competition_category_id);

        // Sekolah lain, kategori sama
        $otherSchool = Registration::factory()->for($eventner, 'eventner')->create([
            'competition_category_id' => $category->id,
            'npsn' => '99999999',
            'danton_nama' => 'Danton Lain',
        ]);
        Participant::create(['registration_id' => $otherSchool->id, 'nama' => 'Anggota Lain']);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($this->user)
            ->test(\App\Livewire\Eventner\Participant\Index::class)
            ->call('openSwapModal', $this->regA->id)
            ->call('swapPasukan', $otherSchool->id);
    }

    public function test_swap_candidates_hanya_pasukan_sekolah_sama()
    {
        $eventner = $this->regA->eventner;
        $category = CompetitionCategory::find($this->regA->competition_category_id);

        // Sekolah lain, kategori sama — tidak boleh jadi kandidat
        Registration::factory()->for($eventner, 'eventner')->create([
            'competition_category_id' => $category->id,
            'npsn' => '99999999',
        ]);
        // Sekolah sama, kategori lain — tidak boleh jadi kandidat
        $otherParent = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id, 'parent_id' => null]);
        $otherCategory = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id, 'parent_id' => $otherParent->id]);
        Registration::factory()->for($eventner, 'eventner')->create([
            'competition_category_id' => $otherCategory->id,
            'npsn' => '12345678',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(\App\Livewire\Eventner\Participant\Index::class)
            ->call('openSwapModal', $this->regA->id);

        $candidates = $component->get('swapCandidates');
        $this->assertSame([$this->regB->id], $candidates->pluck('id')->all());
    }
}
