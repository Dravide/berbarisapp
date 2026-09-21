<?php

namespace App\Support;

use App\Models\Registration;
use App\Services\WilayahService;
use Illuminate\Support\Collection;

/**
 * Rekap data sekolah dari baris-baris `registrations`.
 *
 * Halaman peserta menyimpan satu baris per PASUKAN, bukan per sekolah: satu
 * sekolah yang mengirim tiga pasukan di dua kategori muncul tiga kali. Untuk
 * rekap dan kartu akses, yang dibutuhkan justru sudut pandang sekolah — karena
 * itu pengelompokannya di sini, bukan di view.
 *
 * Murni: tanpa DB dan tanpa HTTP. Pemanggil menyiapkan koleksi registrasi
 * (beserta `participants` dan `fieldValues` yang sudah di-eager-load), jadi
 * seluruh keputusan di kelas ini bisa diuji tanpa menyentuh basis data.
 *
 * Catatan soal sekolah tanpa NPSN: kolomnya nullable sejak migrasi
 * 2026_09_21, jadi kuncinya jatuh ke nama sekolah. Dua sekolah berbeda yang
 * sama-sama tanpa NPSN tetap terpisah karena namanya berbeda, dan dua baris
 * dengan nama sama digabung — sama seperti pembanding duplikat import
 * (lihat PendaftarImport::kunci()).
 */
class DataSekolah
{
    /**
     * Peringkat status: yang paling jauh menang saat satu sekolah punya
     * beberapa pasukan berstatus berbeda.
     *
     * Tabel rekap hanya muat satu kata per sekolah, dan yang paling perlu
     * dilihat panitia adalah status terbaiknya — pasukan yang sudah terverifikasi
     * tidak boleh tampak "booking" hanya karena pasukan lain belum diurus.
     * Rincian per pasukan tetap terbaca di kolom lain dan di halaman pendaftar.
     */
    private const PERINGKAT_STATUS = [
        'Terverifikasi' => 5,
        'Menunggu' => 4,
        'confirmed' => 4,
        'booking' => 3,
        'Ditolak' => 2,
        'dibatalkan' => 1,
    ];

    /** Sebutan status untuk dibaca manusia — `confirmed` dan `Menunggu` sama-sama "Menunggu Verifikasi". */
    private const LABEL_STATUS = [
        'Terverifikasi' => 'Terverifikasi',
        'Menunggu' => 'Menunggu Verifikasi',
        'confirmed' => 'Menunggu Verifikasi',
        'booking' => 'Booking',
        'Ditolak' => 'Ditolak',
        'dibatalkan' => 'Dibatalkan',
    ];

    /**
     * Kunci pengelompokan sekolah.
     *
     * Huruf besar/kecil dan spasi disamakan HANYA untuk pembandingan: nilai yang
     * ditampilkan tetap ejaan asli panitia. NPSN yang menentukan bila ada — dua
     * sekolah bernama sama tapi NPSN berbeda adalah dua sekolah.
     */
    public static function kunciSekolah(?string $npsn, ?string $namaSekolah): string
    {
        $npsn = trim((string) $npsn);

        return $npsn !== ''
            ? 'NPSN:'.mb_strtoupper($npsn)
            : 'SEKOLAH:'.mb_strtoupper(trim((string) $namaSekolah));
    }

    /** Sebutan status untuk dibaca manusia; status tak dikenal dikembalikan apa adanya. */
    public static function labelStatus(?string $status): string
    {
        $status = (string) $status;

        return self::LABEL_STATUS[$status] ?? ($status !== '' ? $status : '—');
    }

    /**
     * Id definisi field Kabupaten/Kota (`field_key = 'asal_kabupaten'`), null
     * bila panitia menghapus atau menonaktifkannya.
     *
     * Pemanggil WAJIB tahan terhadap null — field ini field buatan panitia,
     * bukan kolom bawaan, jadi event yang belum pernah menyentuh builder bisa
     * saja tidak punya barisnya.
     *
     * @param  Collection<int, \App\Models\RegistrationField>  $fields
     */
    public static function fieldKabupatenId(Collection $fields): ?int
    {
        $field = $fields->first(fn ($f) => $f->field_key === 'asal_kabupaten' && $f->is_active);

        return $field?->id;
    }

    /**
     * Nama kabupaten/kota sebuah registrasi, '' bila fieldnya tidak ada/kosong.
     *
     * Nilainya tersimpan sebagai kode BPS ("32.04 - Bandung"), jadi ditampilkan
     * lewat WilayahService::nama(). Nilai lama era teks bebas dikembalikan apa
     * adanya oleh fungsi yang sama — tidak ada pemaksaan format kode di sini.
     */
    public static function kabupaten(Registration $registration, ?int $fieldKabupatenId): string
    {
        if (! $fieldKabupatenId) {
            return '';
        }

        $nilai = $registration->fieldValues
            ->firstWhere('registration_field_id', $fieldKabupatenId)
            ?->value;

        return WilayahService::nama($nilai);
    }

