<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Html;

class ExportProposalDocx extends Command
{
    protected $signature = 'proposal:docx';
    protected $description = 'Export proposal promosi ke DOCX dengan placeholder screenshot';

    public function handle()
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);

        // Styles
        $phpWord->addTitleStyle(1, ['size' => 22, 'bold' => true, 'color' => '0062FF'], ['spaceAfter' => 200]);
        $phpWord->addTitleStyle(2, ['size' => 16, 'bold' => true, 'color' => '1E293B'], ['spaceAfter' => 150, 'spaceBefore' => 300]);
        $phpWord->addTitleStyle(3, ['size' => 13, 'bold' => true, 'color' => '334155'], ['spaceAfter' => 100, 'spaceBefore' => 200]);
        $phpWord->addParagraphStyle('normal', ['spaceAfter' => 120, 'lineHeight' => 1.3]);
        $phpWord->addParagraphStyle('listitem', ['spaceAfter' => 60, 'indentation' => ['left' => 400]]);
        $phpWord->addParagraphStyle('center', ['align' => 'center', 'spaceAfter' => 120]);

        // === COVER PAGE ===
        $section = $phpWord->addSection(['margin' => [2000, 1000, 1000, 1000]]);
        $section->addTextBreak(8);

        // Decorative line
        $section->addText('━' . str_repeat('━', 60), ['size' => 6, 'color' => '0062FF'], ['align' => 'center']);
        $section->addTextBreak(2);

        $section->addText('BARIS APP', ['size' => 32, 'bold' => true, 'color' => '0062FF', 'name' => 'Calibri Light'], ['align' => 'center']);
        $section->addTextBreak(1);
        $section->addText('Platform Manajemen Event & Kompetisi Terpadu', ['size' => 16, 'color' => '64748B', 'italic' => true], ['align' => 'center']);
        $section->addTextBreak(1);
        $section->addText('━' . str_repeat('━', 60), ['size' => 6, 'color' => '0062FF'], ['align' => 'center']);
        $section->addTextBreak(3);
        $section->addText('Proposal Aplikasi', ['size' => 18, 'bold' => true, 'color' => '1E293B'], ['align' => 'center']);
        $section->addTextBreak(6);

        // Features summary
        $features = ['Pendaftaran Online', 'Penilaian Digital', 'Voting QRIS', 'Tiket Event Online', 'Livestream Overlay', 'Scoreboard Publik', 'Drawing / Undian', 'Subdomain Kustom'];
        $ftrText = '';
        foreach ($features as $i => $f) {
            $ftrText .= $f;
            if ($i < count($features)-1) $ftrText .= '  •  ';
        }
        $section->addText($ftrText, ['size' => 10, 'color' => '475569', 'italic' => true], ['align' => 'center']);
        $section->addTextBreak(4);
        $section->addText(date('d F Y'), ['size' => 10, 'color' => '94A3B8'], ['align' => 'center']);

        // === PAGE BREAK ===
        $section->addPageBreak();

        // ================================================================
        // DAFTAR ISI
        // ================================================================
        $section->addTitle('Daftar Isi', 1);
        $toc = [
            'Masalah yang Kami Selesaikan',
            'Kenapa BARIS APP?',
            'Siapa yang Butuh BARIS APP?',
            'Perbandingan Biaya',
            'Fitur Lengkap',
            'Hubungi Kami',
        ];
        foreach ($toc as $i => $item) {
            $section->addText(($i+1) . '.  ' . $item, ['size' => 12], ['spaceAfter' => 80]);
        }
        $section->addPageBreak();

        // ================================================================
        // 1. MASALAH YANG KAMI SELESAIKAN
        // ================================================================
        $section->addTitle('Masalah yang Kami Selesaikan', 1);
        $section->addText('Platform tradisional dalam penyelenggaraan event kompetisi masih menggunakan sistem manual yang merepotkan. BARIS APP hadir sebagai solusi digital all-in-one.', 'normal');

        // Table: Masalah vs Solusi
        $tableStyle = ['borderSize' => 6, 'borderColor' => 'E2E8F0', 'cellMargin' => 80];
        $phpWord->addTableStyle('probTable', $tableStyle, [
            ['width' => 2500, 'bold' => true],
            ['width' => 3500],
            ['width' => 3500],
        ]);
        $table = $section->addTable('probTable');
        $headers = ['Masalah', 'Dampak', 'Solusi BARIS APP'];
        $rows = [
            ['Pendaftaran manual & kertas', 'Data hilang, antrean panjang', 'Pendaftaran online multi-step + magic link'],
            ['Penilaian juri pakai kertas', 'Rekap lama, rawan manipulasi', 'Scoring digital real-time dari HP juri'],
            ['Voting penonton ribet', 'Potensi donasi hilang', 'QRIS voting — scan, bayar, hasil langsung'],
            ['Tiket event antre fisik', 'Pemalsuan tiket', 'QR Code tiket + scan check-in'],
            ['Papan skor manual', 'Update lambat, nonton bingung', 'Overlay OBS profesional + scoreboard publik'],
            ['Pengumuman juara lambat', 'Peserta menunggu', 'Champion generator otomatis + PDF ranking'],
            ['Biaya sewa platform mahal', 'Ribuan per bulan', 'Bayar sekali, pakai selamanya!'],
        ];
        $table->addRow();
        foreach ($headers as $h) {
            $table->addCell(3000, ['shading' => ['fill' => '0062FF']])->addText($h, ['bold' => true, 'color' => 'FFFFFF', 'size' => 10]);
        }
        foreach ($rows as $row) {
            $table->addRow();
            foreach ($row as $cell) {
                $table->addCell(3000)->addText($cell, ['size' => 10]);
            }
        }

        $section->addTextBreak(1);
        $this->addScreenshotPlaceholder($section, 'Tampilan halaman pendaftaran event', 'SCREENSHOT_1');
        $section->addPageBreak();

        // ================================================================
        // 2. KENAPA BARIS APP?
        // ================================================================
        $section->addTitle('Kenapa BARIS APP?', 1);

        // --- 2.1 All-in-One ---
        $section->addTitle('All-in-One Platform', 2);
        $section->addText('Tidak perlu 5 aplikasi berbeda. Cukup BARIS APP untuk semuanya:', 'normal');

        $phpWord->addTableStyle('fiturTable', $tableStyle, [
            ['width' => 4000],
            ['width' => 5500],
        ]);
        $t2 = $section->addTable('fiturTable');
        $t2->addRow();
        $t2->addCell(4000, ['shading' => ['fill' => '0062FF']])->addText('Fitur', ['bold' => true, 'color' => 'FFFFFF', 'size' => 10]);
        $t2->addCell(5500, ['shading' => ['fill' => '0062FF']])->addText('Manfaat', ['bold' => true, 'color' => 'FFFFFF', 'size' => 10]);
        $fiturs = [
            ['Landing Page Event', 'Halaman publik otomatis dengan tema kustom'],
            ['Pendaftaran Online', 'Sekolah daftar via magic link tanpa perlu buat akun'],
            ['Manajemen Peserta', 'Data tim, pelatih, danton, upload berkas'],
            ['Penilaian Digital', 'Juri nilai via HP, skor otomatis terkalkulasi'],
            ['Champion Generator', 'Juara otomatis dari akumulasi nilai + tiebreak'],
            ['Vote Berbayar QRIS', 'Dana masuk real-time via scan QRIS'],
            ['Tiket Event QRIS', 'Jual tiket online + QR Code check-in'],
            ['Drawing / Undian', 'Layar spin peserta untuk pengundian'],
            ['Livestream Overlay', 'Tampilan profesional untuk siaran langsung OBS'],
            ['Live Scoreboard', 'Papan skor real-time untuk penonton'],
            ['Sponsor & Tenant', 'Kelola dan tampilkan partner event'],
            ['Subdomain Sendiri', 'nama-event.berbaris.app — branding event sendiri'],
        ];
        foreach ($fiturs as $f) {
            $t2->addRow();
            $t2->addCell(4000)->addText($f[0], ['bold' => true, 'size' => 10]);
            $t2->addCell(5500)->addText($f[1], ['size' => 10]);
        }

        $section->addTextBreak(1);
        $this->addScreenshotPlaceholder($section, 'Tampilan dashboard eventner', 'SCREENSHOT_2');
        $section->addTextBreak(1);

        // --- 2.2 Mudah Digunakan ---
        $section->addTitle('Mudah Digunakan', 2);
        $easy = [
            'Peserta' => 'Tidak perlu daftar akun — cukup klik magic link dari email',
            'Juri' => 'Buka link, nilai langsung, simpan — dari HP mana pun',
            'Panitia' => 'Satu dashboard untuk semua kontrol event',
            'Penonton' => 'Scan QRIS untuk vote, lihat scoreboard real-time',
        ];
        foreach ($easy as $role => $desc) {
            $section->addText('✓  ' . $role . ':  ' . $desc, ['size' => 11], ['spaceAfter' => 60]);
        }

        $section->addTextBreak(1);
        $this->addScreenshotPlaceholder($section, 'Tampilan halaman vote publik', 'SCREENSHOT_3');
        $section->addPageBreak();

        // --- 2.3 Harga Terjangkau ---
        $section->addTitle('Harga Terjangkau', 2);
        $section->addText('Bayar SEKALI — 1 event bisa melayani ribuan peserta, puluhan juri, ratusan vote — TANPA BIAYA TAMBAHAN.', ['bold' => true, 'size' => 12, 'color' => '0062FF'], ['spaceAfter' => 200]);

        $phpWord->addTableStyle('priceTable', ['borderSize' => 6, 'borderColor' => 'E2E8F0', 'cellMargin' => 100], [
            ['width' => 3000],
            ['width' => 2000],
            ['width' => 5000],
        ]);
        $t3 = $section->addTable('priceTable');
        $t3->addRow();
        $t3->addCell(3000, ['shading' => ['fill' => '0062FF']])->addText('Paket', ['bold' => true, 'color' => 'FFFFFF', 'size' => 11]);
        $t3->addCell(2000, ['shading' => ['fill' => '0062FF']])->addText('Harga', ['bold' => true, 'color' => 'FFFFFF', 'size' => 11]);
        $t3->addCell(5000, ['shading' => ['fill' => '0062FF']])->addText('Keterangan', ['bold' => true, 'color' => 'FFFFFF', 'size' => 11]);
        $t3->addRow();
        $t3->addCell(3000)->addText('Gratis', ['bold' => true, 'size' => 11, 'color' => '16A34A']);
        $t3->addCell(2000)->addText('Rp 0', ['bold' => true, 'size' => 11]);
        $t3->addCell(5000)->addText('Trial 3 hari — eksplorasi semua fitur premium', ['size' => 10]);
        $t3->addRow();
        $t3->addCell(3000)->addText('Berbayar', ['bold' => true, 'size' => 11, 'color' => '0062FF']);
        $t3->addCell(2000)->addText('Rp 50.000', ['bold' => true, 'size' => 11, 'color' => '0062FF']);
        $t3->addCell(5000)->addText('Sekali bayar — semua fitur terbuka permanen', ['size' => 10]);

        $section->addTextBreak(1);
        $this->addScreenshotPlaceholder($section, 'Tampilan halaman pembayaran QRIS', 'SCREENSHOT_4');
        $section->addPageBreak();

        // --- 2.4 Branding Profesional ---
        $section->addTitle('Branding Profesional', 2);
        $brands = [
            'Subdomain kustom: kejurcab-cianjur.berbaris.app',
            'Tema warna: Sesuaikan dengan warna acara Anda',
            'Logo & Poster: Upload logo dan poster event',
            'Font kustom: Pilih font untuk halaman publik',
            'Overlay OBS: Tampilan profesional untuk siaran langsung',
        ];
        foreach ($brands as $b) {
            $section->addText('✦  ' . $b, ['size' => 11], ['spaceAfter' => 60]);
        }
        $section->addTextBreak(1);
        $this->addScreenshotPlaceholder($section, 'Tampilan halaman event dengan tema kustom', 'SCREENSHOT_5');
        $section->addPageBreak();

        // ================================================================
        // 3. SIAPA YANG BUTUH BARIS APP?
        // ================================================================
        $section->addTitle('Siapa yang Butuh BARIS APP?', 1);

        $phpWord->addTableStyle('targetTable', $tableStyle, [
            ['width' => 3500],
            ['width' => 6000],
        ]);
        $t4 = $section->addTable('targetTable');
        $t4->addRow();
        $t4->addCell(3500, ['shading' => ['fill' => '0062FF']])->addText('Target', ['bold' => true, 'color' => 'FFFFFF', 'size' => 10]);
        $t4->addCell(6000, ['shading' => ['fill' => '0062FF']])->addText('Kebutuhan', ['bold' => true, 'color' => 'FFFFFF', 'size' => 10]);
        $targets = [
            ['Sekolah / OSIS', 'Lomba PBB & paskibra dengan sistem profesional'],
            ['Pembina Paskibra', 'Penilaian juri transparan & real-time'],
            ['Pramuka', 'Lomba keterampilan tingkat kwarcab/kwaran'],
            ['Universitas', 'UKM, dies natalis, lomba antar fakultas'],
            ['Pemerintah Daerah', 'Event FORBASI tingkat kabupaten/provinsi'],
            ['Event Organizer', 'Platform siap pakai untuk klien event kompetisi'],
            ['Komunitas', 'Turnamen dengan budget terbatas'],
        ];
        foreach ($targets as $t) {
            $t4->addRow();
            $t4->addCell(3500)->addText($t[0], ['bold' => true, 'size' => 10]);
            $t4->addCell(6000)->addText($t[1], ['size' => 10]);
        }

        $section->addTextBreak(1);
        $this->addScreenshotPlaceholder($section, 'Tampilan halaman publik event di HP', 'SCREENSHOT_6');
        $section->addPageBreak();

        // ================================================================
        // 4. PERBANDINGAN BIAYA
        // ================================================================
        $section->addTitle('Perbandingan Biaya', 1);

        $phpWord->addTableStyle('costTable', ['borderSize' => 6, 'borderColor' => 'E2E8F0', 'cellMargin' => 80], [
            ['width' => 3500],
            ['width' => 3500],
            ['width' => 3500],
        ]);
        $t5 = $section->addTable('costTable');
        $t5->addRow();
        $t5->addCell(3500, ['shading' => ['fill' => '0062FF']])->addText('Platform', ['bold' => true, 'color' => 'FFFFFF', 'size' => 10]);
        $t5->addCell(3500, ['shading' => ['fill' => '0062FF']])->addText('Model Harga', ['bold' => true, 'color' => 'FFFFFF', 'size' => 10]);
        $t5->addCell(3500, ['shading' => ['fill' => '0062FF']])->addText('Estimasi / Event', ['bold' => true, 'color' => 'FFFFFF', 'size' => 10]);
        $t5->addRow();
        $t5->addCell(3500)->addText('BARIS APP', ['bold' => true, 'size' => 10, 'color' => '0062FF']);
        $t5->addCell(3500)->addText('Sekali bayar', ['size' => 10]);
        $t5->addCell(3500)->addText('Rp 50.000', ['bold' => true, 'size' => 10]);
        $t5->addRow();
        $t5->addCell(3500)->addText('Platform A', ['size' => 10]);
        $t5->addCell(3500)->addText('Rp 500rb - 2jt / bulan', ['size' => 10]);
        $t5->addCell(3500)->addText('Rp 500.000 - 2.000.000', ['size' => 10]);
        $t5->addRow();
        $t5->addCell(3500)->addText('Manual (kertas)', ['size' => 10]);
        $t5->addCell(3500)->addText('Cetak + lembur', ['size' => 10]);
        $t5->addCell(3500)->addText('> Rp 300.000 + tenaga', ['size' => 10]);
        $t5->addRow();
        $t5->addCell(3500)->addText('Bangun sendiri', ['size' => 10]);
        $t5->addCell(3500)->addText('Developer 3-6 bulan', ['size' => 10]);
        $t5->addCell(3500)->addText('> Rp 50.000.000', ['size' => 10]);

        $section->addTextBreak(1);
        $section->addText('Hemat hingga 96% dibanding platform lain!', ['bold' => true, 'size' => 12, 'color' => '16A34A'], ['align' => 'center', 'spaceAfter' => 200]);
        $section->addPageBreak();

        // ================================================================
        // 5. FITUR LENGKAP
        // ================================================================
        $section->addTitle('Fitur Lengkap', 1);

        $section->addTitle('Manajemen Event', 2);
        $section->addText('Profil event lengkap (nama, tanggal, lokasi, venue, deskripsi) — tampilan halaman publik otomatis dengan tema kustom. Tersedia subdomain sendiri untuk branding maksimal.', 'normal');
        $this->addScreenshotPlaceholder($section, 'Profil event — logo, poster, informasi acara', 'SCREENSHOT_7');

        $section->addTitle('Pendaftaran & Peserta', 2);
        $section->addText('Pendaftaran multi-step: pilih kategori → data sekolah → konfirmasi. Setiap sekolah mendapat magic link via email untuk mengisi data tim tanpa perlu registrasi akun.', 'normal');
        $this->addScreenshotPlaceholder($section, 'Form pendaftaran event — pilih kategori', 'SCREENSHOT_8');

        $section->addTitle('Penilaian Digital', 2);
        $section->addText('Buat kategori penilaian, sub-kategori, dan kriteria dengan bobot masing-masing. Juri menilai dari HP masing-masing. Skor langsung terkalkulasi. Rekap nilai otomatis per kategori.', 'normal');
        $this->addScreenshotPlaceholder($section, 'Halaman input nilai juri', 'SCREENSHOT_9');

        $section->addTitle('Voting Penonton', 2);
        $section->addText('Penonton bisa memberikan dukungan via vote berbayar. Pembayaran via QRIS (GoPay, OVO, DANA, ShopeePay, Mobile Banking). Hasil voting live dan bisa di-recap ke PDF.', 'normal');
        $this->addScreenshotPlaceholder($section, 'Halaman voting publik dengan QRIS', 'SCREENSHOT_10');

        $section->addTitle('Tiket Event', 2);
        $section->addText('Jual tiket event online dengan harga dan kuota sendiri. Pembayaran QRIS otomatis. QR Code tiket untuk check-in di pintu masuk.', 'normal');
        $this->addScreenshotPlaceholder($section, 'Halaman pembelian tiket', 'SCREENSHOT_11');

        $section->addTitle('Livestream Overlay & Scoreboard', 2);
        $section->addText('Tampilan profesional untuk siaran langsung via OBS. Mode: full view, vote, kegiatan, greenscreen. Dilengkapi marquee leaderboard, komentar vote real-time, dan top voter card. Scoreboard publik bisa diakses siapa saja.', 'normal');
        $this->addScreenshotPlaceholder($section, 'Tampilan livestream overlay dengan leaderboard', 'SCREENSHOT_12');

        $section->addTitle('Drawing, Champion & Lainnya', 2);
        $section->addText('Drawing / undian dengan layar spin interaktif. Champion generator otomatis berdasarkan akumulasi nilai + pengaturan tiebreak. Kelola sponsor, tenant, FAQ, dan galeri foto event. Aktivitas terekam di activity log.', 'normal');
        $this->addScreenshotPlaceholder($section, 'Halaman drawing / spin undian', 'SCREENSHOT_13');

        $section->addPageBreak();

        // ================================================================
        // PENUTUP
        // ================================================================
        $section->addTitle('Hubungi Kami', 1);
        $section->addTextBreak(1);

        $section->addText('Website:  https://berbaris.app', ['size' => 12], ['spaceAfter' => 80]);
        $section->addText('Email:     support@berbaris.app', ['size' => 12], ['spaceAfter' => 80]);
        $section->addText('Instagram: @berbaris.app', ['size' => 12], ['spaceAfter' => 200]);

        $section->addTextBreak(1);

        // Quote box
        $section->addText('━' . str_repeat('━', 55), ['size' => 6, 'color' => '0062FF'], ['align' => 'center']);
        $section->addTextBreak(1);
        $section->addText(
            '"BARIS APP — Bikin event kompetisimu profesional, modern, dan bebas ribet. '
            . 'Cukup satu platform untuk semua kebutuhan: pendaftaran, penilaian, voting, tiket, '
            . 'dan siaran langsung. Gratis coba 3 hari. Bayar sekali, pakai selamanya."',
            ['size' => 11, 'italic' => true, 'color' => '475569'],
            ['align' => 'center', 'spaceAfter' => 100]
        );
        $section->addTextBreak(1);
        $section->addText('━' . str_repeat('━', 55), ['size' => 6, 'color' => '0062FF'], ['align' => 'center']);

        $section->addTextBreak(4);
        $section->addText('BARIS APP — Platform Manajemen Event & Kompetisi Terpadu', ['size' => 9, 'color' => '94A3B8'], ['align' => 'center']);

        // === SAVE ===
        $path = storage_path('app/public/proposal-barisapp.docx');
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($path);

        // Also copy to public
        copy($path, base_path('public/proposal-barisapp.docx'));

        $this->info("DOCX exported: {$path}");
        $this->info("Public: " . base_path('public/proposal-barisapp.docx'));

        return Command::SUCCESS;
    }

    private function addScreenshotPlaceholder($section, string $label, string $code)
    {
        $section->addTextBreak(1);
        $section->addText(
            str_repeat('━', 55),
            ['size' => 4, 'color' => 'CBD5E1'],
            ['align' => 'center']
        );

        $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'CBD5E1', 'cellMargin' => 200]);
        $table->addRow();
        $cell = $table->addCell(10000, [
            'valign' => 'center',
            'shading' => ['fill' => 'F8FAFC'],
        ]);
        $cell->addTextBreak(3);
        $cell->addText(' [ ' . $code . ' ] ', ['size' => 14, 'bold' => true, 'color' => '94A3B8'], ['align' => 'center']);
        $cell->addTextBreak(1);
        $cell->addText($label, ['size' => 10, 'italic' => true, 'color' => '64748B'], ['align' => 'center']);
        $cell->addText('[ Tempatkan screenshot di sini — ukuran ideal: lebar 600px ]', ['size' => 8, 'color' => '94A3B8'], ['align' => 'center']);
        $cell->addTextBreak(3);

        $section->addText(
            str_repeat('━', 55),
            ['size' => 4, 'color' => 'CBD5E1'],
            ['align' => 'center']
        );
        $section->addTextBreak(1);
    }
}
