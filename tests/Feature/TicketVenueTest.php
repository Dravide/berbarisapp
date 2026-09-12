<?php

namespace Tests\Feature;

use App\Exceptions\TicketQuotaExceededException;
use App\Livewire\Public\Checkin\Scan;
use App\Livewire\Public\EventTicket;
use App\Models\Eventner;
use App\Models\EventnerVenue;
use App\Models\Ticket;
use App\Services\TicketQuota;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Satu tiket = satu tempat: harga per tempat, kuota ditegakkan sistem,
 * check-in menolak tiket dari gerbang lain.
 */
class TicketVenueTest extends TestCase
{
    use RefreshDatabase;

    private function fakeAutoGoPay(): void
    {
        Http::fake([
            '*/qris/generate' => Http::response([
                'success' => true,
                'data' => [
                    'transaction_id' => 'AGP-VENUE-001',
                    'order_id' => 'ORD-VENUE',
                    'amount' => 50000,
                    'transaction_status' => 'pending',
                    'qr_string' => '000201010212',
                    'qr_url' => 'https://api.autogopay.id/qr/venue.png',
                    'transaction_time' => now()->toIso8601String(),
                    'expiry_time' => now()->addMinutes(5)->toIso8601String(),
                ],
            ], 200),
        ]);
    }

    /** Event dengan dua tempat berbeda harga — kasus SMA 1 vs SMA 2. */
    private function makeEventDuaTempat(array $eventAttrs = []): array
    {
        $event = Eventner::factory()->create(array_merge([
            'status' => 'approved',
            'ticket_active' => true,
            'ticket_price' => 50000,
        ], $eventAttrs));

        $smaSatu = EventnerVenue::factory()->create([
            'eventner_id' => $event->id,
            'name' => 'SMA 1',
            'is_active' => true,
            'ticket_price' => 50000,
            'ticket_kuota' => 200,
        ]);

        $smaDua = EventnerVenue::factory()->create([
            'eventner_id' => $event->id,
            'name' => 'SMA 2',
            'is_active' => true,
            'ticket_price' => 35000,
            'ticket_kuota' => 100,
        ]);

        return [$event, $smaSatu, $smaDua];
    }

