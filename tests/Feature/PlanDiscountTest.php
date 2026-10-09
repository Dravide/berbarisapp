<?php

namespace Tests\Feature;

use App\Livewire\Admin\PricingSettings;
use App\Models\Eventner;
use App\Models\SaasPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Diskon persen per paket: harga efektif dipakai konsisten oleh penagihan
 * QRIS (register/dashboard/upgrade), validasi webhook, dan tampilan harga.
 */
class PlanDiscountTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create(['is_active' => true]);
    }

    private function buatPlan(int $price, int $diskon): SaasPlan
    {
        return SaasPlan::create([
            'name' => 'Event Penuh Diskon',
            'slug' => 'penuh-diskon',
            'price' => $price,
            'discount_percent' => $diskon,
            'registration_fee' => 0,
            'is_active' => true,
            'is_free' => false,
            'is_contact' => false,
            'sort_order' => 9,
        ]);
    }

    // ────────────────────────────────────────────────
    // Accessor model
    // ────────────────────────────────────────────────

    public function test_harga_efektif_tanpa_diskon_sama_dengan_price()
    {
        $plan = $this->buatPlan(150000, 0);

        $this->assertSame(150000, $plan->effective_price);
        $this->assertFalse($plan->has_discount);
    }

    public function test_harga_efektif_dengan_diskon_dibulatkan()
    {
        $plan = $this->buatPlan(99000, 15); // 84.150

        $this->assertSame(84150, $plan->effective_price);
        $this->assertTrue($plan->has_discount);
    }

    // ────────────────────────────────────────────────
    // Form admin
    // ────────────────────────────────────────────────

    public function test_admin_menyimpan_diskon_paket()
    {
        $plan = SaasPlan::where('is_free', false)->where('is_contact', false)->firstOrFail();

        Livewire::actingAs($this->admin())
            ->test(PricingSettings::class)
            ->call('editPlan', $plan->id)
            ->set('discount_percent', 20)
            ->call('savePlan')
            ->assertHasNoErrors();

        $this->assertSame(20, $plan->fresh()->discount_percent);
    }

    public function test_diskon_diatas_90_ditolak()
    {
        $plan = SaasPlan::where('is_free', false)->where('is_contact', false)->firstOrFail();

        Livewire::actingAs($this->admin())
            ->test(PricingSettings::class)
            ->call('editPlan', $plan->id)
            ->set('discount_percent', 95)
            ->call('savePlan')
            ->assertHasErrors(['discount_percent']);

        $this->assertSame(0, $plan->fresh()->discount_percent);
    }

    public function test_paket_gratis_diskonnya_dinolkan()
    {
        $gratis = SaasPlan::where('is_free', true)->firstOrFail();

        Livewire::actingAs($this->admin())
            ->test(PricingSettings::class)
            ->call('editPlan', $gratis->id)
            ->set('discount_percent', 50)
            ->call('savePlan')
            ->assertHasNoErrors();

        $this->assertSame(0, $gratis->fresh()->discount_percent);
    }

    // ────────────────────────────────────────────────
    // Penagihan QRIS pakai harga efektif
    // ────────────────────────────────────────────────

    private function fakeAutoGoPay(): void
    {
        Http::fake([
            '*/qris/generate' => Http::response([
                'success' => true,
                'data' => [
                    'transaction_id' => 'AGP-GEN-DISC',
                    'order_id' => 'ORD-GEN-DISC',
                    'amount' => 120000,
                    'transaction_status' => 'pending',
                    'qr_string' => '000201010212',
                    'qr_url' => 'https://api.autogopay.id/qr/disc.png',
                    'transaction_time' => now()->toIso8601String(),
                    'expiry_time' => now()->addMinutes(5)->toIso8601String(),
                ],
            ], 200),
        ]);
    }

    public function test_penagihan_qris_memakai_harga_efektif()
    {
        $this->fakeAutoGoPay();
        SaasPlan::query()->update(['is_active' => false]);
        $plan = $this->buatPlan(150000, 20); // tagih 120.000

        $eventner = Eventner::factory()->create([
            'plan' => 'paid',
            'saas_plan_id' => $plan->id,
            'status' => 'pending',
        ]);
        // Nonaktif = jalur "unpaid paid plan" di mount() — tanpa ini mount()
        // me-redirect ke eventner.dashboard dan snapshot Livewire rusak.
        $eventner->user->update(['is_active' => false]);

        Livewire::actingAs($eventner->user)
            ->test(\App\Livewire\Dashboard\Index::class)
            ->call('generatePayment');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'qris/generate')
            && (int) $request['amount'] === 120000);
    }

    // ────────────────────────────────────────────────
    // Webhook memvalidasi harga efektif
    // ────────────────────────────────────────────────

    private function postWebhook(array $payload): \Illuminate\Testing\TestResponse
    {
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, config('services.autogopay.api_key'));

        return $this->postJson('/webhook/autogopay', $payload, ['X-Signature' => $signature]);
    }

    public function test_webhook_menerima_settlement_dengan_harga_diskon()
    {
        $plan = $this->buatPlan(150000, 20); // efektif 120.000
        $eventner = Eventner::factory()->create([
            'plan' => 'paid',
            'saas_plan_id' => $plan->id,
            'status' => 'pending',
        ]);
        $eventner->update(['autogopay_transaction_id' => 'AGP-DISC-1']);

        $this->postWebhook([
            'event' => 'transaction.received',
            'transaction' => [
                'transaction_id' => 'AGP-DISC-1',
                'status' => 'settlement',
                'amount' => 120000,
            ],
        ])->assertOk();

        $this->assertNotNull($eventner->fresh()->registration_paid_at);
    }

    public function test_webhook_menolak_nominal_dibawah_harga_efektif()
    {
        $plan = $this->buatPlan(150000, 20); // efektif 120.000
        $eventner = Eventner::factory()->create([
            'plan' => 'paid',
            'saas_plan_id' => $plan->id,
            'status' => 'pending',
        ]);
        $eventner->update(['autogopay_transaction_id' => 'AGP-DISC-2']);

        $this->postWebhook([
            'event' => 'transaction.received',
            'transaction' => [
                'transaction_id' => 'AGP-DISC-2',
                'status' => 'settlement',
                'amount' => 100000, // < 120.000 → ditolak
            ],
        ])->assertOk();

        $this->assertNull($eventner->fresh()->registration_paid_at);
    }

    // ────────────────────────────────────────────────
    // Tampilan
    // ────────────────────────────────────────────────

    public function test_halaman_harga_menampilkan_harga_coret_dan_label_hemat()
    {
        $this->buatPlan(150000, 25);

        $this->get(route('pricing'))
            ->assertOk()
            ->assertSee('Event Penuh Diskon')
            ->assertSee('line-through', false)
            ->assertSee('Hemat 25%')
            ->assertSee('Rp 112.500');
    }

    public function test_halaman_harga_tanpa_diskon_tetap_normal()
    {
        $plan = $this->buatPlan(150000, 0);
        $plan->update(['name' => 'Paket Biasa Tanpa Diskon']);

        $this->get(route('pricing'))
            ->assertOk()
            ->assertSee('Paket Biasa Tanpa Diskon')
            ->assertSee('Rp 150.000');
    }
}
