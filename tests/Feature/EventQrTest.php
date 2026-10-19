<?php

namespace Tests\Feature;

use App\Livewire\Eventner\EventQr;
use App\Models\Eventner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * QR Link Event.
 *
 * Empat QR dicetak panitia: halaman event, formulir pendaftaran, tiket, vote.
 * Yang diuji di sini bukan gambarnya (itu GD + chillerlan), melainkan URL
 * tujuannya dan rambu status pendaftaran pada QR formulir — QR ditempel di
 * spanduk dan hidup lebih lama daripada masa pendaftarannya.
 */
class EventQrTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Eventner} */
    private function panitia(array $atributEvent = []): array
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        $eventner = Eventner::factory()->create(array_merge([
            'user_id' => $user->id,
            'status' => 'approved',
            'subdomain' => null,
            'slug' => 'kejurcab-qr01',
        ], $atributEvent));

        return [$user, $eventner];
    }

    public function test_halaman_qr_menampilkan_empat_target_termasuk_pendaftaran()
    {
        [$user] = $this->panitia([
            'tanggal_pendaftaran' => now()->addMonth()->toDateString(),
        ]);

        $test = Livewire::actingAs($user)->test(EventQr::class)
            ->assertSee('Halaman Event')
            ->assertSee('Formulir Pendaftaran')
            ->assertSee('Laman Tiket')
            ->assertSee('Laman Vote');

        $qrs = $test->get('qrs');

        $this->assertSame(
            ['detail', 'register', 'ticket', 'vote'],
            array_keys($qrs),
            'Urutan QR mengikuti urutan perjalanan sekolah: event → daftar → tiket → vote.'
        );
    }

    public function test_url_qr_pendaftaran_menunjuk_ke_route_register_event()
    {
        [$user, $eventner] = $this->panitia();

        $qrs = Livewire::actingAs($user)->test(EventQr::class)->get('qrs');

        $this->assertSame($eventner->publicUrl('register'), $qrs['register']['url']);
        $this->assertStringContainsString('/event/kejurcab-qr01/register', $qrs['register']['url']);
    }

    public function test_qr_pendaftaran_mencetak_batas_waktu_saat_dibuka()
    {
        [$user, $eventner] = $this->panitia([
            'tanggal_pendaftaran' => '2026-11-20',
        ]);

        $qrs = Livewire::actingAs($user)->test(EventQr::class)->get('qrs');

        $this->assertSame('success', $qrs['register']['nada']);
        $this->assertStringContainsString('20 November 2026', $qrs['register']['catatan']);
    }

    public function test_qr_pendaftaran_memakai_mode_booking_saat_masih_booking()
    {
        [$user] = $this->panitia([
            'tanggal_pendaftaran' => now()->addMonth()->toDateString(),
            'technical_meeting' => now()->addWeek(),
        ]);

        $qrs = Livewire::actingAs($user)->test(EventQr::class)->get('qrs');

        $this->assertSame('info', $qrs['register']['nada'], 'Booking bukan "dibuka" — warnanya dibedakan.');
        $this->assertStringContainsString('Mode booking', $qrs['register']['catatan']);
    }

    public function test_qr_pendaftaran_menyebut_penutupan_saat_deadline_lewat()
    {
        [$user] = $this->panitia([
            'tanggal_pendaftaran' => '2026-10-01',
        ]);

        $qrs = Livewire::actingAs($user)->test(EventQr::class)->get('qrs');

        $this->assertSame('warning', $qrs['register']['nada']);
        $this->assertStringContainsString('sudah ditutup', $qrs['register']['catatan']);
    }

    /**
     * Deadline kosong = pendaftaran publik ditutup (accessor
     * computeRegistrationStatus). Rambunya harus mengatakannya, bukan
     * menampilkan tanggal kosong.
     */
    public function test_qr_pendaftaran_tanpa_deadline_menjelaskan_mengapa_ditutup()
    {
        [$user] = $this->panitia(['tanggal_pendaftaran' => null]);

        $qrs = Livewire::actingAs($user)->test(EventQr::class)->get('qrs');

        $this->assertSame('warning', $qrs['register']['nada']);
        $this->assertStringContainsString('deadline pendaftaran belum ditetapkan', $qrs['register']['catatan']);
    }

    /** QR selain formulir pendaftaran tidak perlu rambu apa pun. */
    public function test_qr_selain_pendaftaran_tanpa_rambu_status()
    {
        [$user] = $this->panitia();

        $qrs = Livewire::actingAs($user)->test(EventQr::class)->get('qrs');

        foreach (['detail', 'ticket', 'vote'] as $key) {
            $this->assertArrayNotHasKey('catatan', $qrs[$key], "QR '$key' tak seharusnya punya rambu.");
        }
    }

    public function test_setiap_qr_terender_jadi_png()
    {
        [$user] = $this->panitia();

        $qrs = Livewire::actingAs($user)->test(EventQr::class)->get('qrs');

        foreach ($qrs as $key => $qr) {
            $this->assertNotNull($qr['dataUri'], "QR '$key' gagal di-generate.");
            $this->assertStringStartsWith('data:image/png;base64,', $qr['dataUri']);
        }

        // Signature PNG di byte pertama — memastikan yang tersimpan adalah
        // gambar, bukan string kosong yang kebetulan ber-prefix.
        $png = base64_decode(substr($qrs['register']['dataUri'], strlen('data:image/png;base64,')));
        $this->assertSame("\x89PNG", substr($png, 0, 4));
    }

    public function test_eventner_lain_tidak_bisa_membaca_qr_milik_event_lain()
    {
        [$user] = $this->panitia();
        [, $eventnerLain] = $this->panitia(['slug' => 'event-lain-qr']);

        $qrs = Livewire::actingAs($user)->test(EventQr::class)->get('qrs');

        $this->assertStringNotContainsString('event-lain-qr', $qrs['register']['url']);
        $this->assertStringContainsString('kejurcab-qr01', $qrs['register']['url']);
    }
}
