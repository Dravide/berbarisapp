<?php

namespace App\Support;

/**
 * Satu-satunya daftar section laman depan.
 *
 * Dulu daftar ini ada dua kali: sekali di `Public\LandingPage` sebagai urutan
 * render, sekali lagi sebagai `$navItems` yang ditulis tangan di
 * `layouts/landing.blade.php`. Keduanya sempat berselisih — nav menaruh `Tim`
 * sebelum `FAQ` sementara halamannya merender FAQ sebelum Tim — jadi tautan
 * nav menggulir melawan arah baca. Komentar "harus sama dengan …" di komponen
 * admin tidak menahan apa pun; yang menahan hanya satu sumber.
 *
 * Sekarang urutan nav DITURUNKAN dari `DIKENAL`, bukan ditulis ulang: mengubah
 * urutan render otomatis mengubah urutan nav, jadi keduanya tidak bisa lagi
 * berselisih. Label dan anchor tambahan tetap di sini supaya semua yang perlu
 * disunting saat menambah section ada di satu berkas.
 */
final class LandingSections
{
    /**
     * Section yang punya komponen Blade, dalam urutan bawaan.
     *
     * Urutannya sengaja mendahulukan EVENT (eventners, ticket, vote), baru
     * fitur dan harga: laman depan menjual acaranya lebih dulu, alatnya
     * belakangan. `contact` tidak ada di sini karena ia bukan section sendiri —
     * kartu di dalam `#faq`.
     */
    public const DIKENAL = [
        'hero',
        'eventners',
        'ticket',
        'vote',
        'features',
        'pricing',
        'perbandingan',
        'faq',
        'team',
        'sponsor',
        'cta',
    ];

    /**
     * Label nav per section. Section yang tidak punya label tidak muncul di nav
     * (`sponsor` dan `cta` — keduanya section penutup, bukan tujuan navigasi).
     */
    private const LABEL_NAV = [
        'hero' => 'Beranda',
        'eventners' => 'Event',
        'ticket' => 'Tiket',
        'vote' => 'Vote',
        'features' => 'Fitur',
        'pricing' => 'Harga',
        'perbandingan' => 'Perbandingan',
        'faq' => 'FAQ',
        'team' => 'Tim',
    ];

    /**
     * Anchor yang bukan section sendiri: anchor => [label, section penaungnya].
     *
     * Nama kunci di sini masih dipakai sebagai teks label, jadi ketiganya
     * ditulis eksplisit, bukan disimpulkan.
     */
    private const ANCHOR_TAMBAHAN = [
        'contact' => ['Kontak', 'faq'],
    ];

    /**
     * Item nav, mengikuti urutan section yang BENAR-BENAR dirender.
     *
     * `$urutan` adalah `sectionsOrder` milik halaman, bukan urutan bawaan:
     * admin boleh memindahkan blok, dan nav yang tetap memakai urutan bawaan
     * akan menggulir ke atas setiap kali admin membalik dua section. Karena
     * daftarnya dijalani, bukan disusun ulang, nav tidak bisa lagi berselisih
     * dengan halaman — menambah section baru pun tidak perlu menyentuh layout.
     *
     * @param  array<int, string>|null  $urutan  null = urutan bawaan
     * @return array<int, array{0: string, 1: string, 2: string}> [anchor, label, gerbang]
     */
    public static function navItems(?array $urutan = null): array
    {
        $urutan = $urutan ?: self::DIKENAL;
        $items = [];

        foreach ($urutan as $key) {
            if (isset(self::LABEL_NAV[$key])) {
                $items[] = [$key, self::LABEL_NAV[$key], $key];
            }

            // Anchor tambahan menempel tepat setelah section penaungnya, jadi
            // "Kontak" selalu ikut ke mana pun FAQ dipindahkan.
            foreach (self::ANCHOR_TAMBAHAN as $anchor => [$label, $penaung]) {
                if ($penaung === $key) {
                    $items[] = [$anchor, $label, $penaung];
                }
            }
        }

        return $items;
    }

    /**
     * Urutan bawaan sebagai JSON — nilai default setting saat belum pernah
     * disimpan. Dibangun dari `DIKENAL`, bukan diketik ulang: string yang
     * diketik ulang persis inilah yang dulu tertinggal dari kode.
     */
    public static function urutanBawaan(): string
    {
        return json_encode(self::DIKENAL);
    }

    /** Saklar bawaan: semua section menyala. */
    public static function aktifBawaan(): string
    {
        return json_encode(array_fill_keys(self::DIKENAL, true));
    }
}
