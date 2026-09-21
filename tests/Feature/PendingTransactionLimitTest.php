<?php

namespace Tests\Feature;

use App\Livewire\Public\EventTicket;
use App\Livewire\Public\EventVote;
use App\Models\Eventner;
use App\Models\EventnerVenue;
use App\Models\Registration;
use App\Models\Ticket;
use App\Models\VoteTransaction;
use App\Services\AutoGoPay;
use App\Services\PendingPaymentGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Satu email pembeli = maksimal satu QR hidup per alur.
 *
 * AutoGoPay bisa memblokir akun merchant kalau satu pembeli menumpuk QRIS
 * PENDING. Dulu tiap klik "Bayar" langsung memanggil generateQris() tanpa
 * memeriksa transaksi yang masih berjalan, dan tombol "Batal" hanya
 * membersihkan state Livewire — QR-nya tetap hidup di gateway.
 *
 * Penjaganya ada di App\Services\PendingPaymentGuard; tes ini menguji
 * keempat titik pemanggilnya (web vote, web tiket, API vote, API tiket).
 */
class PendingTransactionLimitTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Nomor urut transaksi gateway.
     *
     * `autogopay_transaction_id` unik di DB, jadi fake-nya harus memberi id
     * berbeda tiap panggilan — kalau tidak, tes "pembeli lain boleh membuat QR"
     * gagal karena bentrok unique, bukan karena penjaganya salah.
     */
    private int $nomorTrx = 0;

    private function fakeAutoGoPay(): void
    {
        Http::fake([
            '*/qris/generate' => function () {
                $this->nomorTrx++;
                $trxId = 'AGP-TEST-' . str_pad((string) $this->nomorTrx, 3, '0', STR_PAD_LEFT);

                return Http::response([
                    'success' => true,
                    'data' => [
                        'transaction_id' => $trxId,
                        'order_id' => 'ORD-' . $this->nomorTrx,
                        'amount' => 50000,
                        'transaction_status' => 'pending',
                        'qr_string' => '000201010212',
                        'qr_url' => 'https://api.autogopay.id/qr/' . $trxId . '.png',
                        'transaction_time' => now()->toIso8601String(),
                        'expiry_time' => now()->addMinutes(5)->toIso8601String(),
                    ],
                ], 200);
            },
            '*/qris/cancel' => Http::response([
                'success' => true,
                'data' => ['transaction_status' => 'cancel'],
            ], 200),
        ]);
    }

    /** Eventner dengan voting menyala + satu peserta untuk divote. */
    private function eventVote(): array
    {
        $eventner = Eventner::factory()->voteAktif()->create([
            'status' => 'approved',
            'vote_price' => 1000,
        ]);

        $reg = Registration::factory()->for($eventner, 'eventner')->create([
            'nama_sekolah' => 'SMP Penjaga',
        ]);

        return [$eventner, $reg];
    }

    /** Eventner dengan tiket menyala + satu tempat berkuota. */
    private function eventTiket(int $kuota = 50): array
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'ticket_active' => true,
            'ticket_price' => 50000,
        ]);

        $venue = EventnerVenue::factory()->create([
            'eventner_id' => $eventner->id,
            'name' => 'SMA Penjaga',
            'is_active' => true,
            'ticket_price' => 50000,
            'ticket_kuota' => $kuota,
        ]);

        return [$eventner, $venue];
    }

    /** Tekan Bayar sekali di halaman vote. */
    private function bayarVote(Eventner $eventner, Registration $reg, string $email): \Livewire\Features\SupportTesting\Testable
    {
        return Livewire::test(EventVote::class, ['slug' => $eventner->slug])
            ->set('selectedCategoryId', $reg->competition_category_id)
            ->set('selectedRegistrationId', $reg->id)
            ->set('voterName', 'Pembeli')
            ->set('voterEmail', $email)
            ->set('voteCount', 10)
            ->call('submitVote');
    }

    // ────────────────────────────────────────────────
    // Web — vote
    // ────────────────────────────────────────────────

    /**
     * Klik "Bayar" kedua tidak boleh menambah QR baru di gateway. Ini inti
     * permintaannya: satu email = satu QR hidup.
     */
    public function test_klik_bayar_kedua_tidak_membuat_transaksi_baru()
    {
        $this->fakeAutoGoPay();
        [$eventner, $reg] = $this->eventVote();

        $this->bayarVote($eventner, $reg, 'pembeli@example.com');
        $this->bayarVote($eventner, $reg, 'pembeli@example.com');

        Http::assertSentCount(1);
        $this->assertSame(1, VoteTransaction::where('eventner_id', $eventner->id)->count());
    }

    /** Klik kedua menampilkan QR lama, bukan kotak kosong atau QR baru. */
    public function test_klik_bayar_kedua_menampilkan_qr_lama()
    {
        $this->fakeAutoGoPay();
        [$eventner, $reg] = $this->eventVote();

        $pertama = $this->bayarVote($eventner, $reg, 'pembeli@example.com');
        $qrPertama = $pertama->get('qrImageUrl');

        $kedua = $this->bayarVote($eventner, $reg, 'pembeli@example.com');

        $this->assertSame($qrPertama, $kedua->get('qrImageUrl'));
        $this->assertTrue($kedua->get('reusedExisting'));
        $this->assertSame('payment', $kedua->get('view'));
    }

    /**
     * Label "N transaksi × harga" dibaca dari state, jadi jumlahnya harus
     * disamakan dengan baris lama — kalau tidak, QR bernilai 3 vote tertulis
     * sebagai 10 di layar pembeli.
     */
    public function test_klik_kedua_menyamakan_jumlah_dengan_qr_lama()
    {
        $this->fakeAutoGoPay();
        [$eventner, $reg] = $this->eventVote();

        $this->bayarVote($eventner, $reg, 'pembeli@example.com');

        $kedua = Livewire::test(EventVote::class, ['slug' => $eventner->slug])
            ->set('selectedCategoryId', $reg->competition_category_id)
            ->set('selectedRegistrationId', $reg->id)
            ->set('voterName', 'Pembeli')
            ->set('voterEmail', 'pembeli@example.com')
            ->set('voteCount', 3)
            ->call('submitVote');

        // voteCount disamakan dengan baris lama (10), bukan yang baru diketik (3).
        $this->assertSame(10, (int) $kedua->get('voteCount'));
        $this->assertSame(10000, (int) $kedua->get('paymentAmount'));
    }

    /** Pembeli lain tidak ikut terhalang — kuncinya per email, bukan per event. */
    public function test_email_beda_tetap_boleh_membuat_qr()
    {
        $this->fakeAutoGoPay();
        [$eventner, $reg] = $this->eventVote();

        $this->bayarVote($eventner, $reg, 'pertama@example.com');
        $kedua = $this->bayarVote($eventner, $reg, 'kedua@example.com');

        Http::assertSentCount(2);
        $this->assertFalse($kedua->get('reusedExisting'));
        $this->assertSame(2, VoteTransaction::where('eventner_id', $eventner->id)->count());
    }

    /**
     * Huruf besar/kecil tidak boleh jadi celah: kunci dibandingkan setelah
     * dinormalkan, jadi "Budi@Email.com" menemukan baris "budi@email.com".
     */
    public function test_email_besar_kecil_dianggap_sama()
    {
        $this->fakeAutoGoPay();
        [$eventner, $reg] = $this->eventVote();

        $this->bayarVote($eventner, $reg, 'budi@email.com');
        $kedua = $this->bayarVote($eventner, $reg, 'BUDI@Email.COM');

        Http::assertSentCount(1);
        $this->assertTrue($kedua->get('reusedExisting'));
        $this->assertSame(1, VoteTransaction::where('eventner_id', $eventner->id)->count());
    }

    /**
     * Lewat tenggat bayar → pembeli boleh membuat QR baru. Tanpa ini baris
     * yang webhook kedaluwarsanya hilang memblokir pemiliknya selamanya.
     */
    public function test_transaksi_lewat_tenggat_boleh_dibuat_ulang()
    {
        $this->fakeAutoGoPay();
        [$eventner, $reg] = $this->eventVote();

        $this->bayarVote($eventner, $reg, 'pembeli@example.com');

        // Tenggat sudah lewat, tapi belum lewat tenggang — baris masih PENDING
        // supaya jalur pulih EXPIRED → PAID tetap bisa mengklaimnya.
        VoteTransaction::where('eventner_id', $eventner->id)->update([
            'payable_until' => now()->subMinute(),
        ]);

        $kedua = $this->bayarVote($eventner, $reg, 'pembeli@example.com');

        Http::assertSentCount(2);
        $this->assertFalse($kedua->get('reusedExisting'));
        $this->assertSame('PENDING', VoteTransaction::find($kedua->get('currentTransactionId'))->status);
    }

    /**
     * Lewat tenggat + tenggang → sweepExpired() menandai EXPIRED, jadi baris
     * itu berhenti menahan emailnya dan berhenti menahan kuota tempat.
     */
    public function test_transaksi_lewat_grace_ditandai_expired()
    {
        $this->fakeAutoGoPay();
        [$eventner, $reg] = $this->eventVote();

        $this->bayarVote($eventner, $reg, 'pembeli@example.com');

        $tx = VoteTransaction::where('eventner_id', $eventner->id)->first();
        $this->assertSame('PENDING', $tx->status);

        $tx->forceFill(['payable_until' => now()->subHours(PendingPaymentGuard::GRACE_HOURS + 1)])->save();

        $this->assertSame(1, PendingPaymentGuard::sweepExpired());
        $this->assertSame('EXPIRED', $tx->fresh()->status);
    }

    /** Baris yang masih di dalam tenggat tidak boleh ikut disapu. */
    public function test_sapuan_tidak_menyentuh_baris_yang_masih_hidup()
    {
        $this->fakeAutoGoPay();
        [$eventner, $reg] = $this->eventVote();

        $this->bayarVote($eventner, $reg, 'pembeli@example.com');

        $this->assertSame(0, PendingPaymentGuard::sweepExpired());

        $tx = VoteTransaction::where('eventner_id', $eventner->id)->first();
        $this->assertSame('PENDING', $tx->fresh()->status);
    }

    /** Membatalkan benar-benar membatalkan QR di gateway + melepas barisnya. */
    public function test_batalkan_membatalkan_qr_di_gateway()
    {
        $this->fakeAutoGoPay();
        [$eventner, $reg] = $this->eventVote();

        $this->bayarVote($eventner, $reg, 'pembeli@example.com')
            ->call('resetPayment');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'qris/cancel')
            && $request['transaction_id'] === 'AGP-TEST-001');

        // FAILED sudah melepas gerbangnya — find() hanya mencari PENDING.
        $tx = VoteTransaction::where('eventner_id', $eventner->id)->first();
        $this->assertSame('FAILED', $tx->status);
    }

    /** Setelah dibatalkan, emailnya bebas lagi — QR baru boleh terbit. */
    public function test_setelah_dibatalkan_boleh_membuat_qr_baru()
    {
        $this->fakeAutoGoPay();
        [$eventner, $reg] = $this->eventVote();

        $this->bayarVote($eventner, $reg, 'pembeli@example.com')->call('resetPayment');

        $kedua = $this->bayarVote($eventner, $reg, 'pembeli@example.com');

        // generate, cancel, generate — dua QR berbeda dibuat, satu dibatalkan.
        Http::assertSentCount(3);
        $this->assertFalse($kedua->get('reusedExisting'));
        $this->assertSame(2, VoteTransaction::where('eventner_id', $eventner->id)->count());
    }

    /**
     * Gateway menolak membatalkan → jangan tutup layarnya. QR-nya masih bisa
     * dibayar; menutup layar hanya menyembunyikan masalah dan menahan pembeli
     * tanpa penjelasan.
     */
    public function test_gateway_menolak_batal_state_tidak_dibersihkan()
    {
        Http::fake([
            '*/qris/generate' => Http::response([
                'success' => true,
                'data' => [
                    'transaction_id' => 'AGP-TEST-001',
                    'qr_url' => 'https://api.autogopay.id/qr/test.png',
                    'qr_string' => '000201010212',
                    'expiry_time' => now()->addMinutes(5)->toIso8601String(),
                ],
            ], 200),
            '*/qris/cancel' => Http::response(['success' => false, 'message' => 'gagal'], 500),
        ]);

        [$eventner, $reg] = $this->eventVote();

        $komponen = $this->bayarVote($eventner, $reg, 'pembeli@example.com')
            ->call('resetPayment');

        $this->assertSame('payment', $komponen->get('view'));
        $this->assertNotNull($komponen->get('qrImageUrl'));
        $this->assertSame('PENDING', VoteTransaction::where('eventner_id', $eventner->id)->first()->status);
    }

    // ────────────────────────────────────────────────
    // Web — tiket
    // ────────────────────────────────────────────────

    /**
     * Pemakaian ulang TIDAK boleh menyentuh kuota tempat: tidak ada tiket baru
     * yang dibuat, jadi memanggil TicketQuota::reserve() akan memotong kuota
     * dua kali untuk satu pembelian.
     */
    public function test_klik_bayar_kedua_tidak_menahan_kuota_dua_kali()
    {
        $this->fakeAutoGoPay();
        [$eventner, $venue] = $this->eventTiket(kuota: 10);

        $bayar = fn () => Livewire::test(EventTicket::class, ['slug' => $eventner->slug])
            ->set('venueId', $venue->id)
            ->set('buyerName', 'Budi')
            ->set('buyerEmail', 'budi@email.com')
            ->set('quantity', 2)
            ->call('submitTicket');

        $bayar();
        $kedua = $bayar();

        Http::assertSentCount(1);
        $this->assertTrue($kedua->get('reusedExisting'));
        $this->assertSame(1, Ticket::where('eventner_id', $eventner->id)->count());
        $this->assertSame(2, $venue->fresh()->ticketsSoldCount());
    }

    /** Rincian di kartu bayar diambil dari baris lama, bukan dari form. */
    public function test_klik_kedua_tiket_menyamakan_rincian_dengan_qr_lama()
    {
        $this->fakeAutoGoPay();
        [$eventner, $venue] = $this->eventTiket(kuota: 10);

        Livewire::test(EventTicket::class, ['slug' => $eventner->slug])
            ->set('venueId', $venue->id)
            ->set('buyerName', 'Budi')
            ->set('buyerEmail', 'budi@email.com')
            ->set('quantity', 2)
            ->call('submitTicket');

        // Pembeli mengetik jumlah lain sebelum menekan Bayar lagi.
        $kedua = Livewire::test(EventTicket::class, ['slug' => $eventner->slug])
            ->set('venueId', $venue->id)
            ->set('buyerName', 'Budi')
            ->set('buyerEmail', 'budi@email.com')
            ->set('quantity', 5)
            ->call('submitTicket');

        $this->assertSame(2, (int) $kedua->get('paymentQuantity'));
        $this->assertSame(50000, (int) $kedua->get('paymentUnitPrice'));
        $this->assertSame(100000, (int) $kedua->get('paymentAmount'));
    }

    // ────────────────────────────────────────────────
    // API v1 — klien Flutter
    // ────────────────────────────────────────────────

    /**
     * Balasan HTTP 200 dengan amplop `data` yang sama plus penanda `reused`.
     * Sengaja bukan 4xx: yang diminta klien adalah QR yang bisa dibayar, dan
     * status galat akan memaksa rilis aplikasi lebih dulu.
     */
    public function test_api_vote_klik_kedua_membalas_qr_yang_sama()
    {
        $this->fakeAutoGoPay();
        [$eventner, $reg] = $this->eventVote();

        $payload = [
            'event_slug' => $eventner->slug,
            'registration_id' => $reg->id,
            'vote_count' => 10,
            'voter_name' => 'Pembeli',
            'voter_email' => 'pembeli@example.com',
        ];

        $this->postJson('/api/v1/vote/calculate', $payload)
            ->assertOk()
            ->assertJsonPath('data.reused', false);

        $this->postJson('/api/v1/vote/calculate', $payload)
            ->assertOk()
            ->assertJsonPath('data.reused', true)
            ->assertJsonPath('data.qr_url', 'https://api.autogopay.id/qr/AGP-TEST-001.png');

        Http::assertSentCount(1);
        $this->assertSame(1, VoteTransaction::where('eventner_id', $eventner->id)->count());
    }

    public function test_api_ticket_klik_kedua_membalas_qr_yang_sama()
    {
        $this->fakeAutoGoPay();
        [$eventner, $venue] = $this->eventTiket(kuota: 10);

        $payload = [
            'event_slug' => $eventner->slug,
            'venue_id' => $venue->id,
            'buyer_name' => 'Budi',
            'buyer_email' => 'budi@email.com',
            'quantity' => 2,
        ];

        $pertama = $this->postJson('/api/v1/ticket/purchase', $payload)
            ->assertOk()
            ->assertJsonPath('data.reused', false);

        $kedua = $this->postJson('/api/v1/ticket/purchase', $payload)
            ->assertOk()
            ->assertJsonPath('data.reused', true)
            ->assertJsonPath('data.order_code', $pertama->json('data.order_code'));

        Http::assertSentCount(1);
        $this->assertSame(1, Ticket::where('eventner_id', $eventner->id)->count());
        $this->assertSame(2, $venue->fresh()->ticketsSoldCount());
    }

    /** Tiket gratis tidak lewat penjaga — tidak ada baris PENDING untuk dibuka. */
    public function test_tiket_gratis_tidak_terhalang_penjaga()
    {
        $this->fakeAutoGoPay();
        [$eventner, $venue] = $this->eventTiket(kuota: 10);
        $eventner->update(['ticket_price' => 0]);
        $venue->update(['ticket_price' => 0]);

        $payload = [
            'event_slug' => $eventner->slug,
            'venue_id' => $venue->id,
            'buyer_name' => 'Budi',
            'buyer_email' => 'budi@email.com',
            'quantity' => 1,
        ];

        $this->postJson('/api/v1/ticket/purchase', $payload)
            ->assertOk()
            ->assertJsonPath('data.status', 'ACTIVE');

        $this->postJson('/api/v1/ticket/purchase', $payload)
            ->assertOk()
            ->assertJsonPath('data.status', 'ACTIVE');

        Http::assertNothingSent();
        $this->assertSame(2, Ticket::where('eventner_id', $eventner->id)->count());
    }

    /**
     * Jalur cancel gateway menulis 'FAILED' ke tabel tickets. Enum-nya dulu
     * tidak memuat nilai itu — di MySQL strict itu error 1265.
     */
    public function test_sinkron_tiket_cancel_tidak_menyebabkan_sql_error()
    {
        Http::fake([
            '*/qris/status' => Http::response([
                'success' => true,
                'data' => ['transaction_id' => 'AGP-BATAL', 'transaction_status' => 'cancel'],
            ], 200),
        ]);

        [$eventner, $venue] = $this->eventTiket(kuota: 10);

        $ticket = Ticket::create([
            'eventner_id' => $eventner->id,
            'venue_id' => $venue->id,
            'order_code' => 'TCK-BATAL',
            'buyer_name' => 'Budi',
            'buyer_email' => 'budi@email.com',
            'quantity' => 1,
            'price_per_ticket' => 50000,
            'total_amount' => 50000,
            'autogopay_transaction_id' => 'AGP-BATAL',
            'qr_url' => 'https://api.autogopay.id/qr/batal.png',
            'status' => 'PENDING',
            'expires_at' => now()->addMinutes(5),
            'payable_until' => PendingPaymentGuard::payableUntil(now()->addMinutes(5)),
        ]);

        $this->artisan('payment:sync-pending')->assertSuccessful();

        $this->assertSame('FAILED', $ticket->fresh()->status);
        // FAILED tidak menahan kuota tempat — slotnya bebas lagi.
        $this->assertSame(0, $venue->fresh()->ticketsSoldCount());
    }

    /**
     * Nominal petaan status tidak berubah: penjaga bergantung padanya untuk
     * membebaskan slot, jadi mapper-nya dikunci di sini.
     */
    public function test_petaan_status_gateway_tetap()
    {
        $this->assertSame('PAID', AutoGoPay::mapStatus('settlement'));
        $this->assertSame('EXPIRED', AutoGoPay::mapStatus('expire'));
        $this->assertSame('FAILED', AutoGoPay::mapStatus('cancel'));
        $this->assertNull(AutoGoPay::mapStatus('pending'));
        $this->assertNull(AutoGoPay::mapStatus(null));
    }
}
