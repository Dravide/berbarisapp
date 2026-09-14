<?php

namespace Tests\Feature;

use App\Livewire\Admin\Setting\LandingPage;
use App\Livewire\Public\HelpSupport;
use App\Livewire\Public\LandingPage as PublicLandingPage;
use App\Models\Eventner;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminLandingSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_saves_contact_and_schedule_settings()
    {
        $admin = User::factory()->admin()->create();

        $comp = Livewire::actingAs($admin)->test(LandingPage::class);
        $comp->set('contact_phone', '+62 800-123-4567')
            ->set('contact_email', 'kontak@berbaris.test')
            ->set('contact_address', 'Jl. Test No. 1')
            ->set('schedule_title', 'Jadwal Event')
            ->call('addScheduleItem')
            ->set('schedule_items.0.date', '10 Jul')
            ->set('schedule_items.0.title', 'Pembukaan')
            ->call('save');

        $contact = json_decode(Setting::get('landing_contact'), true);
        $this->assertEquals('+62 800-123-4567', $contact['phone']);
        $this->assertEquals('kontak@berbaris.test', $contact['email']);

        $schedule = json_decode(Setting::get('landing_schedule'), true);
        $this->assertEquals('Jadwal Event', $schedule['title']);
        $this->assertEquals('Pembukaan', $schedule['items'][0]['title']);
    }

    public function test_gambar_hero_tetap_ada_setelah_simpan_dua_kali()
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $comp = Livewire::actingAs($admin)->test(LandingPage::class);
        $comp->set('hero_background_image', UploadedFile::fake()->image('hero.jpg', 800, 600))
            ->call('save');

        $first = json_decode(Setting::get('landing_hero'), true)['background_image'];
        $this->assertNotEmpty($first);
        Storage::disk('public')->assertExists($first);

        // Simpan lagi tanpa memilih gambar baru — gambar lama tidak boleh hilang.
        $comp->call('save');

        $second = json_decode(Setting::get('landing_hero'), true)['background_image'];
        $this->assertSame($first, $second);
        Storage::disk('public')->assertExists($second);
    }

    public function test_gambar_galeri_tersimpan_dan_muncul_di_state()
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $comp = Livewire::actingAs($admin)->test(LandingPage::class);
        $comp->call('addGalleryItem')
            ->set('gallery_items.0.image_upload', UploadedFile::fake()->image('galeri.png', 400, 300))
            ->set('gallery_items.0.caption', 'Foto Pembukaan')
            ->call('save');

        $saved = json_decode(Setting::get('landing_gallery'), true)['items'][0];
        $this->assertNotEmpty($saved['image']);
        $this->assertSame('Foto Pembukaan', $saved['caption']);
        Storage::disk('public')->assertExists($saved['image']);

        // State komponen harus mencerminkan path tersimpan supaya preview muncul
        // dan upload yang sama tidak diproses ulang di simpan berikutnya.
        $state = $comp->get('gallery_items')[0];
        $this->assertSame($saved['image'], $state['image']);
        $this->assertArrayNotHasKey('image_upload', $state);
    }

    public function test_admin_toggles_auto_statistics()
    {
        $admin = User::factory()->admin()->create();

        $comp = Livewire::actingAs($admin)->test(LandingPage::class);
        $comp->set('activeTab', 'statistics')
            ->call('toggleStatAuto')
            ->set('statistics_auto_metrics', ['events', 'registrations'])
            ->call('save');

        $stats = json_decode(Setting::get('landing_statistics'), true);
        $this->assertTrue($stats['auto']);
        $this->assertEquals(['events', 'registrations'], $stats['metrics']);
    }

    public function test_public_statistics_renders_auto_metrics()
    {
        $user = User::factory()->admin()->create();
        Setting::set('landing_statistics', json_encode([
            'auto' => true,
            'metrics' => ['events', 'registrations'],
            'items' => [],
        ]));
        Setting::set('landing_sections_order', json_encode(['statistics']));
        Setting::set('landing_sections_active', json_encode(['statistics' => true]));

        Eventner::factory()->create(['status' => 'approved']);

        $comp = Livewire::test(PublicLandingPage::class);
        $html = $comp->html();

        $this->assertStringContainsString('Event Diselenggarakan', $html);
        $this->assertStringContainsString('Pendaftaran', $html);
    }

    public function test_help_support_faq_comes_from_setting()
    {
        $user = User::factory()->create();
        Setting::set('landing_faq', json_encode([
            'title' => 'FAQ',
            'items' => [
                ['question' => 'Pertanyaan Admin', 'answer' => 'Jawaban Admin'],
            ],
        ]));
        Setting::set('landing_contact', json_encode([
            'phone' => '+62 888-000-1111',
            'email' => 'support@berbaris.test',
            'address' => '',
            'map_embed_url' => '',
        ]));

        $comp = Livewire::test(HelpSupport::class);
        $html = $comp->html();

        $this->assertStringContainsString('Pertanyaan Admin', $html);
        $this->assertStringContainsString('+62 888-000-1111', $html);
    }
}
