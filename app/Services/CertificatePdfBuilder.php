<?php

namespace App\Services;

use App\Models\CertificateTemplate;
use App\Models\ChampionCategory;
use App\Models\Participant;
use App\Models\Registration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * Perakit berkas sertifikat PDF.
 *
 * Tiga jalur memakai berkas yang sama: unduhan panitia
 * (CertificateController::downloadPdf), portal kontingen
 * (CertificateController::downloadCertificateByToken), dan laman publik
 * e-sertifikat. Sebelum ini penyusunan `$pages` dan pemanggilan PDF disalin di
 * dua tempat dengan aturan yang gampang menyimpang — mis. satu jalur membuang
 * peserta bernilai nol lalu yang lain tidak, sehingga gelar di sertifikat
 * berbeda dari gelar di /hasil.
 *
 * Yang tinggal di controller adalah penentuan SIAPA yang berhak (auth, token,
 * sesi) dan APA cakupannya (satu mata lomba, satu sekolah). Penyusunan
 * halaman dan render PDF-nya ada di sini.
 */
class CertificatePdfBuilder
{
    /**
     * Susun daftar halaman untuk satu registrasi pada satu kategori juara.
     *
     * Satu halaman per anggota pasukan (plus danton), dengan gelar juara bila
     * registrasi ini masuk jajaran juara, atau PESERTA bila tidak. `$rank` dan
     * `$title` dari pemanggil dipakai apa adanya — penghitungan peringkatnya
     * milik ChampionCalculator, bukan di sini.
     *
     * @param  Registration  $reg
     * @param  ?array  $won  ['rank' => int, 'title' => string, 'total' => int] atau null
     * @return array<int, array{registration: Registration, participant: ?Participant, rank: ?int, title: string, total: ?float}>
     */
    public function pagesForRegistration(Registration $reg, ?array $won = null): array
    {
        $reg->loadMissing('participants');

        $satuHalamanPerSekolah = $reg->participants->isEmpty() && ! $reg->danton_nama;

        // Fallback: sekolah tanpa satu pun baris anggota hanya dapat satu
        // halaman, bukan nihil.
        if ($satuHalamanPerSekolah) {
            return [[
                'registration' => $reg,
                'participant' => null,
                'rank' => $won['rank'] ?? null,
                'title' => $won['title'] ?? 'PESERTA',
                'total' => $won['total'] ?? null,
            ]];
        }

        $pages = [];

        foreach ($reg->participants as $p) {
            $pages[] = [
                'registration' => $reg,
                'participant' => $p,
                'rank' => $won['rank'] ?? null,
                'title' => $won['title'] ?? 'PESERTA',
                'total' => $won['total'] ?? null,
            ];
        }

        // Danton juga anggota pasukan, tapi tidak punya baris `participants`.
        if ($reg->danton_nama) {
            $pages[] = [
                'registration' => $reg,
                'participant' => new Participant(['nama' => $reg->danton_nama]),
                'rank' => $won['rank'] ?? null,
                'title' => $won['title'] ?? 'PESERTA',
                'total' => $won['total'] ?? null,
            ];
        }

        return $pages;
    }

    /**
     * Template aktif yang dipakai event ini, null bila belum ada.
     *
     * Satu template saja per event — yang paling awal dipasang. Kalau panitia
     * memasang beberapa, yang pertama menang supaya sertifikat satu event
     * seragam; memilih per mata lomba akan membuat dua peserta satu event
     * menerima desain berbeda tanpa ada yang menyetujuinya.
     */
    public function activeTemplate(int $eventnerId): ?CertificateTemplate
    {
        return CertificateTemplate::where('eventner_id', $eventnerId)
            ->where('is_active', true)
            ->with('textFields')
            ->orderBy('id')
            ->first();
    }

    /**
     * Render PDF dari halaman yang sudah disusun.
     *
     * QR menuju halaman event ikut dirender hanya kalau template memang punya
     * field `qr_event` — template tanpa field itu tidak membayar ongkos
     * pembuatan QR-nya.
     *
     * @param  array<int, array>  $pages
     */
    public function render(
        CertificateTemplate $template,
        array $pages,
        ?ChampionCategory $championCategory = null,
        ?\App\Models\CompetitionCategory $competitionCategory = null,
        ?string $filename = null,
        ?string $schoolLabel = null,
    ): Response {
        $eventner = $template->eventner;

        $data = [
            'eventner' => $eventner,
            'template' => $template,
            'championCategory' => $championCategory,
            'competitionCategory' => $competitionCategory,
            'pages' => $pages,
            'eventQrDataUri' => $this->eventQrDataUri($template, $eventner),
        ];

        // Background template di-decode GD per halaman pemenang — butuh memori besar.
        ini_set('memory_limit', '1024M');
        gc_collect_cycles();

        $pdf = Pdf::loadView('eventner.certificate.pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', '0mm')
            ->setOption('margin-bottom', '0mm')
            ->setOption('margin-left', '0mm')
            ->setOption('margin-right', '0mm');

        $nama = $filename ?? $this->defaultFilename($championCategory, $competitionCategory, $schoolLabel);

        return $pdf->download($nama);
    }

    /** Nama berkas bawaan: Sertifikat_{kategori juara}_{mata lomba}[_{sekolah}].pdf */
    public function defaultFilename(
        ?ChampionCategory $championCategory,
        ?\App\Models\CompetitionCategory $competitionCategory,
        ?string $schoolLabel = null,
    ): string {
        $bersih = fn (?string $s) => str_replace(['/', '\\'], '-', (string) $s);

        $bagian = array_filter([
            'Sertifikat',
            $bersih($championCategory?->name),
            $bersih($competitionCategory?->name),
            $bersih($schoolLabel),
        ], fn ($b) => $b !== '');

        return implode('_', $bagian) . '.pdf';
    }

    /**
     * QR menuju halaman event sebagai data-URI, null bila template tidak
     * memakai field `qr_event`.
     */
    private function eventQrDataUri(CertificateTemplate $template, $eventner): ?string
    {
        if (! $template->textFields->contains('field_key', 'qr_event')) {
            return null;
        }

        return qr_data_uri($eventner->publicUrl('detail'), 8, 'H');
    }
}
