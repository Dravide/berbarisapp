<?php

namespace Tests\Unit;

use App\Support\LandingSections;
use Tests\TestCase;

/**
 * Sumber tunggal urutan section laman depan.
 *
 * Dulu urutan itu ditulis tiga kali — di komponen publik, di komponen admin,
 * dan sebagai `$navItems` di layout — dan dua di antaranya memang berbeda
 * (Tim sebelum FAQ di nav, FAQ sebelum Tim di halaman). Tes ini menjaga
 * sumbernya, bukan salinannya: menambah section ke `DIKENAL` otomatis
 * mengubah nav, dan tidak mungkin ada lagi daftar kedua yang tertinggal.
 */
class LandingSectionsTest extends TestCase
{
    /** Nav memakai urutan render; itu inti perbaikannya. */
    public function test_urutan_nav_mengikuti_urutan_section_dikenal()
    {
        $anchorNav = array_map(fn ($item) => $item[0], LandingSections::navItems());

        // `contact` menempel pada FAQ-nya, jadi ia tidak punya posisi sendiri
        // di DIKENAL; sisanya harus muncul persis dalam urutan itu.
        $diharapkan = array_values(array_filter(
            LandingSections::DIKENAL,
            fn ($key) => in_array($key, $anchorNav, true)
        ));

        $this->assertSame(
            $diharapkan,
            array_values(array_filter($anchorNav, fn ($a) => $a !== 'contact')),
            'Urutan nav tidak lagi sejalan dengan urutan render section.'
        );
    }

    /** Nav yang mengikuti susunan admin, bukan kembali ke urutan bawaan. */
    public function test_urutan_nav_mengikuti_urutan_yang_diberikan()
    {
        $anchorNav = array_map(
            fn ($item) => $item[0],
            LandingSections::navItems(['features', 'hero', 'faq', 'team'])
        );

        $this->assertSame(['features', 'hero', 'faq', 'contact', 'team'], $anchorNav);
    }

    /** Kontak tidak pernah lepas dari FAQ-nya, ke mana pun FAQ dipindah. */
    public function test_kontak_selalu_menempel_pada_faq()
    {
        foreach ([['faq', 'team'], ['team', 'faq']] as $urutan) {
            $anchorNav = array_map(fn ($item) => $item[0], LandingSections::navItems($urutan));
            $faq = array_search('faq', $anchorNav, true);

            $this->assertSame($faq + 1, array_search('contact', $anchorNav, true));
        }
    }

    /**
     * `URUTAN_BAWAAN` dulu string JSON yang diketik tangan di dua tempat —
     * persis bentuk yang paling gampang tertinggal saat `DIKENAL` berubah.
     */
    public function test_urutan_dan_saklar_bawaan_dibangun_dari_daftar_dikenal()
    {
        $urutan = json_decode(LandingSections::urutanBawaan(), true);
        $aktif = json_decode(LandingSections::aktifBawaan(), true);

        $this->assertSame(LandingSections::DIKENAL, $urutan);
        $this->assertSame(array_fill_keys(LandingSections::DIKENAL, true), $aktif);
    }

    /**
     * Section tanpa label tidak muncul di nav — `sponsor` dan `cta` memang
     * section penutup, bukan tujuan navigasi.
     */
    public function test_section_penutup_tidak_masuk_nav()
    {
        $anchorNav = array_map(fn ($item) => $item[0], LandingSections::navItems());

        $this->assertNotContains('sponsor', $anchorNav);
        $this->assertNotContains('cta', $anchorNav);
    }

    /** Daftar ini harus memuat setiap section yang punya komponen Blade. */
    public function test_setiap_section_punya_komponen_blade()
    {
        foreach (LandingSections::DIKENAL as $key) {
            $this->assertFileExists(
                resource_path("views/components/landing/{$key}.blade.php"),
                "Section '{$key}' ada di daftar tapi tidak punya komponen Blade — namanya akan memanggil view yang tidak ada."
            );
        }
    }
}
