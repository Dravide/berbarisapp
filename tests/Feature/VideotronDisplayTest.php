<?php

namespace Tests\Feature;

use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\EventRundown;
use App\Models\Eventner;
use App\Models\LandingPartner;
use App\Models\Registration;
use App\Models\VoteTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Videotron Display — layar big-screen venue per event.
 * Pola LivestreamOverlayTest: halaman publik, data nyata per mode.
 */
class VideotronDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function buatEvent(): Eventner
    {
        return Eventner::factory()->create([
            'status' => 'approved',
            'slug' => 'test-videotron',
            'nama_event' => 'Festival Videotron Uji',
            'venue' => 'Gedung Uji Konvensi',
        ]);
    }

    public function test_videotron_welcome_default_renders()
    {
        $eventner = $this->buatEvent();

        $this->get('/event/' . $eventner->slug . '/videotron')
            ->assertOk()
            ->assertSee('Festival Videotron Uji')
            ->assertSee('Gedung Uji Konvensi')
            ->assertSee('Selamat Datang');
    }

    public function test_videotron_mode_tidak_dikenal_jatuh_ke_welcome()
    {
        $eventner = $this->buatEvent();

        $this->get('/event/' . $eventner->slug . '/videotron?mode=hackerman')
            ->assertOk()
            ->assertSee('Selamat Datang');
    }

    public function test_videotron_mode_drawing_menampilkan_urutan()
    {
        $eventner = $this->buatEvent();
        $kategori = CompetitionCategory::factory()->for($eventner, 'eventner')->create();

        Registration::factory()->for($eventner, 'eventner')->create([
            'competition_category_id' => $kategori->id,
            'nama_sekolah' => 'SMA Undian Pertama',
            'urutan_tampil' => 1,
        ]);
        Registration::factory()->for($eventner, 'eventner')->create([
            'competition_category_id' => $kategori->id,
            'nama_sekolah' => 'SMA Undian Kedua',
            'urutan_tampil' => 2,
        ]);
        // 3 peserta pertama pindah ke kolom "Sudah Tampil" (ambil 3 dari antrean),
        // sisanya 1 tetap di kolom antrean supaya baris "Berikutnya" tampil.
        Registration::factory()->for($eventner, 'eventner')->create([
            'competition_category_id' => $kategori->id,
            'nama_sekolah' => 'SMA Undian Ketiga',
            'urutan_tampil' => 3,
        ]);
        Registration::factory()->for($eventner, 'eventner')->create([
            'competition_category_id' => $kategori->id,
            'nama_sekolah' => 'SMA Undian Keempat',
            'urutan_tampil' => 4,
        ]);

        $this->get('/event/' . $eventner->slug . '/videotron?mode=drawing&categoryId=' . $kategori->id)
            ->assertOk()
            ->assertSee('Urutan Tampil')
            ->assertSee('SMA Undian Pertama')
            ->assertSee('SMA Undian Kedua')
            ->assertSee('SMA Undian Keempat')
            ->assertSee('Berikutnya');
    }

    public function test_videotron_mode_vote_menampilkan_klasemen()
    {
        $eventner = $this->buatEvent();
        $reg = Registration::factory()->for($eventner, 'eventner')->create([
            'nama_sekolah' => 'SMP Vote Terbanyak',
        ]);

        VoteTransaction::create([
            'eventner_id' => $eventner->id,
            'registration_id' => $reg->id,
            'autogopay_transaction_id' => 'AGP-VT-' . $eventner->id,
            'qr_url' => 'https://example.com/qr/vt',
            'amount' => 10000,
            'votes_earned' => 42,
            'voter_name' => 'Donatur Uji',
            'status' => 'PAID',
            'paid_at' => now(),
        ]);

        $this->get('/event/' . $eventner->slug . '/videotron?mode=vote')
            ->assertOk()
            ->assertSee('Klasemen Vote')
            ->assertSee('SMP Vote Terbanyak')
            ->assertSee('42');
    }

    public function test_videotron_mode_champion_hanya_is_public()
    {
        $eventner = $this->buatEvent();
        $kategori = CompetitionCategory::factory()->for($eventner, 'eventner')->create();

        ChampionCategory::create([
            'eventner_id' => $eventner->id,
            'name' => 'Juara Umum',
            'quantity' => 3,
            'is_public' => true,
        ]);
        ChampionCategory::create([
            'eventner_id' => $eventner->id,
            'name' => 'Juara Rahasia',
            'quantity' => 1,
            'is_public' => false,
        ]);

        $this->get('/event/' . $eventner->slug . '/videotron?mode=champion&categoryId=' . $kategori->id)
            ->assertOk()
            ->assertDontSee('Juara Rahasia');
    }

    public function test_videotron_mode_rundown_menampilkan_agenda()
    {
        $eventner = $this->buatEvent();

        EventRundown::create([
            'eventner_id' => $eventner->id,
            'title' => 'Pembukaan & Pawai',
            'start_time' => '08:00',
            'sort_order' => 1,
        ]);
        EventRundown::create([
            'eventner_id' => $eventner->id,
            'title' => 'Babak Penyisihan',
            'start_time' => '09:00',
            'sort_order' => 2,
        ]);

        $this->get('/event/' . $eventner->slug . '/videotron?mode=rundown')
            ->assertOk()
            ->assertSee('Pembukaan & Pawai')
            ->assertSee('Babak Penyisihan');
    }

    public function test_videotron_mode_sponsor_menggabungkan_platform_dan_event()
    {
        $eventner = $this->buatEvent();

        LandingPartner::factory()->create(['name' => 'PT Sponsor Platform']);
        \App\Models\Sponsor::create([
            'eventner_id' => $eventner->id,
            'name' => 'PT Sponsor Event Uji',
            'type' => 'sponsor',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->get('/event/' . $eventner->slug . '/videotron?mode=sponsor')
            ->assertOk()
            ->assertSee('PT Sponsor Platform')
            ->assertSee('PT Sponsor Event Uji');
    }

    public function test_videotron_mode_loop_renders()
    {
        $eventner = $this->buatEvent();

        $this->get('/event/' . $eventner->slug . '/videotron?mode=loop')
            ->assertOk()
            ->assertSee('Festival Videotron Uji');
    }

    public function test_videotron_mode_champion_menampilkan_juara_dari_cache()
    {
        $eventner = $this->buatEvent();
        $kategori = CompetitionCategory::factory()->for($eventner, 'eventner')->create();
        $juara = ChampionCategory::create([
            'eventner_id' => $eventner->id,
            'name' => 'Juara Umum',
            'quantity' => 3,
            'is_public' => true,
        ]);
        $reg = Registration::factory()->for($eventner, 'eventner')->create([
            'competition_category_id' => $kategori->id,
            'nama_sekolah' => 'SMA Juara Satu',
        ]);

        // Perhitungan /champions membaca cache ini; videotron ikut pakai.
        cache()->put(
            "champions:{$eventner->id}:cat:{$kategori->id}:group:",
            [[
                'champion' => $juara,
                'rankTitles' => [],
                'participants' => [
                    ['rank' => 1, 'title' => 'Juara Satu', 'participant' => $reg, 'total' => 95.5],
                ],
            ]],
            300
        );

        $this->get('/event/' . $eventner->slug . '/videotron?mode=champion&categoryId=' . $kategori->id)
            ->assertOk()
            ->assertSee('SMA Juara Satu')
            ->assertSee('Juara Umum');
    }

    public function test_videotron_mode_champion_cache_rusak_tetap_tampil()
    {
        // Regresi: firstWhere('champion.id', ...) di produksi memicu warning
        // via data_get dan layar 500. Struktur janggal harus dilewati.
        $eventner = $this->buatEvent();
        $kategori = CompetitionCategory::factory()->for($eventner, 'eventner')->create();
        ChampionCategory::create([
            'eventner_id' => $eventner->id,
            'name' => 'Juara Umum',
            'quantity' => 3,
            'is_public' => true,
        ]);

        cache()->put(
            "champions:{$eventner->id}:cat:{$kategori->id}:group:",
            [
                null,
                'string-biasa',
                42,
                ['champion' => 'bukan-model', 'participants' => null],
                ['champion' => null, 'participants' => [['rank' => 1]]],
            ],
            300
        );

        $this->get('/event/' . $eventner->slug . '/videotron?mode=champion&categoryId=' . $kategori->id)
            ->assertOk()
            ->assertSee('Belum Ada Juara');
    }

    public function test_videotron_event_tidak_disetujui_gagal()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'pending',
            'slug' => 'videotron-pending',
        ]);

        $this->get('/event/' . $eventner->slug . '/videotron')->assertNotFound();
    }
}
