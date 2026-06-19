<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use App\Models\CompetitionCategory;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DrawingController extends Controller
{
    public function print(Request $request)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $categoryId = $request->query('competition_category_id');
        if (!$categoryId) {
            abort(400, 'ID Kategori Lomba diperlukan.');
        }

        $category = CompetitionCategory::where('eventner_id', $eventner->id)->findOrFail($categoryId);

        $results = Registration::where('eventner_id', $eventner->id)
            ->where('competition_category_id', $categoryId)
            ->whereNotNull('urutan_tampil')
            ->orderBy('urutan_tampil')
            ->get();

        return view('eventner.drawing.print_results', [
            'eventner' => $eventner,
            'category' => $category,
            'results' => $results,
        ]);
    }
}
