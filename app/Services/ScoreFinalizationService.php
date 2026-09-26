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
     * @param  int|null  $roundId  Babak yang sedang dinilai. WAJIB diisi kalau
     *                             tingkat lomba punya beberapa babak: tanpa ini
     *                             finalisasi penyisihan ikut mengunci nilai babak
     *                             final (satu UPDATE per registrasi+juri, tanpa
     *                             penyaring kriteria).
     * @return array{ok: bool, missing: bool, updated: int}
     *         missing = true bila masih ada kriteria yang belum diisi.
     */
    public function finalize(int $eventnerId, int $registrationId, int $judgeId, array $pendingScores = [], ?int $roundId = null): array
    {
        $registration = Registration::where('eventner_id', $eventnerId)->find($registrationId);
        $regCategoryId = $registration?->competition_category_id;
        $groupId = $registration?->competition_group_id;

        // Daftar kriteria yang harus terisi untuk juri ini — sama dengan yang
        // dirender di UI: rubrik tingkat ini (+ grup peserta, + babak yang
        // sedang dinilai) digabung dengan rubrik global (NULL).
        $baseQuery = fn () => AssessmentCategory::with(['subCategories.criterias'])
            ->where('eventner_id', $eventnerId)
            ->forEntry($regCategoryId, $groupId, $roundId);

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

        $criteriaIds = [];

        foreach ($assessmentCategories as $cat) {
            foreach ($cat->subCategories as $sub) {
                foreach ($sub->criterias as $crit) {
                    $criteriaIds[] = $crit->id;

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
            // Dibatasi ke kriteria babak ini saja (kalau tidak, satu UPDATE
            // mengunci nilai babak lain yang belum digelar).
            ->when(count($criteriaIds), fn ($q) => $q->whereIn('assessment_criteria_id', $criteriaIds))
            ->update(['is_finalized' => true]);

        return ['ok' => true, 'missing' => false, 'updated' => $updated];
    }

    /**
     * Kirim notifikasi "nilai final" bila SEMUA juri yang ditugaskan pada
     * kategori lomba registrasi ini sudah mengunci nilainya.
     */
    public function notifyIfComplete(int $eventnerId, Registration $registration, ?int $roundId = null): void
    {
        $category = $registration->competitionCategory;
        if (!$category) {
            return;
        }

        $groupId = $registration->competition_group_id;

        // Juri wajib dihitung per babak & grup peserta: kalau tidak, nota
        // "nilai selesai" terkirim padahal juri babak final belum menilai.
        $judgeIds = Judge::where('eventner_id', $eventnerId)
            ->whereHas('assessmentCategories', function ($q) use ($eventnerId, $category, $groupId, $roundId) {
                $q->where('assessment_categories.eventner_id', $eventnerId)
                    ->forEntry($category->id, $groupId, $roundId);
            })
            ->pluck('judges.id');

        if ($judgeIds->isEmpty()) {
            return;
        }

        $finalizedJudgeIds = AssessmentScore::where('registration_id', $registration->id)
            ->where('eventner_id', $eventnerId)
            ->where('is_finalized', true)
            ->when($roundId, function ($q) use ($eventnerId, $category, $groupId, $roundId) {
                // Kriteria babak ini — memakai scope yang sama dengan finalize()
                // supaya rubrik tanpa babak (berlaku semua babak) ikut terhitung,
                // tidak cuma rubrik yang eksplisit menempel ke babak ini.
                $criteriaIds = AssessmentCategory::where('eventner_id', $eventnerId)
                    ->forEntry($category->id, $groupId, $roundId)
                    ->with('subCategories.criterias')
                    ->get()
                    ->flatMap(fn ($cat) => $cat->subCategories->flatMap(fn ($sub) => $sub->criterias->pluck('id')))
                    ->all();

                $q->whereIn('assessment_criteria_id', $criteriaIds);
            })
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