    /** Pembeli harus bayar harga tempat, bukan harga event. */
    public function test_harga_tiket_mengikuti_tempat_yang_dipilih()
    {
        $this->fakeAutoGoPay();
        [$event, , $smaDua] = $this->makeEventDuaTempat();

        Livewire::test(EventTicket::class, ['slug' => $event->slug])
            ->set('venueId', $smaDua->id)
            ->set('buyerName', 'Budi')
            ->set('buyerEmail', 'budi@email.com')
            ->set('quantity', 2)
            ->call('submitTicket')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tickets', [
            'eventner_id' => $event->id,
            'venue_id' => $smaDua->id,
            'price_per_ticket' => 35000,
            'total_amount' => 70000,
        ]);
    }

    /** venueId datang dari klien — tempat milik event lain harus ditolak. */
    public function test_tempat_milik_event_lain_ditolak()
    {
        $this->fakeAutoGoPay();
        [$event] = $this->makeEventDuaTempat();

        $eventLain = Eventner::factory()->create(['status' => 'approved', 'ticket_active' => true, 'ticket_price' => 50000]);
        $venueAsing = EventnerVenue::factory()->create([
            'eventner_id' => $eventLain->id,
            'ticket_price' => 10000,
            'ticket_kuota' => 50,
        ]);

        Livewire::test(EventTicket::class, ['slug' => $event->slug])
            ->set('venueId', $venueAsing->id)
            ->set('buyerName', 'Budi')
            ->set('buyerEmail', 'budi@email.com')
            ->set('quantity', 1)
            ->call('submitTicket')
            ->assertHasErrors('venueId');

        $this->assertSame(0, Ticket::where('eventner_id', $event->id)->count());
        Http::assertNothingSent();
    }

    /** Kuota ditegakkan: beli 3 di tempat berkuota 2 harus ditolak. */
    public function test_kuota_tempat_ditegakkan_di_jalur_api()
    {
        $this->fakeAutoGoPay();
        [$event, , $smaDua] = $this->makeEventDuaTempat();
        $smaDua->update(['ticket_kuota' => 2]);

        $this->postJson('/api/v1/ticket/purchase', [
            'event_slug' => $event->slug,
            'venue_id' => $smaDua->id,
            'buyer_name' => 'Budi',
            'buyer_email' => 'budi@email.com',
            'quantity' => 3,
        ])->assertStatus(400);

        $this->assertSame(0, Ticket::where('venue_id', $smaDua->id)->count());
        Http::assertNothingSent();
    }

    /** PENDING menahan slot: kuota 2 habis oleh transaksi belum lunas. */
    public function test_pending_ikut_menahan_slot_kuota()
    {
        $this->fakeAutoGoPay();
        [$event, , $smaDua] = $this->makeEventDuaTempat();
        $smaDua->update(['ticket_kuota' => 2]);

        $payload = [
            'event_slug' => $event->slug,
            'venue_id' => $smaDua->id,
            'buyer_name' => 'Budi',
            'buyer_email' => 'budi@email.com',
            'quantity' => 2,
        ];

        $this->postJson('/api/v1/ticket/purchase', $payload)->assertOk();

        RateLimiter::clear('api-ticket:127.0.0.1');

        // Sisa 0 — pembelian berikutnya ditolak walau tiket pertama belum lunas.
        $this->postJson('/api/v1/ticket/purchase', array_merge($payload, ['quantity' => 1]))
            ->assertStatus(400);

        $this->assertSame(2, (int) Ticket::where('venue_id', $smaDua->id)->sum('quantity'));
        $this->assertSame(0, $smaDua->fresh()->remainingTicketSlots());
    }

    /** Penjaga kuota adalah pintu terakhir, terpisah dari validasi form. */
    public function test_reserve_menolak_saat_sisa_kuota_kurang()
    {
        [$event, $smaSatu] = $this->makeEventDuaTempat();
        $smaSatu->update(['ticket_kuota' => 2]);

        Ticket::create([
            'eventner_id' => $event->id,
            'venue_id' => $smaSatu->id,
            'order_code' => 'TCK-HELD-1',
            'buyer_name' => 'Budi',
            'buyer_email' => 'budi@email.com',
            'quantity' => 2,
            'price_per_ticket' => 50000,
            'total_amount' => 100000,
            'status' => 'PENDING',
        ]);

        $this->expectException(TicketQuotaExceededException::class);

        try {
            TicketQuota::reserve($smaSatu->fresh(), 1, fn () => Ticket::create([
                'eventner_id' => $event->id,
                'order_code' => 'TCK-HELD-2',
                'buyer_name' => 'Siti',
                'buyer_email' => 'siti@email.com',
                'quantity' => 1,
                'total_amount' => 0,
                'status' => 'ACTIVE',
            ]));
        } catch (TicketQuotaExceededException $e) {
            $this->assertStringContainsString('tinggal 0 tiket', $e->getMessage());
            throw $e;
        }

        $this->assertSame(0, Ticket::where('order_code', 'TCK-HELD-2')->count());
    }

    /** Event satu tempat: pembeli tidak memilih, tiket tetap terisi tempatnya. */
    public function test_event_satu_tempat_mengisi_venue_id_otomatis()
    {
        $this->fakeAutoGoPay();

        $event = Eventner::factory()->create([
            'status' => 'approved',
            'ticket_active' => true,
            'ticket_price' => 25000,
        ]);

        $venue = EventnerVenue::factory()->create([
            'eventner_id' => $event->id,
            'name' => 'GOR Rukibra',
            'is_active' => true,
            'ticket_price' => 25000,
            'ticket_kuota' => 50,
        ]);

        Livewire::test(EventTicket::class, ['slug' => $event->slug])
            ->assertSet('venueId', $venue->id)
            ->set('buyerName', 'Budi')
            ->set('buyerEmail', 'budi@email.com')
            ->set('quantity', 1)
            ->call('submitTicket')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tickets', [
            'eventner_id' => $event->id,
            'venue_id' => $venue->id,
            'price_per_ticket' => 25000,
        ]);
    }

    /** Event tanpa tempat sama sekali: perilaku lama persis (venue_id NULL). */
    public function test_event_tanpa_tempat_membuat_tiket_tanpa_venue_id()
    {
        $this->fakeAutoGoPay();

        $event = Eventner::factory()->create([
            'status' => 'approved',
            'ticket_active' => true,
            'ticket_price' => 20000,
        ]);

        Livewire::test(EventTicket::class, ['slug' => $event->slug])
            ->assertSet('venueId', null)
            ->set('buyerName', 'Budi')
            ->set('buyerEmail', 'budi@email.com')
            ->set('quantity', 1)
            ->call('submitTicket')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tickets', [
            'eventner_id' => $event->id,
            'venue_id' => null,
            'price_per_ticket' => 20000,
        ]);
    }

    /** Harga tempat kosong → jatuh ke harga event, bukan nol. */
    public function test_harga_tempat_kosong_memakai_harga_event()
    {
        $this->fakeAutoGoPay();

        $event = Eventner::factory()->create([
            'status' => 'approved',
            'ticket_active' => true,
            'ticket_price' => 40000,
        ]);

        $venue = EventnerVenue::factory()->create([
            'eventner_id' => $event->id,
            'is_active' => true,
            'ticket_price' => null,
            'ticket_kuota' => 10,
        ]);

        Livewire::test(EventTicket::class, ['slug' => $event->slug])
            ->set('venueId', $venue->id)
            ->set('buyerName', 'Budi')
            ->set('buyerEmail', 'budi@email.com')
            ->set('quantity', 1)
            ->call('submitTicket')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tickets', [
            'venue_id' => $venue->id,
            'price_per_ticket' => 40000,
            'total_amount' => 40000,
        ]);
    }

    /**
     * Harga hanya di tempat, event.ticket_price kosong: halaman tiket harus
     * tetap hidup — dulu `!ticket_price` membuatnya 404.
     */
    public function test_harga_hanya_di_tempat_tidak_menutup_halaman_tiket()
    {
        $this->fakeAutoGoPay();

        [, $smaSatu, $smaDua] = $this->makeEventDuaTempat(['ticket_price' => null]);

        $this->get('/event/' . $smaSatu->eventner->slug . '/ticket')->assertOk();

        Livewire::test(EventTicket::class, ['slug' => $smaSatu->eventner->slug])
            ->set('venueId', $smaDua->id)
            ->assertSee('35.000')
            ->set('buyerName', 'Budi')
            ->set('buyerEmail', 'budi@email.com')
            ->set('quantity', 1)
            ->call('submitTicket')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tickets', [
            'venue_id' => $smaDua->id,
            'price_per_ticket' => 35000,
            'total_amount' => 35000,
        ]);
    }

    /** "Mulai dari" dihitung dari tempat berharga, bukan dari harga event 0. */
    public function test_harga_mulai_dari_mengabaikan_tempat_tanpa_harga()
    {
        [, $smaSatu] = $this->makeEventDuaTempat(['ticket_price' => 0]);

        // Tempat tanpa harga jatuh ke harga event (0) — tidak boleh jadi "Mulai Rp 0".
        $smaSatu->update(['ticket_price' => null]);

        $html = $this->get('/event/' . $smaSatu->eventner->slug . '/ticket')->assertOk()->getContent();

        $this->assertStringContainsString('Mulai Rp 35.000', $html);
        $this->assertStringNotContainsString('Mulai Rp 0', $html);
    }

    /** Menu "Tiket" muncul walau harga hanya diisi di tempat. */
    public function test_menu_tiket_muncul_saat_harga_hanya_di_tempat()
    {
        [, $smaSatu] = $this->makeEventDuaTempat(['ticket_price' => null]);

        $html = $this->get('/event/' . $smaSatu->eventner->slug)->assertOk()->getContent();

        // Event ber-subdomain memakai /tiket, tanpa subdomain /event/{slug}/ticket —
        // jadi alamatnya dibandingkan lewat helper yang sama dengan view.
        $this->assertStringContainsString(event_url($smaSatu->eventner, 'ticket'), $html);
    }

    /** venueId di query string yang tidak lagi dijual dibiarkan kosong. */
    public function test_venue_tidak_valid_di_query_string_tidak_mengarah_ke_tempat_lain()
    {
        [$event, $smaSatu, $smaDua] = $this->makeEventDuaTempat();
        $smaDua->update(['is_active' => false]);

        Livewire::withQueryParams(['venueId' => $smaDua->id])
            ->test(EventTicket::class, ['slug' => $event->slug])
            ->assertSet('venueId', null)
            ->assertSee($smaSatu->name);
    }

    /** Tempat penuh → pesan "sudah habis", bukan gagal QRIS. */
    public function test_tempat_penuh_memberi_pesan_habis_bukan_gagal_qris()
    {
        $this->fakeAutoGoPay();
        [$event, , $smaDua] = $this->makeEventDuaTempat();
        $smaDua->update(['ticket_kuota' => 1]);

        Ticket::create([
            'eventner_id' => $event->id,
            'venue_id' => $smaDua->id,
            'order_code' => 'TCK-FULL-1',
            'buyer_name' => 'Budi',
            'buyer_email' => 'budi@email.com',
            'quantity' => 1,
            'price_per_ticket' => 35000,
            'total_amount' => 35000,
            'status' => 'PENDING',
        ]);

        Livewire::test(EventTicket::class, ['slug' => $event->slug])
            ->set('venueId', $smaDua->id)
            ->set('buyerName', 'Siti')
            ->set('buyerEmail', 'siti@email.com')
            ->set('quantity', 1)
            ->call('submitTicket')
            ->assertSet('view', 'form')
            ->assertSee('sudah habis');

        // Tidak ada transaksi AutoGoPay yang dibuat untuk pembelian yang gagal.
        Http::assertNothingSent();
        $this->assertSame(1, Ticket::where('venue_id', $smaDua->id)->count());
    }

    /** Check-in menolak tiket tempat A di gerbang tempat B. */
    public function test_scan_menolak_tiket_dari_gerbang_lain()
    {
        [$event, $smaSatu, $smaDua] = $this->makeEventDuaTempat();

        $smaSatu->update(['checkin_token' => 'token-gerbang-sma-1']);
        $smaDua->update(['checkin_token' => 'token-gerbang-sma-2']);

        $ticket = Ticket::create([
            'eventner_id' => $event->id,
            'venue_id' => $smaSatu->id,
            'order_code' => 'TCK-GATE-1',
            'buyer_name' => 'Budi',
            'buyer_email' => 'budi@email.com',
            'quantity' => 1,
            'price_per_ticket' => 50000,
            'total_amount' => 50000,
            'status' => 'PAID',
        ]);

        // Gerbang SMA 2: tiket untuk SMA 1 harus ditolak.
        Livewire::test(Scan::class, ['token' => 'token-gerbang-sma-2'])
            ->assertSet('gateVenueName', 'SMA 2')
            ->call('lookupTicket', 'TCK-GATE-1')
            ->assertSet('result.kind', 'wrong_venue')
            ->call('confirmCheckIn', $ticket->id)
            ->assertSet('result.kind', 'wrong_venue');

        $this->assertSame('PAID', $ticket->fresh()->status);

        // Gerbang SMA 1: tiket yang benar lolos.
        Livewire::test(Scan::class, ['token' => 'token-gerbang-sma-1'])
            ->call('lookupTicket', 'TCK-GATE-1')
            ->assertSet('result.kind', 'ready')
            ->call('confirmCheckIn', $ticket->id)
            ->assertSet('result.kind', 'success');

        $this->assertSame('CHECKED_IN', $ticket->fresh()->status);
    }

    /** Tiket lama tanpa tempat tetap lolos di gerbang mana pun. */
    public function test_tiket_tanpa_tempat_lolos_di_gerbang_mana_pun()
    {
        [$event, $smaSatu] = $this->makeEventDuaTempat();
        $smaSatu->update(['checkin_token' => 'token-gerbang-sma-1']);

        $ticket = Ticket::create([
            'eventner_id' => $event->id,
            'venue_id' => null,
            'order_code' => 'TCK-LAMA-1',
            'buyer_name' => 'Budi',
            'buyer_email' => 'budi@email.com',
            'quantity' => 1,
            'total_amount' => 0,
            'status' => 'ACTIVE',
        ]);

        Livewire::test(Scan::class, ['token' => 'token-gerbang-sma-1'])
            ->call('lookupTicket', 'TCK-LAMA-1')
            ->assertSet('result.kind', 'ready')
            ->call('confirmCheckIn', $ticket->id)
            ->assertSet('result.kind', 'success');

        $this->assertSame('CHECKED_IN', $ticket->fresh()->status);
    }
}
