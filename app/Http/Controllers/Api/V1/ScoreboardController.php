<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\AssessmentScore;
use Illuminate\Http\Request;

class ScoreboardController extends Controller
{
    public function index($scoringCode)
    {
        $event = Eventner::where('scoring_code', $scoringCode)->firstOrFail();

        $categories = $event->competitionCategories()
            ->whereNotNull('parent_id')
            ->with(['parent'])
            ->get();

        return response()->json([
            'data' => [
                'event' => [
                    'nama_event' => $event->nama_event,
                    'slug' => $event->slug,
                ],
                'categories' => $categories->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->full_name,
                ]),
            ],
        ]);
    }

    public function byCategory($scoringCode, $categoryId)
    {
        $event = Eventner::where('scoring_code', $scoringCode)->firstOrFail();

        $registrations = Registration::where('competition_category_id', $categoryId)
            ->where('eventner_id', $event->id)
            ->whereIn('status_berkas', ['confirmed', 'Terverifikasi'])
            ->withSum(['voteTransactions as total_votes' => fn($q) => $q->where('status', 'PAID')], 'votes_earned')
            ->get();

        $rankings = [];
        foreach ($registrations as $reg) {
            $totalScore = AssessmentScore::where('registration_id', $reg->id)
                ->where('is_finalized', true)
                ->sum('score');

            $rankings[] = [
                'id' => $reg->id,
                'nama_sekolah' => $reg->nama_sekolah,
                'logo_sekolah' => $reg->logo_sekolah ? asset('storage/' . $reg->logo_sekolah) : null,
                'total_skor' => (float) $totalScore,
                'total_votes' => (int) ($reg->total_votes ?? 0),
            ];
        }

        usort($rankings, fn($a, $b) => $b['total_skor'] <=> $a['total_skor']);

        // Tambah ranking
        $rankings = array_map(fn($r, $i) => array_merge($r, ['ranking' => $i + 1]), $rankings, array_keys($rankings));

        $category = $event->competitionCategories()->find($categoryId);

        return response()->json([
            'data' => [
                'category' => $category?->full_name ?? 'Unknown',
                'rankings' => array_values($rankings),
            ],
        ]);
    }
}
