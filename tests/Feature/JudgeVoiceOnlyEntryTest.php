<?php

namespace Tests\Feature;

use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentSubCategory;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\JudgeNote;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class JudgeVoiceOnlyEntryTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;
    private CompetitionCategory $category;
    private Registration $reg1;
    private Registration $reg2;
    private Judge $judge;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config(['app.entry_host' => 'entry.berbaris.test']);

        $user = User::factory()->eventner()->create(['is_active' => true]);
        $this->eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'nama_event' => 'Kejurda Baris Berbaris 2026',
            'slug' => 'kejurda-2026',
        ]);

        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
            'name' => 'Tingkat Sekolah',
        ]);
        $this->category = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
            'name' => 'SMA/SMK Putra',
        ]);

        $this->reg1 = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->category->id,
            'nama_sekolah' => 'SMAN 1 Berbaris',
            'urutan_tampil' => 1,
        ]);

        $this->reg2 = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->category->id,
            'nama_sekolah' => 'SMAN 2 Tangguh',
            'urutan_tampil' => 2,
        ]);

        $this->judge = Judge::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Juri Utama',
        ]);
    }

    private function tablet(string $path)
    {
        return $this->get('http://' . judge_entry_host() . $path);
    }

    public function test_voice_url_renders_voice_mode()
    {
        $response = $this->tablet('/juri/' . $this->judge->access_token . '/voice');
        $response->assertOk();
        $response->assertSee('Evaluasi Voice Note');
        $response->assertSee('Juri Evaluasi');

        $responseShort = $this->tablet('/voice-note/' . $this->judge->access_token);
        $responseShort->assertOk();
        $responseShort->assertSee('Evaluasi Voice Note');
    }

    public function test_legacy_voice_route_redirects_permanently_to_entry_host()
    {
        $response = $this->get('/juri/' . $this->judge->access_token . '/voice');
        $response->assertStatus(301);
        $response->assertRedirect('http://' . judge_entry_host() . '/juri/' . $this->judge->access_token . '/voice');
    }

    public function test_voice_mode_can_open_categories_without_rubric_assignment()
    {
        $this->get('http://' . judge_entry_host() . '/juri/' . $this->judge->access_token . '/voice');

        Livewire::withQueryParams(['mode' => 'voice'])
            ->test(\App\Livewire\Public\JudgeScoring\Index::class, [
                'token' => $this->judge->access_token,
            ])
            ->assertSet('isVoiceOnly', true)
            ->assertSet('view', 'categories')
            ->assertSee('SMA/SMK Putra')
            ->call('selectCategory', $this->category->id)
            ->assertSet('view', 'participants')
            ->assertSee('SMAN 1 Berbaris')
            ->assertSee('SMAN 2 Tangguh');
    }

    public function test_voice_only_step_three_hides_numeric_rubrics_and_shows_navigation()
    {
        // Berikan rubrik penilaian pada kategori
        $cat = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'PBB Murni',
            'competition_category_id' => $this->category->id,
        ]);
        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $cat->id,
            'name' => 'Gerakan Dasar',
        ]);
        AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Sikap Sempurna',
            'score_options' => [['score' => 10], ['score' => 20]],
        ]);

        Livewire::withQueryParams(['mode' => 'voice'])
            ->test(\App\Livewire\Public\JudgeScoring\Index::class, [
                'token' => $this->judge->access_token,
            ])
            ->assertSet('isVoiceOnly', true)
            ->call('selectCategory', $this->category->id)
            ->call('selectParticipant', $this->reg1->id)
            ->assertSet('view', 'scoring')
            ->assertSee('Langkah 3 dari 3 · Evaluasi Suara')
            ->assertSee('Laman Khusus Evaluasi Voice Note')
            ->assertDontSee('Sikap Sempurna') // Rubrik nilai disembunyikan
            ->assertDontSee('Finalisasi & Lanjut') // Tombol finalisasi angka disembunyikan
            ->assertSee('Berikutnya')
            ->assertSee('Sebelumnya');
    }

    public function test_peleton_navigation_advances_in_voice_mode()
    {
        Livewire::withQueryParams(['mode' => 'voice'])
            ->test(\App\Livewire\Public\JudgeScoring\Index::class, [
                'token' => $this->judge->access_token,
            ])
            ->call('selectCategory', $this->category->id)
            ->call('selectParticipant', $this->reg1->id)
            ->assertSet('selectedRegistrationId', $this->reg1->id)
            ->call('nextParticipant')
            ->assertSet('selectedRegistrationId', $this->reg2->id)
            ->call('prevParticipant')
            ->assertSet('selectedRegistrationId', $this->reg1->id);
    }

    public function test_status_badges_display_voice_note_status()
    {
        // Simpan voice note untuk reg1
        JudgeNote::create([
            'eventner_id' => $this->eventner->id,
            'judge_id' => $this->judge->id,
            'registration_id' => $this->reg1->id,
            'audio_path' => 'voice-notes/sample.webm',
            'duration_seconds' => 125, // 02:05
            'notes' => 'Tempo variasi sangat rapi.',
        ]);

        Livewire::withQueryParams(['mode' => 'voice'])
            ->test(\App\Livewire\Public\JudgeScoring\Index::class, [
                'token' => $this->judge->access_token,
            ])
            ->call('selectCategory', $this->category->id)
            ->assertSee('Terekam (02:05)')
            ->assertSee('Belum Rekam'); // reg2 belum ada rekaman
    }

    public function test_judge_can_toggle_between_scoring_and_voice_mode()
    {
        Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, [
            'token' => $this->judge->access_token,
        ])
            ->assertSet('isVoiceOnly', false)
            ->call('toggleVoiceOnlyMode')
            ->assertSet('isVoiceOnly', true)
            ->call('toggleVoiceOnlyMode')
            ->assertSet('isVoiceOnly', false);
    }
}
