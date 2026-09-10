<?php

namespace Tests\Feature;

use App\Jobs\SyncPendingPayments;
use App\Livewire\Admin\Setting\Index as SettingIndex;
use App\Models\Eventner;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AutoGoPay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Gateway pembayaran: GoPay (default) vs InstaQRIS.
 *
 * Yang dijaga di sini adalah bentuk data hasil normalisasi InstaQRIS —
 * pemanggil (Livewire/Job/Webhook) ditulis untuk bentuk GoPay, jadi InstaQRIS
 * wajib mengembalikan field yang sama supaya tidak ada call site yang pecah.
 */
class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Daftar mutasi InstaQRIS di-cache 15 detik dgn key per-tanggal. Cache
        // tidak direset RefreshDatabase, jadi tanpa flush di sini hasil satu tes
        // bisa bocor ke tes lain dan hasilnya bergantung urutan eksekusi.
        \Illuminate\Support\Facades\Cache::flush();
    }

    private function fakeInstaQrisCreate(array $overrides = []): void
    {
        Http::fake([
            '*/instaqris/create' => Http::response([
                'success' => true,
                'message' => 'QRIS created successfully',
                'data' => array_merge([
                    'amount' => 50000,
                    'bill_number' => '1789038429446281',
                    'expires_in' => 900,
                    'outlet_id' => '127686667',
                    'qr_url' => 'https://v1-gateway.autogopay.site/instaqris/qr/1789038429446281',
                ], $overrides),
            ], 200),
        ]);
    }

    public function test_gateway_default_adalah_gopay(): void
    {
        // Tanpa setting apa pun, perilaku lama tidak boleh berubah.
        $this->assertSame('gopay', AutoGoPay::activeGateway());
        $this->assertFalse(AutoGoPay::isInstaQris());
    }

    public function test_instaqris_dipakai_setelah_admin_mengaktifkannya(): void
    {
        Setting::set('payment_gateway', 'instaqris');

        $this->assertTrue(AutoGoPay::isInstaQris());

        // Nilai rusak / tidak dikenal harus jatuh balik ke gopay, bukan bikin error.
        Setting::set('payment_gateway', 'midtrans');
        $this->assertSame('gopay', AutoGoPay::activeGateway());
    }

    public function test_instaqris_dinormalisasi_ke_bentuk_gopay(): void
    {
        Setting::set('payment_gateway', 'instaqris');
        $this->fakeInstaQrisCreate();

        $result = (new AutoGoPay)->generateQris(50000);

        $this->assertTrue($result['success']);
        $data = $result['data'];

        // bill_number jadi identifier — disimpan pemanggil di
        // kolom autogopay_transaction_id, dan dipakai jalur polling utk cek status.
        $this->assertSame('1789038429446281', $data['transaction_id']);
        $this->assertSame('https://v1-gateway.autogopay.site/instaqris/qr/1789038429446281', $data['qr_url']);

        // Field yang dibaca call site harus ada semua (kalau tidak: "Undefined array key").
        foreach (['transaction_id', 'order_id', 'amount', 'transaction_status', 'qr_string', 'qr_url', 'transaction_time', 'expiry_time'] as $key) {
            $this->assertArrayHasKey($key, $data);
        }

        // Timer di halaman bayar mem-parse expiry_time sebagai datetime absolut.
        // expires_in (900 detik) harus dikonversi, bukan dikirim mentah —
        // nilai mentah bikin timer menampilkan "NaN:NaN".
        $expiry = \Carbon\Carbon::parse($data['expiry_time']);
        $this->assertTrue($expiry->isFuture(), 'expiry_time harus di masa depan');
        $this->assertTrue($expiry->lessThanOrEqualTo(now()->addSeconds(901)), 'expiry_time harus sekitar 15 menit');
    }

    public function test_instaqris_menolak_nominal_di_atas_batasnya(): void
    {
        Setting::set('payment_gateway', 'instaqris');
        $this->fakeInstaQrisCreate();

        $this->expectException(\InvalidArgumentException::class);

        // 10jt + 1 — batas InstaQRIS, dan tidak ada cek nominal di call site mana pun.
        (new AutoGoPay)->generateQris(10_000_001);
    }

    public function test_instaqris_mencocokkan_bill_pada_daftar_mutasi(): void
    {
        Setting::set('payment_gateway', 'instaqris');

        Http::fake([
            '*/instaqris/transactions*' => Http::response([
                'success' => true,
                'data' => [
                    'transactions' => [
                        // Mutasi masuk yang sukses → PAID
                        ['amount' => 50000, 'bill' => '1789038429446281', 'type' => 'IN', 'rc' => '00', 'brand' => 'via Mandiri'],
                        // Bukan mutasi masuk → diabaikan
                        ['amount' => 50000, 'bill' => '9999999999999999', 'type' => 'OUT', 'rc' => '00'],
                        // Gagal → diabaikan
                        ['amount' => 50000, 'bill' => '8888888888888888', 'type' => 'IN', 'rc' => '05'],
                    ],
                ],
            ], 200),
        ]);

        $statuses = (new AutoGoPay)->checkStatusMany([
            '1789038429446281',
            '9999999999999999',
            '8888888888888888',
            'belum-dibayar-sama-sekali',
        ]);

        $this->assertSame('settlement', $statuses['1789038429446281'] ?? null);
        $this->assertArrayNotHasKey('9999999999999999', $statuses);
        $this->assertArrayNotHasKey('8888888888888888', $statuses);
        // Bill yang belum dibayar tidak masuk map → pemanggil skip, status tidak berubah.
        $this->assertArrayNotHasKey('belum-dibayar-sama-sekali', $statuses);
        $this->assertSame('PAID', AutoGoPay::mapStatus($statuses['1789038429446281']));
    }

    public function test_mutasi_yang_error_tidak_mengubah_status(): void
    {
        Setting::set('payment_gateway', 'instaqris');

        // Endpoint mutasi sedang 502 di sisi AutoGoPay — jangan sampai bikin
        // exception yang menggagalkan seluruh job sinkronisasi.
        Http::fake([
            '*/instaqris/transactions*' => Http::response('error code: 502', 502),
        ]);

        $this->assertSame([], (new AutoGoPay)->checkStatusMany(['1789038429446281']));
    }

    public function test_admin_mengganti_gateway_lewat_pengaturan_situs(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(SettingIndex::class)
            ->assertSee('GoPay QRIS')
            ->set('payment_gateway', 'instaqris')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('instaqris', Setting::get('payment_gateway'));
        // Pilihan admin harus langsung berlaku, bukan cuma tersimpan.
        $this->assertTrue(AutoGoPay::isInstaQris());
    }

    public function test_halaman_pengaturan_menampilkan_peringatan_instaqris(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(SettingIndex::class)
            ->set('payment_gateway', 'instaqris')
            ->assertSee('Perhatian InstaQRIS')
            // Batas nominal harus disebut ke admin, karena tidak ada cek di UI bayar.
            ->assertSee('10.000.000');
    }

    /**
     * InstaQRIS tidak punya webhook, jadi job sinkronisasi inilah satu-satunya
     * jalur konfirmasi tiket. Sebelumnya jalur polling sengaja tidak mengisi
     * qr_code_path (dulu hanya webhook yang mengisi) — kalau tidak diperbaiki,
     * tiket InstaQRIS jadi PAID tanpa QR masuk sama sekali.
     */
    public function test_polling_instaqris_membuat_qr_masuk_tiket(): void
    {
        Setting::set('payment_gateway', 'instaqris');
        Storage::fake('public');

        Http::fake([
            '*/instaqris/transactions*' => Http::response([
                'success' => true,
                'data' => [
                    'transactions' => [
                        ['amount' => 50000, 'bill' => '1789038429446281', 'type' => 'IN', 'rc' => '00'],
                    ],
                ],
            ], 200),
        ]);

        $eventner = Eventner::factory()->create(['status' => 'approved']);
        $ticket = Ticket::create([
            'eventner_id' => $eventner->id,
            'buyer_name' => 'Budi',
            'buyer_email' => 'budi@example.com',
            'quantity' => 1,
            'price_per_ticket' => 50000,
            'total_amount' => 50000,
            // bill_number disimpan di kolom yang sama seperti transaction_id GoPay
            'autogopay_transaction_id' => '1789038429446281',
            'status' => 'PENDING',
        ]);

        (new SyncPendingPayments)->handle();

        $ticket->refresh();
        $this->assertSame('PAID', $ticket->status);
        $this->assertNotNull($ticket->qr_code_path, 'tiket PAID wajib punya QR masuk');
        Storage::disk('public')->assertExists($ticket->qr_code_path);
    }
}
