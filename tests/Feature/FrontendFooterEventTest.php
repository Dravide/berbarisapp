<?php

namespace Tests\Feature;

use App\Models\Eventner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Footer halaman EVENT — pita identitas, bukan empat kolom.
 *
 * Footer di layouts/frontend.blade.php dulu empat kolom (Brand / Navigasi /
 * Kontak / Penyelenggara) dan dipakai bersama halaman publik lain. Di halaman
 * event, tiga kolom terakhir isinya pengulangan: tiap tautannya sudah dirender
 * header dan nav bawah ponsel, nama penyelenggara sudah ada di hero, lokasi
 * punya kartu sendiri. Yang dibuang digantikan fakta acara yang sebelumnya
 * tak muncul sama sekali di footer: tanggal, lokasi, tingkat.
 *
 * Tes di berkas ini yang menjaga pemisahan itu tetap ada — kalau suatu hari
 * kolom Navigasi/Kontak/Penyelenggara muncul lagi di halaman event, atau
 * footer cabang non-event ikut kehilangan kolomnya, tes ini yang gagal.
 */
class FrontendFooterEventTest extends TestCase
{
    use RefreshDatabase;

    private function event(array $attributes = []): Eventner
    {
        return Eventner::factory()->create(array_merge([
            'status' => 'approved',
            'nama_event' => 'Lomba PBB se-Jawa Barat 2026',
            'diselenggarakan_oleh' => 'Dinas Pendidikan Jawa Barat',
            'lokasi' => 'Bandung',
            'tingkat_perlombaan' => 'Provinsi',
            'tanggal' => '2026-11-02',
            'tanggal_akhir' => '2026-11-04',
            'is_verified' => true,
        ], $attributes));
    }

    /** Ambil hanya isi <footer>…</footer>, supaya judul kolom di tempat lain tidak ikut terhitung. */
    private function footer(string $html): string
    {
        $mulai = strpos($html, '<footer');

        if ($mulai === false) {
            return '';
        }

        $akhir = strpos($html, '</footer>', $mulai);

        return substr($html, $mulai, $akhir - $mulai + 9);
    }

    /**
     * Tiga kolom itu tidak ada lagi di footer halaman event.
     *
     * Yang diuji adalah JUDUL kolomnya, bukan tiap tautannya: judul kolom yang
     * kembali muncul berarti footernya kembali menjadi daftar tautan, apa pun
     * isi tautannya.
     */
    public function test_footer_event_tidak_punya_kolom_navigasi_kontak_penyelenggara()
    {
        $eventner = $this->event();

        $footer = $this->footer($this->get("/event/{$eventner->slug}")->assertStatus(200)->getContent());

        $this->assertNotSame('', $footer, 'Halaman event tidak merender <footer> sama sekali.');

        foreach (['Navigasi', 'Kontak', 'Penyelenggara'] as $judul) {
            $this->assertStringNotContainsString(
                '>' . $judul . '</h4>',
                $footer,
                "Footer halaman event masih punya kolom \"{$judul}\"."
            );
        }
    }

    /**
     * Yang menggantikannya: fakta acara. Inilah alasan footer baru ini ada —
     * tanggal, lokasi, dan tingkat tak pernah muncul di footer mana pun.
     */
    public function test_footer_event_menyebut_tanggal_lokasi_dan_tingkat()
    {
        $eventner = $this->event();

        $footer = $this->footer($this->get("/event/{$eventner->slug}")->assertStatus(200)->getContent());

        $this->assertStringContainsString('02 Nov – 04 November 2026', $footer);
        $this->assertStringContainsString('Bandung', $footer);
        $this->assertStringContainsString('Tingkat Provinsi', $footer);
    }

    /**
     * Identitas penyelenggara tetap terbaca di footer — tapi sebagai satu baris
     * di pita, bukan kolom tersendiri.
     */
    public function test_footer_event_menyebut_penyelenggara_sebagai_baris_identitas()
    {
        $eventner = $this->event();

        $footer = $this->footer($this->get("/event/{$eventner->slug}")->assertStatus(200)->getContent());

        $this->assertStringContainsString('Dinas Pendidikan Jawa Barat', $footer);
        $this->assertStringContainsString('Hak cipta dilindungi', $footer);
    }

