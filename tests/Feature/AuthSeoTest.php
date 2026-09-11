<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_login_punya_meta_seo_lengkap(): void
    {
        Setting::set('site_title', 'Berbaris App');

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('<title>Masuk - Berbaris App</title>', false);
        $response->assertSee('rel="shortcut icon"', false);
        $response->assertSee('name="robots" content="index, follow"', false);
        $response->assertSee('<link rel="canonical"', false);
        $response->assertSee('property="og:title" content="Masuk - Berbaris App"', false);
        $response->assertSee('property="og:image"', false);
        $response->assertSee('name="twitter:card" content="summary_large_image"', false);
    }

    public function test_favicon_login_memakai_setting_situs(): void
    {
        Setting::set('favicon', 'settings/ikon.png');

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('settings/ikon.png', false);
    }

    public function test_favicon_login_fallback_ke_template(): void
    {
        Setting::set('favicon', null);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('templates/assets/images/logos/favicon.png', false);
    }

    public function test_halaman_privat_tidak_diindeks(): void
    {
        $user = User::factory()->eventner()->create(['is_active' => false]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('name="robots" content="noindex, nofollow"', false);
    }

    public function test_halaman_login_robots_index_dan_follow(): void
    {
        $response = $this->get('/register/eventner');

        $response->assertOk();
        $response->assertSee('name="robots" content="index, follow"', false);
        $response->assertSee('Daftar Eventner', false);
    }
}
