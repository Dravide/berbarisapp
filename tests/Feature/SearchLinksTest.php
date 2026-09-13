<?php

namespace Tests\Feature;

use App\Livewire\SearchLinks;
use App\Models\Eventner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SearchLinksTest extends TestCase
{
    use RefreshDatabase;

    private function links(User $user): array
    {
        Auth::login($user);

        return (new SearchLinks())->quickLinks;
    }

    private function flat(array $groups): array
    {
        return collect($groups)->flatMap(fn($g) => $g['items'])->keyBy('label')->all();
    }

    public function test_semua_tautan_eventner_mengarah_ke_route_yang_ada(): void
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'scoring_code' => 'PBBTEST',
        ]);

        foreach ($this->flat($this->links($user)) as $label => $item) {
            $this->assertNotSame('#', $item['url'], "Tautan '$label' tidak menghasilkan URL.");
        }
    }

    public function test_semua_tautan_admin_mengarah_ke_route_yang_ada(): void
    {
        $admin = User::factory()->admin()->create(['is_active' => true]);

        foreach ($this->flat($this->links($admin)) as $label => $item) {
            $this->assertNotSame('#', $item['url'], "Tautan '$label' tidak menghasilkan URL.");
        }
    }

    public function test_tautan_baru_eventner_tersedia(): void
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'scoring_code' => 'PBBTEST',
        ]);

        $links = $this->flat($this->links($user));

        foreach (['Tempat Lomba', 'Rundown Acara', 'Komentar Voting', 'Kirim Notifikasi', 'TTD & Stempel'] as $label) {
            $this->assertArrayHasKey($label, $links, "Tautan '$label' belum ada di Quick Page Links.");
        }

        $this->assertStringContainsString('/eventner/venues', $links['Tempat Lomba']['url']);
    }

    public function test_tautan_baru_admin_tersedia(): void
    {
        $admin = User::factory()->admin()->create(['is_active' => true]);
        $links = $this->flat($this->links($admin));

        $this->assertArrayHasKey('Pengaturan Harga', $links);
        $this->assertArrayHasKey('Pendapatan', $links);
        $this->assertStringContainsString('/admin/pricing-settings', $links['Pengaturan Harga']['url']);
    }

    public function test_scoreboard_hanya_muncul_saat_event_punya_scoring_code(): void
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'scoring_code' => null,
        ]);

        $links = $this->flat($this->links($user));

        $this->assertArrayNotHasKey('Live Scoreboard', $links);
        $this->assertArrayNotHasKey('Pengumuman Juara', $links);
    }

    public function test_scoreboard_terbuka_di_tab_baru(): void
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'scoring_code' => 'PBBTEST',
        ]);

        $links = $this->flat($this->links($user));

        $this->assertSame('_blank', $links['Live Scoreboard']['target']);
        $this->assertStringContainsString('/scoreboard/PBBTEST', $links['Live Scoreboard']['url']);
        $this->assertStringContainsString('/champions/PBBTEST', $links['Pengumuman Juara']['url']);
    }

    public function test_group_kosong_dibuang_dari_hasil(): void
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);
        Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'scoring_code' => null,
        ]);

        // Grup Overlay hanya berisi dua tautan bersyarat, jadi harus hilang seluruhnya.
        foreach ($this->links($user) as $group) {
            $this->assertNotEmpty($group['items'], "Grup '{$group['category']}' kosong tapi masih dikirim.");
        }
    }
}
