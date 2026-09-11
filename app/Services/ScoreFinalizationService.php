<?php

namespace App\Services;

use App\Models\AssessmentCategory;
use App\Models\AssessmentScore;
use App\Models\Judge;
use App\Models\Registration;
use Illuminate\Support\Facades\Log;

/**
 * Finalisasi nilai + notifikasi FCM saat semua juri selesai.
 *
 * Dipakai bersama oleh dashboard panitia (Eventner\Scoring\Index) dan
 * halaman tablet juri (Public\JudgeScoring\Index) supaya aturan validasi
 * dan trigger notifikasi tidak pernah berbeda antar dua jalur input.
 */
class ScoreFinalizationService
{
    /**
     * Kunci nilai satu juri untuk satu registrasi.
     *
     * @param  array  $pendingScores  Nilai di layar yang belum tersimpan
     *                                ([criteria_id => score]) — dipakai dashboard
     *                                panitia yang menyimpan sekaligus saat finalisasi.
     *                                Tablet juri menyimpan tiap ketukan, jadi kosong.
     * @return array{ok: bool, missing: bool, updated: int}
     *         missing = true bila masih ada kriteria yang belum diisi.
     */
    public function finalize(int $eventnerId, int $registrationId, int $judgeId, array $pendingScores = []): array
    {
        $regCategoryId = Registration::where('eventner_id', $eventnerId)
            ->where('id', $registrationId)
            ->value('competition_category_id');

        // Daftar kriteria yang harus terisi untuk juri ini — sama dengan yang
        // dirender di UI: rubrik milik kategori lomba ini + rubrik global (NULL).
        $baseQuery = fn () => AssessmentCategory::with(['subCategories.criterias'])
            ->where('eventner_id', $eventnerId)
            ->where(function ($q) use ($regCategoryId) {
                $q->where('competition_category_id', $regCategoryId)
                  ->orWhereNull('competition_category_id');
            });

        $assessmentCategories = (clone $baseQuery())
            ->whereHas('judges', fn ($q) => $q->where('judges.id', $judgeId))
            ->get();

        if ($assessmentCategories->isEmpty()) {
            $assessmentCategories = $baseQuery()->get();
        }

        // Peta skor: nilai tersimpan di database, ditimpa nilai di layar yang
        // belum tersimpan (dashboard panitia).
        $savedScores = AssessmentScore::where('registration_id', $registrationId)
            ->where('eventner_id', $eventnerId)
            ->where('judge_id', $judgeId)
            ->pluck('score', 'assessment_criteria_id')
            ->all();

        // Union (bukan array_merge!) — key di sini adalah id kriteria (numerik),
        // dan array_merge akan merenumber ulang key numerik sehingga pencarian
        // per kriteria selalu gagal.
        $scores = $pendingScores + $savedScores;

        foreach ($assessmentCategories as $cat) {
            foreach ($cat->subCategories as $sub) {
                foreach ($sub->criterias as $crit) {
                    $value = $scores[$crit->id] ?? null;
                    if ($value === '' || $value === null) {
                        return ['ok' => false, 'missing' => true, 'updated' => 0];
                    }
                }
            }
        }

        $updated = AssessmentScore::where('registration_id', $registrationId)
            ->where('eventner_id', $eventnerId)
            ->where('judge_id', $judgeId)
            ->update(['is_finalized' => true]);

        return ['ok' => true, 'missing' => false, 'updated' => $updated];
    }

    /**
     * Kirim notifikasi "nilai final" bila SEMUA juri yang ditugaskan pada
     * kategori lomba registrasi ini sudah mengunci nilainya.
     */
    public function notifyIfComplete(int $eventnerId, Registration $registration): void
    {
        $category = $registration->competitionCategory;
        if (!$category) {
            return;
        }

        $judgeIds = Judge::where('eventner_id', $eventnerId)
            ->whereHas('assessmentCategories', function ($q) use ($category, $eventnerId) {
                $q->where('assessment_categories.eventner_id', $eventnerId)
                    ->where(function ($sq) use ($category) {
                        $sq->where('assessment_categories.competition_category_id', $category->id)
                           ->orWhereNull('assessment_categories.competition_category_id');
                    });
            })
            ->pluck('judges.id');

        if ($judgeIds->isEmpty()) {
            return;
        }

        $finalizedJudgeIds = AssessmentScore::where('registration_id', $registration->id)
            ->where('eventner_id', $eventnerId)
            ->where('is_finalized', true)
            ->distinct()
            ->pluck('judge_id');

        if ($judgeIds->diff($finalizedJudgeIds)->isNotEmpty()) {
            return;
        }

        try {
            app(\App\Notifications\NilaiFinal::class)->construct($registration)->send();
        } catch (\Throwable $e) {
            Log::warning('FCM nilai_final notification failed', [
                'registration_id' => $registration->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
