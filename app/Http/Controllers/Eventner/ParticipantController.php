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

    private function renderFormulir(Registration $registration)
    {
        ini_set('memory_limit', '512M');

        $eventner = $registration->eventner;
        $filename = 'Formulir_' . $registration->nama_sekolah . '.pdf';

        return Pdf::loadView('eventner.participant.pdf_formulir', [
            'eventner' => $eventner,
            'registration' => $registration,
            'participants' => $registration->participants,
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
        $schoolName = $registration->nama_sekolah;
        $category = $registration->competitionCategory?->full_name ?? '-';

        $options = new QROptions([
            'outputType' => \chillerlan\QRCode\Output\QRGdImagePNG::class,
            'scale' => 10,
            'imageTransparent' => false,
        ]);
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
                'outputType' => \chillerlan\QRCode\Output\QRGdImagePNG::class,
                'scale' => 8,
                'imageTransparent' => false,
            ]);
            $items[] = [
                'qrCode' => (new QRCode($options))->render($reg->qr_token),
                'schoolName' => $reg->nama_sekolah,
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
