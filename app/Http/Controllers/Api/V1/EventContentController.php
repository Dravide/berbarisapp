<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EventFaq;
use App\Models\EventGallery;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\Sponsor;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Konten read-only milik sebuah event (gallery, faq, sponsor, tenant, juknis, hasil undian).
 * Semua query di-scope per eventner + filter status approved.
 */
class EventContentController extends Controller
{
    private function event(Request $request): Eventner
    {
        return Eventner::approved()
            ->where('slug', $request->route('slug'))
            ->firstOrFail();
    }

    public function gallery(Request $request, $slug)
    {
        $event = $this->event($request);

        $gallery = EventGallery::where('eventner_id', $event->id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'image' => asset('storage/' . $item->image),
                'caption' => $item->caption,
                'sort_order' => $item->sort_order,
            ]);

        return response()->json(['data' => $gallery]);
    }

    public function faq(Request $request, $slug)
    {
        $event = $this->event($request);

        $faqs = EventFaq::where('eventner_id', $event->id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'question' => $item->question,
                'answer' => $item->answer,
            ]);

        return response()->json(['data' => $faqs]);
    }

    public function sponsors(Request $request, $slug)
    {
        $event = $this->event($request);

        $sponsors = Sponsor::where('eventner_id', $event->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'logo' => $item->logo ? asset('storage/' . $item->logo) : null,
                'link' => $item->link,
                'type' => $item->type,
            ]);

        return response()->json(['data' => $sponsors]);
    }

    public function tenants(Request $request, $slug)
    {
        $event = $this->event($request);

        $tenants = Tenant::where('eventner_id', $event->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'logo' => $item->logo ? asset('storage/' . $item->logo) : null,
                'description' => $item->description,
                'type' => $item->type,
            ]);

        return response()->json(['data' => $tenants]);
    }

    public function juknis(Request $request, $slug)
    {
        $event = $this->event($request);

        $categories = \App\Models\AssessmentCategory::with(['subCategories.criterias'])
            ->where('eventner_id', $event->id)
            ->get();

        if ($categories->isEmpty()) {
            return response()->json(['message' => 'Juknis belum tersedia untuk event ini.'], 404);
        }

        $pdf = Pdf::loadView('livewire.public.partials.pdf.juknis', [
            'eventner' => $event,
            'categories' => $categories,
        ])->setPaper('A4', 'portrait')
            ->setOption('margin-top', '15mm')
            ->setOption('margin-bottom', '15mm')
            ->setOption('margin-left', '12mm')
            ->setOption('margin-right', '12mm');

        $filename = 'Juknis_' . str_replace(['/', '\\', ':', ' '], '-', $event->nama_event) . '.pdf';

        return response()->streamDownload(
            fn () => print($pdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    public function drawingResults(Request $request, $slug)
    {
        $event = $this->event($request);

        $categories = $event->competitionCategories()
            ->whereNotNull('parent_id')
            ->with('parent')
            ->orderBy('sort_order')
            ->get();

        $data = $categories->map(function ($category) use ($event) {
            $results = Registration::where('eventner_id', $event->id)
                ->where('competition_category_id', $category->id)
                ->whereNotNull('urutan_tampil')
                ->orderBy('urutan_tampil')
                ->get()
                ->map(fn ($reg, $i) => [
                    'urutan' => $reg->urutan_tampil,
                    'nama_sekolah' => $reg->nama_sekolah,
                    'label_pasukan' => $reg->label_pasukan,
                ]);

            return [
                'category_id' => $category->id,
                'name' => $category->full_name,
                'total_peserta' => count($results),
                'results' => $results,
            ];
        });

        return response()->json(['data' => $data]);
    }
}
