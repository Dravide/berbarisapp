<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Eventner;
use App\Models\ChampionCategory;
use App\Services\ChampionCalculator;

class ChampionController extends Controller
{
    public function index($scoringCode)
    {
        $event = Eventner::where('scoring_code', $scoringCode)->firstOrFail();

        // Hanya kategori juara yang ditandai publik tampil di mobile.
        $championCategories = ChampionCategory::where('eventner_id', $event->id)
            ->where('is_public', true)
            ->with(['assessmentSubCategories', 'rankTitles'])
            ->get();

        $calculator = app(ChampionCalculator::class);

        return response()->json([
            'data' => [
                'event' => [
                    'nama_event' => $event->nama_event,
                    'slug' => $event->slug,
                ],
                'champion_categories' => $championCategories->map(function ($cc) use ($calculator) {
                    // Hitung pemenang on-the-fly — tidak ada tabel winners tersimpan.
                    $winners = $calculator->winners($cc)[2];

                    return [
                        'id' => $cc->id,
                        'name' => $cc->name,
                        'winners' => collect($winners)->map(fn ($w) => [
                            'rank' => $w['rank'],
                            'title' => $w['title'],
                            'nama_sekolah' => $w['registration']?->nama_sekolah,
                            'display_name' => $w['registration']?->display_name,
                            'logo_sekolah' => $w['registration']?->logo_sekolah
                                ? asset('storage/' . $w['registration']->logo_sekolah)
                                : null,
                        ]),
                    ];
                })->values(),
            ],
        ]);
    }
}
