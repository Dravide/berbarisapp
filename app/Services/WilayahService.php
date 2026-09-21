<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Daftar wilayah resmi (BPS) dari api.datawilayah.com.
 *
 * Dipakai field bertipe `wilayah` di form pendaftaran: penyelenggara tidak
 * ingin satu kabupaten ditulis dengan sepuluh ejaan berbeda, karena rekap,
 * sertifikat, dan ekspor jadi tidak bisa diandalkan.
 *
 * Berkasnya JSON statis tanpa API key, jadi tidak ada kredensial dan tidak ada
 * cache di DB (permintaan penyelenggara: ambil live). Yang ada hanya memo
 * per-request — satu render Livewire sering memanggil opsiWilayah() berkali-kali
 * untuk field yang sama, dan tanpa memo itu tiap panggilan jadi satu request HTTP.
 *
 * Aturan penting kelas ini: **tidak pernah melempar**. Kegagalan jaringan
 * dikembalikan sebagai array kosong + log. Dropdown yang kosong membuat UI turun
 * ke input teks biasa (lihat trait MengelolaWilayah), sedangkan exception akan
 * mematikan seluruh halaman pendaftaran.
 */
class WilayahService
{
    /** Tingkat wilayah, dipakai untuk menentukan kedalaman dropdown. */
    public const PROVINSI = 1;
    public const KABUPATEN = 2;
    public const KECAMATAN = 3;

    /**
     * Sebutan singkat provinsi yang lazim ditulis penyelenggara.
     *
     * Perlu daftar sendiri karena `tingkat_perlombaan` bukan pilihan tetap:
     * orang menulis "Jabar", bukan "Jawa Barat". Dipakai sebagai kata utuh
     * (\b) supaya "Balikpapan" tidak terbaca sebagai provinsi Bali.
     */
    private const ALIAS_PROVINSI = [
        'jabar', 'jateng', 'jatim', 'dki', 'diy', 'yogyakarta', 'jogja',
        'sumut', 'sumbar', 'sumsel', 'lampung', 'riau', 'kepri', 'jambi',
        'bengkulu', 'babel', 'banten', 'jakarta', 'bali', 'ntb', 'ntt',
        'kalbar', 'kalteng', 'kalsel', 'kaltim', 'kaltara',
        'sulut', 'sulteng', 'sulsel', 'sultra', 'gorontalo', 'malut',
        'maluku', 'papua', 'aceh',
    ];

    /** Penanda teks yang menyebut kabupaten/kota. */
    private const PENANDA_KABUPATEN = ['kabupaten', 'kab.', 'kab ', 'kota', 'kotamadya', 'kecamatan'];

    /** Memo per-request: url => daftar wilayah. */
    private static array $memo = [];

    /**
     * Daftar provinsi.
     *
     * @return list<array{kode: string, nama: string}>
     */
    public static function provinsi(): array
    {
        return self::ambil('provinsi');
    }

    /**
     * Daftar kabupaten/kota dalam satu provinsi (kode seperti "32").
     *
     * @return list<array{kode: string, nama: string}>
     */
    public static function kabupaten(string $kodeProvinsi): array
    {
        $kode = trim($kodeProvinsi);

        return $kode === '' ? [] : self::ambil('kabupaten_kota/' . rawurlencode($kode));
    }

    /**
     * Daftar kecamatan dalam satu kabupaten/kota (kode seperti "32.01").
     *
     * @return list<array{kode: string, nama: string}>
     */
    public static function kecamatan(string $kodeKabupaten): array
    {
        $kode = trim($kodeKabupaten);

        return $kode === '' ? [] : self::ambil('kecamatan/' . rawurlencode($kode));
    }

    /**
     * Kedalaman dropdown dari teks tingkat lomba event.
     *
     * `tingkat_perlombaan` adalah teks bebas (tidak ada enum, tidak pernah
     * divalidasi daftar tetap), jadi ini pencocokan pola — bukan lookup. Urutan
     * pemeriksaannya disengaja:
     *
     *  1. nasional/internasional → provinsi saja. Diperiksa lebih dulu supaya
     *     tidak ada panggilan HTTP untuk event tingkat nasional.
     *  2. menyebut kabupaten/kota/kecamatan → tiga tingkat.
     *  3. cocok nama provinsi atau sebutan singkatnya → provinsi + kabupaten.
     *  4. selain itu → provinsi saja. Teks aneh tidak boleh mematikan form.
     */
    public static function levelUntukTingkat(?string $tingkat): int
    {
        $teks = mb_strtolower(trim((string) $tingkat));

        if ($teks === '') {
            return self::PROVINSI;
        }

        if (preg_match('/\b(nasional|internasional|international|asean)\b/', $teks)) {
            return self::PROVINSI;
        }

        foreach (self::PENANDA_KABUPATEN as $penanda) {
            if (str_contains($teks, $penanda)) {
                return self::KECAMATAN;
            }
        }

        foreach (self::ALIAS_PROVINSI as $alias) {
            if (preg_match('/\b' . preg_quote($alias, '/') . '\b/', $teks)) {
                return self::KABUPATEN;
            }
        }

        // Nama provinsi lengkap dicocokkan dari daftar live — kalau API sedang
        // mati daftarnya kosong dan teksnya jatuh ke provinsi saja, bukan error.
        $padat = preg_replace('/[^a-z]/', '', $teks);

        foreach (self::provinsi() as $prov) {
            if (preg_replace('/[^a-z]/', '', mb_strtolower($prov['nama'])) === $padat) {
                return self::KABUPATEN;
            }
        }

        return self::PROVINSI;
    }

