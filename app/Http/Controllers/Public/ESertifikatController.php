<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Services\CertificatePdfBuilder;
use App\Services\SertifikatKontingen;
use App\Support\AksesUnduhSertifikat;

/**
 * Unduh satu sertifikat dari laman publik e-sertifikat.
 *
 * Registrasi ada di path (angka yang bisa ditebak), jadi izinnya TIDAK boleh
 * berasal dari situ — lihat AksesUnduhSertifikat untuk alasannya. Di sini yang
 * diperiksa: apakah id ini termasuk yang dibuka pencarian NISN pada sesi ini.
 */
class ESertifikatController extends Controller
{
    public function __construct(
        private SertifikatKontingen $kontingen,
        private CertificatePdfBuilder $builder,
    ) {}

    public function unduh(int $registration)
    {
        // Gerbang pertama, dan satu-satunya yang penting: tanpa pencarian yang
        // sah, id di URL tidak berarti apa-apa.
        if (! AksesUnduhSertifikat::bolehUnduh($registration)) {
            abort(403, 'Sesi pencarian sudah berakhir. Silakan cari NISN Anda lagi.');
        }

        $reg = Registration::with(['eventner', 'competitionCategory.parent', 'participants'])
            ->findOrFail($registration);

        $eventner = $reg->eventner;

        // Gerbang kedua: sertifikat adalah fitur paket, dan template harus ada.
        // Diperiksa ulang di sini, bukan hanya saat pencarian, karena jarak
        // antara keduanya bisa lewat beberapa menit — cukup untuk panitia
        // mematikan template di tengah jalan.
        if (! $eventner?->canAccessFeature('certificate')) {
            abort(403, 'Fitur sertifikat belum aktif untuk event ini.');
        }

        $template = $this->builder->activeTemplate($eventner->id);

        if (! $template) {
            abort(404, 'Panitia belum memasang template sertifikat untuk event ini.');
        }

        // Mata lomba registrasi ini punya kategori juara sendiri; gelar dan
        // field `kategori_juara` diambil dari sana. Tanpa kategori juara yang
        // cocok, halamannya tetap terbit sebagai PESERTA.
        [$championCategory, $pages] = $this->kontingen->halamanUntukKategori(
            $reg,
            null,
            $this->builder,
        );

        return $this->builder->render(
            template: $template,
            pages: $pages,
            championCategory: $championCategory,
            competitionCategory: $reg->competitionCategory,
            schoolLabel: $reg->nama_sekolah,
        );
    }
}
