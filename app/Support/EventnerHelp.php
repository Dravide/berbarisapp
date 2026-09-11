<?php

namespace App\Support;

/**
 * Teks tutorial per halaman dashboard Eventner.
 *
 * Kunci = pola routeIs(). Halaman tanpa entri otomatis tanpa tombol bantuan.
 */
class EventnerHelp
{
    /**
     * @return array<string, array{title: string, intro: string, steps: array<int, string>, tips?: array<int, string>}>
     */
    public static function all(): array
    {
        return [
            'eventner.dashboard' => [
                'title' => 'Dashboard',
                'intro' => 'Ringkasan kondisi acara Anda: kesiapan, pendapatan, dan hal yang perlu ditindak.',
                'steps' => [
                    'Cek kartu "Kesiapan Event Anda" — persentase menunjukkan kelengkapan data acara.',
                    'Buka "Perlu Perhatian" untuk melihat pendaftar yang masih perlu diverifikasi atau berkas yang menunggu.',
                    'Gunakan Aksi Cepat untuk lompat langsung ke Verifikasi, PDF Keuangan, Layar Undian, Check-in Tiket, atau QR Peserta.',
                    'Lihat kartu Pendapatan untuk rincian vote, tiket, dan fee.',
                    'Jika masih trial atau akun belum disetujui, tombol Upgrade/Bayar Sekarang ada di banner atas.',
                ],
                'tips' => [
                    'Fitur yang tampil bertanda gembok berarti belum termasuk paket Anda — klik "Buka Semua Fitur" untuk upgrade.',
                ],
            ],

            'eventner.competition-categories.index' => [
                'title' => 'Kategori Lomba',
                'intro' => 'Menyusun struktur jenis dan tingkat lomba yang dipertandingkan.',
                'steps' => [
                    'Tambah jenis lomba sebagai parent, lalu tambahkan tingkat lomba di bawahnya.',
                    'Isi jumlah anggota minimal/maksimal, biaya pendaftaran, dan batas jumlah kontingen bila perlu.',
                    'Ubah urutan tampil dengan drag & drop pada daftar.',
                    'Tentukan tanggal pelaksanaan per tingkat bila jadwalnya berbeda.',
                ],
                'tips' => [
                    'Kategori yang tampil di halaman publik pendaftaran adalah kategori bertanda aktif.',
                ],
            ],

            'eventner.participants.index' => [
                'title' => 'Daftar Peserta',
                'intro' => 'Rekapitulasi kontingen/sekolah yang mendaftar beserta anggotanya.',
                'steps' => [
                    'Filter berdasarkan kategori lomba dan status (Draft, Menunggu Verifikasi, Finalized, Ditolak, Dibatalkan).',
                    'Klik Verifikasi pada pendaftar untuk memeriksa dan menyetujui datanya.',
                    'Gunakan Tambah Pendaftar untuk memasukkan kontingen secara manual.',
                    'Unduh Formulir PDF atau Invoice dari kolom Aksi bila diperlukan.',
                    'Cetak QR kontingen untuk kebutuhan check-in.',
                    'Salin Magic Link atau Preview Portal untuk membagikan tautan pendaftaran ke sekolah.',
                ],
                'tips' => [
                    'Tambahkan data kontingen terlebih dahulu sebelum mengatur urutan undian.',
                ],
            ],

            'eventner.judges.index' => [
                'title' => 'Daftar Juri',
                'intro' => 'Mengelola profil juri dan menugaskannya ke tingkat lomba.',
                'steps' => [
                    'Klik Tambah Juri, isi nama lengkap, foto profil, dan nomor telepon (opsional).',
                    'Centang tingkat lomba pada bagian Tugaskan Kategori.',
                    'Unduh Format Penilaian Juri (PDF) per juri dari kolom aksi.',
                ],
                'tips' => [
                    'Juri tanpa tugas kategori tidak akan muncul di pilihan Input Nilai.',
                ],
            ],

            'eventner.drawing.index' => [
                'title' => 'Drawing / Undian',
                'intro' => 'Menentukan urutan tampil peserta lewat pengundian.',
                'steps' => [
                    'Isi Kode Proteksi Pengundian bila layar spin ingin dilindungi kode (kosongkan bila tidak perlu), lalu Simpan.',
                    'Buka Layar Spin untuk menjalankan pengundian di depan peserta.',
                    'Gunakan Input Manual bila urutan ingin ditetapkan langsung tanpa spin.',
                    'Pantau hasil pada tabel Hasil Undian; tombol Reset mengosongkan hasil satu kategori.',
                    'Bagikan Layar Pengundian (Spin) atau Lihat Hasil Undian (Publik) dari Tautan Cepat.',
                ],
                'tips' => [
                    'Semua kontingen perlu sudah terdaftar sebelum undian dijalankan.',
                ],
            ],

            'eventner.rundown.index' => [
                'title' => 'Rundown Acara',
                'intro' => 'Menyusun susunan acara beserta jam dan durasinya.',
                'steps' => [
                    'Tambahkan item manual dengan mengisi Judul, Jam Mulai, Jam Selesai, dan Keterangan.',
                    'Gunakan Generate dari Undian untuk membuat urutan acara otomatis berdasarkan hasil undian.',
                    'Unduh PDF atau tampilkan rundown di halaman landing lewat tombol Lihat di Landing.',
                ],
            ],

            'eventner.format-nilai.builder' => [
                'title' => 'Format Penilaian',
                'intro' => 'Menyusun rubrik penilaian: kategori, kriteria, pilihan skor, bobot, dan pengurangan nilai.',
                'steps' => [
                    'Pilih tingkat lomba yang akan diatur formatnya pada dropdown Format untuk.',
                    'Buat Kategori Utama lewat panel kanan, lalu tambahkan kriteria di dalamnya.',
                    'Atur nama kriteria, pilihan skor, bobot, dan label dalam satu modal.',
                    'Tambahkan Kelompok Pengurangan beserta opsi nilai negatif bila ada penalti.',
                    'Gunakan Salin Ke untuk menyalin format ke tingkat lomba lain.',
                    'Cek hasilnya lewat Pratinjau Juri sebelum dipakai.',
                ],
                'tips' => [
                    'Format wajib ada sebelum juri bisa mengisi nilai di halaman Input Nilai.',
                ],
            ],

            'eventner.format-nilai.download' => [
                'title' => 'Unduh Format Penilaian',
                'intro' => 'Mengunduh lembar penilaian dalam bentuk PDF untuk dicetak.',
                'steps' => [
                    'Pilih Juri, Tingkat Lomba, dan Jenis Lembar (format kosong, per peserta, atau daftar peserta).',
                    'Bila memilih per peserta, pilih peserta yang diinginkan.',
                    'Klik Unduh PDF. Gunakan Reset untuk mengosongkan filter.',
                ],
                'tips' => [
                    'Gunakan tombol Unduh & Import di halaman Format Penilaian untuk mengambil template Excel.',
                ],
            ],

            'eventner.scoring.index' => [
                'title' => 'Input Nilai',
                'intro' => 'Memasukkan nilai juri per peserta.',
                'steps' => [
                    'Pilih Kategori Lomba, lalu pilih peserta dari daftar.',
                    'Pilih Juri yang nilainya sedang diinput.',
                    'Isi skor setiap kriteria; subtotal dan nilai akhir terhitung otomatis.',
                    'Isi Pengurangan Nilai bila ada penalti, lalu Simpan Pengurangan.',
                    'Klik Simpan Penilaian setelah semua nilai benar, atau Finalisasi Semua untuk mengunci seluruh penilaian.',
                ],
                'tips' => [
                    'Mode Simulasi berguna untuk latihan — nilainya tidak tersimpan.',
                    'Setelah difinalisasi, penilaian juri tersebut terkunci dan tidak bisa diubah.',
                ],
            ],

            'eventner.score-recap.index' => [
                'title' => 'Rekap Nilai',
                'intro' => 'Rekapitulasi nilai akhir per kategori lomba.',
                'steps' => [
                    'Pilih kategori lomba untuk melihat klasemen (rank, kontingen, pelatih, total, pengurangan, nilai akhir).',
                    'Pantau ringkasan Nilai Tertinggi, Nilai Terendah, Rata-rata, dan Total Peserta.',
                    'Unduh data lewat Download CSV atau cetak PDF.',
                ],
                'tips' => [
                    'Nilai baru muncul setelah penilaian disimpan/difinalisasi di halaman Input Nilai.',
                ],
            ],

            'eventner.champion-categories.index' => [
                'title' => 'Kategori Juara',
                'intro' => 'Menentukan gelar juara dan peringkat yang diperlombakan.',
                'steps' => [
                    'Klik Tambah Kategori, isi Nama Kategori Juara, Deskripsi, dan Jumlah Juara (Top N).',
                    'Centang rubrik penilaian yang masuk perhitungan juara.',
                    'Tentukan Tie Break, yaitu rubrik penentu bila skor akhirnya sama.',
                    'Aktifkan Tampilkan di Laman Hasil bila juara ingin tampil di halaman publik.',
                    'Atur Nama Gelar serta Rank Awal dan Akhir untuk tiap peringkat.',
                    'Klik Unduh Semua PDF untuk merekap seluruh kategori juara pada tingkat lomba yang sedang dipilih.',
                    'Klik PDF pada satu baris kategori juara bila hanya ingin mengunduh kategori itu saja.',
                ],
                'tips' => [
                    'Tingkat lomba penentu rekap adalah yang terpilih di dropdown di atas halaman.',
                ],
            ],

            'eventner.certificate.index' => [
                'title' => 'Sertifikat',
                'intro' => 'Mengelola template sertifikat juara dan mengunduh sertifikatnya.',
                'steps' => [
                    'Klik Template Baru, isi Nama Template dan ukuran kertas (pilih preset atau isi lebar/tinggi mm).',
                    'Upload gambar template, lalu Simpan.',
                    'Klik Atur Field untuk menempatkan teks dinamis di atas template.',
                    'Gunakan Preview untuk memeriksa hasilnya.',
                    'Pada bagian Download Sertifikat PDF, pilih Kategori Juara, Kategori Lomba, Sekolah, dan Mode Sertifikat (per siswa atau per pasukan), lalu klik Download PDF.',
                ],
                'tips' => [
                    'Mode Per Pasukan menggabungkan semua nama pasukan dalam satu sertifikat.',
                ],
            ],

            'eventner.certificate.editor' => [
                'title' => 'Editor Template Sertifikat',
                'intro' => 'Menempatkan field teks dinamis di atas gambar template.',
                'steps' => [
                    'Klik Tambah Field, lalu pilih field data yang ingin ditampilkan (nama, sekolah, kategori juara, dan lain-lain).',
                    'Geser field untuk mengatur posisi di atas template.',
                    'Atur Warna, Text Align, Font Weight, dan Max Width pada panel Properti Field.',
                    'Gunakan Hapus Field untuk membuang field yang tidak dipakai.',
                    'Klik Kembali untuk menyimpan dan kembali ke daftar template.',
                ],
            ],

            'eventner.vote-settings.index' => [
                'title' => 'Pengaturan Vote',
                'intro' => 'Mengatur aktivasi, jadwal, dan harga voting online.',
                'steps' => [
                    'Aktifkan Vote Online bila voting ingin dibuka.',
                    'Isi Jadwal Voting (opsional) — kosongkan bila ingin langsung aktif atau tanpa batas akhir.',
                    'Isi Harga per 1 Vote (Rp).',
                    'Klik Simpan Pengaturan.',
                ],
                'tips' => [
                    'Voting otomatis berhenti setelah waktu berakhir meski statusnya aktif.',
                ],
            ],

            'eventner.vote-booster.index' => [
                'title' => 'Vote Booster',
                'intro' => 'Menjadwalkan pengali vote pada rentang waktu tertentu.',
                'steps' => [
                    'Isi Mulai, Berakhir, dan Multiplier (x lipat) pada form Tambah Jadwal Booster.',
                    'Klik Tambah Booster.',
                    'Pantau status jadwal (Terjadwal, Berjalan, Nonaktif) pada Daftar Vote Booster.',
                ],
                'tips' => [
                    'Harga dasar per transaksi tetap; yang berlipat hanya jumlah vote yang diterima.',
                ],
            ],

            'eventner.vote-results.index' => [
                'title' => 'Hasil Voting',
                'intro' => 'Klasemen perolehan vote peserta beserta ringkasan omzet.',
                'steps' => [
                    'Lihat kartu ringkasan: transaksi terverifikasi, pembayaran PAID, total vote, dan total pendapatan.',
                    'Pilih kategori lomba untuk melihat klasemen (rank, perolehan vote, estimasi pendapatan).',
                    'Klik Detail Voter pada peserta untuk melihat daftar voternya.',
                    'Unduh Rekap PDF bila diperlukan.',
                ],
            ],

            'eventner.vote-results.show' => [
                'title' => 'Detail Voter',
                'intro' => 'Rincian voter yang membayar vote untuk satu peserta.',
                'steps' => [
                    'Lihat ringkasan Total Transaksi (PAID), Total Vote, dan Total Pendapatan peserta ini.',
                    'Telusuri tabel Daftar Voter: jumlah vote, nominal bayar, waktu transaksi, dan statusnya.',
                    'Klik Unduh PDF untuk menyimpan rincian ini.',
                ],
            ],

            'eventner.vote-transactions.index' => [
                'title' => 'Transaksi Voting',
                'intro' => 'Riwayat transaksi pembelian vote.',
                'steps' => [
                    'Filter berdasarkan status (PAID, PENDING, EXPIRED, FAILED), kontingen, dan rentang tanggal.',
                    'Klik Konfirmasi Bayar pada transaksi PENDING yang sudah dibayar untuk memprosesnya.',
                    'Gunakan Sinkron Status PENDING untuk memeriksa ulang transaksi yang belum selesai.',
                    'Export CSV untuk mengunduh seluruh data transaksi.',
                ],
                'tips' => [
                    'Transaksi EXPIRED berasal dari QRIS yang kedaluwarsa dan tidak dihitung sebagai pendapatan.',
                ],
            ],

            'eventner.vote-comments.index' => [
                'title' => 'Komentar Voting',
                'intro' => 'Pesan dan dukungan dari voter, dipakai sebagai bahan penilaian tambahan.',
                'steps' => [
                    'Lihat ringkasan Total Komentar, Vote dari Komentar, dan Kontingen Terkomentari.',
                    'Filter berdasarkan kontingen, Tier (Hot, Elite, MVP), dan rentang tanggal pembayaran.',
                    'Export CSV untuk mengunduh seluruh komentar.',
                ],
            ],

            'eventner.tickets.index' => [
                'title' => 'Manajemen Tiket',
                'intro' => 'Memantau penjualan tiket dan melakukan check-in pengunjung.',
                'steps' => [
                    'Lihat ringkasan Total Tiket, Tiket Terjual (PAID), dan pendapatan.',
                    'Filter berdasarkan status (PAID, PENDING, CHECKED IN, EXPIRED) dan rentang tanggal.',
                    'Klik Check-in pada tiket yang valid, lalu konfirmasi untuk memberi gelang.',
                    'Gunakan Sinkron Status PENDING untuk memeriksa transaksi yang belum selesai.',
                    'Export CSV untuk mengunduh data tiket.',
                ],
            ],

            'eventner.tickets.settings' => [
                'title' => 'Pengaturan Tiket',
                'intro' => 'Mengatur penjualan tiket online dan akses check-in panitia.',
                'steps' => [
                    'Aktifkan Penjualan Tiket Online, lalu isi jadwal penjualan (opsional).',
                    'Isi Harga Per Tiket, Maksimal Tiket Per Transaksi, dan Keterangan Tiket.',
                    'Klik Simpan Pengaturan.',
                    'Di bagian Akses Check-in Panitia, klik Generate Akses Check-in untuk membuat tautan scan bagi petugas.',
                    'Salin URL scan, atau gunakan Rotate Token / Cabut Akses bila tautan perlu diganti atau dihentikan.',
                ],
            ],

            'eventner.finance.index' => [
                'title' => 'Dashboard Keuangan',
                'intro' => 'Rekapitulasi pendapatan dari pendaftaran, voting, dan tiket.',
                'steps' => [
                    'Lihat ringkasan Total Pendapatan beserta rinciannya (biaya pendaftaran, voting, tiket) dan grafik 30 hari terakhir.',
                    'Proses antrean Verifikasi Pembayaran: klik Review untuk memeriksa bukti bayar pendaftar.',
                    'Telusuri Pendapatan per Kategori Lomba untuk melihat jumlah daftar, lunas, menunggu, dan potensi pendapatan.',
                    'Filter tabel detail berdasarkan kategori lomba dan status pembayaran.',
                    'Unduh PDF laporan bila diperlukan.',
                ],
            ],

            'eventner.activity-log.index' => [
                'title' => 'Activity Log',
                'intro' => 'Catatan perubahan data pada event Anda.',
                'steps' => [
                    'Filter berdasarkan tipe aksi (Dibuat, Diperbarui, Dihapus) dan waktu.',
                    'Telusuri kolom Deskripsi, Oleh, dan Event untuk mengetahui siapa mengubah apa.',
                ],
            ],

            'eventner.profile.index' => [
                'title' => 'Profil Event',
                'intro' => 'Data identitas acara yang tampil di halaman publik.',
                'steps' => [
                    'Unggah Logo Event, Poster Event, dan Header Banner.',
                    'Atur Status Pendaftaran (Open Registration, Booking Only, Tutup) dan Deadline Pendaftaran.',
                    'Isi informasi acara: nama, penyelenggara, deskripsi, lokasi, venue, dan koordinat.',
                    'Atur jadwal penting: tanggal pelaksanaan, akhir pendaftaran, dan technical meeting.',
                    'Tentukan Berkas Pendaftaran Wajib dan tautan tambahan.',
                ],
                'tips' => [
                    'Status pendaftaran otomatis mengikuti tanggal yang diisi. Kolom ketua pelaksana pada cetak dokumen memakai QR event.',
                ],
            ],

            'eventner.bank-accounts.index' => [
                'title' => 'Rekening Bank',
                'intro' => 'Daftar rekening tujuan pembayaran yang ditampilkan ke peserta.',
                'steps' => [
                    'Isi Nama Bank, Nomor Rekening, dan Atas Nama, lalu simpan.',
                    'Centang Aktif agar rekening tampil ke peserta.',
                    'Gunakan kolom Aksi untuk mengubah atau menghapus rekening.',
                ],
            ],

            'eventner.signatures.index' => [
                'title' => 'TTD & Stempel',
                'intro' => 'Mengunggah tanda tangan dan stempel untuk dokumen acara.',
                'steps' => [
                    'Isi Nama, unggah File PNG tanda tangan, lalu klik Unggah.',
                    'Klik Pakai untuk memilih tanda tangan yang aktif dipakai di dokumen.',
                ],
                'tips' => [
                    'Kolom ketua pelaksana otomatis memakai QR event, jadi cukup siapkan tanda tangan dan stempel.',
                ],
            ],

            'eventner.event-qr.index' => [
                'title' => 'QR Link Event',
                'intro' => 'QR yang mengarah ke halaman publik event Anda.',
                'steps' => [
                    'QR tampil otomatis beserta URL tujuannya.',
                    'Klik Download untuk menyimpan gambar, atau Cetak untuk langsung mencetaknya.',
                    'Salin Link untuk membagikan URL event.',
                ],
            ],

            'eventner.livestream.index' => [
                'title' => 'Livestream Overlay',
                'intro' => 'Overlay siaran langsung untuk dipakai di OBS.',
                'steps' => [
                    'Pilih Mode Preset (Full, Chroma + Side, Vote, Leaderboard, Komentar, Kegiatan, atau Greenscreen).',
                    'Klik preset untuk membuka overlay publiknya di tab baru.',
                    'Untuk tampilan sendiri, aktifkan komponen pada Kustom Overlay: Header, Vote Leaderboard, Data Kegiatan, dan Footer.',
                    'Isi Teks Berjalan (Marquee) bila ingin ada teks berjalan di bagian bawah.',
                    'Klik Simpan Pengaturan, lalu tempel URL Overlay ke Browser Source OBS (1920×1080).',
                ],
            ],

            'eventner.faq.index' => [
                'title' => 'FAQ (Tanya Jawab)',
                'intro' => 'Pertanyaan umum yang tampil di halaman publik event.',
                'steps' => [
                    'Isi Pertanyaan dan Jawaban pada form di sebelah kiri, lalu klik Tambah.',
                    'Klik ikon pensil untuk mengedit, ikon tempat sampah untuk menghapus.',
                ],
            ],

            'eventner.gallery.index' => [
                'title' => 'Galeri Foto',
                'intro' => 'Foto-foto acara yang tampil di halaman publik.',
                'steps' => [
                    'Pilih file foto, isi keterangan (opsional), lalu klik Upload.',
                    'Klik ikon tempat sampah pada foto untuk menghapusnya.',
                ],
                'tips' => [
                    'Gunakan foto landscape agar tampil rapi di grid galeri.',
                ],
            ],

            'eventner.sponsors.index' => [
                'title' => 'Sponsor',
                'intro' => 'Daftar sponsor dan partner acara.',
                'steps' => [
                    'Unggah logo, isi nama sponsor, lalu pilih Tipe (Sponsor Utama, Gold, Silver, Bronze, Media Partner, dan lainnya).',
                    'Atur Urutan Tampil — angka lebih kecil tampil lebih dulu.',
                    'Tentukan Status Aktif/Nonaktif dan simpan.',
                ],
            ],

            'eventner.tenants.index' => [
                'title' => 'Tenant',
                'intro' => 'Daftar tenant atau stan yang ikut meramaikan acara.',
                'steps' => [
                    'Unggah logo, isi Nama Tenant, Tipe, dan Deskripsi (opsional).',
                    'Atur Urutan Tampil dan Status Aktif/Nonaktif, lalu simpan.',
                ],
            ],

            'eventner.notification.index' => [
                'title' => 'Notifikasi',
                'intro' => 'Mengirim pesan ke peserta acara.',
                'steps' => [
                    'Isi Judul dan Isi Pesan.',
                    'Pilih Target: Semua Peserta atau peserta tertentu.',
                    'Klik Kirim dan tunggu proses selesai.',
                ],
            ],
        ];
    }

    /**
     * Teks bantuan untuk halaman yang sedang dibuka, null bila tidak ada.
     *
     * @return array{title: string, intro: string, steps: array<int, string>, tips?: array<int, string>}|null
     */
    public static function current(): ?array
    {
        $route = request()->route();

        if (! $route) {
            return null;
        }

        $name = $route->getName();

        return $name ? (static::all()[$name] ?? null) : null;
    }
}
