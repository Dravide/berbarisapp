<?php

namespace Tests\Feature;

use App\Models\Eventner;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    private function fakeAutoGoPay(): void
    {
        Http::fake([
            '*/qris/generate' => Http::response([
                'success' => true,
                'data' => [
                    'transaction_id' => 'AGP-TEST-001',
                    'order_id' => 'ORD-001',
                    'amount' => 50000,
                    'transaction_status' => 'pending',
                    'qr_string' => '000201010212',
                    'qr_url' => 'https://api.autogopay.id/qr/test.png',
                    'transaction_time' => now()->toIso8601String(),
                    'expiry_time' => now()->addMinutes(5)->toIso8601String(),
                ],
            ], 200),
        ]);
    }

    public function test_paid_ticket_purchase_creates_pending_ticket()
    {
        $this->fakeAutoGoPay();

        $event = Eventner::factory()->create([
            'status' => 'approved',
            'ticket_active' => true,
            'ticket_price' => 50000,
        ]);

        $response = $this->postJson('/api/v1/ticket/purchase', [
            'event_slug' => $event->slug,
            'buyer_name' => 'Budi Santoso',
            'buyer_email' => 'budi@email.com',
            'quantity' => 2,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'PENDING')
            ->assertJsonPath('data.total_amount', 100000)
            ->assertJsonPath('data.quantity', 2)
            ->assertJsonStructure(['data' => ['order_code', 'qr_url']]);

        $this->assertDatabaseHas('tickets', [
            'eventner_id' => $event->id,
            'status' => 'PENDING',
            'total_amount' => 100000,
        ]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/qris/generate'));
    }

    public function test_free_ticket_purchase_is_active_without_payment()
    {
        // Tiket gratis — tidak boleh hit API pembayaran.
        $event = Eventner::factory()->create([
            'status' => 'approved',
            'ticket_active' => true,
            'ticket_price' => 0,
        ]);

        $response = $this->postJson('/api/v1/ticket/purchase', [
            'event_slug' => $event->slug,
            'buyer_name' => 'Siti Aminah',
            'buyer_email' => 'siti@email.com',
            'quantity' => 1,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'ACTIVE')
            ->assertJsonPath('data.total_amount', 0);

        $this->assertDatabaseHas('tickets', [
            'eventner_id' => $event->id,
            'status' => 'ACTIVE',
            'total_amount' => 0,
        ]);

        Http::assertNothingSent();
    }

    public function test_purchase_requires_event_slug()
    {
        $response = $this->postJson('/api/v1/ticket/purchase', [
            'buyer_name' => 'Budi',
            'buyer_email' => 'budi@email.com',
            'quantity' => 1,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('event_slug');
    }

    public function test_purchase_rejected_when_ticket_inactive()
    {
        $event = Eventner::factory()->create([
            'status' => 'approved',
            'ticket_active' => false,
        ]);

        $response = $this->postJson('/api/v1/ticket/purchase', [
            'event_slug' => $event->slug,
            'buyer_name' => 'Budi',
            'buyer_email' => 'budi@email.com',
            'quantity' => 1,
        ]);

        $response->assertStatus(400);
    }

    public function test_status_requires_event_slug()
    {
        $event = Eventner::factory()->create(['status' => 'approved']);
        $ticket = Ticket::create([
            'eventner_id' => $event->id,
            'order_code' => 'TKT-STATUS-1',
            'buyer_name' => 'Budi',
            'buyer_email' => 'budi@email.com',
            'quantity' => 1,
            'total_amount' => 0,
            'status' => 'ACTIVE',
        ]);

        $this->getJson("/api/v1/ticket/status/{$ticket->order_code}")
            ->assertStatus(404);
    }

    public function test_status_scoped_to_event()
    {
        $event = Eventner::factory()->create(['status' => 'approved']);
        $other = Eventner::factory()->create(['status' => 'approved']);
        $ticket = Ticket::create([
            'eventner_id' => $event->id,
            'order_code' => 'TKT-SCOPED-1',
            'buyer_name' => 'Budi',
            'buyer_email' => 'budi@email.com',
            'quantity' => 1,
            'total_amount' => 0,
            'status' => 'ACTIVE',
        ]);

        // Slug event lain harus 404 — cegah enumerasi order/buyer lintas event.
        $this->getJson("/api/v1/ticket/status/{$ticket->order_code}?event_slug={$other->slug}")
            ->assertStatus(404);

        // Slug yang benar harus sukses.
        $this->getJson("/api/v1/ticket/status/{$ticket->order_code}?event_slug={$event->slug}")
            ->assertOk()
            ->assertJsonPath('data.status', 'ACTIVE')
            ->assertJsonPath('data.order_code', 'TKT-SCOPED-1');
    }

    public function test_pending_event_ticket_endpoint_404()
    {
        $event = Eventner::factory()->pending()->create([
            'ticket_active' => true,
        ]);

        $this->postJson('/api/v1/ticket/purchase', [
            'event_slug' => $event->slug,
            'buyer_name' => 'Budi',
            'buyer_email' => 'budi@email.com',
            'quantity' => 1,
        ])->assertStatus(404);
    }
}
