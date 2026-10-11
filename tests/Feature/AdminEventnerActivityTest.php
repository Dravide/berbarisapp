<?php

namespace Tests\Feature;

use App\Models\Eventner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Panel aktivitas akun di detail event admin (/admin/eventner/{id}).
 *
 * Mencakup dua sumber data: log login/logout akun panitia (listener di
 * AppServiceProvider) dan perubahan data event (spatie activitylog).
 */
class AdminEventnerActivityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create(['is_active' => true]);
    }

    private function eventnerWithOwner(): Eventner
    {
        $owner = User::factory()->eventner()->create(['is_active' => true]);

        return Eventner::factory()->create(['user_id' => $owner->id]);
    }

    public function test_halaman_detail_menampilkan_aktivitas_tercatat()
    {
        $eventner = $this->eventnerWithOwner();

        activity()->performedOn($eventner->user)->event('login')->log('Akun berhasil masuk (login).');

        Livewire::actingAs($this->admin())
            ->test(\App\Livewire\Admin\Eventner\Show::class, ['id' => $eventner->id])
            ->assertOk()
            ->assertSee('Aktivitas Akun')
            ->assertSee('Akun berhasil masuk (login).');
    }

    public function test_login_akun_panitia_tercatat_ke_activity_log()
    {
        $eventner = $this->eventnerWithOwner();

        Livewire::test(\App\Livewire\Auth\Login::class)
            ->set('login', $eventner->user->email)
            ->set('password', 'password')
            ->call('authenticate');

        $this->assertTrue(
            Activity::where('event', 'login')->where('causer_id', $eventner->user->id)->exists(),
            'Login akun panitia tidak tercatat di activity_log.'
        );
    }

    public function test_logout_tercatat()
    {
        $eventner = $this->eventnerWithOwner();
        $this->actingAs($eventner->user);
        auth()->logout();

        $this->assertTrue(
            Activity::where('event', 'logout')->where('causer_id', $eventner->user->id)->exists(),
            'Logout tidak tercatat di activity_log.'
        );
    }

    public function test_perubahan_data_event_muncul_di_panel()
    {
        $eventner = $this->eventnerWithOwner();
        $reg = \App\Models\Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'nama_sekolah' => 'SMA Uji Coba',
        ]);

        $reg->update(['nama_sekolah' => 'SMA Berubah']);

        Livewire::actingAs($this->admin())
            ->test(\App\Livewire\Admin\Eventner\Show::class, ['id' => $eventner->id])
            ->assertOk()
            ->assertSee('SMA Uji Coba');
    }

    public function test_aktivitas_akun_lain_tidak_bocor_ke_panel()
    {
        $eventner = $this->eventnerWithOwner();
        $lain = $this->eventnerWithOwner();

        activity()->by($lain->user)->event('login')->log('Login event lain.');

        Livewire::actingAs($this->admin())
            ->test(\App\Livewire\Admin\Eventner\Show::class, ['id' => $eventner->id])
            ->assertOk()
            ->assertDontSee('Login event lain.');
    }
}
