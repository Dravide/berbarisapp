<?php

namespace Tests\Feature;

use App\Livewire\Eventner\Schedule\Index;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\Eventner;
use App\Models\EventnerVenue;
use App\Models\EventSchedule;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Jadwal pertandingan — pertemuan per tingkat/grup/venue.
 *
 * Dua hal yang dijaga:
 *  1. Scope: tingkat, grup, babak, venue yang dipilih dari luar (event atau
 *     pemilik lain) tidak boleh masuk baris jadwal.
 *  2. Generate dari undian: baris mengikuti urutan undian, venue diambil
 *     dari pengaturan venue tingkat, dan generate ulang menggantikan baris
 *     lama tanpa menyentuh jadwal manual.
 */
class EventScheduleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Eventner $eventner;

    private CompetitionCategory $level;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->eventner()->create(['is_active' => true]);
        $this->eventner = Eventner::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'approved',
            'tanggal' => '2026-11-01',
        ]);

        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
            'name' => 'PBB Putra',
        ]);
        $this->level = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
            'name' => 'Kelas 9',
        ]);
    }

    public function test_panitia_membuat_dan_mengubah_jadwal()
    {
        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->set('categoryId', (string) $this->level->id)
            ->set('startTime', '08:00')
            ->set('endTime', '09:00')
            ->set('title', 'Babak Penyisihan Kelas 9')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('event_schedules', [
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'title' => 'Babak Penyisihan Kelas 9',
        ]);

        $jadwal = EventSchedule::where('eventner_id', $this->eventner->id)->first();

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('edit', $jadwal->id)
            ->set('title', 'Babak Penyisihan Revisi')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Babak Penyisihan Revisi', $jadwal->fresh()->title);
    }

    public function test_jadwal_tingkat_lain_tidak_bisa_dipilih()
    {
        // Tingkat milik event lain — id dari DOM/permintaan langsung.
        $userLain = User::factory()->eventner()->create(['is_active' => true]);
        $eventLain = Eventner::factory()->create([
            'user_id' => $userLain->id,
            'status' => 'approved',
        ]);
        $levelLain = CompetitionCategory::factory()->create([
            'eventner_id' => $eventLain->id,
            'parent_id' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->set('categoryId', (string) $levelLain->id)
            ->set('startTime', '08:00')
            ->call('save')
            ->assertHasErrors('categoryId');

        $this->assertSame(0, EventSchedule::count());
    }

    public function test_generate_dari_undian_membuat_baris_urut()
    {
        $venue = EventnerVenue::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Lapangan Utama',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $this->level->update(['venue_id' => $venue->id]);

        $regB = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'nama_sekolah' => 'SMA Undi Dua',
            'urutan_tampil' => 2,
        ]);
        $regA = Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'nama_sekolah' => 'SMA Undi Satu',
            'urutan_tampil' => 1,
        ]);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->set('importCategoryId', (string) $this->level->id)
            ->set('importStartTime', '08:00')
            ->set('importDefaultDuration', 30)
            ->call('generateFromDrawing');

        $baris = EventSchedule::where('eventner_id', $this->eventner->id)
            ->orderBy('sort_order')->get();

        $this->assertCount(2, $baris);
        $this->assertSame('08:00', $baris[0]->start_time->format('H:i'));
        $this->assertSame('08:30', $baris[0]->end_time->format('H:i'));
        $this->assertSame('08:30', $baris[1]->start_time->format('H:i'));
        $this->assertSame($venue->id, $baris[0]->eventner_venue_id);
    }

    public function test_generate_tanpa_undian_menolak()
    {
        Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'urutan_tampil' => null,
        ]);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->set('importCategoryId', (string) $this->level->id)
            ->set('importStartTime', '08:00')
            ->call('generateFromDrawing')
            ->assertHasErrors('importCategoryId');

        $this->assertSame(0, EventSchedule::count());
    }

    public function test_fitur_terkunci_untuk_paket_gratis()
    {
        // Tamu → login.
        $this->get(route('eventner.schedule.index'))->assertRedirect(route('login'));

        // Free, trial habis → fitur terkunci, panitia diblokir gate.
        $this->eventner->update(['trial_ends_at' => now()->subDay()]);

        $this->assertFalse($this->eventner->canAccessFeature('schedule'));

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->assertRedirect(route('eventner.billing.upgrade'));
    }

    public function test_halaman_publik_rundown_menampilkan_jadwal_per_tanggal_dan_venue()
    {
        $venue = EventnerVenue::create([
            'eventner_id' => $this->eventner->id,
            'name' => 'Lapangan Selatan',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        EventSchedule::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'eventner_venue_id' => $venue->id,
            'title' => 'Pertandingan Pembuka',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'tanggal' => '2026-11-01',
            'sort_order' => 1,
        ]);

        $this->get('/event/' . $this->eventner->slug . '/rundown')
            ->assertOk()
            ->assertSee('Jadwal Pertandingan')
            ->assertSee('Pertandingan Pembuka')
            ->assertSee('Lapangan Selatan')
            ->assertSee('09:00');
    }

    public function test_event_lain_tidak_bocor_di_halaman_publik()
    {
        EventSchedule::create([
            'eventner_id' => $this->eventner->id,
            'competition_category_id' => $this->level->id,
            'title' => 'Jadwal Rahasia Event Lain',
            'start_time' => '09:00',
            'sort_order' => 1,
        ]);

        $userLain = User::factory()->eventner()->create(['is_active' => true]);
        $eventLain = Eventner::factory()->create([
            'user_id' => $userLain->id,
            'status' => 'approved',
        ]);

        $this->get('/event/' . $eventLain->slug . '/rundown')
            ->assertOk()
            ->assertDontSee('Jadwal Rahasia Event Lain');
    }
}
