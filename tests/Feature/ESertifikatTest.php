<?php

namespace Tests\Feature;

use App\Livewire\Public\ESertifikat\Cari;
use App\Models\CertificateTemplate;
use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Participant;
use App\Models\Registration;
use App\Support\AksesUnduhSertifikat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Laman unduh e-sertifikat (e-sertifikat.berbaris.test).
 *
 * Yang dijaga di sini bukan tata letaknya, melainkan tiga hal yang kalau
 * rusak tidak kelihatan dari layar:
 *
 * 1. **Host-nya tetap.** Seperti host EO dan host entry, grup ini BUKAN
 *    tenant. Kalau ia ikut middleware 'subdomain', setiap permintaan mencari
 *    Eventner bernama "e-sertifikat" lalu 404 — halamannya hilang seluruhnya.
 * 2. **Izin unduh berasal dari pencarian, bukan dari angka di URL.** Id
 *    registrasi bisa ditebak; tanpa pencarian NISN yang sah ia tidak membuka
 *    apa pun.
 * 3. **Danton punya NISN di kolomnya sendiri** (`registrations.danton_nisn`,
 *    bukan `participants.nisn`). Mencari satu kolom saja membuat danton tidak
 *    menemukan sertifikatnya sendiri — dan itu tidak akan pernah terlihat di
 *    tes yang hanya memakai anggota pasukan.
 */
class ESertifikatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config([
            'app.cert_host' => 'e-sertifikat.berbaris.test',
            'app.url' => 'http://berbaris.test',
        ]);
    }

    /** @param array<string, mixed> $kueri */
    private function cert(string $path = '/', array $kueri = []): \Illuminate\Testing\TestResponse
    {
        $url = 'http://' . cert_host() . $path;

        if ($kueri) {
            $url .= '?' . http_build_query($kueri);
        }

        return $this->get($url);
    }

    public function test_cert_host_is_configured_and_builds_absolute_urls(): void
    {
        $this->assertSame('e-sertifikat.berbaris.test', cert_host());
        $this->assertSame('http://e-sertifikat.berbaris.test', cert_url());
        $this->assertSame('http://e-sertifikat.berbaris.test/unduh/7', cert_url('/unduh/7'));
    }

    public function test_beranda_renders_on_the_cert_host_without_any_tenant(): void
    {
        // Tanpa satu pun Eventner: kalau grup ini tidak sengaja masuk
        // middleware 'subdomain', permintaan di bawah akan 404.
        $this->assertSame(0, Eventner::count());

        $this->cert('/')
            ->assertOk()
            ->assertSee('Unduh e-sertifikat lomba Anda', false)
            ->assertSee('NISN', false)
            ->assertSee('NISN milik siswa, bukan NPSN sekolah', false);
    }

    public function test_nisn_of_a_squad_member_finds_the_certificate(): void
    {
        [$eventner, $reg] = $this->eventWithSquad('1000000290');

        $this->cert('/', ['nisn' => '1000000290'])
            ->assertOk()
            ->assertSee($eventner->nama_event, false)
            ->assertSee('Unduh PDF', false)
            ->assertSee('PESERTA', false)
            ->assertSee('1000000290', false);
    }

    public function test_nisn_of_a_danton_is_found_from_its_own_column(): void
    {
        // Danton tidak punya baris `participants` — kalau pencariannya hanya
        // menembak participants.nisn, tes ini yang gagal.
        $eventner = Eventner::factory()->paid()->create(['nama_event' => 'Lomba Danton 2026']);
        $reg = Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => CompetitionCategory::factory()->create(['eventner_id' => $eventner->id])->id,
            'danton_nama' => 'Bagas Danton',
            'danton_nisn' => '0099887766',
        ]);
        Participant::factory()->create(['registration_id' => $reg->id, 'nisn' => '1111222233']);

        $this->cert('/', ['nisn' => '0099887766'])
            ->assertOk()
            ->assertSee('Bagas Danton', false)
            ->assertSee('Lomba Danton 2026', false);

        // Anggota pasukannya sendiri tetap ketemu lewat kolomnya sendiri.
        $this->cert('/', ['nisn' => '1111222233'])->assertOk()->assertSee('Lomba Danton 2026', false);
    }

    public function test_leading_zero_nisn_is_not_turned_into_a_number(): void
    {
        // NISN selalu berawalan nol pada sebagian sekolah. Kalau nilainya
        // pernah lewat (int), digit pertamanya hilang dan pencariannya
        // meleset satu digit.
        $this->eventWithSquad('0071234567');

        $this->cert('/', ['nisn' => '0071234567'])->assertOk()->assertSee('Unduh PDF', false);

        // Delapan angka = format tidak sah, dan yang tampil beranda — bukan
        // baris sertifikat yang cocok sebagian.
        $this->cert('/', ['nisn' => '71234567'])
            ->assertOk()
            ->assertSee('Unduh e-sertifikat lomba Anda', false);
    }

    public function test_spaces_and_dots_in_the_typed_nisn_are_stripped(): void
    {
        // NISN kerap disalin persis seperti tercetak di rapor.
        $this->eventWithSquad('0071234567');

        $this->cert('/', ['nisn' => '007 1234 567'])->assertOk()->assertSee('Unduh PDF', false);
    }

    public function test_unknown_nisn_shows_the_empty_state_not_a_blank_page(): void
    {
        $this->eventWithSquad('1000000290');

        $this->cert('/', ['nisn' => '9999999999'])
            ->assertOk()
            ->assertSee('belum terdaftar di lomba mana pun', false)
            ->assertSee('Coba NISN lain', false)
            ->assertSee('danton', false);
    }

    public function test_cancelled_registration_is_hidden_entirely(): void
    {
        [$eventner, $reg] = $this->eventWithSquad('1000000290');
        $reg->update(['status_berkas' => 'dibatalkan']);

        $this->cert('/', ['nisn' => '1000000290'])
            ->assertOk()
            ->assertSee('belum terdaftar di lomba mana pun', false)
            ->assertDontSee($eventner->nama_event, false);
    }

    public function test_unapproved_event_is_hidden_entirely(): void
    {
        // Draf milik panitia tidak boleh bocor lewat halaman lintas-event ini.
        [$eventner, $reg] = $this->eventWithSquad('1000000290');
        $eventner->update(['status' => 'pending']);

        $this->cert('/', ['nisn' => '1000000290'])
            ->assertOk()
            ->assertSee('belum terdaftar di lomba mana pun', false);
    }

    public function test_row_is_shown_with_its_reason_when_template_is_missing(): void
    {
        // Menyembunyikan baris yang belum terbit membuat peserta yang lombanya
        // belum siap melihat halaman kosong dan menyimpulkan NISN-nya salah.
        $eventner = Eventner::factory()->paid()->create(['nama_event' => 'Lomba Tanpa Template']);
        $this->squad($eventner, '1000000290');

        $this->assertSame(0, CertificateTemplate::where('eventner_id', $eventner->id)->count());

        $this->cert('/', ['nisn' => '1000000290'])
            ->assertOk()
            ->assertSee('Lomba Tanpa Template', false)
            ->assertSee('BELUM TERBIT', false)
            ->assertSee('Panitia belum memasang template sertifikat untuk event ini.', false);
    }

    public function test_row_is_shown_with_its_reason_when_the_plan_lacks_the_certificate_feature(): void
    {
        // Event gratis di luar trial: fitur certificate terkunci. Gerbangnya
        // per event, jadi sebutan yang dilihat peserta memang syarat yang
        // ditegakkan server.
        $eventner = Eventner::factory()->create([
            'nama_event' => 'Lomba Paket Gratis',
            'plan' => 'free',
            'trial_ends_at' => now()->subDay(),
        ]);
        $this->squad($eventner, '1000000290');

        $this->cert('/', ['nisn' => '1000000290'])
            ->assertOk()
            ->assertSee('Lomba Paket Gratis', false)
            ->assertSee('BELUM TERBIT', false)
            ->assertSee('Fitur sertifikat belum aktif untuk event ini.', false);
    }

    public function test_download_without_a_search_is_rejected(): void
    {
        [$eventner, $reg] = $this->eventWithSquad('1000000290');

        // Angka di URL bisa ditebak; ini yang membuatnya tidak berarti apa-apa
        // tanpa pencarian yang sah lebih dulu.
        $this->cert('/unduh/' . $reg->id)->assertStatus(403);
    }

    public function test_download_of_a_registration_outside_the_search_grant_is_rejected(): void
    {
        $eventnerA = Eventner::factory()->paid()->create(['nama_event' => 'Lomba A']);
        $eventnerB = Eventner::factory()->paid()->create(['nama_event' => 'Lomba B']);

        $this->squad($eventnerA, '1000000290');
        [, $regB] = $this->eventWithSquad('5555555555', $eventnerB);

        // Cari NISN milik event A dulu, lalu minta berkas event B.
        $this->cert('/', ['nisn' => '1000000290'])->assertOk();

        $this->cert('/unduh/' . $regB->id)->assertStatus(403);
    }

    public function test_download_awards_a_pdf_once_the_search_has_been_made(): void
    {
        [$eventner, $reg] = $this->eventWithSquad('1000000290');

        $this->cert('/', ['nisn' => '1000000290'])->assertOk();

        $response = $this->cert('/unduh/' . $reg->id);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_expired_grant_stops_working(): void
    {
        [$eventner, $reg] = $this->eventWithSquad('1000000290');

        $this->cert('/', ['nisn' => '1000000290'])->assertOk();
        $this->assertTrue(AksesUnduhSertifikat::bolehUnduh($reg->id));

        // Masa berlakunya diperiksa saat dibaca, bukan lewat tugas terjadwal.
        $this->travel(AksesUnduhSertifikat::MASA_BERLAKU_MENIT + 1)->minutes();

        $this->assertFalse(AksesUnduhSertifikat::bolehUnduh($reg->id));
        $this->cert('/unduh/' . $reg->id)->assertStatus(403);
    }

    public function test_download_is_refused_when_the_feature_is_gated_even_after_a_search(): void
    {
        // Jarak antara pencarian dan unduhan cukup untuk panitia mematikan
        // gerbangnya, jadi controller memeriksanya ulang.
        [$eventner, $reg] = $this->eventWithSquad('1000000290');

        $this->cert('/', ['nisn' => '1000000290'])->assertOk();

        $eventner->update(['plan' => 'free', 'trial_ends_at' => now()->subDay()]);

        $this->cert('/unduh/' . $reg->id)->assertStatus(403);
    }

    public function test_komponen_membersihkan_hasil_dan_izin_saat_diulangi(): void
    {
        [$eventner, $reg] = $this->eventWithSquad('1000000290');

        $this->cert('/', ['nisn' => '1000000290'])->assertOk();
        $this->assertTrue(AksesUnduhSertifikat::bolehUnduh($reg->id));

        Livewire::test(Cari::class)
            ->set('nisn', '1000000290')
            ->call('cari')
            ->assertSet('nisn', '1000000290')
            ->call('ulangi')
            ->assertSet('nisn', '')
            ->assertSet('hasil', null);

        $this->assertFalse(AksesUnduhSertifikat::bolehUnduh($reg->id));
    }

    public function test_komponen_menolak_nisn_yang_bukan_sepuluh_angka(): void
    {
        Livewire::test(Cari::class)
            ->set('nisn', '123')
            ->call('cari')
            ->assertHasErrors('nisn')
            ->assertSet('hasil', null);

        Livewire::test(Cari::class)
            ->set('nisn', '')
            ->call('cari')
            ->assertHasErrors('nisn');
    }

    public function test_e_sertifikat_subdomain_cannot_be_claimed_by_an_event(): void
    {
        // "e-sertifikat" lolos regex subdomain, jadi tanpa dicadangkan ia bisa
        // didaftarkan penyelenggara dan menutup laman ini selamanya.
        $method = new \ReflectionMethod(\App\Livewire\Eventner\Settings\Profile::class, 'reservedSubdomain');
        $method->setAccessible(true);

        $daftar = explode(',', $method->invoke(new \App\Livewire\Eventner\Settings\Profile()));

        $this->assertContains('e-sertifikat', $daftar);
        $this->assertContains('eo', $daftar);
        $this->assertContains('entry', $daftar);
    }

    public function test_root_host_does_not_serve_the_cert_page(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Unduh e-sertifikat lomba Anda', false);
    }

    public function test_landing_links_to_the_cert_host(): void
    {
        // Halaman ini hanya berguna kalau peserta tahu alamatnya. Tautannya
        // lintas host, jadi seperti tautan EO ia ditaruh absolut — di menu
        // layanan navbar dan kolom Kontak footer, bukan di daftar anchor yang
        // digerakkan scroll-spy.
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Unduh E-Sertifikat', $html);
        $this->assertStringContainsString('href="' . cert_url('/') . '"', $html);
    }

    public function test_landing_hides_the_cert_link_when_the_host_is_unset(): void
    {
        config(['app.cert_host' => null]);

        $this->assertNull(cert_host());

        $this->get('/')->assertOk()->assertDontSee('Unduh E-Sertifikat', false);
    }

    public function test_menu_layanan_navbar_menyaring_tautan_lintas_host_yang_kosong(): void
    {
        // Panel menu dibangun dari array_filter atas dua host opsional. Kalau
        // penyaringnya hilang, entri null ikut terender dan tautannya jadi
        // "https://" telanjang — bukan galat, tapi tautan mati di navbar.
        config(['app.eo_host' => null, 'app.cert_host' => null]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Daftar Eventner', $html);
        $this->assertStringNotContainsString('Jasa Event Organizer', $html);
        $this->assertStringNotContainsString('Unduh E-Sertifikat', $html);
        $this->assertStringNotContainsString('href="https://"', $html);
    }

    public function test_katalog_fitur_memuat_laman_e_sertifikat(): void
    {
        // Katalog /fitur dijaga tesnya sendiri (PublicFeaturesPageTest), tapi
        // yang mudah hilang diam-diam adalah kaitan antara entri katalog dan
        // halaman ini: entri tanpa route nyata atau tanpa cabang miniatur
        // gagal di sana, bukan di sini.
        $item = collect(\App\Support\ProductFeatures::all())
            ->firstWhere('route', 'cert.home');

        $this->assertNotNull($item, 'Laman e-sertifikat hilang dari katalog /fitur.');
        $this->assertSame('cari-nisn', $item['miniature']);
        $this->assertSame([], $item['gates']);

        $this->get(route('public.features'))
            ->assertOk()
            ->assertSee('Unduh E-Sertifikat via NISN', false);
    }

    /**
     * Event ber-paket berbayar + satu pasukan + satu anggota ber-NISN, plus
     * template aktif supaya sertifikatnya benar-benar bisa terbit.
     *
     * @return array{0: Eventner, 1: Registration}
     */
    private function eventWithSquad(string $nisn, ?Eventner $eventner = null): array
    {
        $eventner ??= Eventner::factory()->paid()->create();

        $reg = $this->squad($eventner, $nisn);

        CertificateTemplate::factory()->create([
            'eventner_id' => $eventner->id,
            'is_active' => true,
        ]);

        // Kategori juara tidak wajib ada: tanpa itu sertifikatnya tetap terbit
        // sebagai PESERTA. Dibuat supaya field `kategori_juara` terisi.
        $parent = CompetitionCategory::factory()->create(['eventner_id' => $eventner->id, 'parent_id' => null]);
        $reg->update(['competition_category_id' => CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => $parent->id,
        ])->id]);

        ChampionCategory::create([
            'eventner_id' => $eventner->id,
            'name' => 'Juara Umum',
            'quantity' => 3,
        ]);

        return [$eventner, $reg->refresh()];
    }

    private function squad(Eventner $eventner, string $nisn): Registration
    {
        $reg = Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => CompetitionCategory::factory()->create(['eventner_id' => $eventner->id])->id,
        ]);

        Participant::factory()->create([
            'registration_id' => $reg->id,
            'nisn' => $nisn,
            'nama' => 'Anisa Putri Nuraini',
        ]);

        return $reg;
    }
}
