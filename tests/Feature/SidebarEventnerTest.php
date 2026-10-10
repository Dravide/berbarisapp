<?php

namespace Tests\Feature;

use App\Models\Eventner;
use App\Models\SaasPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sidebar untuk akun Eventner: struktur menu, gate fitur, dan jalur upgrade.
 * Halaman yang benar-benar memuat sidebar = eventner.dashboard (polanya di
 * EventnerPasswordTest).
 */
class SidebarEventnerTest extends TestCase
{
    use RefreshDatabase;

    private function akunDenganEvent(): User
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);

        Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        return $user;
    }

    private function paket(bool $isFree, array $features = []): SaasPlan
    {
        $plan = SaasPlan::create([
            'name' => $isFree ? 'Paket Uji Gratis' : 'Paket Uji Berbayar',
            'slug' => $isFree ? 'uji-gratis' : 'uji-berbayar',
            'is_free' => $isFree,
            'is_active' => true,
        ]);

        foreach ($features as $feature) {
            $plan->features()->create(['feature_key' => $feature]);
        }

        return $plan;
    }

    public function test_menu_format_nilai_tanpa_item_unduh_terpisah()
    {
        // "Unduh Format Penilaian" kini menu dropdown di halaman Builder —
        // dua item menu untuk satu fitur jadi satu.
        $this->actingAs($this->akunDenganEvent())
            ->get(route('eventner.dashboard'))
            ->assertOk()
            ->assertSee('Format Penilaian')
            ->assertDontSee('Unduh Format Penilaian');
    }

    public function test_menu_videotron_display_taut_ke_mode_loop()
    {
        $user = $this->akunDenganEvent();
        $eventner = $user->eventner;

        $this->actingAs($user)
            ->get(route('eventner.dashboard'))
            ->assertOk()
            ->assertSee('Videotron Display')
            ->assertSee(event_url($eventner, 'videotron') . '?mode=loop', false);
    }

    public function test_menu_upgrade_pakai_registration_paid_at_bukan_plan()
    {
        // Eventner ber-plan 'paid' yang BELUM membayar tetap melihat menu —
        // penjaga lama (plan !== 'paid') menyembunyikannya padahal QRIS-nya
        // belum lewat.
        $user = $this->akunDenganEvent();
        $eventner = $user->eventner;
        $eventner->update(['plan' => 'paid', 'registration_paid_at' => null]);

        $this->actingAs($user)
            ->get(route('eventner.dashboard'))
            ->assertOk()
            ->assertSee('Upgrade Paket');

        // Yang sudah membayar (jalur apa pun) tak perlu menu upgrade lagi.
        $eventner->update(['registration_paid_at' => now()]);

        $this->actingAs($user)
            ->get(route('eventner.dashboard'))
            ->assertOk()
            ->assertDontSee('Upgrade Paket');
    }

    public function test_komentar_voting_terkunci_terpisah_dari_transaksi()
    {
        // Paket berbayar yang membawa vote_transactions tapi bukan
        // vote_comments: menu Komentar terkunci, Transaksi tidak.
        // Sidebar menampilkan item terkunci DENGAN ikon gembok (tak
        // disembunyikan), jadi penjaganya diuji di level model + menu
        // JSON (SearchLinks) yang menandai locked.
        $user = $this->akunDenganEvent();
        $eventner = $user->eventner;
        $plan = $this->paket(false, ['vote_transactions']);

        $eventner->assignPlan($plan, 'test');

        $this->assertFalse($eventner->canAccessFeature('vote_comments'));
        $this->assertTrue($eventner->canAccessFeature('vote_transactions'));

        // Menu JSON (pencarian/quick-nav) menandai Komentar terkunci,
        // Transaksi tidak — keduanya tetap tampil.
        $this->actingAs($user)
            ->get(route('eventner.dashboard'))
            ->assertOk()
            ->assertSee('Komentar Voting')
            ->assertSee(route('eventner.vote-transactions.index'));
    }
}
