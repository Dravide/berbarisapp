<?php

namespace Tests\Feature;

use App\Models\Eventner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Deadline Pendaftaran kosong = pendaftaran publik tertutup.
 *
 * Pendaftar biasanya diinput manual oleh panitia, jadi event tanpa deadline
 * tidak boleh membuka pendaftaran mandiri di halaman publik.
 *
 * Input manual panitia tidak lewat gerbang ini — Registration::create()
 * dipanggil langsung dari Participant\Index, bukan lewat halaman publik.
 */
class EventnerRegistrationWindowTest extends TestCase
{
    use RefreshDatabase;

    private function buatStatusEventner(string $status): Eventner
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);

        return Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'registration_status' => $status,
            // Factory memakai Str::random() yang mengandung huruf besar,
            // sedangkan validasi subdomain hanya menerima huruf kecil.
            'subdomain' => null,
        ]);
    }

    public function test_deadline_kosong_menutup_pendaftaran(): void
    {
        $eventner = new Eventner([
            'tanggal_pendaftaran' => null,
            'tanggal' => now()->addMonth()->toDateString(),
        ]);

        $this->assertSame('closed', $eventner->computeRegistrationStatus());
    }

    public function test_deadline_masa_depan_membuka_pendaftaran(): void
    {
        $eventner = new Eventner([
            'tanggal_pendaftaran' => now()->addMonth()->toDateString(),
            'tanggal' => now()->addMonths(2)->toDateString(),
        ]);

        $this->assertSame('open', $eventner->computeRegistrationStatus());
    }

    public function test_deadline_terlewat_menutup_pendaftaran(): void
    {
        $eventner = new Eventner([
            'tanggal_pendaftaran' => now()->subDay()->toDateString(),
            'tanggal' => now()->addMonth()->toDateString(),
        ]);

        $this->assertSame('closed', $eventner->computeRegistrationStatus());
    }

    public function test_halaman_daftar_publik_menolak_saat_deadline_kosong(): void
    {
        $eventner = $this->buatStatusEventner('closed');
        $eventner->update(['tanggal_pendaftaran' => null]);

        $this->get("/event/{$eventner->slug}/register")
            ->assertStatus(200)
            ->assertSee('Pendaftaran Ditutup');
    }

    /**
     * Inti perbaikannya: kolom tersimpan bisa basi, pembacaan tidak boleh.
     */
    public function test_status_basi_di_kolom_tidak_dipakai_halaman_publik(): void
    {
        $eventner = $this->buatStatusEventner('open');
        $eventner->update([
            'tanggal_pendaftaran' => now()->subMonth()->toDateString(),
            'tanggal' => now()->addMonth()->toDateString(),
        ]);

        // Kolomnya memang masih 'open' — hanya accessor yang menyegarkan.
        $this->assertSame('open', $eventner->fresh()->getRawOriginal('registration_status'));

        $this->assertSame('closed', $eventner->fresh()->registration_status);
        $this->get("/event/{$eventner->slug}")->assertDontSee('Daftar Sekarang', false);
    }

    /**
     * Panitia harus tahu dua hal: kenapa tertutup, dan bahwa mereka tetap
     * bisa menambah pendaftar sendiri tanpa membuka pendaftaran publik.
     */
    public function test_profil_menjelaskan_alasan_dan_jalur_input_manual(): void
    {
        $eventner = $this->buatStatusEventner('closed');
        $eventner->update(['tanggal_pendaftaran' => null]);

        Livewire::actingAs($eventner->user)
            ->test(\App\Livewire\Eventner\Settings\Profile::class)
            ->assertSee('Deadline Pendaftaran belum diset')
            ->assertSee('halaman Peserta')
            ->assertSee(route('eventner.participants.index'), false);
    }

    public function test_profil_menyebut_tanggal_saat_deadline_sudah_lewat(): void
    {
        $eventner = $this->buatStatusEventner('open');
        $eventner->update([
            'tanggal_pendaftaran' => now()->subMonth()->toDateString(),
            'tanggal' => now()->addMonth()->toDateString(),
        ]);

        Livewire::actingAs($eventner->user)
            ->test(\App\Livewire\Eventner\Settings\Profile::class)
            ->assertSee('sudah lewat')
            ->assertDontSee('Deadline Pendaftaran belum diset');
    }

    public function test_simpan_profil_mengunci_status_saat_deadline_kosong(): void
    {
        $eventner = $this->buatStatusEventner('open');
        $eventner->update(['tanggal_pendaftaran' => now()->addMonth()->toDateString()]);

        Livewire::actingAs($eventner->user)
            ->test(\App\Livewire\Eventner\Settings\Profile::class)
            ->set('tanggal_pendaftaran', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('closed', $eventner->fresh()->registration_status);
    }

    public function test_simpan_profil_membuka_status_saat_deadline_diisi(): void
    {
        $eventner = $this->buatStatusEventner('closed');
        $eventner->update(['tanggal_pendaftaran' => null]);

        Livewire::actingAs($eventner->user)
            ->test(\App\Livewire\Eventner\Settings\Profile::class)
            ->set('tanggal_pendaftaran', now()->addMonth()->toDateString())
            ->set('tanggal', now()->addMonths(2)->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('open', $eventner->fresh()->registration_status);
    }
}
