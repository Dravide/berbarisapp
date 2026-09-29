<?php

namespace App\Services;

use App\Models\AssessmentCategory;
use App\Models\AssessmentScore;
use App\Models\CompetitionGroup;
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
        $seriesId = $registration?->competition_series_id;

        $assessmentCategories = $this->rubricsForEntry($eventnerId, $regCategoryId, $seriesId, $roundId, $judgeId);

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
     * Rubrik yang berlaku untuk satu juri pada satu peserta.
     *
     * Dipakai bersama finalize() dan unfinalize() supaya keduanya tak pernah
     * melihat daftar kriteria yang berbeda. Kalau kunci memakai satu daftar
     * dan buka-kunci memakai daftar lain, sisa baris yang tetap terkunci akan
     * membuat tombol Buka Kunci tampak tidak bekerja tanpa pesan kesalahan.
     *
     * Aturan "rubrik kosong → semua rubrik tingkat" tetap dipertahankan: juri
     * yang belum ditugaskan ke rubrik mana pun dinilai memakai seluruh rubrik
     * tingkat, sama seperti yang dirender di UI.
     */
    private function rubricsForEntry(int $eventnerId, ?int $regCategoryId, ?int $seriesId, ?int $roundId, int $judgeId): \Illuminate\Support\Collection
    {
        $baseQuery = fn () => AssessmentCategory::with(['subCategories.criterias'])
            ->where('eventner_id', $eventnerId)
            ->forEntry($regCategoryId, $seriesId, $roundId);

        $assessmentCategories = (clone $baseQuery())
            ->whereHas('judges', fn ($q) => $q->where('judges.id', $judgeId))
            ->get();

        return $assessmentCategories->isEmpty() ? $baseQuery()->get() : $assessmentCategories;
    }

    /**
     * Buka kunci nilai satu juri untuk satu registrasi.
     *
     * Wajib memakai resolusi kriteria yang SAMA dengan finalize(): rubrik
     * tanpa babak ikut dikunci finalize() lewat forEntry() (scopeForLevel()
     * menyertakan baris competition_round_id NULL), jadi membukanya dengan
     * daftar kriteria yang lebih sempit — misalnya hanya kriteria yang
     * menempel persis ke babak terpilih — meninggalkan baris terkunci.
     * Akibatnya hasFinalizedScores() tetap true dan tombol Buka Kunci tampak
     * tidak bekerja, tanpa satu pun pesan kesalahan.
     *
     * @param  int|null  $roundId  Babak yang dibuka. Null = tingkat tanpa babak.
     * @return int jumlah baris yang dibuka (0 = tidak ada yang terkunci)
     */
    public function unfinalize(int $eventnerId, int $registrationId, int $judgeId, ?int $roundId = null): int
    {
        $registration = Registration::where('eventner_id', $eventnerId)->find($registrationId);

        if (! $registration) {
            return 0;
        }

        $assessmentCategories = $this->rubricsForEntry(
            $eventnerId,
            $registration->competition_category_id,
            $registration->competition_series_id,
            $roundId,
            $judgeId,
        );

        $criteriaIds = [];

        foreach ($assessmentCategories as $cat) {
            foreach ($cat->subCategories as $sub) {
                foreach ($sub->criterias as $crit) {
                    $criteriaIds[] = $crit->id;
                }
            }
        }

        // Tanpa daftar kriteria, jangan jatuh ke UPDATE tanpa penyaring: itu
        // membuka SEMUA babak sekaligus, kebalikan dari yang diminta di sini.
        if (! count($criteriaIds)) {
            return 0;
        }

        return AssessmentScore::where('registration_id', $registrationId)
            ->where('eventner_id', $eventnerId)
            ->where('judge_id', $judgeId)
            ->where('is_finalized', true)
            ->whereIn('assessment_criteria_id', $criteriaIds)
            ->update(['is_finalized' => false]);
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

        // Juri wajib dihitung dari PENUGASAN, bukan dari rubrik: penugasan
        // menempel di grup peserta (atau baris final/ungrouped/level-nya), dan
        // rubrik cuma menentukan lembar mana yang terbuka. Menghitungnya dari
        // rubrik membuat nota "nilai selesai" tak pernah terkirim, karena tak
        // ada lagi rubrik bergrup yang menunjuk juri.
        $judgeIds = CompetitionGroup::judgesForRegistration($registration, $roundId)->pluck('id');

        if ($judgeIds->isEmpty()) {
            return;
        }

        $finalizedJudgeIds = AssessmentScore::where('registration_id', $registration->id)
            ->where('eventner_id', $eventnerId)
            ->where('is_finalized', true)
            ->when($roundId, function ($q) use ($eventnerId, $category, $registration, $roundId) {
                // Kriteria babak ini — memakai scope yang sama dengan finalize()
                // supaya rubrik tanpa babak (berlaku semua babak) ikut terhitung,
                // tidak cuma rubrik yang eksplisit menempel ke babak ini.
                $criteriaIds = AssessmentCategory::where('eventner_id', $eventnerId)
                    ->forEntry($category->id, $registration->competition_series_id, $roundId)
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
