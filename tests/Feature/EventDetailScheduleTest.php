<?php

namespace Tests\Feature;

use App\Models\Eventner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ringkasan jadwal & lokasi di halaman detail event publik.
 *
 * Dulu medan ini terpecah: hari H, lokasi, tingkat, dan kuota ada di kartu
 * ringkasan atas; sedangkan technical meeting, batas pendaftaran, hitung
 * mundurnya, dan peta ada di kartu "Informasi Jadwal" di sidebar. Di mobile
 * sidebar menumpuk paling bawah, jadi batas pendaftaran justru muncul terakhir
 * — padahal itu yang paling menentukan keputusan mendaftar.
 *
 * Kartu sidebar itu sudah dilebur ke kartu ringkasan. Tes di sini menjaga
 * medannya tidak hilang lagi dan tidak ikut tergandakan.
 */
class EventDetailScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_ringkasan_menampilkan_hari_h_tm_batas_pendaftaran_dan_lokasi()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'tanggal' => '2026-10-18',
            'lokasi' => 'GOR Rukibra',
            'technical_meeting' => '2026-10-15 08:00:00',
            'tanggal_pendaftaran' => '2026-10-10',
        ]);

        $this->get("/event/{$eventner->slug}")
            ->assertStatus(200)
            ->assertSee('18 Oktober 2026', false)
            ->assertSee('GOR Rukibra', false)
            ->assertSee('Technical Meeting', false)
            ->assertSee('15 Oktober 2026, 08:00', false)
            ->assertSee('Batas Pendaftaran', false)
            ->assertSee('10 Oktober 2026', false);
    }

    public function test_hitung_mundur_pendaftaran_muncul_sekali_saja()
    {
        // Dua hitung mundur di satu halaman (hari H dan batas pendaftaran)
        // membingungkan; labelnya harus unik.
        //
        // Tanggalnya relatif terhadap sekarang, bukan tanggal mati: tes ini
        // dulu bertanggal tetap, dan begitu hari itu lewat ia berhenti menguji
        // hitung mundurnya tanpa gagal — hitung mundurnya berganti jadi baris
        // "telah ditutup", dan substr_count-nya tetap 0 == 0.
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'tanggal' => now()->addDays(30)->toDateString(),
            'tanggal_pendaftaran' => now()->addDays(10)->toDateString(),
        ]);

        $html = $this->get("/event/{$eventner->slug}")->assertStatus(200)->getContent();

        $this->assertSame(1, substr_count($html, 'Pendaftaran Ditutup Dalam'));
    }

    /**
     * Deadline lewat: hitung mundurnya berhenti, labelnya ikut berubah.
     *
     * Selama ini empat selnya cuma berhenti di "00" sambil tetap berbunyi
     * "Ditutup Dalam" — angka nol yang menunggu selamanya, dan pembaca
     * menyimpulkan masih ada waktu. Yang benar adalah kebalikannya.
     *
     * Tanggal `tanggal` sengaja juga di masa lalu supaya hitung mundur hari H
     * tak ikut dirender; tes ini lalu bisa menuntut halaman itu tidak memuat
     * satu pun `countdown(` — bukan sekadar tidak memuat labelnya.
     */
    public function test_deadline_terlewat_menampilkan_pendaftaran_telah_ditutup()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'tanggal' => now()->subDays(5)->toDateString(),
            'tanggal_pendaftaran' => now()->subDays(10)->toDateString(),
        ]);

        $html = $this->get("/event/{$eventner->slug}")->assertStatus(200)->getContent();

        $this->assertStringContainsString('Pendaftaran Telah Ditutup', $html);
        $this->assertStringNotContainsString('Pendaftaran Ditutup Dalam', $html);
        $this->assertStringNotContainsString('countdown(', $html);
        $this->assertStringContainsString(
            'Batas pendaftaran ' . now()->subDays(10)->translatedFormat('d F Y'),
            $html
        );
    }

    public function test_tanpa_tanggal_pendaftaran_tidak_ada_hitung_mundur()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'tanggal' => '2026-10-18',
            'tanggal_pendaftaran' => null,
        ]);

        $this->get("/event/{$eventner->slug}")
            ->assertStatus(200)
            ->assertDontSee('Pendaftaran Ditutup Dalam', false)
            ->assertDontSee('Batas Pendaftaran', false);
    }

    public function test_peta_muncul_hanya_saat_koordinat_diisi()
    {
        $tanpa = Eventner::factory()->create(['status' => 'approved', 'latitude' => null, 'longitude' => null]);
        $this->get("/event/{$tanpa->slug}")->assertStatus(200)->assertDontSee('output=embed', false);

        $dengan = Eventner::factory()->create(['status' => 'approved', 'latitude' => -6.2, 'longitude' => 106.8]);
        $this->get("/event/{$dengan->slug}")
            ->assertStatus(200)
            ->assertSee('output=embed', false)
            ->assertSee('-6.2,106.8', false);
    }
}
