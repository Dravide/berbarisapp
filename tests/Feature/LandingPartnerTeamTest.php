<?php

namespace Tests\Feature;

use App\Models\LandingPartner;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Section Sponsor/Media Partner & Team di landing.
 * Sponsor dikelola dari tabel landing_partners (halaman admin khusus);
 * Team dikelola dari tab baru di Pengaturan Landing Page.
 */
class LandingPartnerTeamTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create(['is_active' => true]);
    }

    // ────────────────────────────────────────────────
    // Section sponsor (data live dari landing_partners)
    // ────────────────────────────────────────────────

    public function test_section_sponsor_tampil_dengan_partner_aktif()
    {
        LandingPartner::factory()->create(['name' => 'PT Maju Berkah', 'type' => 'sponsor']);
        LandingPartner::factory()->medpart()->create(['name' => 'Harian Kita']);

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('<section id="sponsor"', $html);
        $this->assertStringContainsString('PT Maju Berkah', $html);
        $this->assertStringContainsString('Harian Kita', $html);
        $this->assertStringContainsString('Media Partner', $html);
    }

    public function test_section_sponsor_hilang_tanpa_data_dan_navnya_ikut_hilang()
    {
        $response = $this->get('/');

        $this->assertStringNotContainsString('<section id="sponsor"', $response->getContent());
        $header = substr($response->getContent(), 0, strpos($response->getContent(), '</header>'));
        $this->assertStringNotContainsString('href="#sponsor"', $header);
    }

    public function test_partner_nonaktif_tidak_tampil_di_landing()
    {
        LandingPartner::factory()->inactive()->create(['name' => 'PT Mundur']);

        $response = $this->get('/');

        $response->assertDontSee('PT Mundur', false);
        $this->assertStringNotContainsString('<section id="sponsor"', $response->getContent());
    }

    // ────────────────────────────────────────────────
    // Section team (setting landing_team)
    // ────────────────────────────────────────────────

    public function test_section_team_tampil_dengan_anggota_terisi()
    {
        Setting::set('landing_team', json_encode([
            'title' => 'Tim di Balik Layar',
            'subtitle' => 'Kenali kami.',
            'items' => [
                ['name' => 'Dery Saputra', 'role' => 'Founder', 'photo' => null],
                ['name' => 'Rani Wijaya', 'role' => 'Product Design', 'photo' => null],
            ],
        ]));

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('<section id="team"', $html);
        $this->assertStringContainsString('Tim di Balik Layar', $html);
        $this->assertStringContainsString('Dery Saputra', $html);
        $this->assertStringContainsString('Founder', $html);
        $this->assertStringContainsString('Rani Wijaya', $html);
    }

    public function test_section_team_hilang_kosong_dan_navnya_ikut_hilang()
    {
        $response = $this->get('/');

        $this->assertStringNotContainsString('<section id="team"', $response->getContent());
        $header = substr($response->getContent(), 0, strpos($response->getContent(), '</header>'));
        $this->assertStringNotContainsString('href="#team"', $header);
    }

    public function test_anggota_tanpa_nama_tidak_membuat_section_render()
    {
        // Klik "Tambah" lalu simpan tanpa mengisi: section tidak boleh muncul
        // cuma karena item array-nya ada.
        Setting::set('landing_team', json_encode([
            'title' => 'Tim Kami',
            'items' => [['name' => '', 'role' => '', 'photo' => null]],
        ]));

        $response = $this->get('/');

        $this->assertStringNotContainsString('<section id="team"', $response->getContent());
    }

    public function test_admin_menyimpan_team_dari_pengaturan_landing()
    {
        $this->actingAs($this->admin());

        Livewire::test(\App\Livewire\Admin\Setting\LandingPage::class)
            ->set('activeTab', 'team')
            ->call('addTeamItem')
            ->set('team_items.0.name', 'Dery Saputra')
            ->set('team_items.0.role', 'Founder')
            ->call('save')
            ->assertHasNoErrors();

        $team = json_decode(Setting::get('landing_team'), true);
        $this->assertSame('Dery Saputra', $team['items'][0]['name']);
        $this->assertSame('Founder', $team['items'][0]['role']);

        // Langsung tampil di landing
        $this->get('/')->assertSee('Dery Saputra', false);
    }

    // ────────────────────────────────────────────────
    // CRUD admin partner
    // ────────────────────────────────────────────────

    public function test_admin_mengelola_partner_dari_halaman_khusus()
    {
        $this->actingAs($this->admin());

        Livewire::test(\App\Livewire\Admin\LandingPartnerIndex::class)
            ->call('createPartner')
            ->set('name', 'PT Maju Berkah')
            ->set('type', 'sponsor')
            ->set('link', 'https://majuberkah.example')
            ->call('savePartner')
            ->assertHasNoErrors();

        $partner = LandingPartner::where('name', 'PT Maju Berkah')->firstOrFail();
        $this->assertSame('sponsor', $partner->type);
        $this->assertTrue($partner->is_active);

        // Edit → tipe jadi medpart
        Livewire::test(\App\Livewire\Admin\LandingPartnerIndex::class)
            ->call('editPartner', $partner->id)
            ->set('type', 'medpart')
            ->call('savePartner')
            ->assertHasNoErrors();

        $this->assertSame('medpart', $partner->fresh()->type);
    }

    public function test_admin_menghapus_partner_beserta_logonya()
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $logo = \Illuminate\Http\Testing\File::fake()->image('logo.png');
        $path = $logo->store('landing-partners', 'public');

        $partner = LandingPartner::create([
            'name' => 'PT Hapus Saya',
            'logo' => $path,
            'type' => 'sponsor',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Livewire::test(\App\Livewire\Admin\LandingPartnerIndex::class)
            ->call('deletePartner', $partner->id);

        $this->assertDatabaseMissing('landing_partners', ['id' => $partner->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_validasi_nama_dan_link_partner()
    {
        $this->actingAs($this->admin());

        Livewire::test(\App\Livewire\Admin\LandingPartnerIndex::class)
            ->call('createPartner')
            ->set('name', '')
            ->set('link', 'bukan-url')
            ->call('savePartner')
            ->assertHasErrors(['name', 'link']);

        $this->assertSame(0, LandingPartner::count());
    }

    // ────────────────────────────────────────────────
    // Akses
    // ────────────────────────────────────────────────

    public function test_halaman_admin_partner_terlarang_bukan_admin()
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);

        $this->actingAs($user)->get(route('admin.landing-partners'))
            ->assertForbidden();
    }
}
