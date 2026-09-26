<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
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

        // Grup dari query wajib milik tingkat ini — kalau tidak, cetakan bisa
        // menarik peserta tingkat lain.
        $groupId = $request->query('competition_group_id');
        $group = null;

        if ($groupId) {
            $group = CompetitionGroup::where('eventner_id', $eventner->id)
                ->where('competition_category_id', $categoryId)
                ->findOrFail($groupId);
        }

        $results = Registration::where('eventner_id', $eventner->id)
            ->where('competition_category_id', $categoryId)
            ->when($group, fn ($q) => $q->where('competition_group_id', $group->id))
            ->whereNotNull('urutan_tampil')
            ->orderBy('urutan_tampil')
            ->get();

        return view('eventner.drawing.print_results', [
            'eventner' => $eventner,
            'category' => $category,
            'group' => $group,
            'results' => $results,
        ]);
    }
}
