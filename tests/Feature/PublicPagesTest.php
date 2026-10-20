<?php

namespace Tests\Feature;

use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\LandingPartner;
use App\Models\Participant;
use App\Models\Registration;
use App\Models\Setting;
use App\Models\Sponsor;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_loads()
    {
        Eventner::factory(3)->create(['status' => 'approved']);
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    /**
     * Tautan nav yang menunjuk section yang tidak ada = klik yang tidak terjadi
     * apa-apa. Halaman ini pernah punya dua (#vote, #gallery): nav-nya aktif
     * sementara komponennya berhenti render begitu datanya kosong. #gallery
     * masih hilang; #vote kembali dengan gerbang data yang sama seperti #tiket —
     * lihat test_section_vote_* di bawah.
     */
    public function test_tautan_nav_landing_menunjuk_ke_section_yang_ada()
    {
        Eventner::factory(3)->create(['status' => 'approved']);

        $html = $this->get('/')->getContent();
        $header = substr($html, 0, strpos($html, '</header>'));

        preg_match_all('/href="#([^"]+)"/', $header, $matches);
        $anchors = array_unique($matches[1]);

        $this->assertNotEmpty($anchors, 'Nav landing tidak punya tautan anchor sama sekali.');

        foreach ($anchors as $anchor) {
            $this->assertStringContainsString(
                'id="' . $anchor . '"',
                $html,
                "Tautan nav #{$anchor} tidak punya section dengan id itu di halaman."
            );
        }
    }

    /**
     * Urutan section yang tersimpan di DB bisa tertinggal dari kode — dan di
     * produksi memang tertinggal. Akibatnya nyata: laman harga hilang dari
     * halaman depan, karena `pricing` tidak ada di urutan lama dan shim yang
     * dulu menyelipkannya sudah dibuang.
     */
    public function test_landing_tetap_menampilkan_harga_walau_urutan_tersimpan_tertinggal()
    {
        Setting::set('landing_sections_order', json_encode([
            'hero', 'features', 'about', 'statistics', 'eventners', 'ticket',
            'vote', 'schedule', 'testimonials', 'faq', 'gallery', 'cta', 'contact',
        ]));
        Setting::set('landing_sections_active', json_encode([
            'hero' => true, 'features' => true, 'about' => true, 'statistics' => true,
            'eventners' => true, 'ticket' => true, 'vote' => true, 'schedule' => true,
            'testimonials' => true, 'faq' => true, 'gallery' => true, 'cta' => true,
            'contact' => true,
        ]));

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('id="pricing"', $html);
        $this->assertStringContainsString('Daftar Gratis', $html);
    }

    /**
     * Nama section lama yang komponennya sudah dihapus tidak boleh ikut
     * dirender — kalau ikut, landing memanggil view yang tidak ada dan
     * halaman depan error 500.
     */
    public function test_landing_menyaring_nama_section_yang_komponennya_sudah_dihapus()
    {
        Setting::set('landing_sections_order', json_encode([
            'testimonials', 'gallery', 'vote', 'schedule', 'statistics', 'about',
        ]));
        Setting::set('landing_sections_active', json_encode([
            'testimonials' => true, 'gallery' => true, 'vote' => true,
            'schedule' => true, 'statistics' => true, 'about' => true,
        ]));

        $this->get('/')->assertStatus(200);
    }

    public function test_penyelenggara_menampilkan_tanggal_pelaksanaan()
    {
        Eventner::factory()->create([
            'status' => 'approved',
            'tanggal' => '2026-10-05',
            'tanggal_akhir' => null,
        ]);

        $html = $this->penyelenggaraSection($this->get('/')->getContent());

        $this->assertStringContainsString('05 Okt 2026', $html);
    }

    public function test_penyelenggara_menampilkan_rentang_tanggal()
    {
        Eventner::factory()->create([
            'status' => 'approved',
            'tanggal' => '2026-10-05',
            'tanggal_akhir' => '2026-10-07',
        ]);

        $html = $this->penyelenggaraSection($this->get('/')->getContent());

        $this->assertStringContainsString('05 Okt 2026', $html);
        $this->assertStringContainsString('07 Okt 2026', $html);
    }

    /** Event yang sudah selesai ditandai "Terlaksana" dan kartunya diredam. */
    public function test_penyelenggara_menandai_event_terlaksana()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'nama_event' => 'Lomba Lampau',
            'tanggal' => now()->subMonths(2)->toDateString(),
            'tanggal_akhir' => null,
        ]);

        $html = $this->penyelenggaraSection($this->get('/')->getContent());

        $this->assertStringContainsString('Terlaksana', $html);
        // Dulu chip-nya memakai kelas `chip-past`; setelah restyling, penanda
        // "Terlaksana" berdiri sendiri sebagai pill putih — penandaan meredam
        // kartu tetap dipegang `card-past`.
        $this->assertStringContainsString('card-past', $html);
        $this->assertStringContainsString('Lihat Hasil', $html);
    }

    /** Event yang belum lewat tidak boleh ikut ditandai. */
    public function test_penyelenggara_tidak_menandai_event_akan_datang()
    {
        Eventner::factory()->create([
            'status' => 'approved',
            'tanggal' => now()->addMonths(2)->toDateString(),
            'tanggal_akhir' => null,
        ]);

        $html = $this->penyelenggaraSection($this->get('/')->getContent());

        $this->assertStringNotContainsString('Terlaksana', $html);
        $this->assertStringContainsString('Lihat Event', $html);
    }

    /**
     * Event yang belum disetujui admin (pending) belum punya halaman publik —
     * kartunya akan menautkan ke 404, jadi jangan ikut ditampilkan.
     */
    public function test_penyelenggara_menyembunyikan_event_belum_aktif()
    {
        Eventner::factory()->pending()->create([
            'nama_event' => 'Lomba Belum Disetujui',
            'tanggal' => now()->addMonth()->toDateString(),
        ]);

        $response = $this->get('/');

        $response->assertDontSee('Lomba Belum Disetujui', false);
        $this->assertStringNotContainsString('<section id="eventners"', $response->getContent());
    }

    /**
     * Tanggal akhir yang masih hari ini belum dihitung terlaksana — event
     * sehari tidak boleh berubah jadi "Terlaksana" begitu lewat tengah malam.
     */
    public function test_penyelenggara_memakai_tanggal_akhir_sebagai_penentu()
    {
        Eventner::factory()->create([
            'status' => 'approved',
            'tanggal' => now()->subDays(3)->toDateString(),
            'tanggal_akhir' => now()->addDay()->toDateString(),
        ]);

        $html = $this->penyelenggaraSection($this->get('/')->getContent());

        $this->assertStringNotContainsString('Terlaksana', $html);
    }

    /**
     * Kebocoran yang sama di SELURUH dokumen, bukan hanya header.
     *
     * `layouts/landing.blade.php` menulis `$sectionsActive = $sectionsActive ?? []`
     * dan `$sectionsRender = $sectionsRender ?? []`; hanya LandingPage yang
     * mengirim keduanya. Di lima halaman lain default `[]` membuat gerbang
     * `?? true` lolos untuk SEMUA section, jadi sembilan anchor `#hero…#contact`
     * dirender padahal tak satu pun section-nya ada — dan enam di antaranya
     * duduk di footer, di luar jangkauan tes header di atas.
     *
     * `/fitur` sengaja TIDAK ada di daftar ini: ia memang punya anchor `#g-…`,
     * tapi ke section yang benar-benar ada di halaman itu sendiri. Invariannya
     * "nol anchor mati", bukan "nol anchor" — bentuknya dijaga di
     * PublicFeaturesPageTest::test_setiap_chip_kategori_punya_section.
     */
    public function test_halaman_non_landing_tidak_punya_tautan_anchor()
    {
        foreach (['/pricing', '/help', '/privacy', '/terms', '/eventner'] as $path) {
            $this->assertStringNotContainsString(
                'href="#',
                $this->get($path)->getContent(),
                "Halaman {$path} memakai layout landing tapi masih punya tautan anchor mati."
            );
        }
    }

    /**
     * Perbaikannya tidak boleh dilakukan dengan cara meratakan footer: di
     * laman depan, footer memang harus masih melompat ke section-nya.
     */
    public function test_footer_landing_masih_menaut_ke_section()
    {
        Eventner::factory(3)->create(['status' => 'approved']);

        $html = $this->get('/')->getContent();
        $footer = substr($html, strpos($html, '<footer'));

        $this->assertStringContainsString('href="#features"', $footer);
        $this->assertStringContainsString('href="#contact"', $footer);
    }

    /** Potongan markup <section id="eventners"> dari HTML halaman landing. */
    private function penyelenggaraSection(string $html): string
    {
        $start = strpos($html, '<section id="eventners"');
        $this->assertNotFalse($start, 'Section penyelenggara tidak ditemukan di halaman landing.');
        $end = strpos($html, '</section>', $start);

        return substr($html, $start, $end - $start);
    }

    /** Potongan markup <section id="perbandingan"> dari HTML halaman landing. */
    private function perbandinganSection(string $html): string
    {
        $start = strpos($html, '<section id="perbandingan"');
        $this->assertNotFalse($start, 'Section perbandingan tidak ditemukan di halaman landing.');

        $end = strpos($html, '</section>', $start);

        return substr($html, $start, $end - $start);
    }

    public function test_perbandingan_menampilkan_nama_paket_sebagai_kolom()
    {
        $html = $this->perbandinganSection($this->get('/')->getContent());

        $this->assertStringContainsString('Gratis', $html);
        $this->assertStringContainsString('Event Penuh', $html);
    }

    /**
     * Tabel perbandingan harus sepakat dengan paket di DB, bukan dengan daftar
     * yang ditulis di Blade: paket berbayar yang tidak memuat 'certificate'
     * tidak boleh mencentang barisnya.
     */
    public function test_perbandingan_mengikuti_fitur_paket_dari_database()
    {
        $plan = \App\Models\SaasPlan::where('is_free', false)->firstOrFail();
        $plan->features()->delete();
        $plan->features()->createMany([
            ['feature_key' => 'tickets'],
        ]);

        $html = $this->perbandinganSection($this->get('/')->getContent());

        $this->assertStringContainsString('Tiket Event', $html);
        $this->assertStringContainsString('Sertifikat', $html);

        // Baris Sertifikat: kurang di kolom berbayar, karena paketnya tidak
        // memuat fitur itu. Baris dihitung lewat nama fiturnya di tabel.
        $baris = substr($html, strpos($html, 'Sertifikat'));
        $this->assertStringContainsString('ti-minus', substr($baris, 0, 400));
    }

    /** Fitur dasar terbuka di semua paket, termasuk paket gratis. */
    public function test_perbandingan_mencentang_fitur_dasar_di_paket_gratis()
    {
        $html = $this->perbandinganSection($this->get('/')->getContent());

        $this->assertStringContainsString('Manajemen juri &amp; input nilai', $html);
    }

    /** Section-nya bisa dimatikan admin dari panel urutan &amp; visibilitas. */
    public function test_perbandingan_hilang_saat_saklarnya_dimatikan()
    {
        Setting::set('landing_sections_active', json_encode([
            'hero' => true, 'features' => true, 'pricing' => true, 'perbandingan' => false,
            'eventners' => true, 'ticket' => true, 'faq' => true, 'cta' => true,
        ]));

        $response = $this->get('/');

        $this->assertStringNotContainsString('<section id="perbandingan"', $response->getContent());
        // Nav-nya ikut hilang, bukan jadi tautan yang kliknya tidak terjadi apa-apa.
        $header = substr($response->getContent(), 0, strpos($response->getContent(), '</header>'));
        $this->assertStringNotContainsString('href="#perbandingan"', $header);
    }

    /**
     * Section Vote hidup lagi di laman depan, dengan gerbang DATA seperti
     * Tiket: tampil hanya kalau ada event yang membuka voting. Dulu section
     * ini dibuang justru karena menyala tanpa satu pun event yang memenuhi,
     * jadi nav-nya tautan yang kliknya tidak terjadi apa-apa.
     */
    public function test_section_vote_tampil_saat_ada_event_yang_membuka_voting()
    {
        Eventner::factory()->voteAktif()->create(['nama_event' => 'Lomba Vokal 2026']);

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('<section id="vote"', $html);
        $this->assertStringContainsString('Lomba Vokal 2026', $html);
        $this->assertStringContainsString('href="#vote"', $html);
    }

    public function test_section_vote_hilang_saat_tidak_ada_event_yang_membuka_voting()
    {
        // Eventnya ada, tapi votingnya mati.
        Eventner::factory()->create(['nama_event' => 'Lomba Vokal 2026', 'vote_active' => false]);

        $html = $this->get('/')->getContent();

        $this->assertStringNotContainsString('<section id="vote"', $html);

        // Nav-nya ikut hilang — inilah bagian yang dulu bocor.
        $header = substr($html, 0, strpos($html, '</header>'));
        $this->assertStringNotContainsString('href="#vote"', $header);
    }

    /** Voting yang sudah ditutup tidak lagi menautkan ke halaman vote. */
    public function test_section_vote_hilang_saat_voting_sudah_ditutup()
    {
        Eventner::factory()->voteAktif()->create([
            'nama_event' => 'Lomba Vokal 2026',
            'vote_end' => now()->subDay(),
        ]);

        $this->assertStringNotContainsString('<section id="vote"', $this->get('/')->getContent());
    }

    /** Saklarnya tetap milik admin: menyala di data pun bisa dimatikan. */
    public function test_section_vote_hilang_saat_saklarnya_dimatikan()
    {
        Eventner::factory()->voteAktif()->create(['nama_event' => 'Lomba Vokal 2026']);

        Setting::set('landing_sections_active', json_encode([
            'vote' => false,
        ]));

        $html = $this->get('/')->getContent();

        $this->assertStringNotContainsString('<section id="vote"', $html);
        $this->assertStringNotContainsString('href="#vote"', $html);
    }

    /**
     * Urutan bawaannya mendahulukan EVENT: event, tiket, vote, baru fitur dan
     * harga. Yang menjaga ini adalah posisi anchor-nya di dokumen.
     */
    public function test_urutan_bawaan_event_sebelum_fitur_dan_harga()
    {
        Eventner::factory()->voteAktif()->create([
            'ticket_active' => true,
            'ticket_price' => 50000,
        ]);

        $html = $this->get('/')->getContent();

        $event = strpos($html, '<section id="eventners"');
        $tiket = strpos($html, '<section id="ticket"');
        $vote = strpos($html, '<section id="vote"');
        $fitur = strpos($html, '<section id="features"');
        $harga = strpos($html, '<section id="pricing"');

        foreach (compact('event', 'tiket', 'vote', 'fitur', 'harga') as $nama => $posisi) {
            $this->assertNotFalse($posisi, "Section {$nama} tidak dirender sama sekali.");
        }

        $this->assertLessThan($fitur, $event, 'Event harus sebelum Fitur.');
        $this->assertLessThan($fitur, $tiket, 'Tiket harus sebelum Fitur.');
        $this->assertLessThan($fitur, $vote, 'Vote harus sebelum Fitur.');
        $this->assertLessThan($harga, $fitur, 'Fitur harus sebelum Harga.');
    }

    /**
     * Susunan pilihan admin tidak ditimpa: section yang belum ada di urutan
     * tersimpan diselipkan SETELAH tetangga terdekatnya, bukan selalu di
     * belakang. Urutan lama (fitur di depan) tetap dipakai apa adanya.
     */
    public function test_urutan_tersimpan_tetap_dipakai_saat_push_urutannya_lain()
    {
        Eventner::factory()->create(['status' => 'approved']);

        Setting::set('landing_sections_order', json_encode([
            'hero', 'features', 'pricing', 'eventners', 'ticket', 'faq', 'cta',
        ]));

        $html = $this->get('/')->getContent();

        $fitur = strpos($html, '<section id="features"');
        $harga = strpos($html, '<section id="pricing"');
        $event = strpos($html, '<section id="eventners"');

        $this->assertNotFalse($fitur);
        $this->assertNotFalse($harga);
        $this->assertNotFalse($event);

        $this->assertLessThan($harga, $fitur, 'Urutan tersimpan (fitur sebelum harga) harus dihormati.');
        $this->assertLessThan($event, $harga);
    }

    /**
     * Nav harus menggulir SEARAH halaman, bukan melawannya.
     *
     * Anchor-nya selalu hidup (dijaga tes di atas), tapi urutannya pernah
     * salah: `$navItems` yang ditulis tangan di layout menaruh Tim sebelum FAQ,
     * sementara halamannya merender FAQ sebelum Tim — jadi mengklik "Tim" lalu
     * "FAQ" berarti menggulir ke bawah lalu kembali ke atas.
     *
     * Sekarang urutan nav diturunkan dari `LandingSections::navItems()`, dan
     * yang dijaga di sini adalah AKIBATNYA: posisi setiap anchor di nav harus
     * menaik di dalam dokumen yang benar-benar dirender. Ini menangkap
     * perselisihan dari arah mana pun — menambah section baru, memindahkan
     * blok, atau menyunting nav langsung.
     */
    public function test_urutan_nav_landing_mengikuti_urutan_section_yang_dirender()
    {
        $this->landingLengkap();

        $html = $this->get('/')->getContent();
        $anchors = $this->navAnchors($html);

        // Tanpa ini tesnya bisa lulus hanya karena nav-nya kosong.
        $this->assertNotEmpty($anchors, 'Nav landing tidak punya tautan anchor sama sekali.');

        $posisiSebelumnya = -1;
        foreach ($anchors as $anchor) {
            $posisi = strpos($html, 'id="' . $anchor . '"');

            $this->assertNotFalse($posisi, "Anchor nav #{$anchor} tidak punya section di halaman.");
            $this->assertGreaterThan(
                $posisiSebelumnya,
                $posisi,
                "Nav menaruh #{$anchor} sebelum anchor sebelumnya, padahal section-nya dirender sesudahnya — gulirnya melawan alur baca."
            );

            $posisiSebelumnya = $posisi;
        }
    }

    /**
     * Urutan nav mengikuti susunan yang dipilih admin, bukan hanya urutan
     * bawaan yang kebetulan sama. Tim dan FAQ sengaja dibalik di sini.
     */
    public function test_urutan_nav_ikut_susunan_section_pilihan_admin()
    {
        $this->landingLengkap();

        Setting::set('landing_sections_order', json_encode([
            'hero', 'eventners', 'ticket', 'vote', 'features', 'pricing',
            'team', 'faq', 'perbandingan', 'cta',
        ]));

        $html = $this->get('/')->getContent();
        $anchors = $this->navAnchors($html);

        $tim = array_search('team', $anchors, true);
        $faq = array_search('faq', $anchors, true);

        $this->assertNotFalse($tim, 'Nav tidak memuat anchor #team.');
        $this->assertNotFalse($faq, 'Nav tidak memuat anchor #faq.');
        $this->assertLessThan(
            $faq,
            $tim,
            'Admin menaruh Tim sebelum FAQ; nav harus mengikuti, bukan kembali ke urutan bawaannya.'
        );

        // "Kontak" bukan section sendiri — ia kartu di dalam #faq, jadi ia
        // harus ikut berpindah mengikuti FAQ-nya.
        $kontak = array_search('contact', $anchors, true);
        $this->assertNotFalse($kontak, 'Nav tidak memuat anchor #contact.');
        $this->assertSame($faq + 1, $kontak, 'Kontak tidak lagi menempel pada FAQ-nya.');
    }

    /**
     * Halaman depan dengan semua section yang bisa menyala benar-benar hidup —
     * Event, Tiket, Vote, FAQ, Tim, dan Sponsor punya gerbang datanya masing-
     * masing, jadi tanpa data ini nav-nya setengah kosong dan tes urutan di
     * atas cuma menguji sebagian daftar.
     */
    private function landingLengkap(): void
    {
        Setting::set('landing_faq', json_encode([
            'items' => [['question' => 'Apa itu Berbaris App?', 'answer' => 'Platform manajemen lomba.']],
        ]));

        Setting::set('landing_team', json_encode([
            'items' => [['name' => 'Budi Santoso', 'role' => 'Founder']],
        ]));

        LandingPartner::factory()->create(['is_active' => true]);

        // Satu event yang membuka tiket sekaligus voting: cukup untuk
        // menyalakan #eventners, #ticket, dan #vote.
        Eventner::factory()->voteAktif()->create([
            'ticket_active' => true,
            'ticket_price' => 50000,
        ]);
    }

    /** Anchor nav desktop, urut seperti yang dibaca pengunjung. */
    private function navAnchors(string $html): array
    {
        $awal = strpos($html, 'id="landing-desktop-nav"');
        $this->assertNotFalse($awal, 'Nav desktop tidak ditemukan di halaman landing.');

        // Berhenti di </nav>, bukan </header>: menu mobile mengulang daftar yang
        // sama setelahnya, dan salinan kedua itu akan mengacaukan urutannya.
        $akhir = strpos($html, '</nav>', $awal);
        $nav = substr($html, $awal, $akhir - $awal);

        preg_match_all('/href="(?:[^"#]*)#([^"]+)"/', $nav, $cocok);

        return $cocok[1];
    }

    public function test_event_detail_loads()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'nama_event' => 'Lomba Seni 2026',
        ]);
        $category = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => null,
            'name' => 'Seni Tari',
        ]);

        $response = $this->get("/event/{$eventner->slug}");
        $response->assertStatus(200);
    }

    public function test_event_participant_loads()
    {
        $eventner = Eventner::factory()->create(['status' => 'approved']);
        $cat = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => null,
        ]);
        $child = CompetitionCategory::factory()->create([
            'eventner_id' => $eventner->id,
            'parent_id' => $cat->id,
        ]);
        Registration::factory()->create([
            'eventner_id' => $eventner->id,
            'competition_category_id' => $child->id,
            'status_berkas' => 'confirmed',
        ]);

        $response = $this->get("/event/{$eventner->slug}/participant");
        $response->assertStatus(200);
    }

    public function test_scoreboard_page_loads()
    {
        Eventner::factory()->create([
            'status' => 'approved',
            'scoring_code' => 'SC-12345',
        ]);

        $response = $this->get('/scoreboard/SC-12345');
        $response->assertStatus(200);
    }

    public function test_champions_page_loads()
    {
        Eventner::factory()->create([
            'status' => 'approved',
            'scoring_code' => 'CH-67890',
        ]);

        $response = $this->get('/champions/CH-67890');
        $response->assertStatus(200);
    }

    public function test_event_detail_with_sponsors_and_tenants()
    {
        $eventner = Eventner::factory()->create(['status' => 'approved']);
        Sponsor::factory()->create([
            'eventner_id' => $eventner->id,
            'is_active' => true,
        ]);
        Tenant::factory()->create([
            'eventner_id' => $eventner->id,
            'is_active' => true,
        ]);

        $response = $this->get("/event/{$eventner->slug}");
        $response->assertStatus(200);
    }

    public function test_pending_event_not_accessible_via_slug()
    {
        $eventner = Eventner::factory()->pending()->create(['nama_event' => 'Pending Test']);

        // Pending event harus 404 — tidak boleh live sebelum disetujui.
        $response = $this->get("/event/{$eventner->slug}");
        $response->assertStatus(404);
    }

    public function test_ticket_page_loads()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'ticket_active' => true,
            'ticket_price' => 50000,
        ]);

        $response = $this->get("/event/{$eventner->slug}/ticket");
        $response->assertStatus(200);
    }

    public function test_404_for_nonexistent_slug()
    {
        $response = $this->get('/event/tidak-ada-99999');
        $response->assertStatus(404);
    }

    public function test_mobile_bottom_nav_replaces_hamburger()
    {
        $eventner = Eventner::factory()->create([
            'status' => 'approved',
            'ticket_active' => true,
            'ticket_price' => 25000,
            // Menu Vote dan Tiket masing-masing ikut saklarnya — keduanya
            // harus menyala supaya bar-nya lengkap.
            'vote_active' => true,
            // Tombol "Daftar Sekarang" hanya muncul saat pendaftaran terbuka;
            // deadline kosong berarti ditutup.
            'tanggal_pendaftaran' => now()->addMonth()->toDateString(),
            'tanggal' => now()->addMonths(2)->toDateString(),
        ]);

        $response = $this->get("/event/{$eventner->slug}");
        $response->assertStatus(200);

        // Bottom bar: label semua item + FAB daftar tampil
        foreach (['Info', 'Peserta', 'Hasil', 'Vote', 'Tiket', 'Daftar Sekarang'] as $label) {
            $response->assertSee($label, false);
        }
        $response->assertSee('aria-label="Navigasi utama"', false);

        // Hamburger menu + dropdown hilang dari markup
        $response->assertDontSee('nav-toggle');
        $response->assertDontSee('mobile-menu');
    }
}
