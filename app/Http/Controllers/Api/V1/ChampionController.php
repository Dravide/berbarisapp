<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Eventner;
use App\Models\ChampionCategory;
use Illuminate\Http\Request;

class ChampionController extends Controller
{
    public function index($scoringCode)
    {
        $event = Eventner::where('scoring_code', $scoringCode)->firstOrFail();

        $categories = $event->competitionCategories()
            ->whereNotNull('parent_id')
            ->with(['parent'])
            ->get();

        $championCategories = ChampionCategory::where('eventner_id', $event->id)
            ->with(['winners.registration.participants'])
            ->get();

        return response()->json([
            'data' => [
                'event' => [
                    'nama_event' => $event->nama_event,
                    'slug' => $event->slug,
                ],
                'champion_categories' => $championCategories->map(fn ($cc) => [
                    'id' => $cc->id,
                    'name' => $cc->name,
                    'winners' => $cc->winners->map(fn ($w) => [
                        'rank' => $w->rank,
                        'title' => $w->title,
                        'nama_sekolah' => $w->registration?->nama_sekolah,
                        'display_name' => $w->registration?->display_name,
                        'logo_sekolah' => $w->registration?->logo_sekolah
                            ? asset('storage/' . $w->registration->logo_sekolah)
                            : null,
                    ]),
                ]),
            ],
        ]);
    }
}
