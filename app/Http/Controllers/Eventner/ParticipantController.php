<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        // Eager load data
        $registration->load(['participants', 'competitionCategory', 'eventner']);

        return view('eventner.participant.print_formulir', [
            'eventner' => $eventner,
            'registration' => $registration,
            'participants' => $registration->participants,
        ]);
    }
}
