<?php

namespace Tests\Feature;

use App\Models\DeviceToken;
use App\Models\Eventner;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceTokenApiTest extends TestCase
{
    use RefreshDatabase;

    private function authAsRegistration(Registration $reg): string
    {
        $token = $reg->createToken('test-device')->plainTextToken;
        return $token;
    }

    public function test_register_device_token()
    {
        $event = Eventner::factory()->create(['status' => 'approved']);
        $reg = Registration::factory()->create([
            'eventner_id' => $event->id,
            'status_berkas' => 'confirmed',
        ]);

        $token = $this->authAsRegistration($reg);

        $response = $this->postJson('/api/v1/portal/device-token', [
            'token' => 'fcm_test_token_abc123',
            'platform' => 'android',
        ], ['Authorization' => "Bearer {$token}"]);

        $response->assertOk()
            ->assertJsonPath('message', 'Token device berhasil disimpan.');

        $this->assertDatabaseHas('device_tokens', [
            'registration_id' => $reg->id,
            'token' => 'fcm_test_token_abc123',
            'platform' => 'android',
        ]);
    }

    public function test_upsert_token_no_duplicate()
    {
        $event = Eventner::factory()->create(['status' => 'approved']);
        $reg = Registration::factory()->create([
            'eventner_id' => $event->id,
            'status_berkas' => 'confirmed',
        ]);

        DeviceToken::create([
            'registration_id' => $reg->id,
            'token' => 'fcm_token_xyz',
            'platform' => 'ios',
        ]);

        $token = $this->authAsRegistration($reg);

        $response = $this->postJson('/api/v1/portal/device-token', [
            'token' => 'fcm_token_xyz',
            'platform' => 'android',
        ], ['Authorization' => "Bearer {$token}"]);

        $response->assertOk();

        // Pastikan tidak ada duplikat
        $this->assertEquals(1, DeviceToken::where('token', 'fcm_token_xyz')->count());
        $this->assertEquals('android', DeviceToken::where('token', 'fcm_token_xyz')->first()->platform);
    }

    public function test_requires_auth()
    {
        $this->postJson('/api/v1/portal/device-token', [
            'token' => 'fcm_token',
        ])->assertUnauthorized();
    }

    public function test_requires_token_field()
    {
        $event = Eventner::factory()->create(['status' => 'approved']);
        $reg = Registration::factory()->create(['eventner_id' => $event->id]);
        $token = $this->authAsRegistration($reg);

        $this->postJson('/api/v1/portal/device-token', [], ['Authorization' => "Bearer {$token}"])
            ->assertStatus(422);
    }
}
