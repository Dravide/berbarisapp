<?php

namespace App\Support;

/**
 * Isi halaman subdomain EO — profil, lingkup kerja, alur, peran tim, tingkat
 * sponsor, dan daftar isian formulir pengajuan.
 *
 * Kenapa kelas sendiri, bukan Setting dan bukan `ProductFeatures`:
 *
 * 1. Yang dijual di sini JASA, bukan perangkat lunak. `ProductFeatures` adalah
 *    katalog fitur yang setiap entrinya dipetakan ke gerbang paket berbayar dan
 *    dijaga `PaywallCompletenessTest` dua arah. Menumpangkan jasa ke sana akan
 *    membuat tes gerbang ikut menuntut harga jasa.
 * 2. Isinya harus bisa diperiksa: `EoPagesTest` memastikan tiap peran tim,
 *    tiap kelompok layanan, dan tiap langkah alur benar-benar dirender. Karena
 *    itu ia kode, bukan suntingan admin yang bisa kosong tanpa ada yang gagal.
 *
 * Aturan isi — sama seperti katalog fitur: **tidak ada janji aspirasional**.
 * Butir layanan yang menyebut sistem (pendaftaran, drawing, penilaian tablet,
 * papan skor, rekap) harus benar-benar ada di aplikasinya. Butir yang belum
 * diputuskan (angka paket sponsor, perkiraan waktu alur) TIDAK ditulis di sini
 * sebagai angka — kelas ini hanya menyediakan tempatnya, dan halaman
 * menampilkannya sebagai "menunggu kesepakatan" alih-alih angka karangan.
 *
 * Nama tim sengaja tidak ada di sini: halaman /tim menampilkan peran dan
 * tugasnya lebih dulu, dan nama diisi belakangan lewat setelan `eo_team`.
 */
class EoProfile
{
    /**
     * Identitas EO. `nama` kosong berarti halaman memakai nama platform —
     * bukan nama contoh. Ganti lewat setelan `eo_nama` setelah timnya punya
     * nama resmi.
     */
    public const IDENTITAS = [
        'nama' => '',
        'tagline' => 'Penyelenggara lomba baris-berbaris',
        'ringkas' => 'Kami menyelenggarakan lombanya: dari poster dan pendaftaran, penilaian juri di lapangan, sampai rekap juara dan laporan ke sponsor.',
    ];

    /**
     * Jenis lomba yang ditangani. Istilahnya sama dengan yang dipakai panitia
     * dan yang sudah hidup di kategori lomba aplikasinya (induk – tingkat).
     *
     * @return array<int, string>
     */
    public static function jenisLomba(): array
    {
        return [
            'PBB / LOBB',
            'LKBB',
            'TUB',
            'RUKIBRA',
            'VARMUS',
            'Danton',
            'Variasi & Formasi',
            'Tingkat SD–SMA & Umum',
        ];
    }

    /**
     * Lingkup kerja, urut pekerjaan panitia — bukan urutan kepintaran tim.
     * Huruf A–E dipakai sebagai rujukan di halaman lain ("lingkup B").
     *
     * @return array<int, array{kode: string, judul: string, ringkas: string, butir: array<int, array{nama: string, ket: string}>}>
     */
    public static function layanan(): array
    {
        return [
            [
                'kode' => 'A',
                'judul' => 'Identitas & Desain',
                'ringkas' => 'Semua yang tercetak dan terlihat: dari logo sampai medali.',
                'butir' => [
                    ['nama' => 'Logo & identitas event', 'ket' => 'logo, palet, tipografi'],
                    ['nama' => 'Poster, banner, backdrop', 'ket' => 'cetak & digital'],
                    ['nama' => 'Jersey, ID card, medali', 'ket' => 'desain siap produksi'],
                    ['nama' => 'Sertifikat & piagam', 'ket' => 'siap tanda tangan'],
                ],
            ],
            [
                'kode' => 'B',
                'judul' => 'Sistem Lomba',
                'ringkas' => 'Bagian yang berjalan di BARIS APP — bukan berkas terpisah.',
                'butir' => [
                    ['nama' => 'Halaman pendaftaran & verifikasi berkas', 'ket' => 'peserta mendaftar sendiri'],
                    ['nama' => 'Drawing / undian urutan tampil', 'ket' => 'di panggung atau layar'],
                    ['nama' => 'Penilaian juri via tablet', 'ket' => 'tanpa kertas'],
                    ['nama' => 'Live scoreboard & rekap PDF', 'ket' => 'berita acara'],
                ],
            ],
            [
                'kode' => 'C',
                'judul' => 'Hari-H & Arena',
                'ringkas' => 'Yang berdiri di lapangan pada hari lomba.',
                'butir' => [
                    ['nama' => 'Denah arena & alur gerak', 'ket' => 'per kategori lomba'],
                    ['nama' => 'Meja daftar ulang & scan QR', 'ket' => 'kartu akses peserta'],
                    ['nama' => 'Rundown & jadwal pertandingan', 'ket' => 'disusun sebelum hari-H'],
                    ['nama' => 'Juri, MC, dan protokol', 'ket' => 'dikonfirmasi panitia'],
                ],
            ],
            [
                'kode' => 'D',
                'judul' => 'Dokumentasi & Siaran',
                'ringkas' => 'Yang tersisa setelah acara selesai.',
                'butir' => [
                    ['nama' => 'Foto & video aftermovie', 'ket' => 'liputan hari-H'],
                    ['nama' => 'Livestream & videotron', 'ket' => 'tayangan venue'],
                    ['nama' => 'Konten media sosial', 'ket' => 'pengumuman, jadwal, hasil'],
                ],
            ],
            [
                'kode' => 'E',
                'judul' => 'Administrasi & Laporan',
                'ringkas' => 'Berkas yang diminta atasan setelah acara usai.',
                'butir' => [
                    ['nama' => 'Rekap peserta & laporan sponsor', 'ket' => 'angka dari sistem, bukan perkiraan'],
                    ['nama' => 'Kwitansi, invoice, arsip event', 'ket' => 'tersimpan dan bisa diunduh'],
                ],
            ],
        ];
    }

