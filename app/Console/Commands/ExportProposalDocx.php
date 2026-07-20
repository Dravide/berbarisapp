<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class ExportProposalDocx extends Command
{
    protected $signature = 'proposal:docx';
    protected $description = 'Export proposal promosi ke DOCX';

    public function handle()
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);

        $phpWord->addTitleStyle(1, ['size' => 22, 'bold' => true, 'color' => '0062FF'], ['spaceAfter' => 200]);
        $phpWord->addTitleStyle(2, ['size' => 16, 'bold' => true, 'color' => '1E293B'], ['spaceAfter' => 150, 'spaceBefore' => 300]);
        $phpWord->addParagraphStyle('p', ['spaceAfter' => 120, 'lineHeight' => 1.3]);

        $section = $phpWord->addSection(['margin' => [1500, 1000, 1000, 1000]]);

        // COVER
        for ($i=0; $i<8; $i++) $section->addTextBreak(1);
        $section->addText('BARIS APP', ['size' => 32, 'bold' => true, 'color' => '0062FF', 'name' => 'Calibri Light'], ['align' => 'center']);
        $section->addTextBreak(1);
        $section->addText('Platform Manajemen Event & Kompetisi Terpadu', ['size' => 16, 'color' => '64748B', 'italic' => true], ['align' => 'center']);
        $section->addTextBreak(2);
        $section->addText('Proposal Aplikasi', ['size' => 18, 'bold' => true, 'color' => '1E293B'], ['align' => 'center']);
        $section->addTextBreak(4);
        $section->addText('Pendaftaran Online  |  Penilaian Digital  |  Voting QRIS', ['size' => 10, 'color' => '475569'], ['align' => 'center']);
        $section->addText('Tiket Event  |  Livestream Overlay  |  Scoreboard Publik', ['size' => 10, 'color' => '475569'], ['align' => 'center']);
        $section->addText('Drawing  |  Champion Generator  |  Subdomain Kustom', ['size' => 10, 'color' => '475569'], ['align' => 'center']);
        $section->addTextBreak(4);
        $section->addText(date('d F Y'), ['size' => 10, 'color' => '94A3B8'], ['align' => 'center']);
        $section->addPageBreak();

        // HELPER FUNCTION
        $addTbl = function ($section, $headers, $rows) {
            $t = $section->addTable(['borderSize' => 6, 'borderColor' => 'B0BEC5', 'cellMargin' => 60]);
            $t->addRow();
            foreach ($headers as $h)
                $t->addCell(3000, ['shading' => ['fill' => '0062FF']])->addText($h, ['bold' => true, 'color' => 'FFFFFF', 'size' => 10]);
            foreach ($rows as $r) {
                $t->addRow();
                foreach ($r as $c)
                    $t->addCell(3000)->addText($c, ['size' => 10]);
            }
        };

        $pl = function ($section, $label) {
            $section->addTextBreak(1);
            $t = $section->addTable(['borderSize' => 4, 'borderColor' => 'B0BEC5', 'cellMargin' => 150]);
            $t->addRow();
            $c = $t->addCell(10000, ['shading' => ['fill' => 'F1F5F9']]);
            $c->addTextBreak(2);
            $c->addText($label, ['size' => 12, 'bold' => true, 'color' => '94A3B8'], ['align' => 'center']);
            $c->addText('(Tempatkan screenshot di sini, hapus border table setelahnya)', ['size' => 9, 'italic' => true, 'color' => 'B0BEC5'], ['align' => 'center']);
            $c->addTextBreak(2);
            $section->addTextBreak(1);
        };

        // 1. MASALAH
        $section->addTitle('1. Masalah yang Kami Selesaikan', 1);
        $section->addText('Platform tradisional dalam penyelenggaraan event kompetisi masih menggunakan sistem manual yang merepotkan. BARIS APP hadir sebagai solusi digital all-in-one.', 'p');
        $addTbl($section, ['Masalah', 'Dampak', 'Solusi BARIS APP'], [
            ['Pendaftaran manual & kertas', 'Data hilang, antrean panjang', 'Pendaftaran online + magic link'],
            ['Penilaian juri pakai kertas', 'Rekap lama, rawan manipulasi', 'Scoring digital dari HP juri'],
            ['Voting penonton ribet', 'Potensi donasi hilang', 'QRIS voting - scan, bayar, langsung'],
            ['Tiket event antre fisik', 'Pemalsuan tiket, antre', 'QR Code tiket + scan check-in'],
            ['Papan skor manual', 'Update lambat', 'Overlay OBS + scoreboard publik'],
            ['Pengumuman juara lambat', 'Peserta menunggu', 'Champion generator otomatis'],
            ['Biaya platform mahal', 'Ribuan per bulan', 'Bayar sekali, pakai selamanya'],
        ]);
        $pl($section, '[ SCREENSHOT_1 - Halaman pendaftaran event ]');
        $section->addPageBreak();

        // 2. KENAPA
        $section->addTitle('2. Kenapa BARIS APP?', 1);
        $section->addTitle('All-in-One Platform', 2);
        $section->addText('Tidak perlu 5 aplikasi berbeda. Cukup BARIS APP untuk semuanya:', 'p');
        $addTbl($section, ['Fitur', 'Manfaat'], [
            ['Landing Page Event', 'Halaman publik otomatis dengan tema kustom'],
            ['Pendaftaran Online', 'Via magic link, tanpa buat akun'],
            ['Manajemen Peserta', 'Data tim, pelatih, danton, upload berkas'],
            ['Penilaian Digital', 'Juri nilai via HP, skor otomatis'],
            ['Champion Generator', 'Juara dari akumulasi nilai + tiebreak'],
            ['Vote Berbayar QRIS', 'Dana masuk real-time via scan QRIS'],
            ['Tiket Event QRIS', 'Jual tiket online + QR Code check-in'],
            ['Drawing / Undian', 'Layar spin interaktif'],
            ['Livestream Overlay', 'Tampilan OBS profesional'],
            ['Live Scoreboard', 'Papan skor real-time'],
            ['Sponsor & Tenant', 'Kelola partner event'],
            ['Subdomain Sendiri', 'nama-event.berbaris.app'],
        ]);
        $pl($section, '[ SCREENSHOT_2 - Dashboard eventner ]');
        $section->addTitle('Mudah Digunakan', 2);
        foreach (['Peserta: Tidak perlu daftar akun - cukup klik magic link dari email', 'Juri: Buka link, nilai langsung, simpan - dari HP mana pun', 'Panitia: Satu dashboard untuk semua kontrol event', 'Penonton: Scan QRIS untuk vote, lihat scoreboard real-time'] as $b)
            $section->addText('  ' . $b, 'p');
        $pl($section, '[ SCREENSHOT_3 - Halaman vote publik ]');
        $section->addPageBreak();

        $section->addTitle('Harga Terjangkau', 2);
        $section->addText('Bayar SEKALI - 1 event bisa melayani ribuan peserta, puluhan juri, ratusan vote - TANPA BIAYA TAMBAHAN.', ['bold' => true, 'size' => 12, 'color' => '0062FF'], ['spaceAfter' => 200]);
        $addTbl($section, ['Paket', 'Harga', 'Keterangan'], [
            ['Gratis', 'Rp 0', 'Trial 3 hari - eksplorasi semua fitur premium'],
            ['Berbayar', 'Rp 50.000', 'Sekali bayar - semua fitur terbuka permanen'],
        ]);
        $pl($section, '[ SCREENSHOT_4 - Halaman pembayaran QRIS ]');
        $section->addTitle('Branding Profesional', 2);
        foreach (['Subdomain kustom: kejurcab-cianjur.berbaris.app', 'Tema warna: Sesuaikan dengan warna acara Anda', 'Logo & Poster: Upload logo dan poster event', 'Font kustom: Pilih font untuk halaman publik', 'Overlay OBS: Tampilan profesional untuk siaran langsung'] as $b)
            $section->addText('  ' . $b, 'p');
        $pl($section, '[ SCREENSHOT_5 - Halaman event dengan tema kustom ]');
        $section->addPageBreak();

        // 3. SIAPA
        $section->addTitle('3. Siapa yang Butuh BARIS APP?', 1);
        $addTbl($section, ['Target', 'Kebutuhan'], [
            ['Sekolah / OSIS', 'Lomba PBB & paskibra dengan sistem profesional'],
            ['Pembina Paskibra', 'Penilaian juri transparan & real-time'],
            ['Pramuka', 'Lomba keterampilan tingkat kwarcab/kwaran'],
            ['Universitas', 'UKM, dies natalis, lomba antar fakultas'],
            ['Pemerintah Daerah', 'Event FORBASI tingkat kabupaten/provinsi'],
            ['Event Organizer', 'Platform siap pakai untuk klien event'],
            ['Komunitas', 'Turnamen dengan budget terbatas'],
        ]);
        $pl($section, '[ SCREENSHOT_6 - Tampilan mobile ]');
        $section->addPageBreak();

        // 4. BIAYA
        $section->addTitle('4. Perbandingan Biaya', 1);
        $addTbl($section, ['Platform', 'Model Harga', 'Estimasi / Event'], [
            ['BARIS APP', 'Sekali bayar', 'Rp 50.000'],
            ['Platform A', 'Rp 500rb - 2jt / bulan', 'Rp 500.000 - 2.000.000'],
            ['Manual (kertas)', 'Cetak + lembur', '> Rp 300.000'],
            ['Bangun sendiri', 'Developer 3-6 bulan', '> Rp 50.000.000'],
        ]);
        $section->addText('Hemat hingga 96% dibanding platform lain!', ['bold' => true, 'size' => 12, 'color' => '16A34A'], ['align' => 'center']);
        $section->addPageBreak();

        // 5. FITUR
        $section->addTitle('5. Fitur Lengkap', 1);
        $fiturs = [
            ['Manajemen Event', 'Profil event lengkap dengan halaman publik otomatis, tema kustom, dan subdomain sendiri.', 'SCREENSHOT_7 - Profil event'],
            ['Pendaftaran & Peserta', 'Pendaftaran multi-step: pilih kategori, data sekolah, konfirmasi. Magic link untuk isi data tim tanpa registrasi.', 'SCREENSHOT_8 - Form pendaftaran'],
            ['Penilaian Digital', 'Buat kategori penilaian dengan bobot. Juri nilai dari HP. Skor langsung terkalkulasi. Rekap otomatis.', 'SCREENSHOT_9 - Input nilai juri'],
            ['Voting Penonton', 'Support via vote berbayar QRIS. Hasil live. Recap PDF.', 'SCREENSHOT_10 - Voting publik'],
            ['Tiket Event', 'Jual tiket online. QRIS. QR Code check-in.', 'SCREENSHOT_11 - Pembelian tiket'],
            ['Overlay & Scoreboard', 'Tampilan OBS profesional. Mode: full, vote, kegiatan, greenscreen. Marquee leaderboard. Scoreboard publik.', 'SCREENSHOT_12 - Overlay livestream'],
            ['Drawing, Champion & More', 'Layar spin undian. Champion generator. Sponsor, tenant, FAQ, galeri. Activity log.', 'SCREENSHOT_13 - Drawing spin'],
        ];
        foreach ($fiturs as [$title, $desc, $ss]) {
            $section->addTitle($title, 2);
            $section->addText($desc, 'p');
            $pl($section, '[ ' . $ss . ' ]');
        }
        $section->addPageBreak();

        // PENUTUP
        $section->addTitle('Hubungi Kami', 1);
        foreach (['Website: https://berbaris.app', 'Email: support@berbaris.app', 'Instagram: @berbaris.app'] as $c)
            $section->addText($c, ['size' => 12], ['spaceAfter' => 80]);
        $section->addTextBreak(2);
        $section->addText('"BARIS APP - Bikin event kompetisimu profesional, modern, dan bebas ribet. Cukup satu platform untuk semua kebutuhan. Gratis coba 3 hari. Bayar sekali, pakai selamanya."', ['size' => 11, 'italic' => true, 'color' => '475569'], ['align' => 'center']);
        $section->addTextBreak(4);
        $section->addText('BARIS APP - Platform Manajemen Event & Kompetisi Terpadu', ['size' => 9, 'color' => '94A3B8'], ['align' => 'center']);

        // SAVE
        $path = storage_path('app/public/proposal-barisapp.docx');
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);
        copy($path, base_path('public/proposal-barisapp.docx'));

        $this->info("DOCX exported!");
        return Command::SUCCESS;
    }
}
