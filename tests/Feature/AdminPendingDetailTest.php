<?php

namespace Tests\Feature;

use App\Livewire\Admin\Eventner\Pending;
use App\Models\Eventner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Modal detail di /admin/eventner/pending — data kontak pendaftar.
 *
 * Sumbernya hanya yang sudah ada: akun pendaftar (users) dan tautan profil
 * event (eventners.link_*). Form pendaftaran tidak meminta nomor telepon,
 * jadi WhatsApp memang sering kosong.
 */
class AdminPendingDetailTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create(['is_active' => true]);
    }

    public function test_detail_menampilkan_kontak_pendaftar()
    {
        $user = User::factory()->eventner()->create([
            'name' => 'Panitia SMAN 1',
            'username' => 'panitia_sman1',
            'email' => 'panitia@contoh.com',
        ]);
        $eventner = Eventner::factory()->pending()->create([
            'user_id' => $user->id,
            'nama_event' => 'Lomba PBB 2026',
            'link_whatsapp' => 'https://wa.me/628123456789',
            'link_instagram' => 'https://instagram.com/lombapbb',
        ]);

        Livewire::actingAs($this->admin())
            ->test(Pending::class)
            ->call('openDetailModal', $eventner->id)
            ->assertSet('showDetailModal', true)
            ->assertSee('Panitia SMAN 1')
            ->assertSee('panitia_sman1')
            ->assertSee('panitia@contoh.com')
            ->assertSee('https://wa.me/628123456789')
            ->assertSee('https://instagram.com/lombapbb');
    }

    /** Nomor telanjang dirangkai jadi tautan wa.me yang bisa diklik. */
    public function test_nomor_whatsapp_telanjang_dirangkai_jadi_tautan()
    {
        $eventner = Eventner::factory()->pending()->create([
            'link_whatsapp' => '0812-3456-789',
        ]);

        Livewire::actingAs($this->admin())
            ->test(Pending::class)
            ->call('openDetailModal', $eventner->id)
            ->assertSee('https://wa.me/628123456789');
    }

    /** Kontak yang belum diisi ditandai jelas, bukan dibiarkan kosong. */
    public function test_kontak_kosong_ditandai_belum_diisi()
    {
        $eventner = Eventner::factory()->pending()->create([
            'link_whatsapp' => null,
            'link_instagram' => null,
            'link_tiktok' => null,
        ]);

        Livewire::actingAs($this->admin())
            ->test(Pending::class)
            ->call('openDetailModal', $eventner->id)
            ->assertSee('Belum diisi');
    }

    /** Setelah disetujui, barisnya hilang dari daftar — modal pun ditutup. */
    public function test_approve_menutup_modal_detail()
    {
        $eventner = Eventner::factory()->pending()->create();

        Livewire::actingAs($this->admin())
            ->test(Pending::class)
            ->call('openDetailModal', $eventner->id)
            ->call('approve', $eventner->id)
            ->assertSet('showDetailModal', false)
            ->assertSet('detail', null);
    }
}
