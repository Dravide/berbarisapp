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
     * pemanggil yang mengurutkan barisnya akan mendapat rekap yang urut. Rekap
     * Data Sekolah dan Kartu Akses mengurutkan lewat nomor undian, sehingga
     * sekolah yang punya beberapa pasukan duduk di posisi pasukan dengan undian
     * terkecil.
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

    /**
     * Pecah hasil `kelompokkan()` menjadi satu bagian per kategori lomba.
     *
     * Rekap tabel dikelompokkan kategori karena pendaftaran memang per kategori:
     * satu sekolah yang mengirim pasukan di dua tingkat muncul SEKALI di tiap
     * bagian. Menampilkannya sekali saja (di kategori pertama) akan membuat
     * jumlah pasukan sebuah bagian tidak cocok dengan isinya, dan panitia yang
     * mencetak halaman ini untuk meja pendaftaran ulang membaca angka itu
     * sebagai kebenaran.
     *
     * Kategori yang tidak punya satu pun pendaftar tidak dibuatkan bagiannya —
     * judul kosong di atas tabel kosong hanya memakan kertas. Kategori
     * pengelompokannya kategori TINGKAT yang bisa dipilih pendaftar, jadi
     * pendaftaran lama yang mendarat di induk (data flat sebelum hierarki)
     * tidak akan muncul di sini — sama seperti ia memang tidak punya tab di
     * halaman peserta.
     *
     * @param  Collection<int, Registration>  $registrations  dengan participants + fieldValues ter-eager-load
     * @param  Collection<int, \App\Models\CompetitionCategory>  $categories  sudah urut sesuai keinginan pemanggil
     * @return Collection<int, array{kategori: \App\Models\CompetitionCategory, sekolah: Collection<int, array>}>
     */
    public static function perKategori(Collection $registrations, Collection $categories, ?int $fieldKabupatenId = null): Collection
    {
        return $categories
            ->map(function ($kategori) use ($registrations, $fieldKabupatenId) {
                $milikKategori = $registrations->where('competition_category_id', $kategori->id);

                if ($milikKategori->isEmpty()) {
                    return null;
                }

                return [
                    'kategori' => $kategori,
                    'sekolah' => self::kelompokkan($milikKategori->values(), $fieldKabupatenId),
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Angka ringkas untuk blok "Rekapitulasi" di kaki rekap.
     *
     * `baris` sengaja dibedakan dari `sekolah_unik`: satu sekolah yang mendaftar
     * di dua kategori punya satu baris di tiap bagian, jadi menjumlahkan baris
     * akan melebihkan jumlah sekolah. Keduanya dilaporkan supaya catatannya
     * jujur, bukan sekadar angka besar yang terlihat bagus.
     *
     * Status dihitung dari sekolah UNIK, memakai status paling jauh di antara
     * baris-barisnya — satu sekolah tidak boleh terhitung dua kali hanya karena
     * statusnya berbeda antar kategori.
     *
     * @param  Collection<int, array{kategori: mixed, sekolah: Collection<int, array>}>  $perKategori
     * @return array{kategori: int, baris: int, sekolah_unik: int, lintas_kategori: int, pasukan: int, anggota: int, status: array<string, int>}
     */
    public static function rekapitulasi(Collection $perKategori): array
    {
        $semuaBaris = $perKategori->flatMap(fn (array $bagian) => $bagian['sekolah']);

        $unik = [];
        foreach ($semuaBaris as $s) {
            $kunci = $s['kunci'];

            if (! isset($unik[$kunci]) || self::peringkatStatus($s['status']) > self::peringkatStatus($unik[$kunci]['status'])) {
                $unik[$kunci] = $s;
            }
        }

        $status = [];
        foreach ($unik as $s) {
            $label = self::labelStatus($s['status']);
            $status[$label] = ($status[$label] ?? 0) + 1;
        }

        // Sekolah yang muncul di lebih dari satu bagian kategori — inilah yang
        // membuat "baris tabel" > "sekolah unik", jadi jumlahnya dilaporkan
        // supaya selisihnya bisa dipertanggungjawabkan dari catatan itu sendiri.
        $jumlahBarisPerKunci = $semuaBaris->countBy('kunci');
        $lintasKategori = $jumlahBarisPerKunci->filter(fn (int $n) => $n > 1)->count();

        return [
            'kategori' => $perKategori->count(),
            'baris' => $semuaBaris->count(),
            'sekolah_unik' => count($unik),
            'lintas_kategori' => $lintasKategori,
            'pasukan' => (int) $semuaBaris->sum('jumlah_pasukan'),
            'anggota' => (int) $semuaBaris->sum('jumlah_anggota'),
            'status' => $status,
        ];
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

    /**
     * Versi `denganTautan()` untuk hasil `perKategori()`.
     *
     * Tautannya sama untuk semua bagian (satu sekolah satu tautan portal, tak
     * peduli kategori mana yang dibuka), tapi tetap dihitung per bagian supaya
     * tiap baris membawa kunci `url` yang sama seperti pada rekap satu tabel —
     * view tidak perlu tahu bentuk mana yang sedang dirender.
     *
     * @param  Collection<int, array{kategori: mixed, sekolah: Collection<int, array>}>  $perKategori
     */
    public static function denganTautanPerKategori(Collection $perKategori): Collection
    {
        return $perKategori->map(fn (array $bagian) => [
            ...$bagian,
            'sekolah' => self::denganTautan($bagian['sekolah']),
        ]);
    }
}
