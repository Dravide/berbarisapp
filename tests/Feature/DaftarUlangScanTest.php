<?php

namespace Tests\Feature;

use App\Livewire\Eventner\DaftarUlang\Scan;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Scan QR kartu peserta di meja daftar ulang.
 *
 * Kartu peserta (registrations.qr_token) sudah tercetak; layar scan mengubah
 * token itu menjadi kehadiran tanpa mencari satu per satu. Yang dijaga:
 *
 *  1. Token milik event lain tidak boleh menandai apa pun — kartu sah
 *     tetapi beda meja, harus punya pesan tersendiri.
 *  2. Penandaan hadir idempoten — dua petugas memukul tombol hampir
 *     bersamaan tidak menimpa jam kedatangan yang pertama.
 *  3. Pembatalan eksplisit, bukan efek samping scan kedua.
 */
class DaftarUlangScanTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Eventner $eventner;

    private CompetitionCategory $level;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->eventner()->create(['is_active' => true]);
        $this->eventner = Eventner::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'approved',
        ]);

        $parent = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => null,
            'name' => 'PBB Putra',
        ]);
        $this->level = CompetitionCategory::factory()->create([
            'eventner_id' => $this->eventner->id,
            'parent_id' => $parent->id,
            'name' => 'Kelas 9',
        ]);
    }

    private function peserta(string $nama): Registration
    {
        return Registration::factory()->for($this->eventner, 'eventner')->create([
            'competition_category_id' => $this->level->id,
            'nama_sekolah' => $nama,
        ]);
    }

    public function test_scan_token_valid_mengisi_daftar_ulang_at()
    {
        $reg = $this->peserta('SMA Scan Pertama');

        Livewire::actingAs($this->user)
            ->test(Scan::class)
            ->call('lookup', $reg->qr_token)
            ->assertSet('result.kind', 'ready')
            ->call('tandaiHadir', $reg->id);

        $this->assertNotNull($reg->fresh()->daftar_ulang_at);
    }

    public function test_scan_token_event_lain_ditolak()
    {
        $reg = $this->peserta('SMA Milik Sendiri');

        // Event kedua milik petugas lain — token pesertanya harus ditolak.
        $userLain = User::factory()->eventner()->create(['is_active' => true]);
        $eventLain = Eventner::factory()->create([
            'user_id' => $userLain->id,
            'status' => 'approved',
        ]);
        $regAsing = Registration::factory()->for($eventLain, 'eventner')->create();

        Livewire::actingAs($this->user)
            ->test(Scan::class)
            ->call('lookup', $regAsing->qr_token)
            ->assertSet('result.kind', 'wrong_event');

        $this->assertNull($regAsing->fresh()->daftar_ulang_at);
        $this->assertNotNull($reg->fresh()); // peserta sendiri tak tersentuh
    }

    public function test_scan_token_tak_kenal_menampilkan_pesan()
    {
        Livewire::actingAs($this->user)
            ->test(Scan::class)
            ->call('lookup', 'ZZZZ9999')
            ->assertSet('result.kind', 'not_found')
            ->assertSet('result.code', 'ZZZZ9999');
    }

    public function test_input_manual_diterima()
    {
        $reg = $this->peserta('SMA Input Manual');

        // Kode dari kartu bisa terbaca huruf kecil oleh petugas — disamakan.
        Livewire::actingAs($this->user)
            ->test(Scan::class)
            ->set('manualCode', strtolower($reg->qr_token))
            ->call('lookup')
            ->assertSet('result.kind', 'ready')
            ->assertSet('result.registration.id', $reg->id);
    }

    public function test_batal_mengosongkan_daftar_ulang_at()
    {
        $reg = $this->peserta('SMA Batal Hadir');
        $reg->update(['daftar_ulang_at' => now()]);

        Livewire::actingAs($this->user)
            ->test(Scan::class)
            ->call('batalkan', $reg->id)
            ->assertSet('result.kind', 'ready');

        $this->assertNull($reg->fresh()->daftar_ulang_at);
    }

    public function test_tandai_hadir_dua_kali_tidak_mengubah_jam()
    {
        $reg = $this->peserta('SMA Idempoten');

        $komponen = Livewire::actingAs($this->user)->test(Scan::class);

        $komponen->call('tandaiHadir', $reg->id);
        $jamPertama = $reg->fresh()->daftar_ulang_at;

        $komponen->call('tandaiHadir', $reg->id);

        $this->assertSame($jamPertama?->format('U'), $reg->fresh()->daftar_ulang_at?->format('U'));
    }

    public function test_tamu_diarahkan_login()
    {
        $this->get(route('eventner.daftar-ulang.scan'))->assertRedirect(route('login'));
    }
}
