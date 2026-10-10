<?php

namespace Tests\Feature;

use App\Livewire\Admin\VoucherIndex;
use App\Models\Eventner;
use App\Models\RegistrationVoucher;
use App\Models\SaasPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Kode promo pendaftaran eventner — potongan biaya paket SaaS saat
 * pendaftaran akun event (/register/eventner).
 *
 * Tiga titik yang wajib cocok (preseden bug registration_fee: penagih ≠
 * webhook → settlement ditolak diam-diam):
 *   1. form pendaftaran menampilkan potongan dari effective_price,
 *   2. QRIS ditagih nominal efektif dikurangi voucher,
 *   3. webhook memvalidasi nominal yang sama via snapshot voucher_discount.
 */
class RegistrationVoucherTest extends TestCase
{
    use RefreshDatabase;

    private function webhookPayload(string $transactionId, string $status = 'settlement', ?int $amount = null): array
    {
        $transaction = ['transaction_id' => $transactionId, 'status' => $status];
        if ($amount !== null) {
            $transaction['amount'] = $amount;
        }

        return [
            'event' => 'transaction.received',
            'transaction' => $transaction,
        ];
    }

    private function postWebhook(array $payload): \Illuminate\Testing\TestResponse
    {
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, config('services.autogopay.api_key'));