    /**
     * Tujuh langkah pelaksanaan. Nomornya memang urutan, jadi penomoran
     * layak dipakai di sini.
     *
     * @return array<int, array{judul: string, ket: string}>
     */
    public static function alur(): array
    {
        return [
            ['judul' => 'Pengajuan', 'ket' => 'Cukup nama instansi, jenis lomba, dan perkiraan tanggal.'],
            ['judul' => 'Konsultasi & survei lokasi', 'ket' => 'Kami hitung kategori, peserta, dan kebutuhan arena.'],
            ['judul' => 'Proposal & RAB', 'ket' => 'Rincian pekerjaan dan biaya per baris, bukan satu angka bulat.'],
            ['judul' => 'Kesepakatan & uang muka', 'ket' => 'Setelah ini jadwal produksi dan tanggal buka pendaftaran dikunci.'],
            ['judul' => 'Persiapan', 'ket' => 'Desain dicetak, rubrik disusun, halaman pendaftaran dibuka, juri dikonfirmasi.'],
            ['judul' => 'Technical meeting & hari-H', 'ket' => 'Drawing, daftar ulang QR, penilaian dari tablet, papan skor di layar.'],
            ['judul' => 'Penutupan & laporan', 'ket' => 'Juara, berita acara, sertifikat, rekap peserta, laporan sponsor.'],
        ];
    }

    /**
     * Peran tim dalam empat kelompok.
     *
     * `nama` sengaja kosong untuk semua peran: kartu yang belum ada namanya
     * tetap dirender dengan penanda "menyusul", bukan disembunyikan. Nama
     * diisi lewat setelan `eo_team` (JSON `{peran: nama}`) — pemetaannya
     * dilakukan `Livewire\Public\Eo\Team`, bukan di sini, supaya katalog ini
     * tetap satu sumber untuk peran dan tugasnya.
     *
     * @return array<int, array{kelompok: string, peran: array<int, array{peran: string, tugas: string}>}>
     */
    public static function tim(): array
    {
        return [
            [
                'kelompok' => 'Inti & Kendali',
                'peran' => [
                    ['peran' => 'Direktur Event', 'tugas' => 'Penanggung jawab acara; satu pintu keputusan dengan panitia.'],
                    ['peran' => 'Manajer Lomba', 'tugas' => 'Menyusun kategori, rubrik, dan jadwal pertandingan.'],
                    ['peran' => 'Sekretaris & Administrasi', 'tugas' => 'Berkas peserta, korespondensi sekolah, arsip acara.'],
                    ['peran' => 'Keuangan', 'tugas' => 'RAB, kwitansi, pembayaran vendor, laporan akhir.'],
                ],
            ],
            [
                'kelompok' => 'Lomba & Lapangan',
                'peran' => [
                    ['peran' => 'Koordinator Juri & Penilaian', 'tugas' => 'Mengundang juri, menyiapkan rubrik, menjaga konsistensi nilai.'],
                    ['peran' => 'Koordinator Arena', 'tugas' => 'Denah lapangan, alur gerak pasukan, urutan tampil.'],
                    ['peran' => 'Operator Sistem & Skor', 'tugas' => 'Menjalankan BARIS APP di lapangan: drawing, penilaian, papan skor.'],
                    ['peran' => 'Meja Daftar Ulang', 'tugas' => 'Pindai kartu QR peserta, cek berkas, bagi ID card.'],
                    ['peran' => 'Logistik & Perlengkapan', 'tugas' => 'Tenda, sound, panggung, listrik, konsumsi panitia.'],
                    ['peran' => 'MC & Protokol', 'tugas' => 'Memandu upacara, drawing, dan pengumuman juara.'],
                    ['peran' => 'Tim Kesehatan', 'tugas' => 'P3K dan rujukan bila ada peserta cedera di arena.'],
                    ['peran' => 'Keamanan & Parkir', 'tugas' => 'Akses peserta, penonton, dan kendaraan di lokasi.'],
                ],
            ],
            [
                'kelompok' => 'Kreatif & Media',
                'peran' => [
                    ['peran' => 'Desain Grafis', 'tugas' => 'Logo, poster, banner, jersey, medali, sertifikat.'],
                    ['peran' => 'Dokumentasi Foto & Video', 'tugas' => 'Liputan hari-H dan aftermovie.'],
                    ['peran' => 'Operator Siaran', 'tugas' => 'Livestream OBS dan tayangan videotron venue.'],
                    ['peran' => 'Media Sosial & Konten', 'tugas' => 'Pengumuman, jadwal, hasil, dokumentasi harian.'],
                ],
            ],
            [
                'kelompok' => 'Mitra',
                'peran' => [
                    ['peran' => 'Humas & Sponsorship', 'tugas' => 'Menawarkan kerja sama ke instansi dan merek.'],
                    ['peran' => 'Tenant & Booth', 'tugas' => 'Mengatur stand dagang, peta tenant, dan listriknya.'],
                ],
            ],
        ];
    }

