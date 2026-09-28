<?php

namespace Tests\Feature;

use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\EventnerVenue;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kartu "Kategori Lomba" di halaman detail event publik.
 *
 * Sebelumnya kartu ini hanya menampilkan bilah kuota. Pendaftar yang mau
 * memilih tingkat lomba tidak bisa melihat biaya, tempat, tanggal, dan juri
 * dari halaman ini — padahal itu semua yang menentukan keputusan mendaftar.
 * Tes di sini mengunci tiap medan supaya tidak hilang lagi saat kartunya
 * dirapikan.
 */
class EventDetailCategoryInfoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Event + satu JENIS lomba berisi satu TINGKAT, lengkap dengan venue,
     * biaya, tanggal, dan juri.
     *
     * @return array{eventner: Eventner, tingkat: CompetitionCategory}
     */
    private function eventLengkap(int $kuota = 10, int $terisi = 2): array
    {
        $eventner = Eventner::factory()->create(['status' => 'approved']);

        $venue = EventnerVenue::factory()->create([
            'eventner_id' => $eventner->id,
            'name' => 'GOR Rukibra',
            'alamat' => 'Jl. Melati No. 3',
        ]);

        $induk = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => null,
            'name' => 'LOBB',
        ]);

        $tingkat = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => $induk->id,
            'name' => 'Tingkat Kelas 9',
            'kuota' => $kuota,
            'registration_fee' => 150000,
            'tanggal_pelaksanaan' => '2026-10-18',
            'venue_id' => $venue->id,
            'max_registrations_per_school' => 2,
        ]);

        for ($i = 0; $i < $terisi; $i++) {
            Registration::factory()->create([
                'eventner_id' => $eventner->id,
                'competition_category_id' => $tingkat->id,
                'status_berkas' => 'Terverifikasi',
            ]);
        }

        return ['eventner' => $eventner, 'tingkat' => $tingkat];
    }

    public function test_kartu_kategori_menampilkan_biaya_tempat_tanggal_dan_batas_sekolah()
    {
        ['eventner' => $eventner] = $this->eventLengkap();

        $this->get("/event/{$eventner->slug}")
            ->assertStatus(200)
            ->assertSee('Kategori Lomba', false)
            ->assertSee('Tingkat Kelas 9', false)
            ->assertSee('Rp 150.000', false)
            ->assertSee('GOR Rukibra', false)
            ->assertSee('Jl. Melati No. 3', false)
            ->assertSee('18 Oktober 2026', false)
            ->assertSee('Maks. 2 pasukan', false);
    }

    public function test_kartu_menampilkan_sisa_kuota_dan_jumlah_pendaftar()
    {
        ['eventner' => $eventner] = $this->eventLengkap(kuota: 10, terisi: 2);

        $this->get("/event/{$eventner->slug}")
            ->assertStatus(200)
            ->assertSee('Sisa 8 Slot', false)
            ->assertSee('2 / 10 Pasukan', false);
    }

    public function test_kuota_penuh_ditandai()
    {
        ['eventner' => $eventner] = $this->eventLengkap(kuota: 3, terisi: 3);

        $this->get("/event/{$eventner->slug}")
            ->assertStatus(200)
            ->assertSee('Kuota Penuh', false)
            ->assertSee('3 / 3 Pasukan', false);
    }

    public function test_pendaftar_yang_dibatalkan_tidak_ikut_menghitung_kuota()
    {
        ['eventner' => $eventner, 'tingkat' => $tingkat] = $this->eventLengkap(kuota: 10, terisi: 2);

        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $tingkat->id,
            'status_berkas' => 'dibatalkan',
        ]);

        $this->get("/event/{$eventner->slug}")
            ->assertStatus(200)
            ->assertSee('2 / 10 Pasukan', false);
    }

    public function test_tingkat_tanpa_biaya_ditandai_gratis_dan_tanpa_kuota_tanpa_batas()
    {
        $eventner = Eventner::factory()->create(['status' => 'approved']);
        $induk = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => null,
        ]);
        CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => $induk->id,
            'kuota' => null,
            'registration_fee' => null,
        ]);

        $this->get("/event/{$eventner->slug}")
            ->assertStatus(200)
            ->assertSee('Gratis', false)
            ->assertSee('Tanpa Batas', false)
            ->assertSee('0 / ∞ Pasukan', false);
    }

    public function test_kategori_induk_tanpa_tingkat_tetap_tampil_sebagai_kartu()
    {
        // Data lama sebelum hierarki: satu kategori flat tanpa anak. Kartunya
        // harus tetap muncul, bukan hilang karena tidak punya tingkat.
        $eventner = Eventner::factory()->create(['status' => 'approved']);
        CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => null,
            'name' => 'PBB Kreasi',
            'kuota' => 5,
        ]);

        $this->get("/event/{$eventner->slug}")
            ->assertStatus(200)
            ->assertSee('PBB Kreasi', false)
            ->assertSee('0 / 5 Pasukan', false);
    }
}
