<?php

namespace Tests\Feature;

use App\Livewire\Eventner\Settings\Billing\Upgrade;
use App\Models\Eventner;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class MonetizationTest extends TestCase
{
    use RefreshDatabase;

    private function webhookPayload(string $transactionId, string $status = 'settlement'): array
    {
        return [
            'event' => 'transaction.received',
            'transaction' => [
                'id' => $transactionId,
                'status' => $status,
            ],
        ];
    }

    private function postWebhook(array $payload): \Illuminate\Testing\TestResponse
    {
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, config('services.autogopay.api_key'));

        return $this->postJson('/webhook/autogopay', $payload, ['X-Signature' => $signature]);
    }

    // ────────────────────────────────────────────────
    // Webhook: settle → plan paid
    // ────────────────────────────────────────────────

    public function test_webhook_settlement_upgrades_approved_eventner_to_paid()
    {
        $eventner = Eventner::factory()->create([
            'plan' => 'free',
            'saas_plan_id' => \App\Models\SaasPlan::where('is_free', false)->value('id'),
            'status' => 'approved',
            'approved_at' => now()->subDays(5),
            'trial_ends_at' => now()->subDays(2),
            'autogopay_transaction_id' => 'AGP-UPG-001',
        ]);

        $response = $this->postWebhook($this->webhookPayload('AGP-UPG-001'));

        $response->assertOk();
        $this->assertEquals('paid', $eventner->fresh()->plan);
        $this->assertEquals('approved', $eventner->fresh()->status);
        $this->assertNotNull($eventner->fresh()->registration_paid_at);
        // approved_at lama tidak tertimpa
        $this->assertTrue($eventner->fresh()->approved_at->isSameDay(now()->subDays(5)));
    }

    public function test_webhook_settlement_approves_pending_paid_registration()
    {
        $user = User::factory()->create(['role' => 'Eventner', 'is_active' => false]);
        $eventner = Eventner::factory()->create([
            'user_id' => $user->id,
            'plan' => 'paid',
            'status' => 'pending',
            'autogopay_transaction_id' => 'AGP-NEW-001',
        ]);

        $response = $this->postWebhook($this->webhookPayload('AGP-NEW-001'));

        $response->assertOk();
        $this->assertEquals('paid', $eventner->fresh()->plan);
        $this->assertEquals('approved', $eventner->fresh()->status);
        $this->assertTrue((bool) $user->fresh()->is_active);
    }

    public function test_webhook_settlement_is_idempotent()
    {
        $eventner = Eventner::factory()->create([
            'plan' => 'paid',
            'status' => 'approved',
            'autogopay_transaction_id' => 'AGP-IDEM-001',
        ]);
        $firstPaidAt = now();

        // Webhook pertama (ret) — webhook kedua tidak mengubah apapun
        $this->postWebhook($this->webhookPayload('AGP-IDEM-001'))->assertOk();
        $this->postWebhook($this->webhookPayload('AGP-IDEM-001'))->assertOk();

        $this->assertEquals('paid', $eventner->fresh()->plan);
    }

    public function test_webhook_expire_keeps_plan_free()
    {
        $eventner = Eventner::factory()->create([
            'plan' => 'free',
            'status' => 'approved',
            'autogopay_transaction_id' => 'AGP-EXP-001',
        ]);

        $response = $this->postWebhook($this->webhookPayload('AGP-EXP-001', 'expire'));

        $response->assertOk();
        $this->assertEquals('free', $eventner->fresh()->plan);
    }

    public function test_webhook_rejects_invalid_signature()
    {
        $eventner = Eventner::factory()->create([
            'autogopay_transaction_id' => 'AGP-BAD-001',
        ]);

        $response = $this->postJson('/webhook/autogopay', $this->webhookPayload('AGP-BAD-001'), [
            'X-Signature' => 'invalid-signature',
        ]);

        $response->assertStatus(401);
        $this->assertEquals('free', $eventner->fresh()->plan);
    }

    public function test_webhook_ignores_unknown_transaction()
    {
        $response = $this->postWebhook($this->webhookPayload('AGP-UNKNOWN-999'));
        $response->assertOk();
    }

    // ────────────────────────────────────────────────
    // Portal upgrade
    // ────────────────────────────────────────────────

    private function fakeAutoGoPay(): void
    {
        Http::fake([
            '*/qris/generate' => Http::response([
                'success' => true,
                'data' => [
                    'transaction_id' => 'AGP-GEN-001',
                    'order_id' => 'ORD-GEN-001',
                    'amount' => 150000,
                    'transaction_status' => 'pending',
                    'qr_string' => '000201010212',
                    'qr_url' => 'https://api.autogopay.id/qr/upgrade.png',
                    'transaction_time' => now()->toIso8601String(),
                    'expiry_time' => now()->addMinutes(5)->toIso8601String(),
                ],
            ], 200),
        ]);
    }

    public function test_upgrade_page_generates_qris_for_free_eventner()
    {
        $this->fakeAutoGoPay();

        $user = User::factory()->eventner()->create();
        Eventner::factory()->create(['user_id' => $user->id, 'plan' => 'free']);

        Livewire::actingAs($user)->test(Upgrade::class)
            ->call('generatePayment')
            ->assertSet('showPayment', true)
            ->assertSet('paymentTransactionId', 'AGP-GEN-001');

        $this->assertEquals('AGP-GEN-001', $user->eventner->fresh()->autogopay_transaction_id);
    }

    public function test_upgrade_page_redirects_paid_eventner_to_dashboard()
    {
        $user = User::factory()->eventner()->create();
        Eventner::factory()->create(['user_id' => $user->id, 'plan' => 'paid']);

        $this->actingAs($user)->get(route('eventner.billing.upgrade'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_feature_gate_redirects_to_upgrade_instead_of_403()
    {
        $user = User::factory()->eventner()->create();
        Eventner::factory()->create([
            'user_id' => $user->id,
            'plan' => 'free',
            'trial_ends_at' => now()->subDay(), // trial expired
        ]);

        // drawing adalah fitur locked_free
        $this->actingAs($user)->get(route('eventner.drawing.index'))
            ->assertRedirect(route('eventner.billing.upgrade'));
    }

    // ────────────────────────────────────────────────
    // Halaman pricing publik
    // ────────────────────────────────────────────────

    public function test_pricing_page_renders_publicly()
    {
        $this->get(route('pricing'))->assertOk();
    }

    // ────────────────────────────────────────────────
    // Harga & paket: setting admin + landing
    // ────────────────────────────────────────────────

    public function test_pricing_reflects_saas_plans()
    {
        // Admin punya paket berbayar dengan fitur tertentu (tanpa 'certificate')
        $plan = \App\Models\SaasPlan::where('is_free', false)->firstOrFail();
        $plan->update(['price' => 200000]);
        $plan->features()->delete();
        $plan->features()->createMany([
            ['feature_key' => 'tickets'],
            ['feature_key' => 'drawing'],
        ]);

        $response = $this->get(route('pricing'));
        $response->assertOk();
        $response->assertSee('200.000');
        $response->assertSee('Tiket Event');
        $response->assertSee('Drawing / Undian');
        $response->assertDontSee('Sertifikat'); // tidak termasuk paket
    }

    public function test_landing_pricing_section_renders()
    {
        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('Event Penuh')
            ->assertSee('Daftar Gratis');
    }

    public function test_admin_pricing_settings_page_saves_plan()
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(\App\Livewire\Admin\PricingSettings::class)
            ->call('createPlan')
            ->set('name', 'Paket Standar')
            ->set('price', 250000)
            ->set('registration_fee', 75000)
            ->set('sort_order', 3)
            ->set('plan_features.tickets', true)
            ->set('plan_features.certificate', false)
            ->call('savePlan')
            ->assertHasNoErrors();

        $plan = \App\Models\SaasPlan::where('name', 'Paket Standar')->first();
        $this->assertNotNull($plan);
        $this->assertEquals(250000, $plan->price);
        $this->assertEquals(75000, $plan->registration_fee);
        $this->assertTrue($plan->features->pluck('feature_key')->contains('tickets'));
        $this->assertFalse($plan->features->pluck('feature_key')->contains('certificate'));
    }

    public function test_admin_pricing_settings_toggles_and_deletes_plan()
    {
        $admin = User::factory()->admin()->create();
        $plan = \App\Models\SaasPlan::where('is_free', false)->firstOrFail();

        Livewire::actingAs($admin)->test(\App\Livewire\Admin\PricingSettings::class)
            ->call('toggleActive', $plan->id)
            ->assertHasNoErrors();

        $this->assertFalse((bool) $plan->fresh()->is_active);

        // Paket masih dipakai event → tidak bisa dihapus
        $eventner = Eventner::factory()->paid()->create();
        $eventner->update(['saas_plan_id' => $plan->id]);

        Livewire::actingAs($admin)->test(\App\Livewire\Admin\PricingSettings::class)
            ->call('deletePlan', $plan->id)
            ->assertHasNoErrors();

        $this->assertNotNull(\App\Models\SaasPlan::find($plan->id)); // masih ada
    }

    public function test_feature_gate_respects_plan_features()
    {
        // Paket berbayar tanpa fitur certificate
        $plan = \App\Models\SaasPlan::where('is_free', false)->firstOrFail();
        $plan->features()->delete();
        $plan->features()->create(['feature_key' => 'tickets']);

        $user = User::factory()->eventner()->create();
        $eventner = Eventner::factory()->paid()->create([
            'user_id' => $user->id,
            'saas_plan_id' => $plan->id,
        ]);

        $this->assertTrue($eventner->canAccessFeature('tickets'));
        $this->assertFalse($eventner->canAccessFeature('certificate')); // tidak di paket
        $this->assertArrayHasKey('certificate', $eventner->lockedFeatures());
    }

    public function test_admin_pricing_settings_route_requires_admin()
    {
        $user = User::factory()->eventner()->create();
        $this->actingAs($user)->get(route('admin.pricing-settings'))->assertForbidden();
    }

    // ────────────────────────────────────────────────
    // Paket khusus (hubungi admin)
    // ────────────────────────────────────────────────

    private function createContactPlan(): \App\Models\SaasPlan
    {
        $plan = \App\Models\SaasPlan::create([
            'name' => 'Paket Khusus',
            'slug' => 'paket-khusus',
            'price' => 0,
            'registration_fee' => 0,
            'description' => 'Sesuai kebutuhan event besar',
            'is_active' => true,
            'is_free' => false,
            'is_contact' => true,
            'contact_url' => 'https://wa.me/6281234567890',
            'sort_order' => 3,
        ]);
        $plan->features()->create(['feature_key' => 'tickets']);

        return $plan;
    }

    public function test_contact_plan_renders_hubungi_admin_button()
    {
        $this->createContactPlan();

        $response = $this->get(route('pricing'));
        $response->assertOk()
            ->assertSee('Paket Khusus')
            ->assertSee('Kustom')
            ->assertSee('Hubungi Admin')
            ->assertSee('https://wa.me/6281234567890');
    }

    public function test_contact_plan_excluded_from_register_and_upgrade()
    {
        $plan = $this->createContactPlan();

        // Halaman register tidak menampilkan paket contact
        $register = $this->get(route('register.eventner'));
        $register->assertOk()->assertDontSee('Paket Khusus');

        // Slug paket contact ditolak saat validasi register
        Livewire::test(\App\Livewire\Public\EventnerRegister::class)
            ->set('plan', 'paket-khusus')
            ->set('name', 'Panitia')
            ->set('username', 'panitia_contact')
            ->set('email', 'contact@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('nama_event', 'Event Contact')
            ->set('lokasi', 'Bandung')
            ->set('agreeTerms', true)
            ->call('save')
            ->assertHasErrors(['plan']);
    }

    public function test_contact_plan_not_used_as_default_paid_price()
    {
        $this->createContactPlan();

        // Harga berbayar default tetap dari paket QRIS (Event Penuh)
        $this->assertEquals(150000, \App\Support\Pricing::planPrice());

        // Upgrade hanya menawarkan paket QRIS
        $user = User::factory()->eventner()->create();
        Eventner::factory()->create(['user_id' => $user->id, 'plan' => 'free']);

        Livewire::actingAs($user)->test(\App\Livewire\Eventner\Settings\Billing\Upgrade::class)
            ->assertDontSee('Paket Khusus');
    }
}
