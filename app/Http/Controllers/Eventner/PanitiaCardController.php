<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

/**
 * Kartu akses entry panitia — satu lembar: link + QR + PIN.
 *
 * Satu halaman, bukan dua seperti kartu juri. Kartu juri dipecah karena QR-nya
 * harus ditutup lembar identitas selama tablet tidak dipakai; halaman panitia
 * tidak disimpan di meja terbuka seperti itu — yang perlu dijaga justru PIN-nya,
 * dan PIN itu ikut tercetak di sini agar bisa dibagikan utuh ke petugas.
 *
 * Tanpa QR terpisah, kartu ini juga aman difotokopi sekaligus: tidak ada
 * halaman yang isinya harus disembunyikan dari halaman lain.
 */
class PanitiaCardController extends Controller
{
    public function download()
    {
        $eventner = Auth::user()->eventner;
        if (! $eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        // Tanpa token tidak ada yang bisa dicetak. Mengirim kartu kosong ke
        // panitia lebih buruk daripada 404: mereka akan mengira link-nya rusak.
        if (! $eventner->panitia_token || ! $eventner->panitia_pin) {
            abort(404, 'Akses entry panitia belum dibuat.');
        }

        $url = panitia_entry_url($eventner->panitia_token);

        // QR diskalakan besar (satu gambar per dokumen) tapi tetap PNG —
        // dompdf membuang SVG diam-diam. Helper qr_data_uri memaksa
        // outputInterface-nya.
        $qrImage = qr_data_uri($url, 12);

        $filename = 'Kartu_Akses_Panitia_'
            . str_replace(['/', '\\', ' '], '_', $eventner->nama_event)
            . '.pdf';

        return Pdf::loadView('eventner.panitia.pdf_kartu_akses', [
            'eventner' => $eventner,
            'url' => $url,
            'qrImage' => $qrImage,
        ])
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }
}
