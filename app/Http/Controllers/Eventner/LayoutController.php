<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use App\Models\CompetitionLayout;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class LayoutController extends Controller
{
    /**
     * Cetak denah ke PDF.
     *
     * Dompdf, bukan potret browser: PDF sering diminta justru saat kanvasnya
     * tak bisa dibuka. Orientasi kertasnya mengikuti rasio papan, bukan selalu
     * lanskap — denah yang memanjang ke belakang akan tak terbaca.
     */
    public function cetakPdf(int $layout)
    {
        $eventner = Auth::user()?->eventner;

        if (! $eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        if (! $eventner->canAccessFeature('layout')) {
            abort(403, 'Fitur Denah Lapangan hanya tersedia untuk paket berbayar.');
        }

        // Dikurung eventner_id: denah milik event lain jadi 404.
        $denah = CompetitionLayout::where('eventner_id', $eventner->id)
            ->with(['zones', 'competitionCategory.parent', 'venue'])
            ->findOrFail($layout);

        [$kertas, $orientasi] = $denah->kertasPdf;

        $pdf = Pdf::loadView('eventner.denah.pdf-cetak', [
            'denah' => $denah,
            'eventner' => $eventner,
            'tinggiKanvas' => $this->tinggiKanvas($denah, $orientasi),
            // Dompdf tidak punya clamp(); ukuran label dipatok dari rasio papan
            // supaya nama zona tetap terbaca tanpa saling menimpa.
            'ukuranLabel' => $denah->rasio >= 1.4 ? '13px' : '11px',
        ])->setPaper($kertas, $orientasi);

        $nama = preg_replace('/[^A-Za-z0-9\-]+/', '-', $denah->name) ?: 'denah';

        return $pdf->download('denah-'.trim($nama, '-').'.pdf');
    }

    /**
     * Tinggi kanvas di PDF, dalam mm.
     *
     * Dompdf mengabaikan aspect-ratio, jadi tingginya dihitung di sini dari
     * rasio papan. Dibatasi ruang yang tersisa setelah kop, judul, dan tabel
     * keterangan; tanpa batas itu denah 40 × 22 m meluber ke halaman kedua.
     */
    private function tinggiKanvas(CompetitionLayout $denah, string $orientasi): string
    {
        $rasio = max(0.2, min(6, $denah->rasio));
        $lebar = $orientasi === 'landscape' ? 255.0 : 172.0;
        $maksimum = $orientasi === 'landscape' ? 135.0 : 205.0;

        return number_format(max(45.0, min($maksimum, $lebar / $rasio)), 0, '.', '').'mm';
    }
}
