<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\RegistrationResource;
use App\Models\Registration;
use App\Models\AssessmentScore;
use App\Models\AssessmentCategory;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    private function getRegistration(Request $request): Registration
    {
        $token = PersonalAccessToken::findToken($request->bearerToken());
        abort_unless($token, 401);
        return Registration::with([
            'eventner',
            'competitionCategory',
            'participants',
            'paymentBankAccount',
            'voteTransactions' => function ($q) {
                $q->where('status', 'PAID');
            },
        ])->findOrFail($token->tokenable_id);
    }

    public function registration(Request $request)
    {
        $reg = $this->getRegistration($request);
        return new RegistrationResource($reg);
    }

    public function update(Request $request)
    {
        $reg = $this->getRegistration($request);

        if ($reg->status_berkas === 'Terverifikasi') {
            return response()->json(['message' => 'Data sudah terverifikasi, tidak bisa diubah.'], 400);
        }

        $request->validate([
            'nama_pelatih' => 'nullable|string|max:255',
            'danton_nama' => 'nullable|string|max:255',
            'danton_nisn' => 'nullable|string|max:20',
        ]);

        if ($request->nama_pelatih) $reg->nama_pelatih = strip_tags($request->nama_pelatih);
        if ($request->danton_nama) $reg->danton_nama = strip_tags($request->danton_nama);
        if ($request->danton_nisn) $reg->danton_nisn = strip_tags($request->danton_nisn);
        $reg->save();

        return response()->json(['message' => 'Data berhasil disimpan.']);
    }

    public function confirm(Request $request)
    {
        $reg = $this->getRegistration($request);

        if ($reg->status_berkas === 'Terverifikasi') {
            return response()->json(['message' => 'Data sudah terverifikasi.'], 400);
        }

        if ($reg->status_berkas === 'booking') {
            if ($reg->eventner->technical_meeting && now()->lt($reg->eventner->technical_meeting)) {
                return response()->json([
                    'message' => 'Konfirmasi bisa dilakukan setelah Technical Meeting.',
                    'technical_meeting' => $reg->eventner->technical_meeting,
                ], 400);
            }
        }

        $reg->status_berkas = 'confirmed';
        $reg->is_finalized = true;
        $reg->save();

        return response()->json(['message' => 'Data berhasil difinalisasi.']);
    }

    public function participants(Request $request)
    {
        $reg = $this->getRegistration($request);
        return response()->json(['data' => $reg->participants]);
    }

    public function scores(Request $request)
    {
        $reg = $this->getRegistration($request);

        // Cek apakah ada score yang sudah difinalisasi
        $hasFinalized = AssessmentScore::where('registration_id', $reg->id)
            ->where('is_finalized', true)
            ->exists();

        if (!$hasFinalized) {
            return response()->json([
                'data' => [
                    'total_skor' => 0,
                    'maks_skor' => 0,
                    'persentase' => 0,
                    'is_finalized' => false,
                    'categories' => [],
                ],
            ]);
        }

        // Ambil semua scores
        $scores = AssessmentScore::where('registration_id', $reg->id)
            ->where('is_finalized', true)
            ->with(['assessmentCriteria.subCategory.category'])
            ->get();

        $categories = AssessmentCategory::where('eventner_id', $reg->eventner_id)
            ->with(['subCategories.criterias'])
            ->get();

        $result = [];
        $totalSkor = 0;
        $totalMaks = 0;

        foreach ($categories as $cat) {
            $subItems = [];
            $catSkor = 0;
            $catMaks = 0;

            foreach ($cat->subCategories as $sub) {
                $criteriaItems = [];
                $subSkor = 0;
                $subMaks = 0;

                foreach ($sub->criterias as $criteria) {
                    $scoreRecord = $scores->firstWhere('assessment_criteria_id', $criteria->id);
                    $nilai = $scoreRecord ? (float) $scoreRecord->score : 0;

                    // Hitung maks dari score_options
                    $options = $criteria->score_options ?? [];
                    $maxScore = 0;
                    foreach ($options as $opt) {
                        $val = is_array($opt) ? (int) ($opt['score'] ?? 0) : (int) $opt;
                        if ($val > $maxScore) $maxScore = $val;
                    }
                    if ($maxScore === 0) $maxScore = 100;

                    $bobot = (float) ($criteria->weight ?? 1);

                    $criteriaItems[] = [
                        'nama' => $criteria->name,
                        'skor' => $nilai,
                        'maks' => $maxScore,
                        'bobot' => $bobot,
                    ];

                    $subSkor += $nilai * $bobot;
                    $subMaks += $maxScore * $bobot;
                }

                $subItems[] = [
                    'nama' => $sub->name,
                    'skor' => round($subSkor, 2),
                    'maks' => round($subMaks, 2),
                    'criterias' => $criteriaItems,
                ];

                $catSkor += $subSkor;
                $catMaks += $subMaks;
            }

            $result[] = [
                'nama' => $cat->name,
                'skor' => round($catSkor, 2),
                'maks' => round($catMaks, 2),
                'persentase' => $catMaks > 0 ? round(($catSkor / $catMaks) * 100, 1) : 0,
                'sub_categories' => $subItems,
            ];

            $totalSkor += $catSkor;
            $totalMaks += $catMaks;
        }

        return response()->json([
            'data' => [
                'total_skor' => round($totalSkor, 2),
                'maks_skor' => round($totalMaks, 2),
                'persentase' => $totalMaks > 0 ? round(($totalSkor / $totalMaks) * 100, 1) : 0,
                'is_finalized' => true,
                'categories' => $result,
            ],
        ]);
    }

    public function ranking(Request $request)
    {
        $reg = $this->getRegistration($request);

        // Cari semua peserta di kategori yang sama
        $allRegs = Registration::where('competition_category_id', $reg->competition_category_id)
            ->whereIn('status_berkas', ['confirmed', 'Terverifikasi'])
            ->pluck('id');

        // Hitung total score masing-masing
        $rankings = [];
        foreach ($allRegs as $rid) {
            $totalScore = AssessmentScore::where('registration_id', $rid)
                ->where('is_finalized', true)
                ->sum('score');

            if ($totalScore > 0) {
                $nama = Registration::find($rid)?->nama_sekolah ?? 'Unknown';
                $rankings[] = [
                    'id' => $rid,
                    'nama_sekolah' => $nama,
                    'total_skor' => (float) $totalScore,
                ];
            }
        }

        // Sort descending
        usort($rankings, fn($a, $b) => $b['total_skor'] <=> $a['total_skor']);

        // Cari posisi user
        $posisi = null;
        foreach ($rankings as $i => $r) {
            if ($r['id'] === $reg->id) {
                $posisi = $i + 1;
                $rankings[$i]['is_me'] = true;
            } else {
                $rankings[$i]['is_me'] = false;
            }
        }

        // Hanya return top 10 + highlight user
        $top10 = array_slice($rankings, 0, 10);
        $userData = collect($rankings)->firstWhere('id', $reg->id);

        return response()->json([
            'data' => [
                'posisi' => $posisi,
                'total_peserta' => count($rankings),
                'total_skor_saya' => $userData['total_skor'] ?? 0,
                'ranking' => array_values($top10),
            ],
        ]);
    }

    public function ticket(Request $request)
    {
        $reg = $this->getRegistration($request);

        return response()->json([
            'data' => [
                'nama_sekolah' => $reg->nama_sekolah,
                'label_pasukan' => $reg->label_pasukan,
                'kategori' => $reg->competitionCategory?->full_name,
                'event' => $reg->eventner->nama_event,
                'venue' => $reg->eventner->venue,
                'tanggal' => $reg->eventner->tanggal,
                'logo_event' => $reg->eventner->logo_event ? asset('storage/' . $reg->eventner->logo_event) : null,
                'magic_token' => $reg->magic_token,
                'qr_token' => $reg->qr_token,
                'status_berkas' => $reg->status_berkas,
            ],
        ]);
    }
}
