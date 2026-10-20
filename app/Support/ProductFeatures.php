<?php

namespace App\Support;

/**
 * Katalog fitur produk untuk halaman publik /fitur.
 *
 * Sumber kebenaran tampilan — sama perannya dengan App\Support\Pricing yang
 * dibaca /pricing. Array polos, nol I/O, nol DB; admin tidak menyuntingnya
 * dari panel (bukan kunci Setting). Yang admin pegang adalah sembilan item
 * ringkasan di `landing_features` — dan dari sembilan itu hanya EMPAT yang
 * tampil di laman depan, yang bertanda `featured`; ini katalog penuhnya.
 *
 * Kenapa kelas ini terpisah dari config/eventner_features.php — bukan
 * memakainya ulang:
 *
 * 1. Berkas config itu config PAYWALL. Tiap entrinya punya makna
 *    `canAccessFeature()`, dibaca HasFeatureGates, Pricing::comparisonMatrix,
 *    Admin\PricingSettings, dan Dashboard. Menggandeng katalog publik ke sana
 *    berarti satu gate yang diganti nama diam-diam menulis ulang halaman
 *    pemasaran.
 * 2. Isinya sengaja TIDAK lengkap untuk pemasaran: kepala berkasnya sendiri
 *    menyatakan dashboard, profil, peserta, juri, input nilai, rekap, dan
 *    scoreboard tidak terdaftar. Memakainya ulang mengunci katalog di 16
 *    entri dan tetap memaksa modul inti disisipkan.
 * 3. Labelnya beda maksud — config menulis "Tiket Event", kartu di sini
 *    menulis "Penjualan Tiket Online".
 *
 * Penjaga drift-nya ada di tests/Feature/PublicFeaturesPageTest.php:
 * gabungan seluruh `gates` di sini harus SAMA PERSIS dengan
 * array_keys(config('eventner_features')), diuji dua arah. Itu yang membuat
 * pemisahan dua berkas ini aman.
 *
 * Aturan katalog — satu fitur, satu route nyata:
 * `route` wajib nama route yang benar-benar terdaftar (dijaga tes lewat
 * Route::has()). Tidak ada fitur aspirasional. Dua kunci opsional:
 *
 * - `miniature` — kunci seni di components/landing/feature-miniature.blade.php.
 *   Sembilan item yang seninya sudah ada di laman depan menulis nilai
 *   `icon3.png`…`icon11.png` supaya blok @case-nya tidak diduplikasi.
 * - `gates` — kunci config/eventner_features.php yang dipetakan ke fitur ini.
 *   Kosong untuk modul yang selalu terbuka.
 *
 * `route` HANYA rujukan untuk tes & penelusuran. Kartu di /fitur tidak
 * merender tautan: hampir semua route ini butuh login atau konteks event,
 * jadi menautkannya ke pengunjung anonim berarti mendarat di halaman login
 * atau 500.
 */
