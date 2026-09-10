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

        // Peringkat juara dihitung per mata lomba — konsisten dengan
        // /champions web. Pool gabungan lintas mata lomba membuat pasukan
        // sekolah yang sama saling menyalip dan gelar tertukar antar pasukan.
        $competitionCategoryIds = \App\Models\Registration::where('eventner_id', $event->id)
            ->whereNotNull('competition_category_id')
            ->distinct()
            ->pluck('competition_category_id');

        return response()->json([
            'data' => [
                'event' => [
                    'nama_event' => $event->nama_event,
                    'slug' => $event->slug,
                ],
                'champion_categories' => $championCategories->map(function ($cc) use ($calculator, $competitionCategoryIds) {
                    // Hitung pemenang on-the-fly — tidak ada tabel winners tersimpan.
                    // Rank dihitung ulang per mata lomba, lalu digabung.
                    $winners = $competitionCategoryIds
                        ->flatMap(fn ($catId) => $calculator->winners($cc, $catId)[2])
                        ->values()
                        ->all();

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