    /**
     * Kelompokkan baris per pasukan menjadi satu baris per sekolah.
     *
     * Urutan hasil mengikuti urutan kemunculan pertama di koleksi masukan, jadi
     * pemanggil yang mengurutkan `nama_sekolah` akan mendapat rekap yang urut.
     * Baris `dibatalkan` disaring di sini walau pemanggil biasanya sudah
     * menyaringnya — itu baris yang digantikan, dan menghitungnya membuat jumlah
     * pasukan dan status sebuah sekolah salah.
     *
     * @param  Collection<int, Registration>  $registrations  dengan participants + fieldValues ter-eager-load
     * @return Collection<int, array{
     *     kunci: string, npsn: ?string, nama_sekolah: string, kabupaten: string,
     *     jumlah_pasukan: int, jumlah_kategori: int, jumlah_anggota: int,
     *     status: string, label_status: string, pelatih: string, no_hp: string,
     *     email: string, registrasi_induk: Registration
     * }>
     */
    public static function kelompokkan(Collection $registrations, ?int $fieldKabupatenId = null): Collection
    {
        $hasil = [];

        foreach ($registrations as $reg) {
            if ($reg->status_berkas === 'dibatalkan') {
                continue;
            }

            $kunci = self::kunciSekolah($reg->npsn, $reg->nama_sekolah);

            if (! isset($hasil[$kunci])) {
                $hasil[$kunci] = [
                    'kunci' => $kunci,
                    'npsn' => $reg->npsn,
                    'nama_sekolah' => (string) $reg->nama_sekolah,
                    'kabupaten' => self::kabupaten($reg, $fieldKabupatenId),
                    'jumlah_pasukan' => 0,
                    'kategori' => [],
                    'jumlah_anggota' => 0,
                    'status' => '',
                    'pelatih' => '',
                    'no_hp' => '',
                    'email' => '',
                    'registrasi' => [],
                ];
            }

            $hasil[$kunci]['jumlah_pasukan']++;
            $hasil[$kunci]['jumlah_anggota'] += $reg->participants->count();

            // Kategori dihitung distinct: tiga pasukan di dua kategori harus
            // terbaca "3 pasukan, 2 kategori", bukan "3 kategori".
            $hasil[$kunci]['kategori'][$reg->competition_category_id] = true;

            if (self::peringkatStatus($reg->status_berkas) > self::peringkatStatus($hasil[$kunci]['status'])) {
                $hasil[$kunci]['status'] = (string) $reg->status_berkas;
            }

            // Kontak diambil dari pasukan PERTAMA yang mengisinya, bukan dari
            // baris induk saja: identitas sekolah biasanya sama, dan yang kosong
            // di satu pasukan sering terisi di pasukan lain.
            foreach (['pelatih' => 'nama_pelatih', 'no_hp' => 'no_hp', 'email' => 'school_email'] as $tujuan => $kolom) {
                if ($hasil[$kunci][$tujuan] === '' && trim((string) $reg->{$kolom}) !== '') {
                    $hasil[$kunci][$tujuan] = (string) $reg->{$kolom};
                }
            }

            $hasil[$kunci]['registrasi'][] = $reg;
        }

        return collect($hasil)
            ->map(function (array $sekolah) use ($fieldKabupatenId) {
                // Baris induk = id terkecil: deterministik, dan yang paling tua
                // hampir selalu yang pertama didaftarkan sekolah itu.
                $induk = collect($sekolah['registrasi'])->sortBy('id')->first();

                // Dihitung SEBELUM penanda kerjanya dibuang: `kategori` adalah peta
                // id => true, jadi panjangnya jumlah kategori distinct.
                $jumlahKategori = count($sekolah['kategori']);

                // Dua penanda kerja dibuang sebelum hasil diserahkan: `registrasi`
                // (objek model) dan `kategori` (peta id => true) — yang dipakai
                // pemanggil hanya jumlahnya.
                unset($sekolah['registrasi'], $sekolah['kategori']);

                return [
                    ...$sekolah,
                    // NPSN & kontak diambil dari baris induk supaya ejaannya
                    // konsisten walau antar-pasukan berbeda.
                    'npsn' => $induk->npsn,
                    'nama_sekolah' => (string) $induk->nama_sekolah,
                    'kabupaten' => self::kabupaten($induk, $fieldKabupatenId),
                    'jumlah_kategori' => $jumlahKategori,
                    'label_status' => self::labelStatus($sekolah['status']),
                    'registrasi_induk' => $induk,
                ];
            })
            ->values();
    }

    /** Peringkat status; status tak dikenal dianggap paling lemah supaya tidak menutupi yang jelas. */
    private static function peringkatStatus(?string $status): int
    {
        return self::PERINGKAT_STATUS[(string) $status] ?? 0;
    }

    /**
     * Tambahkan magic link tiap sekolah ke hasil `kelompokkan()`.
     *
     * Tautannya diambil dari baris INDUK (`registrasi_induk` = id terkecil), jadi
     * semua pasukan satu sekolah menunjuk tautan yang sama — portal memang
     * menampilkan seluruh pasukan sekolah itu, bukan satu baris saja.
     *
     * Dipisah dari QR-nya dengan sengaja: rekap tabel memuat puluhan sekolah dan
     * tidak menampilkan satu QR pun, jadi merender QR di sana hanya membuang
     * waktu render puluhan PNG. Yang butuh QR memanggil `denganQr()` setelahnya.
     *
     * @param  Collection<int, array>  $sekolah
     */
    public static function denganTautan(Collection $sekolah): Collection
    {
        return $sekolah->map(function (array $s) {
            $token = $s['registrasi_induk']->magic_token;

            return [...$s, 'url' => $token ? route('magic.link', $token) : ''];
        });
    }

    /**
     * Tambahkan gambar QR ke baris yang sudah punya `url`.
     *
     * QR WAJIB PNG — dompdf membuang SVG diam-diam, dan `qr_data_uri()`
     * mengembalikan null saat gagal (bukan melempar). Pemanggil harus tahan
     * terhadap null: halamannya tetap tercetak, hanya tanpa gambarnya.
     *
     * @param  Collection<int, array>  $sekolah  hasil denganTautan(), punya kunci 'url'
     */
    public static function denganQr(Collection $sekolah, int $scale = 8): Collection
    {
        return $sekolah->map(fn (array $s) => [
            ...$s,
            'qr' => ($s['url'] ?? '') !== '' ? qr_data_uri($s['url'], $scale) : null,
        ]);
    }
}
