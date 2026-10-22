<?php

namespace Tests\Feature;

use App\Livewire\Public\Eo\Ajukan;
use App\Models\EoInquiry;
use App\Models\Eventner;
use App\Models\User;
use App\Support\EoProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Subdomain EO (eo.berbaris.test) — tujuh halaman penawaran jasa.
 *
 * Dua hal yang dijaga tes ini dan mudah rusak tanpa ketahuan:
 *
 * 1. Halamannya harus hidup di HOST TETAP, bukan di grup {subdomain}. Kalau
 *    grup EO ikut memakai middleware 'subdomain', ResolveEventnerSubdomain
 *    mencari event bernama "eo" lalu menjawab 404 — halaman penawarannya hilang
 *    seluruhnya.
 * 2. Peran tim tanpa nama tetap harus dirender. Komponen tim di laman utama
 *    memfilter entri tanpa nama, jadi kalau halaman ini keliru memakai
 *    komponen itu, seluruh section tim hilang dan janji "ada timnya" ikut
 *    hilang — padahal peran justru bagian yang sudah pasti.
 */
class EoPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config(['app.eo_host' => 'eo.berbaris.test']);
    }

    /** Request di host EO milik platform. */
    private function eo(string $path = '/'): \Illuminate\Testing\TestResponse
    {
        return $this->get('http://' . eo_host() . $path);
    }

    public function test_eo_host_is_configured_and_distinct_from_root(): void
    {
        $this->assertSame('eo.berbaris.test', eo_host());
        $this->assertSame('http://eo.berbaris.test/layanan', eo_url('/layanan'));
    }

    public function test_all_eo_pages_render_on_the_eo_host(): void
    {
        $halaman = [
            '/' => 'Kami menyelenggarakan lombanya',
            '/layanan' => 'Lima kelompok kerja',
            '/alur' => 'Dari satu pengajuan sampai laporan',
            '/event' => 'Acara yang sedang dibuka',
            '/tim' => 'peran, satu pintu keputusan',
            '/sponsor' => 'Yang didapat pihak yang membiayai',
            '/ajukan' => 'Ceritakan lomba yang ingin Anda selenggarakan',
        ];

        foreach ($halaman as $path => $teks) {
            $this->eo($path)->assertOk()->assertSee($teks, false);
        }
    }

    public function test_eo_pages_do_not_resolve_a_tenant(): void
    {
        // Tanpa satu pun Eventner di basis data, halaman EO tetap harus 200.
        // Kalau grup ini tidak sengaja masuk middleware 'subdomain', semua
        // permintaan di bawah akan 404.
        $this->assertSame(0, Eventner::count());

        $this->eo('/')->assertOk();
        $this->eo('/layanan')->assertOk();
    }

    /**
     * Urutan registrasi grup rute adalah gerbangnya, bukan konfigurasi host.
     *
     * Laravel mencocokkan rute sesuai urutan pendaftaran. Grup
     * {subdomain}.{root} punya rute '/' sendiri (subdomain.detail), jadi kalau
     * grup EO didaftarkan setelahnya, beranda EO tertangkap tenant lalu dijawab
     * 404 "Event tidak ditemukan" — sementara /layanan, /alur, dan seterusnya
     * tetap 200 karena path itu tidak ada di grup tenant. Gejalanya jadi
     * menyesatkan: seolah hanya beranda yang rusak.
     *
     * Tes ini sengaja memeriksa URUTAN, bukan hasil request. Di lingkungan tes
     * APP_URL adalah "localhost", sehingga host eo.berbaris.test tidak pernah
     * cocok dengan wildcard {subdomain}.localhost dan bentrokan itu tidak akan
     * pernah muncul sebagai 404 — permintaan tetap 200 walau urutannya salah.
     */
    public function test_eo_routes_are_registered_before_the_tenant_wildcard(): void
    {
        $urutan = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes())
            ->map(fn ($rute) => $rute->getName())
            ->values();

        $posisiEo = $urutan->search('eo.beranda');
        $posisiTenant = $urutan->search('subdomain.detail');

        $this->assertNotFalse($posisiEo, 'Rute eo.beranda tidak terdaftar.');
        $this->assertNotFalse($posisiTenant, 'Rute subdomain.detail tidak terdaftar.');
        $this->assertLessThan(
            $posisiTenant,
            $posisiEo,
            'Grup rute EO harus didaftarkan SEBELUM grup {subdomain}.{root} di bootstrap/app.php, '
            . 'kalau tidak rute "/" milik tenant menang dan beranda EO menjawab 404.'
        );
    }

    public function test_every_service_group_and_item_is_rendered(): void
    {
        $response = $this->eo('/layanan')->assertOk();

        foreach (EoProfile::layanan() as $grup) {
            $response->assertSee($grup['judul']);
            $response->assertSee($grup['ringkas']);

            foreach ($grup['butir'] as $butir) {
                $response->assertSee($butir['nama']);
            }
        }
    }

    public function test_every_workflow_step_is_rendered(): void
    {
        $response = $this->eo('/alur')->assertOk();

        foreach (EoProfile::alur() as $langkah) {
            $response->assertSee($langkah['judul']);
        }
    }

    /**
     * Inti permintaan pengguna: "tambahkan juga ada timnya siapa saja".
     * Semua peran harus tampil walau belum satu pun nama diisi.
     */
    public function test_team_roles_render_even_when_no_name_is_filled(): void
    {
        $response = $this->eo('/tim')->assertOk();

        $jumlah = 0;
        foreach (EoProfile::tim() as $grup) {
            $response->assertSee($grup['kelompok']);

            foreach ($grup['peran'] as $peran) {
                $response->assertSee($peran['peran']);
                $response->assertSee($peran['tugas']);
                $jumlah++;
            }
        }

        $this->assertSame(18, $jumlah, 'Jumlah peran tim berubah — perbarui harapan tes bila memang disengaja.');
        $response->assertSee('menyusul');
    }

    public function test_team_names_come_from_the_eo_team_setting(): void
    {
        \App\Models\Setting::set('eo_team', json_encode([
            'Direktur Event' => 'Rani Kusuma',
            'Operator Sistem & Skor' => 'Bagas Pratama',
        ]));

        $response = $this->eo('/tim')->assertOk();

        $response->assertSee('Rani Kusuma');
        $response->assertSee('Bagas Pratama');
        $response->assertSee('2 dari 18 peran sudah terisi nama');
    }

    /**
     * Halaman EO memakai bundel CSS sendiri (resources/css/eo.css) dan TIDAK
     * memuat landing.css. Komponen <x-landing.*> memakai kelas landing, jadi
     * memakainya di sini akan menghasilkan kartu tanpa gaya. Tes ini menjaga
     * agar pemisahan itu tidak diam-diam dibatalkan.
     */
    public function test_eo_pages_load_their_own_stylesheet_and_not_the_landing_one(): void
    {
        $html = $this->eo('/')->assertOk()->getContent();

        // Vite menamai berkas hasil build dengan hash (eo-XXXXXXXX.css), jadi
        // yang dicari adalah awalan asetnya, bukan nama berkas sumbernya.
        $this->assertMatchesRegularExpression('#build/assets/eo-[^"]+\.css#', $html);
        $this->assertDoesNotMatchRegularExpression('#build/assets/landing-[^"]+\.css#', $html);

        // Kelas landing tidak boleh muncul: kalau muncul, berarti ada komponen
        // <x-landing.*> yang dipakai dan gayanya tidak akan dimuat.
        $this->assertStringNotContainsString('container-landing', $html);
        $this->assertStringNotContainsString('surface-card', $html);
    }

    public function test_sponsor_tiers_render_without_invented_amounts(): void
    {
        $response = $this->eo('/sponsor')->assertOk();

        foreach (EoProfile::sponsor() as $tingkat) {
            $response->assertSee($tingkat['nama']);
            foreach ($tingkat['butir'] as $butir) {
                $response->assertSee($butir);
            }
        }

        // Nilai paket tidak boleh muncul sebagai angka rupiah karangan.
        $response->assertDontSee('Rp ');
    }

    public function test_workflow_timing_shows_waiting_state_when_unset(): void
    {
        $this->eo('/alur')->assertOk()->assertSee('menunggu kesepakatan');
    }

    public function test_workflow_timing_is_read_from_the_setting(): void
    {
        \App\Models\Setting::set('eo_alur_waktu', json_encode([
            'pengajuan' => '1 hari kerja',
            'kunci_produksi' => 'H-7',
        ]));

        $response = $this->eo('/alur')->assertOk();

        $response->assertSee('1 hari kerja');
        $response->assertSee('H-7');
    }

    public function test_event_page_shows_upcoming_and_past_events(): void
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);

        $akanDatang = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'nama_event' => 'LKBB Nusantara 2027',
            'slug' => 'lkbb-nusantara-2027',
            'tanggal' => now()->addMonth()->toDateString(),
            'tanggal_akhir' => null,
        ]);

        $terlaksana = Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'nama_event' => 'PBB Kemerdekaan 2026',
            'slug' => 'pbb-kemerdekaan-2026',
            'tanggal' => now()->subMonth()->toDateString(),
            'tanggal_akhir' => null,
        ]);

        $response = $this->eo('/event')->assertOk();

        $response->assertSee('LKBB Nusantara 2027');
        $response->assertSee('PBB Kemerdekaan 2026');
        $response->assertSee('Akan datang');
        $response->assertSee('Sudah terlaksana');
    }

    public function test_event_filter_hides_the_other_section(): void
    {
        $user = User::factory()->eventner()->create(['is_active' => true]);

        Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'nama_event' => 'LKBB Nusantara 2027',
            'slug' => 'lkbb-nusantara-2027',
            'tanggal' => now()->addMonth()->toDateString(),
        ]);

        Eventner::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'nama_event' => 'PBB Kemerdekaan 2026',
            'slug' => 'pbb-kemerdekaan-2026',
            'tanggal' => now()->subMonth()->toDateString(),
        ]);

        $response = $this->eo('/event?saring=terlaksana')->assertOk();
        $response->assertSee('PBB Kemerdekaan 2026');
        $response->assertDontSee('LKBB Nusantara 2027');
    }

    public function test_homepage_hides_the_event_block_when_no_event_exists(): void
    {
        $this->eo('/')->assertOk()->assertDontSee('Event Terdekat', false);
    }

    public function test_inquiry_is_persisted_and_normalises_the_phone_number(): void
    {
        Livewire::test(Ajukan::class)
            ->set('instansi', 'SMAN 1 Berbaris')
            ->set('penanggung_jawab', 'Ibu Rani')
            ->set('whatsapp', '+628123456789')
            ->set('jenis_lomba', 'LKBB')
            ->set('tanggal', now()->addMonth()->toDateString())
            ->set('lokasi', 'Bandar Lampung')
            ->set('jumlah_peserta', '500')
            ->set('pekerjaan', ['Sistem Lomba', 'Hari-H & Arena'])
            ->set('catatan', 'Dua hari.')
            ->call('kirim')
            ->assertHasNoErrors()
            ->assertSet('terkirim', true);

        $inquiry = EoInquiry::first();

        $this->assertNotNull($inquiry);
        $this->assertSame('SMAN 1 Berbaris', $inquiry->instansi);
        $this->assertSame('LKBB', $inquiry->jenis_lomba);
        $this->assertSame(500, $inquiry->jumlah_peserta);
        $this->assertSame('baru', $inquiry->status);
        $this->assertSame(['Sistem Lomba', 'Hari-H & Arena'], $inquiry->pekerjaan);

        // Nomor disimpan sebagai 08… supaya admin yang menyalinnya ke WhatsApp
        // tidak perlu menebak formatnya.
        $this->assertSame('08123456789', $inquiry->whatsapp);
    }

    public function test_inquiry_requires_only_the_four_mandatory_fields(): void
    {
        Livewire::test(Ajukan::class)
            ->set('instansi', 'SMAN 1 Berbaris')
            ->set('penanggung_jawab', 'Ibu Rani')
            ->set('whatsapp', '08123456789')
            ->set('jenis_lomba', 'PBB / LOBB')
            ->call('kirim')
            ->assertHasNoErrors()
            ->assertSet('terkirim', true);

        $this->assertSame(1, EoInquiry::count());
        $this->assertNull(EoInquiry::first()->tanggal);
    }

    public function test_inquiry_rejects_missing_mandatory_fields(): void
    {
        Livewire::test(Ajukan::class)
            ->call('kirim')
            ->assertHasErrors(['instansi', 'penanggung_jawab', 'whatsapp']);

        $this->assertSame(0, EoInquiry::count());
    }

    public function test_inquiry_rejects_a_non_indonesian_phone_number(): void
    {
        Livewire::test(Ajukan::class)
            ->set('instansi', 'SMAN 1 Berbaris')
            ->set('penanggung_jawab', 'Ibu Rani')
            ->set('whatsapp', '12345')
            ->call('kirim')
            ->assertHasErrors(['whatsapp']);

        $this->assertSame(0, EoInquiry::count());
    }

    public function test_form_is_cleared_after_a_successful_submission(): void
    {
        Livewire::test(Ajukan::class)
            ->set('instansi', 'SMAN 1 Berbaris')
            ->set('penanggung_jawab', 'Ibu Rani')
            ->set('whatsapp', '08123456789')
            ->call('kirim')
            ->assertSet('instansi', '')
            ->assertSet('penanggung_jawab', '')
            ->assertSet('whatsapp', '')
            // Pesan berhasilnya tidak boleh ikut tereset.
            ->assertSet('terkirim', true);
    }

    /**
     * "eo" harus dicadangkan: kalau tidak, penyelenggara bisa mendaftarkan
     * subdomain "eo" dan halaman penawaran ini tertutup selamanya.
     */
    public function test_eo_subdomain_cannot_be_claimed_by_an_event(): void
    {
        $this->assertTrue(in_array('eo', explode(',', $this->reservedSubdomains()), true));
        $this->assertTrue(in_array('entry', explode(',', $this->reservedSubdomains()), true));
    }

    /** Baca daftar cadangan lewat jalur publik komponennya. */
    private function reservedSubdomains(): string
    {
        $method = new \ReflectionMethod(\App\Livewire\Eventner\Settings\Profile::class, 'reservedSubdomain');
        $method->setAccessible(true);

        $komponen = new \App\Livewire\Eventner\Settings\Profile();

        return $method->invoke($komponen);
    }

    public function test_root_host_still_serves_the_platform_landing(): void
    {
        // Halaman EO tidak boleh "bocor" ke host utama.
        $this->get('/')->assertOk()->assertDontSee('Kami menyelenggarakan lombanya', false);
    }

    /**
     * Halaman penawaran yang tidak ditautkan dari mana pun sama saja dengan
     * tidak ada. Laman depan harus punya satu pintu masuk ke host EO.
     *
     * Tautannya absolut dan lintas host, jadi ia sengaja TIDAK ikut daftar
     * anchor nav (`LandingSections`) — scroll-spy nav mencari elemen `#id` di
     * halaman yang sama, dan tautan seperti ini tidak punya elemen padanan.
     */
    public function test_platform_landing_links_to_the_eo_host(): void
    {
        Eventner::factory()->create(['status' => 'approved']);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Jasa Event Organizer', $html);
        $this->assertStringContainsString('href="' . eo_url('/') . '"', $html);
    }

    /** Tanpa host EO yang dikonfigurasi, jangan render tautan "://". */
    public function test_landing_hides_the_eo_link_when_the_host_is_unset(): void
    {
        Eventner::factory()->create(['status' => 'approved']);

        config(['app.eo_host' => null]);

        $this->get('/')->assertOk()->assertDontSee('Jasa Event Organizer');
    }
}
