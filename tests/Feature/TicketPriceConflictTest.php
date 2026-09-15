<?php

namespace Tests\Feature;

use App\Livewire\Eventner\Ticket\Settings;
use App\Models\Eventner;
use App\Models\EventnerVenue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Bentrok harga tiket antara /eventner/venues dan /eventner/tickets/settings.
 *
 * Harga per tempat menang atas harga event (EventnerVenue::effectiveTicketPrice),
 * tapi halaman Pengaturan Tiket memperlakukan `ticket_price` sebagai satu-satunya
 * harga: wajib diisi walau semua tempat sudah punya harga sendiri, dan angkanya
 * dihapus saat tiket dimatikan — padahal tempat tanpa harga memakainya sebagai
 * fallback.
 *
 * Yang diuji di sini adalah perjanjian barunya: harga event = default yang hanya
 * dibaca tempat tanpa harga, dan halaman Pengaturan Tiket menampilkan harga yang
 * benar-benar dibayar pembeli.
 */
class TicketPriceConflictTest extends TestCase
{
    use RefreshDatabase;

    private function eventner(array $atribut = []): Eventner
    {
        return Eventner::factory()->paid()->create(array_merge([
            'ticket_active' => true,
            'ticket_price' => 50000,
        ], $atribut));
    }

    private function komponen(Eventner $eventner)
    {
        $this->actingAs($eventner->user);

        return Livewire::test(Settings::class);
    }

    private function venue(Eventner $eventner, array $atribut = []): EventnerVenue
    {
        return EventnerVenue::factory()->create(array_merge([
            'eventner_id' => $eventner->id,
            'is_active' => true,
        ], $atribut));
    }

    /** Harga default tidak wajib bila semua tempat sudah punya harga sendiri. */
    public function test_harga_default_tidak_wajib_saat_semua_tempat_sudah_berharga()
    {
        $eventner = $this->eventner();
        $this->venue($eventner, ['ticket_price' => 35000]);
        $this->venue($eventner, ['ticket_price' => 40000]);

        $this->komponen($eventner)
            ->set('ticket_price', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($eventner->fresh()->ticket_price);
    }

    /** Tanpa tempat sama sekali, harga default satu-satunya harga — tetap wajib. */
    public function test_harga_default_wajib_saat_belum_ada_tempat()
    {
        $eventner = $this->eventner();

        $this->komponen($eventner)
            ->set('ticket_price', '')
            ->call('save')
            ->assertHasErrors('ticket_price');
    }

    /** Satu tempat belum diisi = masih ada yang membaca harga default → wajib. */
    public function test_harga_default_wajib_saat_ada_tempat_tanpa_harga()
    {
        $eventner = $this->eventner();
        $this->venue($eventner, ['ticket_price' => 35000]);
        $this->venue($eventner, ['ticket_price' => null, 'ticket_kuota' => 100]);

        $this->komponen($eventner)
            ->set('ticket_price', '')
            ->call('save')
            ->assertHasErrors('ticket_price');
    }

    /**
     * Mematikan tiket tidak boleh menghapus harga default — tempat yang belum
     * punya harga memakainya sebagai fallback saat tiket dinyalakan lagi.
     */
    public function test_harga_default_tidak_dihapus_saat_tiket_dimatikan()
    {
        $eventner = $this->eventner();
        $this->venue($eventner, ['ticket_kuota' => 100]);

        $this->komponen($eventner)
            ->set('ticket_active', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals(50000, $eventner->fresh()->ticket_price);
    }

    /** Halaman Pengaturan menampilkan harga yang benar-benar dibayar pembeli. */
    public function test_halaman_menampilkan_harga_per_tempat()
    {
        $eventner = $this->eventner();
        $this->venue($eventner, ['name' => 'SMA 1', 'ticket_price' => 50000]);
        $this->venue($eventner, ['name' => 'SMA 2', 'ticket_price' => 35000]);

        $html = $this->komponen($eventner)->html();

        $this->assertStringContainsString('Harga per Tempat Pelaksanaan', $html);
        $this->assertStringContainsString('SMA 2', $html);
        // Dua harga berbeda = harga seragam 50.000 tidak berlaku untuk semua.
        $this->assertStringContainsString('Rp 35.000', $html);
        $this->assertStringContainsString('menimpa default', $html);
    }

    /** Tempat tanpa harga ditandai mengikuti default, bukan disembunyikan. */
    public function test_tempat_tanpa_harga_ditandai_ikut_default()
    {
        $eventner = $this->eventner();
        $this->venue($eventner, ['name' => 'SMA 3', 'ticket_price' => null, 'ticket_kuota' => 50]);

        $this->komponen($eventner)
            ->assertSee('SMA 3')
            ->assertSee('Ikut harga default');
    }
}