        return $this->postJson('/webhook/autogopay', $payload, ['X-Signature' => $signature]);
    }

    private function fakeAutoGoPay(): void
    {
        Http::fake([
            '*/qris/generate' => Http::response([
                'success' => true,
                'data' => [
                    'transaction_id' => 'AGP-VOU-001',
                    'order_id' => 'ORD-VOU-001',
                    'amount' => 75000,
                    'transaction_status' => 'pending',
                    'qr_string' => '000201010212',
                    'qr_url' => 'https://api.autogopay.id/qr/voucher.png',
                    'transaction_time' => now()->toIso8601String(),
                ],
            ]),
        ]);
    }

    /** Paket berbayar tunggal @ 150.000 + paket gratis, aktif. */
    private function buatPaket(): SaasPlan
    {
        SaasPlan::where('is_active', true)->update(['is_active' => false]);
        SaasPlan::create([
            'name' => 'Gratis', 'slug' => 'gratis-voucher-test',
            'price' => 0, 'registration_fee' => 0,
            'is_active' => true, 'is_free' => true, 'is_contact' => false, 'sort_order' => 0,
        ]);

        return SaasPlan::create([
            'name' => 'Paket Voucher', 'slug' => 'paket-voucher',
            'price' => 150000, 'registration_fee' => 50000,
            'is_active' => true, 'is_free' => false, 'is_contact' => false, 'sort_order' => 1,
        ]);
    }

    private function isiForm(\Livewire\Features\SupportTesting\Testable $page): \Livewire\Features\SupportTesting\Testable
    {
        return $page
            ->set('name', 'Panitia Voucer')
            ->set('username', 'panitiavoucher')
            ->set('email', 'voucher@example.test')
            ->set('no_hp', '0812 3456 7890')
            ->set('password', 'rahasiaku123')
            ->set('password_confirmation', 'rahasiaku123')
            ->set('nama_event', 'Lomba Voucher')
            ->set('lokasi', 'Depok');
    }

    // ────────────────────────────────────────────────
    // Model: hitung diskon & validasi
    // ────────────────────────────────────────────────

    public function test_diskon_persen_dihitung_dari_harga_efektif_dengan_cap()
    {
        $plan = $this->buatPaket(); // 150.000, tanpa diskon paket

        $voucher = RegistrationVoucher::create([
            'code' => 'hemat50', 'type' => 'percent', 'value' => 50,
            'is_active' => true,
        ]);
        $this->assertSame(75000, $voucher->diskonUntuk($plan));

        // Cap memotong hasil persen.
        $capped = RegistrationVoucher::create([
            'code' => 'HEMATCAP', 'type' => 'percent', 'value' => 50,
            'max_discount' => 20000, 'is_active' => true,
        ]);
        $this->assertSame(20000, $capped->diskonUntuk($plan));

        // Harga efektif paket yang jadi dasar, bukan price mentah.
        $plan->update(['discount_percent' => 20]); // 150.000 → 120.000
        $this->assertSame(60000, $voucher->fresh()->diskonUntuk($plan));
    }

    public function test_diskon_flat_tidak_melampaui_harga_paket()
    {
        $plan = $this->buatPaket();

        $voucher = RegistrationVoucher::create([
            'code' => 'FLAT200', 'type' => 'flat', 'value' => 200000,
            'is_active' => true,
        ]);

        // Voucher 200rb atas paket 150rb — potongan dibatasi harga paket.
        $this->assertSame(150000, $voucher->diskonUntuk($plan));
    }

    public function test_kuota_dihitung_dari_pendaftar_sudah_bayar_saja()
    {
        $voucher = RegistrationVoucher::create([
            'code' => 'KUOTA1', 'type' => 'percent', 'value' => 10,
            'max_uses' => 2, 'is_active' => true,
        ]);

        // Yang sekadar daftar (belum settlement) tidak mengonsumsi kuota.
        Eventner::factory()->create(['registration_voucher_id' => $voucher->id, 'voucher_discount' => 15000]);
        $this->assertSame(0, $voucher->hitungPemakaian());
        $this->assertSame(2, $voucher->sisaKuota());

        // Yang sudah bayar mengonsumsi.
        Eventner::factory()->create([
            'registration_voucher_id' => $voucher->id, 'voucher_discount' => 15000,
            'registration_paid_at' => now(),
        ]);
        $this->assertSame(1, $voucher->hitungPemakaian());
        $this->assertSame(1, $voucher->sisaKuota());
    }

    // ────────────────────────────────────────────────
    // Form pendaftaran: terapkan kode
    // ────────────────────────────────────────────────

    public function test_kode_valid_diterapkan_dan_menampilkan_potongan()
    {
        $plan = $this->buatPaket();
        RegistrationVoucher::create([
            'code' => 'HEMAT50', 'type' => 'percent', 'value' => 50,
            'is_active' => true,
        ]);

        Livewire::withQueryParams(['plan' => 'paket-voucher'])
            ->test(\App\Livewire\Public\EventnerRegister::class)
            ->set('kodePromo', 'hemat50') // lowercase — disimpan uppercase
            ->call('applyPromo')
            ->assertSet('voucherError', '')
            ->assertSet('voucherId', 1)
            ->assertSet('voucherLabel', 'HEMAT50')
            ->assertSet('voucherDiscount', 75000);
    }

    public function test_kode_tak_kenal_ditolak_tanpa_memotong_harga()
    {
        $this->buatPaket();

        Livewire::withQueryParams(['plan' => 'paket-voucher'])
            ->test(\App\Livewire\Public\EventnerRegister::class)
            ->set('kodePromo', 'NGASAL')
            ->call('applyPromo')
            ->assertSet('voucherError', 'Kode promo tidak ditemukan.')
            ->assertSet('voucherDiscount', 0);
    }

    public function test_kode_kadaluarsa_dan_belum_mulai_ditolak()
    {
        $this->buatPaket();
        RegistrationVoucher::create([
            'code' => 'LAMARET', 'type' => 'percent', 'value' => 10,
            'ends_at' => now()->subDay(), 'is_active' => true,
        ]);
        RegistrationVoucher::create([
            'code' => 'BELUMMULAI', 'type' => 'percent', 'value' => 10,
            'starts_at' => now()->addDay(), 'is_active' => true,
        ]);

        Livewire::withQueryParams(['plan' => 'paket-voucher'])
            ->test(\App\Livewire\Public\EventnerRegister::class)
            ->set('kodePromo', 'LAMARET')
            ->call('applyPromo')
            ->assertSet('voucherError', 'Kode promo sudah kadaluarsa.');

        Livewire::withQueryParams(['plan' => 'paket-voucher'])
            ->test(\App\Livewire\Public\EventnerRegister::class)
            ->set('kodePromo', 'BELUMMULAI')
            ->call('applyPromo')
            ->assertSet('voucherError', 'Kode promo belum mulai berlaku.');
    }

    public function test_kuota_habis_ditolak_dan_pendaftar_belum_bayar_tidak_mengonsumsi()
    {
        $plan = $this->buatPaket();
        $voucher = RegistrationVoucher::create([
            'code' => 'KUOTAHABIS', 'type' => 'percent', 'value' => 10,
            'max_uses' => 1, 'is_active' => true,
        ]);

        // Kuota terpakai oleh pendaftar yang SUDAH bayar.
        Eventner::factory()->create([
            'registration_voucher_id' => $voucher->id, 'voucher_discount' => 15000,
            'saas_plan_id' => $plan->id, 'registration_paid_at' => now(),
        ]);

        $page = Livewire::withQueryParams(['plan' => 'paket-voucher'])
            ->test(\App\Livewire\Public\EventnerRegister::class)
            ->set('kodePromo', 'KUOTAHABIS')
            ->call('applyPromo')
            ->assertSet('voucherError', 'Kuota kode promo sudah habis.');

        // Daftar tanpa kode tetap jalan — penolakan kode tidak memblokir pendaftaran.
        $this->isiForm($page)
            ->set('agreeTerms', true)
            ->call('save')
            ->assertHasNoErrors();

        // Dan pendaftaran itu tidak mengonsumsi kuota (belum bayar).
        $this->assertSame(1, $voucher->fresh()->hitungPemakaian());
    }

    public function test_kode_batasi_paket_ditolak_untuk_paket_lain()
    {
        $plan = $this->buatPaket();
        RegistrationVoucher::create([
            'code' => 'KHUSUSINI', 'type' => 'percent', 'value' => 10,
            'saas_plan_id' => $plan->id, 'is_active' => true,
        ]);
        SaasPlan::create([
            'name' => 'Paket Lain', 'slug' => 'paket-lain',
            'price' => 300000, 'registration_fee' => 0,
            'is_active' => true, 'is_free' => false, 'is_contact' => false, 'sort_order' => 2,
        ]);

        Livewire::withQueryParams(['plan' => 'paket-lain'])
            ->test(\App\Livewire\Public\EventnerRegister::class)
            ->set('kodePromo', 'KHUSUSINI')
            ->call('applyPromo')
            ->assertSet('voucherError', 'Kode promo tidak berlaku untuk paket ini.');
    }

    public function test_ganti_paket_setelah_kode_diterapkan_menghitung_ulang_diskon()
    {
        $plan = $this->buatPaket();
        RegistrationVoucher::create([
            'code' => 'FLAT20RB', 'type' => 'flat', 'value' => 20000,
            'is_active' => true,
        ]);
        SaasPlan::create([
            'name' => 'Paket Mahal', 'slug' => 'paket-mahal',
            'price' => 400000, 'registration_fee' => 0,
            'is_active' => true, 'is_free' => false, 'is_contact' => false, 'sort_order' => 2,
        ]);

        Livewire::withQueryParams(['plan' => 'paket-voucher'])
            ->test(\App\Livewire\Public\EventnerRegister::class)
            ->set('kodePromo', 'FLAT20RB')
            ->call('applyPromo')
            ->assertSet('voucherDiscount', 20000)
            // Paket mahal tetap flat 20rb — hitung ulang, bukan nyangkut nilai lama.
            ->set('plan', 'paket-mahal')
            ->assertSet('voucherDiscount', 20000)
            ->assertSet('voucherError', '');
    }

    // ────────────────────────────────────────────────
    // Penagihan & webhook
    // ────────────────────────────────────────────────

    public function test_qris_ditagih_nominal_terpotong_voucher()
    {
        $this->fakeAutoGoPay();
        $this->buatPaket();
        RegistrationVoucher::create([
            'code' => 'HEMAT50', 'type' => 'percent', 'value' => 50,
            'is_active' => true,
        ]);

        $page = Livewire::withQueryParams(['plan' => 'paket-voucher'])
            ->test(\App\Livewire\Public\EventnerRegister::class);
        $this->isiForm($page)
            ->set('kodePromo', 'HEMAT50')
            ->call('applyPromo')
            ->set('agreeTerms', true)
            ->call('save')
            ->assertHasNoErrors();

        // 150.000 − 50% = 75.000 ke gateway, bukan 150.000.
        Http::assertSent(fn ($request) => str_contains($request->url(), 'qris/generate')
            && (int) $request['amount'] === 75000);

        // Snapshot potongan dibekukan ke eventners — webhook mengenalinya.
        $this->assertDatabaseHas('eventners', [
            'nama_event' => 'Lomba Voucher',
            'voucher_discount' => 75000,
        ]);
        $this->assertNotNull(Eventner::where('nama_event', 'Lomba Voucher')->value('registration_voucher_id'));
    }

    public function test_webhook_menerima_settlement_nominal_terpotong()
    {
        $plan = $this->buatPaket();
        $voucher = RegistrationVoucher::create([
            'code' => 'HEMAT50', 'type' => 'percent', 'value' => 50,
            'is_active' => true,
        ]);
        $user = User::factory()->create(['role' => 'Eventner', 'is_active' => false]);
        $eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'plan' => 'paid',
            'saas_plan_id' => $plan->id,
            'registration_voucher_id' => $voucher->id,
            'voucher_discount' => 75000,
            'status' => 'pending',
            'autogopay_transaction_id' => 'AGP-VOU-SETTLE',
        ]);

        // Regresi: webhook yang masih menghitung 150.000 penuh akan menolak
        // settlement 75.000 ini diam-diam dan akun menggantung.
        $this->postWebhook($this->webhookPayload('AGP-VOU-SETTLE', 'settlement', 75000))
            ->assertOk();

        $this->assertNotNull($eventner->fresh()->registration_paid_at);
        $this->assertEquals('approved', $eventner->fresh()->status);
        $this->assertTrue((bool) $user->fresh()->is_active);
    }

    public function test_webhook_tetap_menolak_nominal_kurang_dari_harga_terpotong()
    {
        $plan = $this->buatPaket();
        $voucher = RegistrationVoucher::create([
            'code' => 'HEMAT50', 'type' => 'percent', 'value' => 50,
            'is_active' => true,
        ]);
        $eventner = Eventner::factory()->create([
            'plan' => 'paid',
            'saas_plan_id' => $plan->id,
            'registration_voucher_id' => $voucher->id,
            'voucher_discount' => 75000,
            'status' => 'pending',
            'autogopay_transaction_id' => 'AGP-VOU-KURANG',
        ]);

        // Harga terpotong 75.000 — bayar 50.000 tetap kurang dan ditolak.
        $this->postWebhook($this->webhookPayload('AGP-VOU-KURANG', 'settlement', 50000))
            ->assertOk();

        $this->assertNull($eventner->fresh()->registration_paid_at);
    }

    public function test_voucher_nonaktif_setelah_daftar_tidak_mematahkan_webhook()
    {
        $plan = $this->buatPaket();
        $voucher = RegistrationVoucher::create([
            'code' => 'HEMAT50', 'type' => 'percent', 'value' => 50,
            'is_active' => true,
        ]);
        $eventner = Eventner::factory()->create([
            'plan' => 'paid',
            'saas_plan_id' => $plan->id,
            'registration_voucher_id' => $voucher->id,
            'voucher_discount' => 75000,
            'status' => 'pending',
            'autogopay_transaction_id' => 'AGP-VOU-MATI',
        ]);
        $voucher->update(['is_active' => false]);

        // Snapshot voucher_discount tetap dipakai webhook — kode yang
        // dinonaktifkan SETELAH pendaftar tidak menolak settlementnya.
        $this->postWebhook($this->webhookPayload('AGP-VOU-MATI', 'settlement', 75000))
            ->assertOk();

        $this->assertNotNull($eventner->fresh()->registration_paid_at);
    }

    // ────────────────────────────────────────────────
    // Admin CRUD
    // ────────────────────────────────────────────────

    public function test_admin_membuat_mengubah_dan_menghapus_kode_promo()
    {
        $admin = User::factory()->admin()->create();

        $page = Livewire::actingAs($admin)->test(VoucherIndex::class)
            ->call('createVoucher')
            ->set('code', 'komet50')
            ->set('type', 'percent')
            ->set('value', 50)
            ->set('max_uses', 10)
            ->call('save')
            ->assertHasNoErrors();

        // Kode disimpan uppercase.
        $this->assertDatabaseHas('registration_vouchers', [
            'code' => 'KOMET50', 'type' => 'percent', 'value' => 50, 'max_uses' => 10,
        ]);

        // Edit.
        $voucher = RegistrationVoucher::where('code', 'KOMET50')->first();
        $page->call('editVoucher', $voucher->id)
            ->set('value', 25)
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame(25, $voucher->fresh()->value);

        // Belum dipakai → boleh dihapus.
        $page->call('delete', $voucher->id);
        $this->assertDatabaseMissing('registration_vouchers', ['id' => $voucher->id]);
    }

    public function test_admin_tidak_bisa_menghapus_kode_yang_sudah_dipakai_pendaftar()
    {
        $admin = User::factory()->admin()->create();
        $voucher = RegistrationVoucher::create([
            'code' => 'PAKAI', 'type' => 'percent', 'value' => 10,
            'is_active' => true,
        ]);
        Eventner::factory()->create(['registration_voucher_id' => $voucher->id, 'voucher_discount' => 15000]);

        Livewire::actingAs($admin)->test(VoucherIndex::class)
            ->call('delete', $voucher->id);

        // Jejak snapshot tak boleh putus — baris tetap ada.
        $this->assertDatabaseHas('registration_vouchers', ['id' => $voucher->id]);
    }

    public function test_admin_save_dengan_paket_diluar_daftar_diabaikan_jadi_semua_paket()
    {
        $admin = User::factory()->admin()->create();
        $this->buatPaket();

        Livewire::actingAs($admin)->test(VoucherIndex::class)
            ->call('createVoucher')
            ->set('code', 'DOMPALSU')
            ->set('type', 'flat')
            ->set('value', 10000)
            // Nilai DOM dipalsukan — saas_plan_id 99999 bukan paket.
            ->set('saas_plan_id', '99999')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('registration_vouchers', [
            'code' => 'DOMPALSU',
            'saas_plan_id' => null,
        ]);
    }
}
