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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class JudgeVoiceNoteTest extends TestCase
{
    use RefreshDatabase;

    private Eventner $eventner;
    private CompetitionCategory $category;
    private Registration $registration;
    private Judge $judge;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $user = User::factory()->eventner()->create(['is_active' => true]);
        $this->eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'nama_event' => 'LKBB Championship',
            'slug' => 'lkbb-championship',
        ]);

        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
        ]);
        $this->category = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
            'name' => 'Tingkat SMA/SMK',
        ]);

        $this->registration = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->category->id,
            'nama_sekolah' => 'SMA Negeri 1',
            'urutan_tampil' => 1,
        ]);

        $this->judge = Judge::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Mayor Inf Budi Santoso',
        ]);

        // Buat rubrik dan kaitkan juri
        $cat = AssessmentCategory::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'PBB Murni',
            'competition_category_id' => null,
        ]);
        $sub = AssessmentSubCategory::create([
            'assessment_category_id' => $cat->id,
            'name' => 'Gerakan Dasar',
        ]);
        AssessmentCriteria::create([
            'assessment_sub_category_id' => $sub->id,
            'name' => 'Langkah Tegap',
            'score_options' => [['score' => 10], ['score' => 20]],
        ]);

        $this->judge->assessmentCategories()->attach($cat->id);

        \Illuminate\Support\Facades\DB::table('competition_group_judge')->insert([
            'judge_id' => $this->judge->id,
            'competition_category_id' => $this->category->id,
            'scope' => 'level',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        config(['app.entry_host' => 'entry.berbaris.test']);
    }

    public function test_judge_can_save_voice_note_with_audio_file()
    {
        $fakeAudio = UploadedFile::fake()->create('evaluasi.webm', 1500, 'audio/webm');

        Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judge->access_token])
            ->call('selectCategory', $this->category->id)
            ->call('selectParticipant', $this->registration->id)
            ->set('audioUpload', $fakeAudio)
            ->call('saveVoiceNote', 210) // Durasi 3 menit 30 detik
            ->assertDispatched('toast')
            ->assertDispatched('voice-note-saved');

        $this->assertDatabaseHas('assessment_judge_notes', [
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->registration->id,
            'judge_id' => $this->judge->id,
            'duration_seconds' => 210,
        ]);

        $note = JudgeNote::where('registration_id', $this->registration->id)
            ->where('judge_id', $this->judge->id)
            ->first();

        $this->assertNotNull($note);
        $this->assertNotNull($note->audio_path);
        Storage::disk('public')->assertExists($note->audio_path);
        $this->assertSame('03:30', $note->formatted_duration);
    }

    public function test_voice_note_supports_10_to_12_minutes_duration()
    {
        $fakeAudio = UploadedFile::fake()->create('full_peleton_10min.webm', 3500, 'audio/webm');

        Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judge->access_token])
            ->call('selectCategory', $this->category->id)
            ->call('selectParticipant', $this->registration->id)
            ->set('audioUpload', $fakeAudio)
            ->call('saveVoiceNote', 600) // 10 menit = 600 detik
            ->assertDispatched('voice-note-saved');

        $note = JudgeNote::where('registration_id', $this->registration->id)->first();
        $this->assertEquals(600, $note->duration_seconds);
        $this->assertSame('10:00', $note->formatted_duration);

        // Uji batas 12 menit = 720 detik
        $fakeAudio12 = UploadedFile::fake()->create('full_peleton_12min.webm', 4200, 'audio/webm');
        Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judge->access_token])
            ->call('selectCategory', $this->category->id)
            ->call('selectParticipant', $this->registration->id)
            ->set('audioUpload', $fakeAudio12)
            ->call('saveVoiceNote', 720)
            ->assertDispatched('voice-note-saved');

        $note->refresh();
        $this->assertEquals(720, $note->duration_seconds);
        $this->assertSame('12:00', $note->formatted_duration);
    }

    public function test_judge_can_delete_voice_note_and_file_is_removed_from_storage()
    {
        $fakeAudio = UploadedFile::fake()->create('test.webm', 500, 'audio/webm');

        $component = Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judge->access_token])
            ->call('selectCategory', $this->category->id)
            ->call('selectParticipant', $this->registration->id)
            ->set('audioUpload', $fakeAudio)
            ->call('saveVoiceNote', 45);

        $note = JudgeNote::where('registration_id', $this->registration->id)->first();
        $storedPath = $note->audio_path;
        Storage::disk('public')->assertExists($storedPath);

        // Hapus rekaman
        $component->call('deleteVoiceNote')
            ->assertDispatched('toast')
            ->assertDispatched('voice-note-deleted');

        Storage::disk('public')->assertMissing($storedPath);
        $this->assertDatabaseMissing('assessment_judge_notes', [
            'id' => $note->id,
        ]);
    }

    public function test_judge_can_save_text_note_evaluation()
    {
        Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judge->access_token])
            ->call('selectCategory', $this->category->id)
            ->call('selectParticipant', $this->registration->id)
            ->set('textNote', 'Langkah tegap mantap, aba-aba danton perlu intonasi lebih tegas.')
            ->call('saveTextNote')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('assessment_judge_notes', [
            'registration_id' => $this->registration->id,
            'judge_id' => $this->judge->id,
            'notes' => 'Langkah tegap mantap, aba-aba danton perlu intonasi lebih tegas.',
        ]);
    }

    public function test_voice_note_validation_rejects_file_exceeding_20mb()
    {
        // 21MB = 21504 KB (melebihi batas max:20480)
        $oversizedFile = UploadedFile::fake()->create('huge.webm', 22000, 'audio/webm');

        Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judge->access_token])
            ->call('selectCategory', $this->category->id)
            ->call('selectParticipant', $this->registration->id)
            ->set('audioUpload', $oversizedFile)
            ->call('saveVoiceNote', 60)
            ->assertHasErrors('audioUpload');

        $this->assertDatabaseCount('assessment_judge_notes', 0);
    }

    public function test_judge_cannot_save_note_for_participant_from_other_eventner()
    {
        $otherUser = User::factory()->eventner()->create(['is_active' => true]);
        $otherEventner = Eventner::factory()->create(['user_id' => $otherUser->id]);
        $otherCategory = CompetitionCategory::factory()->create(['eventner_id' => $otherEventner->id]);
        $otherRegistration = Registration::factory()->create([
            'eventner_id' => $otherEventner->id,
            'competition_category_id' => $otherCategory->id,
        ]);

        $fakeAudio = UploadedFile::fake()->create('evil.webm', 500, 'audio/webm');

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::test(\App\Livewire\Public\JudgeScoring\Index::class, ['token' => $this->judge->access_token])
            ->call('selectCategory', $this->category->id)
            ->call('selectParticipant', $otherRegistration->id);
    }

    public function test_voice_notes_appear_exclusively_on_magic_link_portal_page()
    {
        // Simpan note dengan rekaman audio dan teks
        JudgeNote::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->registration->id,
            'judge_id' => $this->judge->id,
            'audio_path' => 'voice-notes/' . $this->eventner->id . '/sample.webm',
            'duration_seconds' => 540,
            'mime_type' => 'audio/webm',
            'notes' => 'Catatan penting: formasi bintang sangat rapi.',
        ]);

        // Voice note privat muncul di portal Magic Link pangkalan/sekolah
        Livewire::test(\App\Livewire\Public\MagicLink\Registration::class, [
            'token' => $this->registration->magic_token,
        ])
            ->assertOk()
            ->assertSee('Evaluasi &amp; Catatan Suara Dewan Juri', false)
            ->assertSee('Mayor Inf Budi Santoso')
            ->assertSee('Catatan penting: formasi bintang sangat rapi.')
            ->assertSee('09:00'); // 540s = 09:00

        // Voice note TIDAK bocor ke publik di halaman rincian hasil umum
        Livewire::test(\App\Livewire\Public\EventResultDetail::class, [
            'slug' => $this->eventner->slug,
            'registration' => $this->registration->id,
        ])
            ->assertOk()
            ->assertDontSee('Evaluasi &amp; Catatan Suara Dewan Juri', false)
            ->assertDontSee('Catatan penting: formasi bintang sangat rapi.');
    }

    public function test_voice_notes_appear_in_eventner_scoring_panel()
    {
        JudgeNote::create([
            'eventner_id' => $this->eventner->id,
            'registration_id' => $this->registration->id,
            'judge_id' => $this->judge->id,
            'audio_path' => 'voice-notes/' . $this->eventner->id . '/sample.webm',
            'duration_seconds' => 300,
            'mime_type' => 'audio/webm',
            'notes' => 'Catatan juri untuk panitia meja.',
        ]);

        $owner = User::find($this->eventner->user_id);

        $this->actingAs($owner);

        Livewire::test(\App\Livewire\Eventner\Scoring\Index::class, ['eventner' => $this->eventner])
            ->call('selectCategory', $this->category->id)
            ->call('selectParticipant', $this->registration->id)
            ->assertSee('Catatan &amp; Evaluasi Juri', false)
            ->assertSee('Catatan juri untuk panitia meja.')
            ->assertSee('05:00');
    }
}