    /**
     * Tingkat sponsorship. `sorot` menandai satu kartu yang diangkat.
     *
     * `nilai` sengaja null: angka kerja sama ditentukan per acara bersama
     * sponsor. Halaman menampilkan "menunggu kesepakatan" — bukan angka
     * karangan yang harus ditarik lagi setelah rapat pertama.
     *
     * @return array<int, array{nama: string, ringkas: string, butir: array<int, string>, sorot: bool}>
     */
    public static function sponsor(): array
    {
        return [
            [
                'nama' => 'Pendukung',
                'ringkas' => 'Untuk instansi atau usaha yang ingin ikut meramaikan.',
                'butir' => ['Logo di poster & backdrop', 'Sebutan terima kasih di penutupan', 'Sertifikat kerja sama'],
                'sorot' => false,
            ],
            [
                'nama' => 'Utama',
                'ringkas' => 'Tingkat yang paling sering dipilih instansi dan merek daerah.',
                'butir' => ['Semua di tingkat Pendukung', 'Logo di backdrop utama & papan skor', 'Stan / tenant di lokasi', 'Konten media sosial bersama'],
                'sorot' => true,
            ],
            [
                'nama' => 'Utama & Penamaan',
                'ringkas' => 'Nama sponsor melekat pada acaranya.',
                'butir' => ['Semua di tingkat Utama', 'Nama sponsor pada judul acara', 'Sebutan di MC sepanjang acara', 'Laporan tayangan & jumlah peserta'],
                'sorot' => false,
            ],
        ];
    }

    /**
     * Yang didapat mitra di semua tingkat — termasuk dua hal yang dikelola
     * sistemnya langsung (peta tenant, pendaftaran tenant online).
     *
     * @return array<int, array{nama: string, ket: string}>
     */
    public static function mitra(): array
    {
        return [
            ['nama' => 'Peta tenant venue', 'ket' => 'posisi petak & listrik'],
            ['nama' => 'Pendaftaran tenant online', 'ket' => 'tanpa daftar lewat pesan'],
        ];
    }

    /**
     * Isian formulir pengajuan. `wajib` menentukan tanda bintang dan aturan
     * validasi komponennya; sisanya boleh dikira-kira (lihat catatan di
     * `Livewire\Public\Eo\Ajukan`).
     *
     * @return array<int, array{nama: string, label: string, tipe: string, wajib: bool, pilihan?: array<int, string>}>
     */
    public static function formulir(): array
    {
        return [
            ['nama' => 'instansi', 'label' => 'Instansi / sekolah', 'tipe' => 'text', 'wajib' => true],
            ['nama' => 'penanggung_jawab', 'label' => 'Nama penanggung jawab', 'tipe' => 'text', 'wajib' => true],
            ['nama' => 'whatsapp', 'label' => 'Nomor WhatsApp', 'tipe' => 'tel', 'wajib' => true],
            ['nama' => 'email', 'label' => 'Surel', 'tipe' => 'email', 'wajib' => false],
            ['nama' => 'jenis_lomba', 'label' => 'Jenis lomba', 'tipe' => 'select', 'wajib' => true],
            ['nama' => 'tanggal', 'label' => 'Perkiraan tanggal', 'tipe' => 'date', 'wajib' => false],
            ['nama' => 'lokasi', 'label' => 'Lokasi / kota', 'tipe' => 'text', 'wajib' => false],
            ['nama' => 'jumlah_peserta', 'label' => 'Perkiraan jumlah peserta', 'tipe' => 'number', 'wajib' => false],
        ];
    }

    /**
     * Pilihan pekerjaan yang bisa dicentang di formulir — diambil dari kode
     * kelompok layanan supaya daftarnya tidak pernah berselisih dengan
     * halaman /layanan.
     *
     * @return array<int, string>
     */
    public static function pekerjaanPilihan(): array
    {
        return array_map(
            fn (array $l) => $l['judul'],
            self::layanan()
        );
    }
}
