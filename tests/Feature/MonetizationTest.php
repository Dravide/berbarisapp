<?php

namespace Tests\Feature;

use App\Livewire\Eventner\Settings\Billing\Upgrade;
use App\Models\Eventner;
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
}
