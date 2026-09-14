<?php

namespace Tests\Feature;

use App\Jobs\SyncPendingPayments;
use App\Livewire\Eventner\Settings\Billing\Upgrade;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\SaasPlan;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use App\Support\PaymentSyncLock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Temuan audit #34, #35, #37, #38 — paket, token API, dan rekonsiliasi.
 *
 * #34: saas_plan_id bisa yatim (nullOnDelete), dan jalur paket membaca
 *      ->features langsung sehingga eventner legacy/ber-paket-dihapus error.
 * #35: scan QR membuat token Sanctum baru tiap kali tanpa mencabut yang lama.
 * #37: generatePayment menimpa autogopay_transaction_id tanpa membatalkan QR
 *      lama, dan webhook settlement tidak memeriksa nominal.
 * #38: command tiap menit dan job tiap lima menit tidak berbagi kunci, jadi
 *      keduanya bisa menembak /qris/status bersamaan.
 */
class PaymentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function eventner(array $atribut = []): Eventner
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);

        return Eventner::factory()->create(array_merge([
            'user_id' => $user->id,
            'status' => 'approved',
        ], $atribut));
    }

    private function paketBerbayar(int $harga = 150000): SaasPlan
    {
        return SaasPlan::create([
            'name' => 'Event Penuh', 'slug' => 'penuh-' . uniqid(),
            'price' => $harga, 'registration_fee' => 50000,
            'is_active' => true, 'is_free' => false, 'is_contact' => false, 'sort_order' => 1,
        ]);
    }

    // ────────────────────────────────────────────────
    // #34 — paket yatim tidak boleh melempar error
    // ────────────────────────────────────────────────

    /**
     * Eventner berbayar TANPA paket sama sekali — jalur legacy. Halaman apa
     * pun yang memanggil canAccessFeature() tidak boleh melempar error.
     */
    public function test_plan_paid_tanpa_paket_tidak_melempar_error()
    {
        $eventner = $this->eventner(['plan' => 'paid', 'saas_plan_id' => null]);

        $this->assertTrue($eventner->canAccessFeature('format_nilai'));
        $this->assertSame([], $eventner->lockedFeatures());
    }

    /**
     * saas_plan_id menunjuk paket yang sudah tidak ada.
     *
     * Baris seperti ini tidak bisa dibuat di sini: FK-nya nullOnDelete dan
     * SQLite menolak id yatim selama penegakan FK hidup, jadi relasinya
     * dipaksa null langsung. Yang dibuktikan tetap sama — keputusan diambil
     * dari RELASI, bukan dari kolom saas_plan_id polos. Kode lama memakai
     * kolomnya, lolos pemeriksaan, lalu membaca ->features dari null.
     */
    public function test_paket_yatim_tidak_melempar_error_dan_akses_penuh()
    {
        $eventner = $this->eventner(['plan' => 'paid']);
        $plan = $this->paketBerbayar();
        $eventner->update(['saas_plan_id' => $plan->id]);

        // Relasi tidak ketemu (paketnya hilang) — kolomnya masih terisi.
        $eventner->setRelation('saasPlan', null);

        $this->assertNotNull($eventner->saas_plan_id, 'Prasyarat: kolom saas_plan_id masih terisi.');

        $this->assertTrue($eventner->canAccessFeature('format_nilai'));
        $this->assertSame([], $eventner->lockedFeatures());
    }

    /** Paket hidup tetap menentukan fitur — bukan jatuh ke legacy. */
    public function test_paket_hidup_tetap_menentukan_fitur()
    {
        $eventner = $this->eventner(['plan' => 'paid']);

        $plan = $this->paketBerbayar();
        $plan->features()->createMany([['feature_key' => 'tickets']]);

        $eventner->update(['saas_plan_id' => $plan->id, 'registration_paid_at' => now()]);
        $eventner->refresh();

        $this->assertTrue($eventner->canAccessFeature('tickets'));
        $this->assertFalse($eventner->canAccessFeature('format_nilai'), 'Fitur di luar paket harus terkunci.');
    }

    // ────────────────────────────────────────────────
    // #35 — token QR tidak menumpuk
    // ────────────────────────────────────────────────

    public function test_scan_qr_berulang_tidak_menumpuk_token()
    {
        $eventner = $this->eventner();
        $reg = Registration::factory()->for($eventner, 'eventner')->create([
            'nama_sekolah' => 'SMP Token',
            'qr_token' => 'AAAA1111',
        ]);

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/qr/scan', ['qr_token' => 'AAAA1111'])
                ->assertOk()
                ->assertJsonStructure(['token', 'data']);
        }

        $this->assertSame(
            1,
            $reg->tokens()->count(),
            'Scan berulang harus memakai satu token, bukan menambah baris tiap kali.'
        );
    }

    // ────────────────────────────────────────────────
    // #37 — QR lama dibatalkan sebelum yang baru dibuat
    // ────────────────────────────────────────────────

    public function test_qr_lama_dibatalkan_sebelum_qr_baru_dibuat()
    {
        Http::fake([
            '*/qris/status' => Http::response([
                'success' => true,
                'data' => ['transaction_id' => 'AGP-LAMA', 'transaction_status' => 'pending'],
            ], 200),
            '*/qris/cancel' => Http::response([
                'success' => true,
                'data' => ['transaction_id' => 'AGP-LAMA', 'transaction_status' => 'cancel'],
            ], 200),
            '*/qris/generate' => Http::response([
                'success' => true,
                'data' => [
                    'transaction_id' => 'AGP-BARU',
                    'amount' => 150000,
                    'qr_string' => '000201010212',
                    'qr_url' => 'https://api.autogopay.id/qr/baru.png',
                ],
            ], 200),
        ]);

        $plan = $this->paketBerbayar();
        $eventner = $this->eventner([
            'plan' => 'paid',
            'saas_plan_id' => $plan->id,
            'autogopay_transaction_id' => 'AGP-LAMA',
            'qr_url' => 'https://api.autogopay.id/qr/lama.png',
        ]);

        Livewire::actingAs($eventner->user)->test(Upgrade::class)
            ->call('generatePayment', $plan->id);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'qris/cancel')
            && $request['transaction_id'] === 'AGP-LAMA');

        $this->assertSame('AGP-BARU', $eventner->fresh()->autogopay_transaction_id);
    }

    /** Gateway menolak membatalkan QR lama → jangan tinggalkan dua QR hidup. */
    public function test_qr_baru_tidak_dibuat_bila_qr_lama_tidak_bisa_dibatalkan()
    {
        Http::fake([
            '*/qris/status' => Http::response([
                'success' => true,
                'data' => ['transaction_id' => 'AGP-LAMA', 'transaction_status' => 'pending'],
            ], 200),
            '*/qris/cancel' => Http::response(['success' => false, 'message' => 'gagal'], 500),
            '*/qris/generate' => Http::response(['success' => true, 'data' => ['transaction_id' => 'AGP-BARU']], 200),
        ]);

        $plan = $this->paketBerbayar();
        $eventner = $this->eventner([
            'plan' => 'paid',
            'saas_plan_id' => $plan->id,
            'autogopay_transaction_id' => 'AGP-LAMA',
        ]);

        Livewire::actingAs($eventner->user)->test(Upgrade::class)
            ->call('generatePayment', $plan->id);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'qris/cancel'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'qris/generate'));

        $this->assertSame('AGP-LAMA', $eventner->fresh()->autogopay_transaction_id);
    }

    /** Settlement dengan nominal kurang tidak mengaktifkan paket. */
    public function test_settlement_nominal_kurang_tidak_mengaktifkan_paket()
    {
        $eventner = $this->eventner([
            'plan' => 'paid',
            'saas_plan_id' => $this->paketBerbayar(150000)->id,
            'autogopay_transaction_id' => 'AGP-KURANG',
        ]);

        $this->postJson('/webhook/autogopay', [
            'event' => 'transaction.received',
            'transaction' => [
                'transaction_id' => 'AGP-KURANG',
                'status' => 'settlement',
                'amount' => 1000,
            ],
        ], ['X-Signature' => $this->signature([
            'event' => 'transaction.received',
            'transaction' => [
                'transaction_id' => 'AGP-KURANG',
                'status' => 'settlement',
                'amount' => 1000,
            ],
        ])])->assertOk();

        $this->assertNull($eventner->fresh()->registration_paid_at, 'Nominal kurang tidak boleh mengaktifkan paket.');
    }

    /** Settlement dengan nominal pas tetap mengaktifkan paket. */
    public function test_settlement_nominal_pas_mengaktifkan_paket()
    {
        $eventner = $this->eventner([
            'plan' => 'paid',
            'saas_plan_id' => $this->paketBerbayar(150000)->id,
            'autogopay_transaction_id' => 'AGP-PAS',
        ]);

        $payload = [
            'event' => 'transaction.received',
            'transaction' => [
                'transaction_id' => 'AGP-PAS',
                'status' => 'settlement',
                'amount' => 150000,
            ],
        ];

        $this->postJson('/webhook/autogopay', $payload, ['X-Signature' => $this->signature($payload)])
            ->assertOk();

        $this->assertNotNull($eventner->fresh()->registration_paid_at);
    }

    private function signature(array $payload): string
    {
        return hash_hmac('sha256', json_encode($payload), config('services.autogopay.api_key'));
    }

    // ────────────────────────────────────────────────
    // #38 — dua sinkronizer berbagi satu kunci
    // ────────────────────────────────────────────────

    public function test_sinkronizer_kedua_dilewati_saat_kunci_dipegang()
    {
        Http::fake(['*/qris/status' => Http::response(['success' => true, 'data' => []], 200)]);

        $eventner = $this->eventner();
        Ticket::create([
            'eventner_id' => $eventner->id,
            'buyer_name' => 'Rina',
            'buyer_email' => 'rina@example.com',
            'quantity' => 1,
            'price_per_ticket' => 50000,
            'total_amount' => 50000,
            'autogopay_transaction_id' => 'AGP-KUNCI',
            'status' => 'PENDING',
        ]);

        // Kunci dipegang di luar — mewakili sinkronizer lain yang sedang jalan.
        $dijalankan = PaymentSyncLock::run(function () use ($eventner) {
            (new SyncPendingPayments)->handle();

            return true;
        });

        $this->assertTrue($dijalankan);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'qris/status'));
    }

    public function test_sinkronizer_jalan_saat_kunci_bebas()
    {
        Http::fake(['*/qris/status' => Http::response(['success' => true, 'data' => []], 200)]);

        $dijalankan = PaymentSyncLock::run(fn () => null);

        $this->assertTrue($dijalankan, 'Kunci bebas harus menjalankan siklusnya.');
    }
}
