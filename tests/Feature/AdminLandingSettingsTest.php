<?php

namespace Tests\Feature;

use App\Livewire\Admin\Setting\LandingPage;
use App\Livewire\Public\HelpSupport;
use App\Livewire\Public\LandingPage as PublicLandingPage;
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

    public function test_admin_saves_contact_settings()
    {
        $admin = User::factory()->admin()->create();

        $comp = Livewire::actingAs($admin)->test(LandingPage::class);
        $comp->set('contact_phone', '+62 800-123-4567')
            ->set('contact_email', 'kontak@berbaris.test')
            ->set('contact_address', 'Jl. Test No. 1')
            ->call('save');

        // Kartu kontak tampil di dalam section FAQ, bukan section sendiri —
        // tapi kuncinya tetap `landing_contact` lewat tab "Kontak".
        $contact = json_decode(Setting::get('landing_contact'), true);
        $this->assertEquals('+62 800-123-4567', $contact['phone']);
        $this->assertEquals('kontak@berbaris.test', $contact['email']);
        $this->assertEquals('Jl. Test No. 1', $contact['address']);
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

    public function test_gambar_visual_tersimpan_dan_muncul_di_state()
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $comp = Livewire::actingAs($admin)->test(LandingPage::class);
        $comp->set('about_image', UploadedFile::fake()->image('visual.png', 400, 300))
            ->call('save');

        $saved = json_decode(Setting::get('landing_about'), true)['image'];
        $this->assertNotEmpty($saved);
        Storage::disk('public')->assertExists($saved);

        // State komponen harus mencerminkan path tersimpan supaya preview muncul
        // dan simpan berikutnya tidak menghapus gambar yang baru diunggah.
        $this->assertSame($saved, $comp->get('about_image_current'));
    }

    public function test_gambar_about_menang_atas_video_bawaan()
    {
        Setting::set('landing_about', json_encode([
            'image' => 'landing/about.jpg',
            'video' => 'https://videos.pexels.com/video-files/3209259/3209259-hd_1920_1080_25fps.mp4',
            'points' => [],
        ]));

        $html = Livewire::test(PublicLandingPage::class)->html();

        // Periksa markup section Hero saja — snapshot Livewire memuat JSON
        // mentah pengaturan, jadi pencarian di seluruh HTML bisa menyesatkan.
        // Visualnya pindah ke sini setelah section "Tentang" dibuang.
        $hero = $this->heroSectionMarkup($html);

        $this->assertStringContainsString('<img', $hero);
        $this->assertStringContainsString('landing/about.jpg', $hero);
        $this->assertStringNotContainsString('<video', $hero);
    }

    /** Potongan markup <section id="hero"> dari HTML halaman landing. */
    private function heroSectionMarkup(string $html): string
    {
        $start = strpos($html, '<section id="hero"');
        $this->assertNotFalse($start, 'Section hero tidak ditemukan di halaman landing.');

        $end = strpos($html, '</section>', $start);

        return substr($html, $start, $end - $start);
    }

    public function test_video_about_dipakai_saat_gambar_kosong()
    {
        Setting::set('landing_about', json_encode([
            'image' => '',
            'video' => 'https://contoh.test/promo.mp4',
            'points' => [],
        ]));

        $hero = $this->heroSectionMarkup(Livewire::test(PublicLandingPage::class)->html());

        $this->assertStringContainsString('<video', $hero);
        $this->assertStringContainsString('https://contoh.test/promo.mp4', $hero);
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