    /**
     * Susun nilai simpan dari bagian yang dipilih berurutan.
     *
     * Hasilnya `"32 - JAWA BARAT / 32.01 - KAB. BOGOR"`. Kode disimpan bersama
     * namanya supaya seluruh pembaca nilai yang sudah ada (PDF formulir,
     * invoice, daftar ulang, sertifikat, RegistrationResource) tetap benar tanpa
     * perubahan apa pun — tidak ada satu pun dari mereka yang menerjemahkan kode.
     *
     * Bagian kosong dilewati, jadi memilih provinsi saja menghasilkan
     * `"32 - JAWA BARAT"` tanpa garis miring menggantung.
     *
     * @param  array<int, array{kode?: string, nama?: string}|null>  $bagian
     */
    public static function gabung(array $bagian): string
    {
        $potongan = [];

        foreach ($bagian as $item) {
            $kode = trim((string) ($item['kode'] ?? ''));
            $nama = trim((string) ($item['nama'] ?? ''));

            if ($kode === '' || $nama === '') {
                continue;
            }

            $potongan[] = $kode . ' - ' . $nama;
        }

        return implode(' / ', $potongan);
    }

    /**
     * Baca balik nilai tersimpan jadi kode per tingkat.
     *
     * Dipakai untuk mengisi ulang dropdown saat peserta membuka form yang sudah
     * pernah diisi. Nilai lama dari era teks bebas ("Bandar Lampung") bukan
     * format kode — hasilnya array kosong, dan itu memang jawabannya: dropdown
     * tampil kosong sampai peserta memilih ulang, nilai lamanya tidak dihapus.
     *
     * @return array<int, string> index = tingkat - 1, isi = kode
     */
    public static function kodeDari(?string $nilai): array
    {
        $teks = trim((string) $nilai);

        if ($teks === '') {
            return [];
        }

        $kode = [];

        foreach (explode('/', $teks) as $i => $potongan) {
            if (! preg_match('/^\s*(\d{2}(?:\.\d{2}){0,3})\s*-\s*\S/', $potongan, $cocok)) {
                // Bagian pertama saja yang menentukan: kalau awalnya bukan kode,
                // seluruh nilai jelas bukan hasil pilihan dropdown.
                return [];
            }

            $kode[$i] = $cocok[1];
        }

        return $kode;
    }

    /**
     * Apakah nilai ini berbentuk hasil gabung() — dipakai rule validasi untuk
     * memutuskan perlu diperiksa atau tidak.
     */
    public static function berbentukKode(?string $nilai): bool
    {
        return self::kodeDari($nilai) !== [];
    }

    /** Buang memo — hanya dipakai tes supaya tiap kasus mulai dari nol. */
    public static function lupakanMemo(): void
    {
        self::$memo = [];
    }

    /**
     * Ambil satu berkas wilayah, dengan memo.
     *
     * Kunci normalisasi diserap di sini: API memakai `kode_wilayah` untuk semua
     * tingkat, tapi di level desa ada berkas yang memakai `kode_kabkota` — dan
     * `nama_provinsi`/`kode_provinsi` di level kabupaten sengaja diabaikan
     * karena yang dibutuhkan pemanggil hanya kode + nama wilayah itu sendiri.
     *
     * @return list<array{kode: string, nama: string}>
     */
    private static function ambil(string $path): array
    {
        if (array_key_exists($path, self::$memo)) {
            return self::$memo[$path];
        }

        $base = rtrim((string) config('services.datawilayah.base_url'), '/');

        try {
            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->retry(2, 300)
                ->get("{$base}/api/{$path}.json");

            if (! $response->successful()) {
                Log::warning('WilayahService: berkas wilayah tidak bisa diambil', [
                    'path' => $path,
                    'status' => $response->status(),
                ]);

                return self::$memo[$path] = [];
            }

            $baris = $response->json('data') ?? [];
        } catch (\Throwable $e) {
            Log::warning('WilayahService: pengambilan wilayah gagal', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return self::$memo[$path] = [];
        }

        $hasil = [];

        foreach ($baris as $item) {
            $kode = trim((string) ($item['kode_wilayah'] ?? $item['kode_kabkota'] ?? ''));
            $nama = trim((string) ($item['nama_wilayah'] ?? ''));

            if ($kode === '' || $nama === '') {
                continue;
            }

            $hasil[] = ['kode' => $kode, 'nama' => $nama];
        }

        return self::$memo[$path] = $hasil;
    }
}
