<?php

namespace App\Services;

use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\ChampionCategory;
use App\Models\DeductionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\ScoreDeduction;
use Illuminate\Support\Collection;

/**
 * Hitung pemenang (juara) per kategori juara — logika yang sama dengan
 * CertificateController::downloadPdf. Diekstrak supaya bisa dipakai ulang
 * untuk notifikasi FCM tanpa duplikasi.
 */
class ChampionCalculator
{
    /**
     * Peringkat juara. Pool peserta default = SEMUA registrasi event —
     * lintas mata lomba. Kirim $competitionCategoryId untuk scope per mata
     * lomba (konsisten dengan halaman /hasil, /champions, dan unduhan
     * sertifikat eventner, yang selalu menghitung per kategori lomba).
     *
     * Kirim $competitionGroupId untuk memeringkat satu grup saja (juara Grup A
     * terpisah dari juara Grup B). Peserta yang belum bergrup tidak ikut saat
     * filter grup aktif — peringkat gabungan tetap tersedia dengan membiarkan
     * parameter ini null.
     *
     * @return array{0: Eventner, 1: ChampionCategory, 2: array} [eventner, category, winners]
     */
    public function winners(ChampionCategory $championCategory, $competitionCategoryId = null, $competitionGroupId = null): array
    {
        [$eventner, $championCategory, $rankings] = $this->rankings(
            $championCategory,
            $competitionCategoryId,
            $competitionGroupId
        );

        return [$eventner, $championCategory, array_slice($rankings, 0, $championCategory->quantity)];
    }

    /**
     * Daftar peringkat LENGKAP (belum dipotong kuota juara), urut terbaik dulu,
     * sudah bernomor peringkat seri-aware.
     *
     * Dipakai tombol "Loloskan Top-N" untuk babak final: butuh N terbaik tiap
     * grup dari angka yang sama dengan penentu juara, jadi pemanggil tidak
     * boleh menyalin ulang logika sort ini.
     *
     * @return array{0: Eventner, 1: ChampionCategory, 2: array}
     */
    public function rankings(ChampionCategory $championCategory, $competitionCategoryId = null, $competitionGroupId = null, $competitionRoundId = null): array
    {
        // Pemanggil sering hanya memuat rubrik sub-kategori; pastikan relasi
        // yang dipakai resolver bobot juga tersedia (hindari N+1). category
        // ikut dimuat karena resolver menyaring kriteria lewat babak & grup
        // rubrik induknya.
        $championCategory->loadMissing([
            'assessmentSubCategories.criterias',
            'assessmentSubCategories.category',
            'tiebreakSubCategories.criterias',
            'tiebreakSubCategories.category',
            'criterias.subCategory.category',
            'tiebreakCriterias.subCategory.category',
        ]);

        $rankings = $this->rankOrdered(
            $championCategory->eventner,
            $championCategory->scoringCriteriaWeights($competitionRoundId, $competitionGroupId),
            $championCategory->tiebreakCriteriaWeights($competitionRoundId, $competitionGroupId),
            $competitionCategoryId,
            $competitionGroupId,
        );

        $winners = [];
        foreach ($rankings as $row) {
            $title = $championCategory->titleForRank($row['rank']);

            $winners[] = [
                'registration' => $row['registration'],
                'rank' => $row['rank'],
                'title' => $title ?: 'Juara ' . $row['rank'],
                'total' => $row['total'],
            ];
        }

        return [$championCategory->eventner, $championCategory, $winners];
    }

