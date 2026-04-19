<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AssessmentCategory;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class FormatNilaiController extends Controller
{
    public function downloadPdf()
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $categories = AssessmentCategory::with(['subCategories.criterias'])
                ->where('eventner_id', $eventner->id)
                ->get();

        $data = [
            'eventner' => $eventner,
            'categories' => $categories
        ];

        $pdf = Pdf::loadView('eventner.format-nilai.pdf_rubrik', $data);
        return $pdf->download('Format_Penilaian_Event.pdf');
    }
}
