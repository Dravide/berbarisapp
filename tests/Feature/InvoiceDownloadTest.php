<?php

namespace Tests\Feature;

use App\Models\Eventner;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceDownloadTest extends TestCase
{
    use RefreshDatabase;

    private function makePaidRegistration(): Registration
    {
        $eventner = Eventner::factory()->create();
        $user = User::find($eventner->user_id);

        $reg = Registration::factory()->for($eventner)->create([
            'payment_status' => 'paid',
            'total_fee' => 500000,
            'payment_verified_at' => now(),
        ]);

        return $reg->setRelation('user', $user);
    }

    public function test_eventner_can_download_invoice_for_paid_registration(): void
    {
        $reg = $this->makePaidRegistration();

        $response = $this->actingAs($reg->user)
            ->get(route('eventner.participants.invoice', $reg->id));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_magic_link_can_download_invoice_for_paid_registration(): void
    {
        $reg = $this->makePaidRegistration();

        $response = $this->get(route('magic.link.invoice', $reg->magic_token));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_invoice_forbidden_when_unpaid(): void
    {
        $reg = $this->makePaidRegistration();
        $reg->update(['payment_status' => 'unpaid']);

        $this->actingAs($reg->user)
            ->get(route('eventner.participants.invoice', $reg->id))
            ->assertForbidden();

        $this->get(route('magic.link.invoice', $reg->magic_token))
            ->assertForbidden();
    }

    public function test_invoice_forbidden_when_pending_verification(): void
    {
        $reg = $this->makePaidRegistration();
        $reg->update(['payment_status' => 'pending_verification']);

        $this->actingAs($reg->user)
            ->get(route('eventner.participants.invoice', $reg->id))
            ->assertForbidden();

        $this->get(route('magic.link.invoice', $reg->magic_token))
            ->assertForbidden();
    }

    public function test_invoice_forbidden_for_other_eventner(): void
    {
        $reg = $this->makePaidRegistration();
        $otherEventner = Eventner::factory()->create();
        $otherUser = User::find($otherEventner->user_id);

        $this->actingAs($otherUser)
            ->get(route('eventner.participants.invoice', $reg->id))
            ->assertForbidden();
    }
}
