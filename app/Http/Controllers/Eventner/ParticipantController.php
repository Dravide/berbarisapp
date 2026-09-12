<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class ParticipantController extends Controller
{
    public function downloadPdf(Registration $registration)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        // Verifikasi kepemilikan
        if ($registration->eventner_id !== $eventner->id) {
            abort(403, 'Anda tidak memiliki hak akses untuk data ini.');
        }

        // Verifikasi status berkas
        if ($registration->status_berkas !== 'Terverifikasi') {
            abort(403, 'Formulir hanya dapat diakses jika status pendaftaran telah Terverifikasi.');
        }

        return $this->renderFormulir($registration);
    }

    public function downloadFormulir(string $token)
    {
        $registration = Registration::with(['participants', 'competitionCategory', 'eventner'])
            ->where('magic_token', $token)
            ->firstOrFail();

        // Hanya bisa diunduh sekolah setelah berkas terverifikasi panitia
        if ($registration->status_berkas !== 'Terverifikasi') {
            abort(403, 'Formulir hanya dapat diunduh setelah pendaftaran Terverifikasi.');
        }

        return $this->renderFormulir($registration);
    }

    /**
     * Unduh kwitansi/invoice PDF dari sisi eventner (halaman participants).
     * Kwitansi digabung per sekolah (NPSN): semua pasukan yang sudah paid.
     */
    public function downloadInvoice(Registration $registration)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        if ($registration->eventner_id !== $eventner->id) {
            abort(403, 'Anda tidak memiliki hak akses untuk data ini.');
        }

        // Invoice hanya sah setelah pembayaran diverifikasi
        if ($registration->payment_status !== 'paid') {
            abort(403, 'Invoice hanya dapat diunduh setelah pembayaran diverifikasi.');
        }

        return $this->renderInvoice($registration);
    }

    /**
     * Unduh kwitansi/invoice PDF via magic link peserta.
     * Kwitansi digabung per sekolah (NPSN): semua pasukan yang sudah paid.
     */
    public function downloadInvoiceByToken(string $token)
    {
        $registration = Registration::with(['eventner'])
            ->where('magic_token', $token)
            ->firstOrFail();

        if ($registration->payment_status !== 'paid') {
            abort(403, 'Invoice hanya dapat diunduh setelah pembayaran diverifikasi panitia.');
        }

        return $this->renderInvoice($registration);
    }

    private function renderInvoice(Registration $registration)
    {
        $eventner = $registration->eventner;

        // Gabung semua pasukan sekolah ini (NPSN sama) yang sudah diverifikasi.
        $registrations = Registration::with(['competitionCategory', 'paymentBankAccount'])
            ->where('eventner_id', $eventner->id)
            ->where('npsn', $registration->npsn)
            ->where('payment_status', 'paid')
            ->where('status_berkas', '!=', 'dibatalkan')
            ->orderBy('id')
            ->get();

        $filename = 'Invoice_' . str_replace(['/', '\\', ' ', '—'], '_', $registration->nama_sekolah) . '.pdf';

        return Pdf::loadView('eventner.participant.pdf_invoice', [
            'eventner' => $eventner,
            'registrations' => $registrations,
        ])
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    private function renderFormulir(Registration $registration)
    {
        ini_set('memory_limit', '512M');

        $eventner = $registration->eventner;
        $filename = 'Formulir_' . $registration->display_name . '.pdf';

        return Pdf::loadView('eventner.participant.pdf_formulir', [
            'eventner' => $eventner,
            'registration' => $registration,
            'participants' => $registration->participants,
        ])
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    /**
     * Daftar ulang peserta per kategori lomba.
     *
     * Satu halaman per kategori (tingkat lomba) berisi kolom tanda tangan
     * untuk sekolah yang hadir saat daftar ulang di meja panitia — dicetak
     * sekali, diisi tangan, bukan per sekolah.
     *
     * Tanpa parameter: PDF berisi SEMUA kategori (halaman dipisah), supaya
     * panitia tidak perlu mengunduh satu per satu.
     */
    public function downloadDaftarUlang(Request $request)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $categoryId = $request->query('category_id') ?: null;

        // Kategori yang dipilih bisa berupa induk (parent_id null) atau tingkat.
        // Induk dipecah jadi semua tingkat di bawahnya supaya ?category_id=<induk>
        // tetap menghasilkan PDF, bukan 404. Query selalu di-scope ke eventner
        // pemilik agar kategori event lain tidak terbaca.
        $categoryIds = null;

        if ($categoryId) {
            $category = $eventner->competitionCategories()->find($categoryId);

            if (!$category) {
                abort(404, 'Kategori lomba tidak ditemukan.');
            }

            $categoryIds = $category->parent_id === null
                ? $eventner->competitionCategories()->where('parent_id', $category->id)->pluck('id')
                : collect([$category->id]);

            if ($categoryIds->isEmpty()) {
                abort(404, 'Kategori lomba tidak ditemukan.');
            }
        }

        $categories = $eventner->competitionCategories()
            ->whereNotNull('parent_id')
            ->with('parent')
            ->when($categoryIds, fn ($q) => $q->whereIn('id', $categoryIds))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if ($categories->isEmpty()) {
            abort(404, 'Kategori lomba tidak ditemukan.');
        }

        // Urutan daftar ulang: nomor tampil (hasil undian) bila sudah ada,
        // sisanya urut nama sekolah — sama dengan urutan di halaman peserta.
        $registrations = Registration::with('participants')
            ->where('eventner_id', $eventner->id)
            ->whereIn('competition_category_id', $categories->pluck('id'))
            ->where('status_berkas', '!=', 'dibatalkan')
            ->orderBy('urutan_tampil')
            ->orderBy('nama_sekolah')
            ->get()
            ->groupBy('competition_category_id');

        ini_set('memory_limit', '512M');

        $filename = $categoryId
            ? 'Daftar_Ulang_' . str_replace(['/', '\\', ' '], '_', $categories->first()->full_name) . '.pdf'
            : 'Daftar_Ulang_Semua_Kategori.pdf';

        return Pdf::loadView('eventner.participant.pdf_daftar_ulang', [
            'eventner' => $eventner,
            'categories' => $categories,
            'registrations' => $registrations,
        ])
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    public function qrCode(Registration $registration)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner || $registration->eventner_id !== $eventner->id) {
            abort(403);
        }

        $qrToken = $registration->qr_token;
        $schoolName = $registration->display_name;
        $category = $registration->competitionCategory?->full_name ?? '-';

        // v6: outputInterface, bukan kunci 'outputType' di array — kunci itu
        // diabaikan diam-diam dan QR keluar sebagai SVG (dompdf tak bisa).
        $options = new QROptions([
            'scale' => 10,
            'imageTransparent' => false,
        ]);
        $options->outputInterface = \chillerlan\QRCode\Output\QRGdImagePNG::class;
        $qrCode = (new QRCode($options))->render($qrToken);

        return view('eventner.participant.print_qr', compact('qrToken', 'schoolName', 'category', 'registration', 'qrCode'));
    }

    public function qrCodeBatch(Request $request)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) abort(403);

        $categoryId = $request->category_id;
        $registrations = Registration::where('eventner_id', $eventner->id)
            ->where('competition_category_id', $categoryId)
            ->get();

        $items = [];
        foreach ($registrations as $reg) {
            $options = new QROptions([
                'scale' => 8,
                'imageTransparent' => false,
            ]);
            $options->outputInterface = \chillerlan\QRCode\Output\QRGdImagePNG::class;
            $items[] = [
                'qrCode' => (new QRCode($options))->render($reg->qr_token),
                'schoolName' => $reg->display_name,
                'category' => $reg->competitionCategory?->full_name ?? '-',
                'qrToken' => $reg->qr_token,
            ];
        }

        ini_set('memory_limit', '512M');

        $filename = 'QR_Peserta.pdf';

        return Pdf::loadView('eventner.participant.print_qr_batch', compact('items'))
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'sans-serif')
            ->download($filename);
    }
}