class ProductFeatures
{
    /**
     * Delapan kelompok fitur, urut alur kerja panitia: mendaftar → hari-H →
     * menilai → menayangkan → monetisasi → menutup acara.
     *
     * @return array<int, array{key: string, title: string, description: string, items: array<int, array>}>
     */
    public static function groups(): array
    {
        return [
            [
                'key' => 'pendaftaran',
                'title' => 'Pendaftaran & Peserta',
                'description' => 'Dari formulir pendaftaran publik sampai kartu akses yang dicetak untuk sekolah.',
                'items' => [
                    [
                        'title' => 'Pendaftaran Online Multi-Step',
                        'description' => 'Peserta mendaftar sendiri lewat halaman event. Formulir bertahap, kuota per kategori, dan status berkas yang bisa dipantau.',
                        'miniature' => 'form-daftar',
                        'icon' => 'ti-forms',
                        'route' => 'event.register',
                        'gates' => [],
                    ],
                    [
                        'title' => 'Magic Link Peserta',
                        'description' => 'Tiap peserta menerima satu tautan pribadi tanpa perlu membuat akun — untuk mengunggah berkas, mengunduh formulir, invoice, dan sertifikat.',
                        'miniature' => 'browser-publik',
                        'icon' => 'ti-link',
                        'route' => 'magic.link',
                        'gates' => [],
                    ],
                    [
                        'title' => 'Field Pendaftaran Kustom',
                        'description' => 'Susun sendiri isian formulir pendaftaran: teks, pilihan, sampai unggahan berkas. Urutannya bisa diseret.',
                        'miniature' => 'form-builder',
                        'icon' => 'ti-adjustments-horizontal',
                        'route' => 'eventner.registration-fields.index',
                        'gates' => [],
                    ],
                    [
                        'title' => 'Kategori Lomba Bertingkat',
                        'description' => 'Kategori, grup, seri, dan babak bertingkat — atur juri dan rubrik per tingkat, bukan satu daftar rata untuk seluruh event.',
                        'miniature' => 'kategori-pohon',
                        'icon' => 'ti-layers-intersect',
                        'route' => 'eventner.competition-categories.index',
                        'gates' => [],
                    ],
                    [
                        'title' => 'Manajemen Peserta & Verifikasi Berkas',
                        'description' => 'Satu tabel untuk seluruh peserta: periksa berkas, ubah status, dan lihat siapa saja yang masih menunggu.',
                        'miniature' => 'icon3.png',
                        'icon' => 'ti-users',
                        'route' => 'eventner.participants.index',
                        'gates' => [],
                    ],
                    [
                        'title' => 'Kartu Akses Peserta & Rekap Sekolah',
                        'description' => 'Cetak kartu ber-QR untuk tiap peserta, rekap jumlah peserta per sekolah, dan kartu akses sekolah berisi tautan pendaftaran ulang.',
                        'miniature' => 'rekap-sekolah',
                        'icon' => 'ti-id',
                        'route' => 'eventner.participants.data-sekolah',
                        'gates' => [],
                    ],
                ],
            ],
            [
                'key' => 'venue',
                'title' => 'Hari-H & Venue',
                'description' => 'Yang dipakai panitia sambil berdiri: denah lapangan, meja daftar ulang, urutan tampil, rundown, dan jadwal.',
                'items' => [
                    [
                        'title' => 'Tempat Lomba',
                        'description' => 'Catat venue yang dipakai event — satu event boleh punya beberapa tempat, masing-masing dengan kuota dan harganya sendiri.',
                        'miniature' => 'venue',
                        'icon' => 'ti-map-pin',
                        'route' => 'eventner.venues.index',
                        'gates' => [],
                    ],
                    [
                        'title' => 'Denah Lapangan per Kategori',
                        'description' => 'Gambar denah lapangan di kanvas: arena, pos penilaian, tribun, sekretariat, kotak danton, sampai DP 1–5. Kotaknya menempel tepi papan atau sejajar zona lain saat digeser, dan hasilnya bisa diunduh PNG/PDF lalu diterbitkan ke halaman publik event.',
                        'miniature' => 'venue',
                        'icon' => 'ti-layout-board-split',
                        'route' => 'eventner.denah.index',
                        'gates' => ['layout'],
                    ],
                    [
                        'title' => 'Kunci Denah & Kunci per Zona',
                        'description' => 'Gembok dua tingkat: kunci seluruh denah, atau kunci satu kotak saja. Arena dan pos penilaian yang sudah pas bisa dibekukan sementara DP 4 masih digeser — zona lain tetap bisa dikerjakan.',
                        'miniature' => 'denah-kunci',
                        'icon' => 'ti-lock',
                        'route' => 'eventner.denah.index',
                        'gates' => ['layout'],
                    ],
                    [
                        'title' => 'Alur Gerak Pasukan',
                        'description' => 'Gambar jalur masuk, titik belok, dan arah hadap pasukan sebagai garis alur di atas denah. Alurnya bisa ditampilkan atau disembunyikan dari halaman publik event.',
                        'miniature' => 'denah-alur',
                        'icon' => 'ti-route',
                        'route' => 'eventner.denah.index',
                        'gates' => ['layout'],
                    ],
                    [
                        'title' => 'Ukuran Meter Papan & Arena',
                        'description' => 'Isi ukuran papan yang sebenarnya dan ukuran arena yang dipatok sendiri, supaya angka di denah cocok dengan lapangan — bukan cuma gambar berskala.',
                        'miniature' => 'denah-ukur',
                        'icon' => 'ti-ruler-measure',
                        'route' => 'eventner.denah.index',
                        'gates' => ['layout'],
                    ],
                    [
                        'title' => 'Meja Daftar Ulang & Scan QR Kartu',
                        'description' => 'Peserta datang, kartunya dipindai, ia langsung ditandai hadir — tanpa mencari namanya di tabel.',
                        'miniature' => 'daftar-ulang',
                        'icon' => 'ti-clipboard-check',
                        'route' => 'eventner.daftar-ulang.index',
                        'gates' => [],
                    ],
                    [
                        'title' => 'Drawing / Undian Urutan Tampil',
                        'description' => 'Undian digital untuk menentukan urutan tampil. Hasilnya bisa dicetak dan ditayangkan langsung ke layar.',
                        'miniature' => 'icon8.png',
                        'icon' => 'ti-arrows-shuffle',
                        'route' => 'eventner.drawing.index',
                        'gates' => ['drawing'],
                    ],
                    [
                        'title' => 'Rundown Acara',
                        'description' => 'Susun rundown per jam dan tayangkan ke halaman publik event, supaya peserta dan penonton tahu acara sedang di tahap mana.',
                        'miniature' => 'rundown',
                        'icon' => 'ti-list-details',
                        'route' => 'eventner.rundown.index',
                        'gates' => ['rundown'],
                    ],
                    [
                        'title' => 'Jadwal Pertandingan',
                        'description' => 'Pertemuan per tingkat, grup, babak, dan venue — tampil di atas rundown acara di halaman publik.',
                        'miniature' => 'jadwal',
                        'icon' => 'ti-calendar-time',
                        'route' => 'eventner.schedule.index',
                        'gates' => ['schedule'],
                    ],
                ],
            ],
            [
                'key' => 'penilaian',
                'title' => 'Penilaian & Juara',
                'description' => 'Inti produk ini: rubrik yang bisa disusun sendiri, juri yang menilai dari tablet, dan juara yang keluar dari data.',
                'items' => [
                    [
                        'title' => 'Format Penilaian Kustom',
                        'description' => 'Bangun rubrik sendiri: kriteria, rentang nilai, dan bobotnya. Bisa disalin antar kategori supaya tidak menyusun ulang dari nol.',
                        'miniature' => 'format-nilai',
                        'icon' => 'ti-checklist',
                        'route' => 'eventner.format-nilai.builder',
                        'gates' => ['format_nilai'],
                    ],
                    [
                        'title' => 'Input Nilai Juri via Tablet',
                        'description' => 'Juri menilai dari tablet atau ponsel lewat tautan bertoken, tanpa akun. Bisa dinilai per peserta atau per sesi.',
                        'miniature' => 'icon4.png',
                        'icon' => 'ti-pencil',
                        'route' => 'eventner.scoring.index',
                        'gates' => [],
                    ],
                    [
                        'title' => 'Perbandingan Nilai Antar Juri',
                        'description' => 'Lihat nilai tiap juri berdampingan untuk peserta yang sama — memudahkan menemukan penilaian yang menyimpang sebelum juara ditetapkan.',
                        'miniature' => 'perbandingan',
                        'icon' => 'ti-scale',
                        'route' => 'eventner.scoring.perbandingan',
                        'gates' => [],
                    ],
                    [
                        'title' => 'Rekap Nilai & Cetak PDF',
                        'description' => 'Rekap nilai per peserta, per kategori, atau seluruh tingkat sekaligus — semua bisa diunduh sebagai CSV dan PDF.',
                        'miniature' => 'rekap-nilai',
                        'icon' => 'ti-chart-bar',
                        'route' => 'eventner.score-recap.index',
                        'gates' => [],
                    ],
                    [
                        'title' => 'Kategori Juara & Berita Acara',
                        'description' => 'Tetapkan juara umum, harapan, dan kategori lain dari hasil penilaian, lalu cetak berita acara yang siap ditandatangani.',
                        'miniature' => 'podium',
                        'icon' => 'ti-trophy',
                        'route' => 'eventner.champion-categories.index',
                        'gates' => ['champion_categories'],
                    ],
                    [
                        'title' => 'Sertifikat Digital',
                        'description' => 'Buat template sertifikat sendiri, tarik data juara dan peserta langsung dari hasil penilaian, lalu unduh per peserta.',
                        'miniature' => 'icon9.png',
                        'icon' => 'ti-certificate',
                        'route' => 'eventner.certificate.index',
                        'gates' => ['certificate'],
                    ],
                ],
            ],
            [
                'key' => 'siaran',
                'title' => 'Papan Skor & Siaran',
                'description' => 'Yang dilihat penonton, di aula maupun di rumah — tiga dari empat halaman ini bisa dibuka tanpa login.',
                'items' => [
                    [
                        'title' => 'Live Scoreboard Publik',
                        'description' => 'Papan skor yang menyegarkan sendiri, dibuka lewat satu tautan atau QR. Bisa diproyeksikan ke layar tanpa perangkat tambahan.',
                        'miniature' => 'icon7.png',
                        'icon' => 'ti-podium',
                        'route' => 'public.scoreboard',
                        'gates' => [],
                    ],
                    [
                        'title' => 'Pengumuman Juara & Hall of Fame',
                        'description' => 'Halaman juara satu event, plus rekap juara lintas event satu penyelenggara — arsip yang tetap berguna setelah acara selesai.',
                        'miniature' => 'hall-of-fame',
                        'icon' => 'ti-award',
                        'route' => 'public.champions',
                        'gates' => [],
                    ],
                    [
                        'title' => 'Livestream Overlay OBS',
                        'description' => 'Alamat overlay siap tempel untuk OBS: menampilkan nama peserta, skor berjalan, dan logo sponsor di atas siaran langsung.',
                        'miniature' => 'icon10.png',
                        'icon' => 'ti-broadcast',
                        'route' => 'eventner.livestream.index',
                        'gates' => ['livestream'],
                    ],
                    [
                        'title' => 'Videotron Display Venue',
                        'description' => 'Layar besar untuk di dalam venue: mode putar otomatis yang bergantian antara skor, jadwal, dan sponsor sepanjang acara.',
                        'miniature' => 'videotron',
                        'icon' => 'ti-device-tv',
                        'route' => 'event.videotron',
                        'gates' => [],
                    ],
                ],
            ],
            [
                'key' => 'voting',
                'title' => 'Voting Penonton',
                'description' => 'Peroleh suara penonton lewat pembayaran digital, dengan harga yang diatur panitia sendiri dan hasil yang transparan.',
                'items' => [
                    [
                        'title' => 'Voting Berbayar',
                        'description' => 'Atur sendiri harga satu suara, berapa suara per transaksi, dan kapan voting dibuka. Peserta menerima perolehan secara real-time.',
                        'miniature' => 'icon5.png',
                        'icon' => 'ti-star',
                        'route' => 'eventner.vote-settings.index',
                        'gates' => ['vote_settings'],
                    ],
                    [
                        'title' => 'Vote Booster',
                        'description' => 'Paket suara tambahan dengan harga khusus untuk pendukung yang ingin memberi lebih dari satu suara.',
                        'miniature' => 'booster',
                        'icon' => 'ti-bolt',
                        'route' => 'eventner.vote-booster.index',
                        'gates' => ['vote_booster'],
                    ],
                    [
                        'title' => 'Hasil & Transaksi Voting',
                        'description' => 'Perolehan suara tiap peserta berdampingan dengan daftar transaksinya, lengkap dengan unduhan CSV dan rincian per pembayaran.',
                        'miniature' => 'hasil-voting',
                        'icon' => 'ti-chart-dots',
                        'route' => 'eventner.vote-results.index',
                        'gates' => ['vote_results', 'vote_transactions'],
                    ],
                    [
                        'title' => 'Moderasi Komentar Voting',
                        'description' => 'Penonton boleh berkomentar di halaman voting. Panitia menyetujui atau menyembunyikannya sebelum tampil.',
                        'miniature' => 'komentar',
                        'icon' => 'ti-messages',
                        'route' => 'eventner.vote-comments.index',
                        'gates' => ['vote_comments'],
                    ],
                ],
            ],
            [
                'key' => 'tiket',
                'title' => 'Tiket Event',
                'description' => 'Untuk acara yang menontonnya berbayar: jual tiketnya online, pindai QR-nya di pintu.',
                'items' => [
                    [
                        'title' => 'Penjualan Tiket Online',
                        'description' => 'Jual tiket lewat halaman event dengan pembayaran digital. Harga bisa berbeda per tempat, dan penjualannya dilaporkan per tiket.',
                        'miniature' => 'icon6.png',
                        'icon' => 'ti-ticket',
                        'route' => 'eventner.tickets.index',
                        'gates' => ['tickets', 'ticket_settings'],
                    ],
                    [
                        'title' => 'E-Tiket QR & Check-in di Pintu',
                        'description' => 'Tiket ber-QR yang dikirim ke pembeli, dipindai petugas di pintu masuk, dan ditandai terpakai supaya tidak bisa dipakai dua kali.',
                        'miniature' => 'checkin',
                        'icon' => 'ti-qrcode',
                        'route' => 'event.checkin.scan',
                        'gates' => [],
                    ],
                ],
            ],
            [
                'key' => 'konten',
                'title' => 'Konten & Komunikasi',
                'description' => 'Yang dibaca peserta sebelum dan sesudah acara, plus catatan siapa mengubah apa di panel.',
                'items' => [
                    [
                        'title' => 'Juknis & Dokumen Unduhan',
                        'description' => 'Unggah juknis dan dokumen pendukung, lalu tampilkan tautan unduhannya di halaman publik event.',
                        'miniature' => 'dokumen',
                        'icon' => 'ti-file-download',
                        'route' => 'eventner.documents.index',
                        'gates' => [],
                    ],
                    [
                        'title' => 'Galeri Foto Event',
                        'description' => 'Kumpulkan foto acara dalam galeri yang tampil di halaman publik — dokumentasi yang tidak hilang begitu acara selesai.',
                        'miniature' => 'galeri',
                        'icon' => 'ti-photo',
                        'route' => 'eventner.gallery.index',
                        'gates' => [],
                    ],
                    [
                        'title' => 'FAQ Event',
                        'description' => 'Pertanyaan yang paling sering ditanyakan, dijawab sekali dan tampil di halaman event — menghemat waktu panitia menjawab satu per satu.',
                        'miniature' => 'faq',
                        'icon' => 'ti-info-circle',
                        'route' => 'eventner.faq.index',
                        'gates' => [],
                    ],
                    [
                        'title' => 'Kirim Notifikasi Peserta',
                        'description' => 'Kirim pengumuman ke seluruh peserta atau hanya ke satu kelompok — misalnya ke para juara saja.',
                        'miniature' => 'notifikasi',
                        'icon' => 'ti-bell',
                        'route' => 'eventner.notification.index',
                        'gates' => [],
                    ],
                    [
                        'title' => 'Activity Log',
                        'description' => 'Catatan siapa mengubah apa dan kapan. Berguna saat event dikelola lebih dari satu orang.',
                        'miniature' => 'log',
                        'icon' => 'ti-history',
                        'route' => 'eventner.activity-log.index',
                        'gates' => [],
                    ],
                ],
            ],
            [
                'key' => 'keuangan',
                'title' => 'Sponsor, Tenant & Keuangan',
                'description' => 'Menutup acara: mencatat pemasukan, menampilkan sponsor, dan mengurus administrasi dokumen.',
                'items' => [
                    [
                        'title' => 'Sponsor & Partner',
                        'description' => 'Kelola logo dan tautan sponsor, lalu tampilkan di halaman event maupun di overlay siaran langsung.',
                        'miniature' => 'icon11.png',
                        'icon' => 'ti-affiliate',
                        'route' => 'eventner.sponsors.index',
                        'gates' => ['sponsors'],
                    ],
                    [
                        'title' => 'Tenant / Stand & Peta Venue',
                        'description' => 'Data stan tenant beserta nomor dan lokasinya, tampil di peta venue supaya pengunjung tidak tersesat.',
                        'miniature' => 'tenant',
                        'icon' => 'ti-building-store',
                        'route' => 'eventner.tenants.index',
                        'gates' => ['tenants'],
                    ],
                    [
                        'title' => 'Dashboard Keuangan & PDF',
                        'description' => 'Ringkasan pemasukan dari pendaftaran, tiket, dan voting dalam satu layar, bisa diunduh sebagai laporan PDF.',
                        'miniature' => 'keuangan',
                        'icon' => 'ti-wallet',
                        'route' => 'eventner.finance.index',
                        'gates' => [],
                    ],
                    [
                        'title' => 'Rekening Bank & TTD/Stempel Dokumen',
                        'description' => 'Simpan rekening penerimaan dan cap tanda tangan penyelenggara sekali, lalu dipakai otomatis di invoice, kwitansi, dan sertifikat.',
                        'miniature' => 'ttd',
                        'icon' => 'ti-building-bank',
                        'route' => 'eventner.bank-accounts.index',
                        'gates' => [],
                    ],
                    [
                        'title' => 'Upgrade Paket & Feature Gating',
                        'description' => 'Fitur dasar terbuka di paket gratis. Fitur lanjutan dibuka sekali bayar per event — tanpa langganan bulanan, dan terkunci rapi selama belum dibayar.',
                        'miniature' => 'paket',
                        'icon' => 'ti-rocket',
                        'route' => 'eventner.billing.upgrade',
                        'gates' => [],
                    ],
                ],
            ],
        ];
    }

    /**
     * Daftar rata seluruh item — dipakai tes (hitungan, pemetaan gate,
     * penelusuran route) supaya tidak perlu menulis perulangan bersarang.
     *
     * @return array<int, array>
     */
    public static function all(): array
    {
        $items = [];
        foreach (self::groups() as $group) {
            foreach ($group['items'] as $item) {
                $items[] = $item;
            }
        }

        return $items;
    }

    public static function total(): int
    {
        return count(self::all());
    }
}
