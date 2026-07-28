<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class LandingPageSeeder extends Seeder
{
    public function run(): void
    {
        // Site identity
        Setting::set('site_title', 'BARIS APP');
        Setting::set('meta_description', 'Platform manajemen event dan kompetisi terpadu untuk penyelenggara dan peserta. Kelola pendaftaran, penilaian, voting, tiket, dan sertifikat secara digital.');
        Setting::set('meta_keywords', 'event, kompetisi, lomba, pendaftaran, voting, tiket, sertifikat, penilaian, undian, manajemen event');
        Setting::set('site_primary_color', '#0062ff');
        Setting::set('site_accent_color', '#a3e635');
        Setting::set('site_font_sans', 'Inter');
        Setting::set('site_font_display', 'Plus Jakarta Sans');

        // Sections order & visibility
        Setting::set('landing_sections_order', json_encode([
            'hero', 'features', 'about', 'statistics', 'eventners', 'ticket', 'vote',
            'schedule', 'testimonials', 'faq', 'gallery', 'cta', 'contact',
        ]));

        Setting::set('landing_sections_active', json_encode([
            'hero' => true,
            'features' => true,
            'about' => true,
            'statistics' => true,
            'eventners' => true,
            'ticket' => true,
            'vote' => true,
            'schedule' => true,
            'testimonials' => true,
            'faq' => true,
            'gallery' => true,
            'cta' => true,
            'contact' => true,
        ]));

        // Hero
        Setting::set('landing_hero', json_encode([
            'heading' => 'Kelola Event & Kompetisi dengan Mudah',
            'subheading' => 'Platform all-in-one untuk penyelenggara event. Pendaftaran digital, penilaian real-time, voting online, e-tiket, sertifikat, live scoreboard — semua dalam satu dashboard.',
            'cta_text' => 'Mulai Sekarang',
            'cta_url' => '/register/eventner',
            'video_url' => '',
            'background_image' => '',
        ]));

        // Features — 9 fitur
        Setting::set('landing_features', json_encode([
            'title' => 'Fitur Lengkap untuk Event Sukses',
            'items' => [
                [
                    'icon' => 'icon3.png',
                    'title' => 'Manajemen Pendaftaran',
                    'description' => 'Kelola pendaftaran peserta secara digital. Verifikasi berkas otomatis, kuota per kategori, dan tracking status real-time.',
                ],
                [
                    'icon' => 'icon4.png',
                    'title' => 'Penilaian Juri Digital',
                    'description' => 'Sistem penilaian dengan format kustom, bobot kriteria fleksibel, input nilai langsung oleh juri, dan rekap otomatis.',
                ],
                [
                    'icon' => 'icon5.png',
                    'title' => 'Voting Online',
                    'description' => 'Voting publik terintegrasi pembayaran digital. Dukung peserta favorit dengan aman, transparan, dan real-time.',
                ],
                [
                    'icon' => 'icon6.png',
                    'title' => 'E-Tiket & Check-in QR',
                    'description' => 'Jual tiket event online. QR code check-in di pintu masuk, laporan penjualan lengkap, dan pengaturan multi-tier.',
                ],
                [
                    'icon' => 'icon7.png',
                    'title' => 'Live Scoreboard',
                    'description' => 'Papan skor real-time untuk ditampilkan ke layar proyektor. Penonton bisa langsung pantau hasil penilaian.',
                ],
                [
                    'icon' => 'icon8.png',
                    'title' => 'Drawing & Undian',
                    'description' => 'Sistem undian digital untuk menentukan urutan tampil. Animasi spin interaktif, hasil langsung tampil di layar.',
                ],
                [
                    'icon' => 'icon9.png',
                    'title' => 'Sertifikat Digital',
                    'description' => 'Buat dan cetak sertifikat otomatis untuk juara dan peserta. Template kustom dengan data dinamis dari hasil penilaian.',
                ],
                [
                    'icon' => 'icon10.png',
                    'title' => 'Livestream Overlay',
                    'description' => 'Overlay live streaming untuk tampil profesional di YouTube/Facebook. Tampilkan skor, nama peserta, dan sponsor real-time.',
                ],
                [
                    'icon' => 'icon11.png',
                    'title' => 'Sponsor & Tenant',
                    'description' => 'Kelola sponsor event dan tenant stand. Tampilkan logo sponsor di overlay livestream dan halaman publik.',
                ],
            ],
        ]));

        // About
        Setting::set('landing_about', json_encode([
            'heading' => 'Platform Event & Kompetisi Terpadu',
            'description' => 'BARIS APP hadir untuk menyederhanakan manajemen event dan kompetisi dari awal hingga akhir. Dari pendaftaran peserta, penilaian juri, voting publik, pencetakan sertifikat, hingga overlay livestream — semua terintegrasi dalam satu platform yang mudah digunakan.',
            'image' => '',
            'video' => 'https://videos.pexels.com/video-files/3209259/3209259-hd_1920_1080_25fps.mp4',
            'points' => [
                ['title' => '100+ Event Sukses', 'text' => 'Telah dipercaya oleh ratusan penyelenggara untuk event skala lokal dan nasional.'],
                ['title' => 'Keamanan Terjamin', 'text' => 'Data peserta, nilai, dan transaksi keuangan dienkripsi dengan standar keamanan terkini.'],
                ['title' => 'Support Responsif', 'text' => 'Tim support siap membantu 7 hari seminggu melalui chat, email, atau telepon.'],
                ['title' => 'Dapat Dikustomisasi', 'text' => 'Sesuaikan tampilan, format, dan alur kerja sesuai kebutuhan event Anda.'],
            ],
        ]));

        // Statistics
        Setting::set('landing_statistics', json_encode([
            'items' => [
                ['value' => '500', 'label' => 'Event Terkelola', 'suffix' => '+'],
                ['value' => '50', 'label' => 'Penyelenggara Aktif', 'suffix' => 'K'],
                ['value' => '1', 'label' => 'Peserta Terdaftar', 'suffix' => 'jt+'],
                ['value' => '99.9', 'label' => 'Uptime Server', 'suffix' => '%'],
            ],
        ]));

        // Schedule
        Setting::set('landing_schedule', json_encode([
            'title' => 'Langkah Memulai Event Anda',
            'items' => [
                [
                    'date' => '1',
                    'time' => '',
                    'title' => 'Daftar Akun Eventner',
                    'description' => 'Buat akun Eventner gratis. Isi data event, pilih kategori lomba, dan atur jadwal pelaksanaan.',
                    'location' => '',
                ],
                [
                    'date' => '2',
                    'time' => '',
                    'title' => 'Buka Pendaftaran Peserta',
                    'description' => 'Sebarkan link pendaftaran ke calon peserta. Kelola berkas, verifikasi administrasi, dan pantau kuota.',
                    'location' => '',
                ],
                [
                    'date' => '3',
                    'time' => '',
                    'title' => 'Hari-H — Penilaian & Voting',
                    'description' => 'Juri menginput nilai secara digital. Publik mem-voting peserta favorit. Semua berjalan paralel dan real-time.',
                    'location' => '',
                ],
                [
                    'date' => '4',
                    'time' => '',
                    'title' => 'Grand Final & Sertifikat',
                    'description' => 'Skor otomatis direkap. Pemenang langsung terlihat di live scoreboard. Undi turnamen, cetak sertifikat, dan umumkan juara.',
                    'location' => '',
                ],
            ],
        ]));

        // Testimonials
        Setting::set('landing_testimonials', json_encode([
            'title' => 'Apa Kata Mereka?',
            'items' => [
                [
                    'name' => 'Budi Santoso',
                    'role' => 'Ketua Panitia FLS2N 2026',
                    'text' => 'BARIS APP benar-benar mengubah cara kami mengelola lomba. Penilaian yang dulu manual sekarang jadi otomatis. Skor langsung tampil ke layar!',
                    'rating' => 5,
                    'avatar' => '',
                ],
                [
                    'name' => 'Rina Anggraini',
                    'role' => 'Event Organizer',
                    'text' => 'Fitur voting-nya transparan dan mudah. Pendaftaran peserta lancar. Support tim-nya luar biasa responsif!',
                    'rating' => 5,
                    'avatar' => '',
                ],
                [
                    'name' => 'Ahmad Fauzi',
                    'role' => 'Kepala Sekolah SMAN 1',
                    'text' => 'E-tiket dan check-in QR sangat membantu saat hari H. Tidak ada antrian panjang, semua terdata rapi.',
                    'rating' => 5,
                    'avatar' => '',
                ],
                [
                    'name' => 'Dewi Lestari',
                    'role' => 'Komite Olimpiade Sains',
                    'text' => 'Format penilaian kustom dan multi-juri bikin hasil lebih objektif. Sertifikat langsung jadi tanpa ribet!',
                    'rating' => 4,
                    'avatar' => '',
                ],
                [
                    'name' => 'Rudi Hermawan',
                    'role' => 'Koor Umum Porseni 2026',
                    'text' => 'Drawing undian digital-nya keren banget! Peserta dan penonton suka. Sekarang undian tidak membosankan lagi.',
                    'rating' => 5,
                    'avatar' => '',
                ],
                [
                    'name' => 'Sari Nirmala',
                    'role' => 'Koordinator Livestream',
                    'text' => 'Overlay livestream bikin siaran kami terlihat profesional. Sponsor senang karena logonya tampil jelas di layar.',
                    'rating' => 5,
                    'avatar' => '',
                ],
            ],
        ]));

        // FAQ
        Setting::set('landing_faq', json_encode([
            'title' => 'Pertanyaan yang Sering Diajukan',
            'items' => [
                [
                    'question' => 'Apa itu BARIS APP?',
                    'answer' => 'BARIS APP adalah platform digital untuk manajemen event dan kompetisi. Kami menyediakan tools lengkap: pendaftaran online, penilaian digital, voting publik, e-tiket, live scoreboard, drawing undian, sertifikat, dan livestream overlay — semua terintegrasi.',
                ],
                [
                    'question' => 'Berapa biaya untuk menggunakan BARIS APP?',
                    'answer' => 'Kami menyediakan paket gratis (trial 14 hari) dengan fitur dasar, dan paket berbayar dengan akses penuh ke semua fitur. Anda bisa memilih paket yang sesuai dengan kebutuhan event Anda.',
                ],
                [
                    'question' => 'Apakah BARIS APP bisa digunakan untuk event kecil?',
                    'answer' => 'Tentu! BARIS APP dirancang fleksibel untuk event skala kecil hingga besar. Dari lomba antar kelas, olimpiade sekolah, hingga festival dan kompetisi nasional.',
                ],
                [
                    'question' => 'Bagaimana cara pendaftaran event?',
                    'answer' => 'Cukup daftarkan akun Eventner di website kami. Setelah akun aktif, Anda bisa langsung mengatur profil event, membuat kategori lomba, dan membuka pendaftaran peserta.',
                ],
                [
                    'question' => 'Apakah data peserta aman?',
                    'answer' => 'Ya. Kami menggunakan enkripsi SSL, penyimpanan data terpusat di server aman, dan backup berkala. Data Anda hanya bisa diakses oleh Anda dan tim yang Anda beri izin.',
                ],
                [
                    'question' => 'Bagaimana cara membuat sertifikat?',
                    'answer' => 'Buka menu Sertifikat di dashboard, buat template dengan drag-and-drop editor, lalu klik Generate. Sertifikat otomatis terisi data juara dari hasil penilaian. Bisa langsung download PDF.',
                ],
                [
                    'question' => 'Apakah ada fitur overlay untuk live streaming?',
                    'answer' => 'Ya, BARIS APP menyediakan fitur Livestream Overlay. Anda bisa menampilkan skor, nama peserta, logo sponsor, dan info event langsung di siaran YouTube atau Facebook.',
                ],
            ],
        ]));

        // Gallery
        Setting::set('landing_gallery', json_encode([
            'title' => 'Galeri BARIS APP',
            'items' => [],
        ]));

        // CTA
        Setting::set('landing_cta', json_encode([
            'heading' => 'Siap Mengelola Event Lebih Efisien?',
            'description' => 'Daftar sekarang dan dapatkan akses penuh gratis selama 14 hari. Tidak perlu kartu kredit.',
            'button_text' => 'Daftar Eventner Sekarang',
            'button_url' => '/register/eventner',
            'image' => '',
        ]));

        // Ticket section settings
        Setting::set('landing_ticket', json_encode([
            'title' => 'E-Tiket Digital',
            'subtitle' => 'Jelajahi event yang tersedia dan beli tiket langsung. QR code siap digunakan di hari H.',
        ]));

        // Vote section settings
        Setting::set('landing_vote', json_encode([
            'title' => 'Voting Online',
            'subtitle' => 'Dukung peserta favoritmu! Setiap suara menentukan siapa juara favorit penonton.',
        ]));

        // Social links
        Setting::set('landing_social_links', json_encode([
            'instagram' => '',
            'tiktok' => '',
            'youtube' => '',
            'facebook' => '',
        ]));

        // Contact
        Setting::set('landing_contact', json_encode([
            'phone' => '+62 812-3456-7890',
            'email' => 'halo@berbaris.local',
            'address' => 'Jl. Teknologi No. 42, Jakarta Selatan, Indonesia',
            'map_embed_url' => '',
        ]));
    }
}
