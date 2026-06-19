<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

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
            abort(403, 'Formulir hanya dapat diunduh jika status pendaftaran telah Terverifikasi.');
        }

        // Eager load data
        $registration->load(['participants', 'competitionCategory', 'eventner']);

        $data = [
            'eventner' => $eventner,
            'registration' => $registration,
            'participants' => $registration->participants,
        ];

        $pdf = Pdf::loadView('eventner.participant.pdf_formulir', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', '15mm')
            ->setOption('margin-bottom', '15mm')
            ->setOption('margin-left', '10mm')
            ->setOption('margin-right', '10mm');

        $filename = 'Formulir_' . str_replace(['/', '\\', ' '], '_', $registration->nama_sekolah) . '.pdf';
        return $pdf->download($filename);
    }
}