    /**
     * Butir yang datanya kosong tidak dicetak sebagai chip kosong.
     *
     * Chip tanpa isi terbaca seperti render yang gagal — persis masalah yang
     * bentuk footer ini selesaikan pada kolom Kontak yang dulu tinggal dua
     * tautan di event tanpa WhatsApp (pbb2026 memang begitu: link_whatsapp
     * dan link_livestreaming kosong).
     *
     * Kolom `tanggal` dan `lokasi` NOT NULL di skema, jadi yang diuji di sini
     * adalah kolom nullable ditambah lokasi berisi string kosong — jalur yang
     * benar-benar bisa terjadi, bukan cabang yang tak terjangkau.
     */
    public function test_butir_kosong_tidak_dicetak_sebagai_chip_kosong()
    {
        $eventner = $this->event([
            'lokasi' => '',
            'tingkat_perlombaan' => null,
            'link_instagram' => null,
            'link_tiktok' => null,
            'link_whatsapp' => null,
            'link_livestreaming' => null,
            'logo_event' => null,
        ]);

        $footer = $this->footer($this->get("/event/{$eventner->slug}")->assertStatus(200)->getContent());

        $this->assertStringNotContainsString('ti-map-pin', $footer);
        $this->assertStringNotContainsString('Tingkat ', $footer);
        $this->assertStringNotContainsString('ti-brand-instagram', $footer);
        $this->assertStringNotContainsString('ti-brand-whatsapp', $footer);
        $this->assertStringNotContainsString('ti-brand-youtube', $footer);

        // Pita tetap dirender meski sebagian butir kosong — identitas dan
        // tanggal yang ada tetap tampil, bukan footer yang hilang.
        $this->assertStringContainsString('Lomba PBB se-Jawa Barat 2026', $footer);
        $this->assertStringContainsString('02 Nov – 04 November 2026', $footer);
        $this->assertStringContainsString('Hak cipta dilindungi', $footer);
    }

    /**
     * Footer cabang NON-event tidak ikut berubah.
     *
     * HallOfFame memakai layout ini tanpa layoutData, jadi $eventner kosong di
     * sana. Kalau cabang @else ikut kehilangan kolomnya, halaman itu kehilangan
     * tautan legalnya tanpa ada yang menyadari.
     */
    public function test_footer_non_event_masih_punya_empat_kolomnya()
    {
        Eventner::factory()->create([
            'status' => 'approved',
            'diselenggarakan_oleh' => 'Panitia Hall of Fame',
        ]);

        $respon = $this->get(route('public.hall-of-fame', 'panitia-hall-of-fame'))->assertStatus(200);
        $footer = $this->footer($respon->getContent());

        $this->assertStringContainsString('>Navigasi</h4>', $footer);
        $this->assertStringContainsString('>Kontak</h4>', $footer);
        $this->assertStringContainsString('>Penyelenggara</h4>', $footer);
        $this->assertStringContainsString(route('pricing'), $footer);
    }

    /**
     * Tepi atas footer memakai warna brand event, bukan palet tetap platform.
     *
     * Inilah yang membuat footer halaman event bisa dibedakan dari footer
     * platform: --color-primary disuntik dari theme_config event, jadi tiap
     * acara memakai warnanya sendiri tanpa konfigurasi tambahan.
     */
    public function test_pita_memakai_warna_brand_event()
    {
        $eventner = $this->event([
            'theme_config' => ['primary_color' => '#0072FF', 'accent_color' => '#00D4AA'],
        ]);

        $html = $this->get("/event/{$eventner->slug}")->assertStatus(200)->getContent();

        $this->assertStringContainsString('--color-primary: #0072FF', $html);
        $this->assertStringContainsString('border-t-2 border-primary', $this->footer($html));
    }

    /**
     * Padding bawah footer mengakomodasi nav bawah ponsel.
     *
     * Nav bawah `fixed bottom-0` setinggi h-16 (64 px) + safe-area menindih
     * dasar halaman. Dengan `py-12` yang lama (48 px) baris hak cipta memang
     * tertutup di ponsel; pb-24 (96 px) menaruhnya di atas nav.
     */
    public function test_footer_event_menyisakan_ruang_untuk_nav_bawah_ponsel()
    {
        $eventner = $this->event();

        $footer = $this->footer($this->get("/event/{$eventner->slug}")->assertStatus(200)->getContent());

        $this->assertStringContainsString('pb-24', $footer);
        $this->assertStringContainsString('md:pb-12', $footer);
    }
}