    /**
     * Inti perhitungan: peserta terurut + nomor peringkat seri-aware, tanpa
     * kuota juara dan tanpa gelar.
     *
     * Dipisah dari rankings() supaya jalur "Loloskan Top-N" (yang berjalan
     * tanpa ChampionCategory, hanya dari rubrik babak penyisihan) memakai
     * logika sort yang sama persis — bukan salinannya.
     *
     * @param  array  $scoringWeightMap   [criteria_id => weight] penentu total
     * @param  array  $tiebreakWeightMap  [criteria_id => weight] pemecah seri
     * @return array<int, array{registration: Registration, rank: int, total: int}>
     */
    public function rankOrdered(
        Eventner $eventner,
        array $scoringWeightMap,
        array $tiebreakWeightMap = [],
        $competitionCategoryId = null,
        $competitionGroupId = null,
        $onlyRegistrationIds = null,
    ): array {
        $criteriaMap = $scoringWeightMap;
        $tiebreakCriteriaMap = $tiebreakWeightMap;

        // All criteria weight map (untuk other_total)
        $allCriteriaWeightMap = AssessmentCriteria::whereIn(
            'assessment_sub_category_id',
            AssessmentSubCategory::whereIn(
                'assessment_category_id',
                AssessmentCategory::where('eventner_id', $eventner->id)->pluck('id')
            )->pluck('id')
        )->pluck('weight', 'id')->toArray();

        // Semua registration event ini, scope per mata lomba & grup bila diminta
        $participants = Registration::where('eventner_id', $eventner->id)
            ->when($competitionCategoryId, fn ($q) => $q->where('competition_category_id', $competitionCategoryId))
            ->when($competitionGroupId, fn ($q) => $q->where('competition_group_id', $competitionGroupId))
            ->when($onlyRegistrationIds, fn ($q) => $q->whereIn('id', $onlyRegistrationIds))
            ->with('participants')
            ->orderBy('nama_sekolah')
            ->get();

        $allScores = AssessmentScore::where('eventner_id', $eventner->id)
            ->whereIn('registration_id', $participants->pluck('id'))
            ->get()
            ->groupBy('registration_id');

        $allDeductions = ScoreDeduction::where('eventner_id', $eventner->id)
            ->get()
            ->groupBy('registration_id');

        // Pengurangan ber-scope 'global' hanya berlaku di tingkat lombanya
        // sendiri. Tanpa saringan ini, sanksi siswa tingkat A ikut memotong
        // nilai peserta tingkat B pada pemecah seri.
        $deductionLevelMap = DeductionCategory::levelMapOfCriteria($eventner->id);

        $participantScores = [];
        foreach ($participants as $participant) {
            $scores = $allScores->get($participant->id, collect());

            $total = 0;
            $tiebreakTotal = 0;
            $otherTotal = 0;

            foreach ($scores as $score) {
                $weight = $criteriaMap[$score->assessment_criteria_id] ?? null;
                if ($weight !== null) {
                    $total += (int) $score->score * $weight;
                } else {
                    $weightOther = $allCriteriaWeightMap[$score->assessment_criteria_id] ?? 1;
                    $otherTotal += (int) $score->score * $weightOther;
                }

                $tbWeight = $tiebreakCriteriaMap[$score->assessment_criteria_id] ?? null;
                if ($tbWeight !== null) {
                    $tiebreakTotal += (int) $score->score * $tbWeight;
                }
            }

            $deductions = $allDeductions->get($participant->id, collect());
            $deductions = DeductionCategory::applicableToLevel(
                $deductions,
                $participant->competition_category_id,
                $deductionLevelMap
            );
            $totalDeduction = $deductions->sum(fn ($d) => $d->magnitude);

            $participantScores[] = [
                'participant' => $participant,
                'total' => $total,
                'tiebreak_total' => $tiebreakTotal,
                'other_total' => $otherTotal,
                'deduction' => $totalDeduction,
                'urutan_tampil' => $participant->urutan_tampil ?? 999999,
            ];
        }

        // Sort
        usort($participantScores, function ($a, $b) {
            if ($b['total'] !== $a['total']) return $b['total'] <=> $a['total'];
            if ($b['tiebreak_total'] !== $a['tiebreak_total']) return $b['tiebreak_total'] <=> $a['tiebreak_total'];
            if ($b['other_total'] !== $a['other_total']) return $b['other_total'] <=> $a['other_total'];
            if ($a['deduction'] !== $b['deduction']) return $a['deduction'] <=> $b['deduction'];
            return $a['urutan_tampil'] <=> $b['urutan_tampil'];
        });

        // Peserta tanpa nilai (skor 0) bukan juara — buang SEBELUM peringkat
        // dihitung, kalau tidak mereka menggeser nomor peringkat juara.
        $participantScores = array_values(array_filter(
            $participantScores,
            fn ($ps) => $ps['total'] > 0
        ));

        // Sengaja TIDAK dipotong quantity di sini — pemotongan itu milik
        // winners(). Tombol "Loloskan Top-N" memakai daftar penuh ini supaya
        // bisa mengambil N terbaik per grup dari angka yang sama.

        // Peringkat seri: dua peserta seri kalau SEMUA kunci pengurutnya sama
        // (total, tiebreak, nilai kriteria lain, besar pengurangan) — sama
        // seperti papan skor publik dan Rekap Nilai, yang memakai nilai akhir.
        // Dulu nomor urut array, jadi dua peserta bernilai identik tetap
        // ditulis Juara 1 dan Juara 2. urutan_tampil tidak ikut: itu cuma
        // penentu terakhir supaya urutannya stabil, bukan penentu juara.
        $rows = [];
        $rank = 0;
        $previousKey = null;
        foreach ($participantScores as $index => $ps) {
            $key = [$ps['total'], $ps['tiebreak_total'], $ps['other_total'], $ps['deduction']];
            if ($previousKey !== $key) {
                $rank = $index + 1;
            }
            $previousKey = $key;

            $rows[] = [
                'registration' => $ps['participant'],
                'rank' => $rank,
                'total' => $ps['total'],
            ];
        }

        return $rows;
    }

    /**
     * Satu halaman sertifikat per anggota pasukan (danton + fallback sekolah
     * tanpa data anggota), untuk registrasi yang BUKAN juara — sertifikat
     * diterbitkan sebagai PESERTA.
     *
     * @return array<int, array{registration: Registration, participant: ?\App\Models\Participant, rank: ?int, title: string, total: ?float}>
     */
    public function participantPages(Registration $registration): array
    {
        $registration->loadMissing('participants');

        $pages = [];
        foreach ($registration->participants as $p) {
            $pages[] = [
                'registration' => $registration,
                'participant' => $p,
                'rank' => null,
                'title' => 'PESERTA',
                'total' => null,
            ];
        }

        // Danton juga anggota pasukan
        if ($registration->danton_nama) {
            $pages[] = [
                'registration' => $registration,
                'participant' => new \App\Models\Participant(['nama' => $registration->danton_nama]),
                'rank' => null,
                'title' => 'PESERTA',
                'total' => null,
            ];
        }

        // Fallback: sekolah tanpa data anggota → 1 sertifikat per sekolah
        if (empty($pages)) {
            $pages[] = [
                'registration' => $registration,
                'participant' => null,
                'rank' => null,
                'title' => 'PESERTA',
                'total' => null,
            ];
        }

        return $pages;
    }
}
