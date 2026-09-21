<?php

namespace Tests\Feature;

use App\Livewire\Public\EventDetail;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Papan "Voter Tertinggi per Kategori" di halaman publik event.
 *
 * Papan itu dulu muncul cukup dengan terisinya `vote_start` — event yang fitur
 * votingnya dimatikan tetap menampilkan juara berisi angka nol, plus tombol
 * "Vote Kategori Ini" yang menuntun ke halaman voting yang sudah mati. Tes di
 * sini mengunci `vote_active` sebagai satu-satunya penentu pertama.
 */
class EventDetailVoteVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private const JUDUL = 'Voter Tertinggi per Kategori';

    /**
     * Event + satu kategori TINGKAT (anak) berisi satu pendaftar.
     *
     * Kategori tingkat wajib ada: `voteLeaderboard()` hanya menghitung kategori
     * ber-`parent_id`, jadi event tanpa tingkat selalu menghasilkan papan kosong
     * dan tidak bisa dipakai membedakan kedua keadaan.
     */
    private function eventDenganPeserta(array $atribut = []): Eventner
    {
        $eventner = Eventner::factory()->create(['status' => 'approved'] + $atribut);

        $induk = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => null,
        ]);

        $tingkat = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => $induk->id,
        ]);

        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $tingkat->id,
            'status_berkas' => 'Terverifikasi',
        ]);

        return $eventner;
    }

    public function test_papan_voter_hilang_saat_vote_dimatikan_walau_jadwalnya_terisi()
    {
        // Inilah bug lamanya: `vote_start` sudah lewat dan `vote_end` belum,
        // tapi sakelar utamanya OFF. Sebelum diperbaiki, halaman ini tetap
        // menampilkan papan juara berisi 0 suara.
        $eventner = $this->eventDenganPeserta([
            'vote_active' => false,
            'vote_start' => now()->subDay(),
            'vote_end' => now()->addWeek(),
        ]);

        $this->get("/event/{$eventner->slug}")
            ->assertStatus(200)
            ->assertDontSee(self::JUDUL, false);
    }

    public function test_papan_voter_muncul_saat_vote_menyala_dan_jadwalnya_berjalan()
    {
        // Kontra-uji: tanpa ini, "hilang" bisa berarti halaman gagal render
        // sama sekali, bukan papan yang disembunyikan.
        $eventner = $this->eventDenganPeserta([
            'vote_active' => true,
            'vote_start' => now()->subDay(),
            'vote_end' => now()->addWeek(),
        ]);

        $this->get("/event/{$eventner->slug}")
            ->assertStatus(200)
            ->assertSee(self::JUDUL, false);
    }

    public function test_papan_voter_tersembunyi_sebelum_jadwal_vote_dimulai()
    {
        $eventner = $this->eventDenganPeserta([
            'vote_active' => true,
            'vote_start' => now()->addWeek(),
            'vote_end' => now()->addWeeks(2),
        ]);

        $this->get("/event/{$eventner->slug}")
            ->assertStatus(200)
            ->assertDontSee(self::JUDUL, false);
    }

    /** Komponennya sendiri yang dijaga, bukan cuma halaman lengkapnya. */
    public function test_komponen_mengembalikan_papan_kosong_saat_vote_dimatikan()
    {
        $eventner = $this->eventDenganPeserta([
            'vote_active' => false,
            'vote_start' => now()->subDay(),
        ]);

        $komponen = new EventDetail;
        $komponen->eventner = $eventner;

        $this->assertCount(0, $komponen->voteLeaderboard());
    }
}
