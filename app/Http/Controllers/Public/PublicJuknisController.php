<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AssessmentCategory;
use App\Models\Eventner;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PublicJuknisController extends Controller
{
    public function downloadJuknis($slug = null)
    {
        $resolved = app('current_eventner');
        if ($resolved) {
            $eventner = $resolved;
        } else {
            $eventner = Eventner::where('slug', $slug)->firstOrFail();
        }

        $categories = AssessmentCategory::with(['subCategories.criterias'])
            ->where('eventner_id', $eventner->id)
            ->get();

        if ($categories->isEmpty()) {
            abort(404, 'Juknis belum tersedia untuk event ini.');
        }

        $data = [
            'eventner' => $eventner,
            'categories' => $categories,
        ];

        $pdf = Pdf::loadView('livewire.public.partials.pdf.juknis', $data)
            ->setPaper('A4', 'portrait')
            ->setOption('margin-top', '15mm')
            ->setOption('margin-bottom', '15mm')
            ->setOption('margin-left', '12mm')
            ->setOption('margin-right', '12mm');

        $filename = 'Juknis_' . str_replace(['/', '\\', ':', ' '], '-', $eventner->nama_event) . '.pdf';
        return $pdf->download($filename);
    }
}
