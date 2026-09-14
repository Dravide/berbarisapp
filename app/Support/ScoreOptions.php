<?php

namespace App\Support;

/**
 * Pemecah teks opsi skor menjadi daftar nilai.
 *
 * Satu-satunya tempat aturan ini hidup. Dulu Builder (simpan) dan blade
 * (preview) memakai daftar pemisah yang berbeda — blade bahkan memisah pada
 * entitas HTML '&ndash;' yang tidak pernah muncul di nilai sebenarnya,
 * sedangkan Builder memisah pada karakter ' – ' asli. Akibatnya preset
 * "0 – 25" tampil sebagai satu badge di preview tapi tersimpan sebagai dua
 * nilai terpisah.
 *
 * Rentang ("0 – 25") sengaja TIDAK dipisah: di layar, satu kelompok label
 * dengan rentang berarti satu tombol bernilai rentang itu. Pemisah yang
 * benar-benar memisah hanya koma dan titik koma.
 */
class ScoreOptions
{
    /** Pemisah antar nilai dalam satu kelompok label. */
    private const SEPARATORS = [',', ';'];

    /**
     * @return array<int, string> nilai yang sudah di-trim, tanpa entri kosong
     */
    public static function split(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        $normalized = str_replace(self::SEPARATORS, ',', $raw);

        return array_values(array_filter(
            array_map('trim', explode(',', $normalized)),
            fn ($v) => $v !== ''
        ));
    }

    /**
     * Nilai numerik tertinggi dari daftar opsi — batas atas untuk perhitungan
     * "skor maksimal" (mis. di portal pendaftar).
     *
     * Rentang diambil batas ATASnya: "0 – 25" berarti maksimalnya 25. Dulu
     * nilainya di-cast (int) langsung, jadi rentang apa pun terbaca 0 dan
     * skor maksimal kriteria itu jatuh ke nilai cadangan 100.
     *
     * @param  array<int, mixed>  $options  skalar atau {score, label}
     * @return int  0 bila tidak ada angka yang bisa dibaca
     */
    public static function maxValue(array $options): int
    {
        $max = 0;

        foreach ($options as $opt) {
            $raw = is_array($opt) ? ($opt['score'] ?? '') : $opt;

            if (! preg_match_all('/-?\d+(?:\.\d+)?/', (string) $raw, $matches)) {
                continue;
            }

            foreach ($matches[0] as $angka) {
                $max = max($max, (int) round((float) $angka));
            }
        }

        return $max;
    }
}
