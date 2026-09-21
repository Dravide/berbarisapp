<?php

namespace Tests\Feature;

use App\Livewire\Eventner\Settings\Password;
use App\Models\Eventner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Ganti password untuk akun eventner.
 *
 * Sebelumnya tidak ada jalur sama sekali: satu-satunya cara mengganti password
 * eventner adalah lewat tombol reset di /admin/users, jadi pemilik event harus
 * menunggu admin platform. Test di sini mengunci dua hal yang mudah rusak:
 * password lama benar-benar diverifikasi, dan nilainya sampai ke DB dalam
 * bentuk hash sekali (bukan dua kali — model User sudah punya cast 'hashed').
 */
class EventnerPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function akun(string $password = 'password-lama'): User
    {
        $user = User::factory()->eventner()->create([
            'password' => Hash::make($password),
        ]);

        Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        return $user;
    }

    private function komponen(User $user)
    {
        return Livewire::actingAs($user)->test(Password::class);
    }

    public function test_halaman_ganti_password_terbuka_untuk_eventner()
    {
        $user = $this->akun();

        $this->actingAs($user)
            ->get(route('eventner.password.index'))
            ->assertOk()
            ->assertSee('Ganti Password');
    }

    public function test_password_berhasil_diganti()
    {
        $user = $this->akun('password-lama');

        $this->komponen($user)
            ->set('password_lama', 'password-lama')
            ->set('password_baru', 'password-baru-2026')
            ->set('password_baru_confirmation', 'password-baru-2026')
            ->call('save')
            ->assertHasNoErrors();

        // Hash::check, bukan perbandingan string: kalau nilai tersimpan
        // ter-hash dua kali, password barunya tidak akan pernah cocok.
        $this->assertTrue(Hash::check('password-baru-2026', $user->fresh()->password));
    }

    public function test_password_lama_salah_ditolak_dan_tidak_mengubah_apa_pun()
    {
        $user = $this->akun('password-lama');

        $this->komponen($user)
            ->set('password_lama', 'password-keliru')
            ->set('password_baru', 'password-baru-2026')
            ->set('password_baru_confirmation', 'password-baru-2026')
            ->call('save')
            ->assertHasErrors('password_lama');

        $this->assertTrue(Hash::check('password-lama', $user->fresh()->password));
    }

    public function test_konfirmasi_tidak_cocok_ditolak()
    {
        $user = $this->akun('password-lama');

        $this->komponen($user)
            ->set('password_lama', 'password-lama')
            ->set('password_baru', 'password-baru-2026')
            ->set('password_baru_confirmation', 'password-beda-2026')
            ->call('save')
            ->assertHasErrors('password_baru');

        $this->assertTrue(Hash::check('password-lama', $user->fresh()->password));
    }

    public function test_password_baru_terlalu_pendek_ditolak()
    {
        $user = $this->akun('password-lama');

        $this->komponen($user)
            ->set('password_lama', 'password-lama')
            ->set('password_baru', 'pendek')
            ->set('password_baru_confirmation', 'pendek')
            ->call('save')
            ->assertHasErrors('password_baru');

        $this->assertTrue(Hash::check('password-lama', $user->fresh()->password));
    }

    public function test_eventner_tanpa_event_tidak_bisa_membuka_halaman()
    {
        $user = User::factory()->eventner()->create([
            'password' => Hash::make('password-lama'),
        ]);

        $this->actingAs($user)
            ->get(route('eventner.password.index'))
            ->assertForbidden();
    }

    public function test_hanya_baris_user_yang_sedang_login_yang_berubah()
    {
        $user = $this->akun('password-lama');
        $lain = User::factory()->eventner()->create([
            'password' => Hash::make('password-lama'),
        ]);
        Eventner::factory()->create(['user_id' => $lain->id, 'status' => 'approved']);

        // Gerbang izin di mount() memakai Auth::user()->eventner, jadi akun
        // yang login selalu mengurus eventnya sendiri. Test ini mengunci
        // konsekuensinya: password akun lain tidak ikut tersentuh.
        $this->komponen($user)
            ->set('password_lama', 'password-lama')
            ->set('password_baru', 'password-baru-2026')
            ->set('password_baru_confirmation', 'password-baru-2026')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('password-baru-2026', $user->fresh()->password));
        $this->assertTrue(Hash::check('password-lama', $lain->fresh()->password));
    }

    public function test_password_lama_diinvalidasi_setelah_ganti()
    {
        $user = $this->akun('password-lama');

        $this->komponen($user)
            ->set('password_lama', 'password-lama')
            ->set('password_baru', 'password-baru-2026')
            ->set('password_baru_confirmation', 'password-baru-2026')
            ->call('save')
            ->assertHasNoErrors();

        $this->komponen($user->fresh())
            ->set('password_lama', 'password-lama')
            ->set('password_baru', 'password-lain-2026')
            ->set('password_baru_confirmation', 'password-lain-2026')
            ->call('save')
            ->assertHasErrors('password_lama');
    }

    public function test_menu_sidebar_menampilkan_tautan_ganti_password()
    {
        $user = $this->akun();

        // /dashboard hanya melempar ke dashboard eventner, jadi halaman yang
        // benar-benar memuat sidebar adalah eventner.dashboard.
        $this->actingAs($user)
            ->get(route('eventner.dashboard'))
            ->assertOk()
            ->assertSee(route('eventner.password.index'));
    }
}
