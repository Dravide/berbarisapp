<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use App\Models\AssessmentCategory;
use App\Models\AssessmentScore;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\CompetitionRoundRegistration;
use App\Models\DeductionCategory;
use App\Models\Judge;
use App\Models\Registration;
use App\Models\ScoreDeduction;
use App\Services\ScoreRecapBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class ScoringController extends Controller
{
    /**
     * Peta bobot kriteria => weight, dari kategori penilaian yang sudah
     * ter-load. Dipakai agar penjumlahan nilai di rekap mengikuti bobot —
     * sama seperti ScoreRecap dan papan skor publik.
     *
     * @param  \Illuminate\Support\Collection  $assessmentCategories
     * @return array<int, int|float>
     */
    private function criteriaWeights($assessmentCategories): array
    {
        $map = [];
        foreach ($assessmentCategories as $cat) {
            foreach ($cat->subCategories as $sub) {
                foreach ($sub->criterias as $crit) {
                    $map[$crit->id] = $crit->weight ?? 1;
                }
            }
        }

        return $map;
    }

    public function downloadCsv(Request $request)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $categoryId = $request->query('category_id');

        // Get assessment categories for this event — jika rekap per kategori lomba,
        // hanya format yang terikat kategori itu + yang umum (NULL).
        $assessmentCategories = AssessmentCategory::with(['subCategories.criterias'])
            ->where('eventner_id', $eventner->id)
            ->when($categoryId, function ($q) use ($categoryId) {
                $q->where(function ($sq) use ($categoryId) {
                    $sq->where('competition_category_id', $categoryId)
                       ->orWhereNull('competition_category_id');
                });
            })
            ->get();

        // Bobot kriteria per id — nilai harus dikalikan bobot saat dijumlah,
        // sama seperti rekap panitia dan papan skor publik. Dulu CSV/PDF
        // menjumlah mentah, jadi kriteria berbobot 2 tertulis separuh dari
        // nilai yang dipakai menentukan juara.
        $criteriaWeights = $this->criteriaWeights($assessmentCategories);

        // Get participants - filtered by competition category if specified
        $participantsQuery = Registration::where('eventner_id', $eventner->id);
        $competitionCategory = null;

        if ($categoryId) {
            $competitionCategory = CompetitionCategory::find($categoryId);
            $participantsQuery->where('competition_category_id', $categoryId);
        }

        $participants = $participantsQuery->orderBy('nama_sekolah')->get();

        // Fetch all scores for these participants in one query
        $allScores = AssessmentScore::where('eventner_id', $eventner->id)
            ->whereIn('registration_id', $participants->pluck('id'))
            ->get()
            ->groupBy('registration_id');

        // Petakan deduction_criteria_id => assessment_category_id (hanya yang menempel
        // kategori penilaian yang ikut rekap ini). Pengurangan dari format nilai
        // kategori lain dibiarkan di luar — kalau ikut dijumlah, kolom
        // "Pengurangan" tidak lagi cocok dengan selisih kolom kategorinya.
        $assessmentCategoryIds = $assessmentCategories->pluck('id');

        $deductionCats = DeductionCategory::with('criterias')
            ->where('eventner_id', $eventner->id)
            ->category()
            ->whereIn('assessment_category_id', $assessmentCategoryIds)
            ->get();
        $critToAssessment = [];
        foreach ($deductionCats as $dc) {
            foreach ($dc->criterias as $c) {
                $critToAssessment[$c->id] = $dc->assessment_category_id;
            }
        }

        // Pengurangan tingkat: tidak menempel pada kolom kategori mana pun,
        // tetapi hanya milik tingkat lomba yang sedang direkap. Tanpa saringan
        // ini, sanksi tingkat lain ikut terpotong dari peserta di sini.
        $levelDeductionCriteriaIds = DeductionCategory::with('criterias')
            ->where('eventner_id', $eventner->id)
            ->global()
            ->forLevel($categoryId)
            ->get()
            ->flatMap->criterias
            ->pluck('id')
            ->flip()
            ->toArray();
        $allDeductions = ScoreDeduction::where('eventner_id', $eventner->id)
            ->whereIn('registration_id', $participants->pluck('id'))
            ->get()
            ->groupBy('registration_id');

        // Build scoring data per participant
        $scoringData = [];
        foreach ($participants as $participant) {
            $participantScores = $allScores->get($participant->id, collect());

            // Sum scores per criteria across all judges
            $criteriaTotals = [];
            foreach ($participantScores as $score) {
                $cid = $score->assessment_criteria_id;
                $criteriaTotals[$cid] = ($criteriaTotals[$cid] ?? 0) + \App\Support\ScoreOptions::value($score->score);
            }

            $categoryTotals = [];
            $grandTotal = 0;
            foreach ($assessmentCategories as $cat) {
                $catTotal = 0;
                foreach ($cat->subCategories as $sub) {
                    foreach ($sub->criterias as $crit) {
                        $catTotal += ($criteriaTotals[$crit->id] ?? 0) * ($criteriaWeights[$crit->id] ?? 1);
                    }
                }
                $categoryTotals[$cat->id] = $catTotal;
                $grandTotal += $catTotal;
            }

            // Pengurangan per kategori — magnitude-nya positif, dikurangkan
            // dari total. Tanda di DB tidak dipercaya (bisa -5 maupun 5).
            $participantDeductions = $allDeductions->get($participant->id, collect());
            $deductionByCat = [];
            $totalDeduction = 0;
            foreach ($participantDeductions as $d) {
                // Pengurangan tingkat tidak mengisi kolom kategori mana pun —
                // nilainya hanya masuk total.
                if (isset($levelDeductionCriteriaIds[$d->deduction_criteria_id])) {
                    $totalDeduction -= $d->magnitude;

                    continue;
                }

                $aid = $critToAssessment[$d->deduction_criteria_id] ?? null;
                if ($aid !== null) {
                    $amt = $d->magnitude;
                    $deductionByCat[$aid] = ($deductionByCat[$aid] ?? 0) - $amt;
                    $totalDeduction -= $amt;
                }
            }
            $finalScore = $grandTotal + $totalDeduction;

            $scoringData[] = [
                'participant' => $participant,
                'criteriaTotals' => $criteriaTotals,
                'categoryTotals' => $categoryTotals,
                'categoryDeductions' => $deductionByCat,
                'grandTotal' => $grandTotal,
                'totalDeduction' => $totalDeduction,
                'finalScore' => $finalScore,
            ];
        }

        // Sort by final score descending (ranking)
        usort($scoringData, fn($a, $b) => $b['finalScore'] <=> $a['finalScore']);

        // Peringkat nilai sama: nilai akhir sama berarti peringkat sama, dan
        // peringkat berikutnya melompat — sama seperti halaman Rekap Nilai
        // dan papan skor publik. Dulu kolom Rank cuma nomor urut baris.
        $rank = 1;
        $previousScore = null;
        foreach ($scoringData as $index => &$row) {
            if ($previousScore !== null && $row['finalScore'] < $previousScore) {
                $rank = $index + 1;
            }
            $row['rank'] = $rank;
            $previousScore = $row['finalScore'];
        }
        unset($row);

        $data = [
            'eventner' => $eventner,
            'assessmentCategories' => $assessmentCategories,
            'scoringData' => $scoringData,
            'competitionCategory' => $competitionCategory,
        ];

        $categoryName = $competitionCategory ? str_replace(['/', '\\'], '-', $competitionCategory->name) : 'Semua';
        $filename = 'Rekap_Nilai_' . $categoryName . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($assessmentCategories, $scoringData, $eventner, $competitionCategory) {
            $file = fopen('php://output', 'w');
            // BOM for Excel UTF-8
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Title row
            fputcsv($file, [$eventner->nama_event . ' — ' . $eventner->diselenggarakan_oleh]);
            fputcsv($file, ['Rekap Penilaian — ' . ($competitionCategory ? $competitionCategory->name : 'Semua Kategori')]);
            fputcsv($file, ['Dicetak: ' . now()->translatedFormat('d F Y H:i')]);
            fputcsv($file, []);

            // Build header rows
            $row1 = ['No', 'Peserta', 'Pelatih'];
            $row2 = ['', '', ''];
            foreach ($assessmentCategories as $cat) {
                $criteriaCount = $cat->subCategories->sum(fn($s) => $s->criterias->count());
                $row1[] = $cat->name;
                for ($i = 1; $i < $criteriaCount; $i++) $row1[] = '';
                $row1[] = 'Sub ' . $cat->name;
                foreach ($cat->subCategories as $sub) {
                    foreach ($sub->criterias as $crit) {
                        $row2[] = $sub->name . ' - ' . $crit->name;
                    }
                }
                $row2[] = 'Subtotal';
            }
            $row1[] = 'Total';
            $row2[] = '';
            $row1[] = 'Pengurangan';
            $row2[] = '';
            $row1[] = 'Nilai Akhir';
            $row2[] = '';
            $row1[] = 'Rank';
            $row2[] = '';

            fputcsv($file, $row1);
            fputcsv($file, $row2);

            // Data rows
            foreach ($scoringData as $index => $data) {
                $row = [
                    $index + 1,
                    $data['participant']->display_name,
                    $data['participant']->nama_pelatih,
                ];
                foreach ($assessmentCategories as $cat) {
                    foreach ($cat->subCategories as $sub) {
                        foreach ($sub->criterias as $crit) {
                            $row[] = $data['criteriaTotals'][$crit->id] ?? '-';
                        }
                    }
                    $row[] = $data['categoryTotals'][$cat->id] ?? 0;
                }
                $row[] = $data['grandTotal'];
                $row[] = $data['totalDeduction'] != 0 ? $data['totalDeduction'] : 0;
                $row[] = $data['finalScore'];
                $row[] = $data['rank'];
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Babak yang dipakai lembar peserta ini.
     *
     * Nilai penyisihan dan final tinggal di baris kriteria yang berbeda, jadi
     * satu lembar hanya bisa mewakili satu babak — menampilkan keduanya
     * mencampur nilai yang memang tidak pernah dijumlahkan.
     *
     * Tanpa babak yang diminta, peserta yang sudah terdaftar sebagai finalis
     * dicetak dengan lembar FINAL-nya; sisanya memakai babak penyisihan.
     * Sekolah finalis tetap berdiri pada tingkat lomba yang sama dengan
     * peserta penyisihan, jadi tanpa pemeriksaan finalis, membuka lembar dari
     * daftar peserta menampilkan rubrik penyisihan padahal nilainya sudah final.
     *
     * Babak penyisihan tetap jadi bawaan bagi yang bukan finalis: halaman rekap
     * dan daftar peserta bekerja pada penyisihan, dan rubrik final yang tidak
     * bergrup akan tercetak sebagai kolom penilai juri final di lembar peserta
     * grup kalau babak tidak ikut disaring.
     *
     * Tingkat tanpa baris babak tetap tanpa babak (null) — perilaku lama.
     */
    public function roundFor(Registration $registration, ?int $requestedRoundId = null): ?int
    {
        $rounds = CompetitionRound::where('eventner_id', $registration->eventner_id)
            ->where('competition_category_id', $registration->competition_category_id);

        if ($requestedRoundId) {
            // Babak milik eventner DAN tingkat yang sama — mencegah lembar
            // peserta satu tingkat dicetak dengan rubrik tingkat lain.
            return $rounds->whereKey($requestedRoundId)->value('id');
        }

        $final = (clone $rounds)->where('type', CompetitionRound::TYPE_FINAL)->value('id');

        if ($final && CompetitionRoundRegistration::where('eventner_id', $registration->eventner_id)
            ->where('competition_round_id', $final)
            ->where('registration_id', $registration->id)
            ->exists()) {
            return $final;
        }

        $preliminary = (clone $rounds)->where('type', CompetitionRound::TYPE_PRELIMINARY)->value('id');

        return $preliminary ?: $rounds->orderBy('sort_order')->value('id');
    }

    /**
     * Rubrik yang berlaku untuk lembar penilaian peserta ini.
     *
     * Satu tempat yang memutuskan "rubrik mana yang tercetak di lembar peserta"
     * — dipisah dari downloadParticipantPdf() supaya keputusannya bisa diuji
     * tanpa merender PDF (dompdf tidak menyisakan viewData).
     *
     * Grup wajib ikut disaring: tanpa itu lembar peserta Grup A memuat juga
     * rubrik Grup B, lalu kolom Grup B yang tak pernah dinilai untuk peserta
     * ini ikut tampil dan mengotori subtotalnya.
     *
    /**
     * Babak juga wajib, dan justru itu yang paling mudah bocor: rubrik babak
     * final sengaja TIDAK berseri (babaknya berlaku untuk semua finalis), jadi
     * tanpa saringan babak ia lolos lewat klausa "seri NULL" dan juri final
     * muncul sebagai kolom penilai di lembar peserta penyisihan.
     */
    public function assessmentCategoriesFor(Registration $registration, ?int $roundId = null): \Illuminate\Support\Collection
    {
        return AssessmentCategory::with(['subCategories.criterias'])
            ->where('eventner_id', $registration->eventner_id)
            ->forEntry(
                $registration->competition_category_id,
                $registration->competition_series_id,
                $roundId ?? $this->roundFor($registration),
            )
            ->get();
    }

    /**
     * Juri yang berhak menilai peserta ini.
     *
     * Satu sumber dengan tablet juri: CompetitionGroup::judgesForRegistration().
     * Regu juri dulu diturunkan dari rubrik yang menempel ke seri peserta;
     * sekarang penugasan menempel di GRUP, dan rubriknya (seri peserta) cuma
     * menentukan lembar mana yang terbuka. Kalau PDF menghitung regunya
     * sendiri, kolom penilai di lembar cetak bisa memuat juri yang tak pernah
     * muncul di layar penilaian.
     */
    public function judgesFor(Registration $registration, ?int $roundId = null): \Illuminate\Support\Collection
    {
        return CompetitionGroup::judgesForRegistration(
            $registration,
            $roundId ?? $this->roundFor($registration),
        );
    }

    /**
     * Rekap keseluruhan satu tingkat lomba: seluruh grup dan seluruh babaknya
     * dalam satu berkas PDF.
     *
     * Angkanya datang dari ScoreRecapBuilder — sumber yang sama dengan layar
     * Rekap Nilai. Menyalin perhitungannya ke sini akan membuat berkas cetak
     * dan layar berbeda begitu salah satunya diperbaiki, dan yang dicetak
     * panitia untuk rapat juara justru yang tak bisa diperiksa ulang.
     *
     * Dulu halaman Rekap Nilai punya tombol "Download CSV" per kategori yang
     * memakai perhitungan sendiri (`downloadCsv()`): ia mengabaikan pemecahan per
     * babak sehingga nilai penyisihan dan final dijumlahkan jadi satu peringkat
     * yang tak pernah dinilai siapa pun — peserta yang tampil bagus di
     * penyisihan lalu biasa saja di final masih muncul di puncak. Tombol di
     * halaman itu kini memakai berkas ini. `downloadCsv()` sendiri masih hidup
     * karena panel Input Nilai memakainya untuk ekspor spreadsheet mentah.
     */
    public function downloadRecapPdf(Request $request)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $categoryId = $request->query('category_id');
        if (!$categoryId) {
            abort(400, 'Category ID diperlukan.');
        }

        // Tingkat wajib milik eventner ini, dan wajib tingkat ANAK: registrasi
        // selalu menempel ke anak, jadi tingkat induk menghasilkan rekap hampa.
        $category = CompetitionCategory::where('eventner_id', $eventner->id)->find($categoryId);
        if (!$category) {
            abort(404, 'Kategori lomba tidak ditemukan.');
        }

        $rekap = (new ScoreRecapBuilder)->build($eventner, (int) $category->id);

        $pdf = Pdf::loadView('eventner.scoring.pdf_recap', [
            'eventner' => $eventner,
            'category' => $category,
            'sections' => $rekap['sections'],
            'hasRounds' => $rekap['hasRounds'],
            'rounds' => $rekap['rounds'],
        ])
            // Lanskap: satu baris memuat peringkat, kontingen, pelatih, kolom
            // tiap rubrik, plus tiga kolom angka. Potret memaksa kolom rubrik
            // berdesakan sampai angkanya tak terbaca.
            ->setPaper('a4', 'landscape')
            ->setOption('margin-top', '10mm')
            ->setOption('margin-bottom', '10mm')
            ->setOption('margin-left', '8mm')
            ->setOption('margin-right', '8mm');

        $name = str_replace(['/', '\\'], '-', $category->name);
        return $pdf->download('Rekap_Nilai_' . $name . '.pdf');
    }

    public function downloadParticipantPdf(Request $request)
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $registrationId = $request->query('registration_id');
        if (!$registrationId) {
            abort(400, 'Registration ID diperlukan.');
        }

        $registration = Registration::with('competitionCategory', 'competitionGroup', 'competitionSeries', 'roundRegistrations')
            ->where('eventner_id', $eventner->id)
            ->findOrFail($registrationId);

        // Babak boleh diminta eksplisit (?round_id=) supaya lembar final bisa
        // dicetak; tanpa itu jatuh ke penyisihan.
        $requestedRoundId = $request->query('round_id') ? (int) $request->query('round_id') : null;
        $roundId = $this->roundFor($registration, $requestedRoundId);

        $assessmentCategories = $this->assessmentCategoriesFor($registration, $roundId);

        $allScores = AssessmentScore::where('eventner_id', $eventner->id)
            ->where('registration_id', $registrationId)
            ->get();

        $criteriaWeights = $this->criteriaWeights($assessmentCategories);

        // Sum scores per criteria across all judges
        $criteriaTotals = [];
        foreach ($allScores as $score) {
            $cid = $score->assessment_criteria_id;
            $criteriaTotals[$cid] = ($criteriaTotals[$cid] ?? 0) + \App\Support\ScoreOptions::value($score->score);
        }

        // Calculate totals
        $categoryTotals = [];
        $grandTotal = 0;
        foreach ($assessmentCategories as $cat) {
            $catTotal = 0;
            foreach ($cat->subCategories as $sub) {
                foreach ($sub->criterias as $crit) {
                    $catTotal += ($criteriaTotals[$crit->id] ?? 0) * ($criteriaWeights[$crit->id] ?? 1);
                }
            }
            $categoryTotals[$cat->id] = $catTotal;
            $grandTotal += $catTotal;
        }

        // Juri hanya yang ditugaskan ke grup peserta ini (atau baris
        // final/ungrouped/level-nya) — regu yang sama dengan tablet juri.
        $judges = $this->judgesFor($registration, $roundId);
        $judgeIds = $judges->pluck('id');

        // Build per-judge scores: [judge_id => [criteria_id => score]]
        $judgeScores = [];
        foreach ($allScores as $score) {
            if ($score->judge_id && $judgeIds->contains($score->judge_id)) {
                $judgeScores[$score->judge_id][$score->assessment_criteria_id] = \App\Support\ScoreOptions::value($score->score);
            }
        }

        // Per-judge category totals
        $judgeCategoryTotals = [];
        foreach ($judges as $judge) {
            $jScores = $judgeScores[$judge->id] ?? [];
            $catTotals = [];
            $jTotal = 0;
            foreach ($assessmentCategories as $cat) {
                $cTotal = 0;
                foreach ($cat->subCategories as $sub) {
                    foreach ($sub->criterias as $crit) {
                        $cTotal += ($jScores[$crit->id] ?? 0) * ($criteriaWeights[$crit->id] ?? 1);
                    }
                }
                $catTotals[$cat->id] = $cTotal;
                $jTotal += $cTotal;
            }
            $judgeCategoryTotals[$judge->id] = [
                'totals' => $catTotals,
                'grand' => $jTotal,
            ];
        }

        // Pengurangan (baik menempel pada kategori maupun tingkat).
        // Ini halaman rekap per peserta: pengurangan kategori mengisi kolom
        // kategorinya, pengurangan tingkat tidak menyentuh kolom mana pun —
        // keduanya tetap dipotongkan ke total akhir.
        // Pengurangan yang berlaku DI BABAK INI saja.
        //
        // Dulu seluruh kelompok pengurangan milik event dibaca tanpa saringan
        // babak, sehingga sanksi fase grup ikut memotong NILAI AKHIR di lembar
        // final — kertasnya tidak cocok dengan panel. Pengurangan per kategori
        // ikut babak lewat rubriknya; pengurangan tingkat lewat kolom
        // competition_round_id-nya sendiri (NULL = semua babak).
        $deductionCategories = DeductionCategory::with(['criterias', 'assessmentCategory'])
            ->where('eventner_id', $eventner->id)
            ->where(function ($q) use ($assessmentCategories, $registration, $roundId) {
                $q->where(fn ($sq) => $sq->category()
                        ->whereIn('assessment_category_id', $assessmentCategories->pluck('id')))
                    ->orWhere(fn ($sq) => $sq->global()
                        ->forLevel($registration->competition_category_id, $roundId));
            })
            ->orderBy('sort_order')
            ->get();

        // Hanya baris yang kriterianya termasuk di atas. Tanpa saringan ini
        // pengurangan babak lain tetap ikut dijumlahkan ke total akhir walau
        // tak satu pun barisnya tampil.
        $allowedDeductionCriteriaIds = $deductionCategories
            ->flatMap->criterias
            ->pluck('id')
            ->all();

        $scoreDeductions = ScoreDeduction::where('eventner_id', $eventner->id)
            ->where('registration_id', $registrationId)
            ->whereIn('deduction_criteria_id', $allowedDeductionCriteriaIds)
            ->get();

        // Petakan deduction_criteria_id => assessment_category_id. Pengurangan
        // tingkat tidak punya kolom kategori, jadi tidak ikut dipetakan.
        $critToAssessment = [];
        foreach ($deductionCategories as $dc) {
            foreach ($dc->criterias as $c) {
                if ($dc->assessment_category_id) {
                    $critToAssessment[$c->id] = $dc->assessment_category_id;
                }
            }
        }

        // Akumulasikan pengurangan
        $categoryDeductions = [];
        $totalDeduction = 0;
        foreach ($scoreDeductions as $d) {
            // Magnitude: tanda di DB tidak dipercaya, selalu dikurangkan.
            $amt = -$d->magnitude;

            $aid = $critToAssessment[$d->deduction_criteria_id] ?? null;
            if ($aid !== null) {
                $categoryDeductions[$aid] = ($categoryDeductions[$aid] ?? 0) + $amt;
            }

            $totalDeduction += $amt;
        }

        $round = $roundId
            ? CompetitionRound::where('eventner_id', $eventner->id)->find($roundId)
            : null;

        $data = [
            'eventner' => $eventner,
            'registration' => $registration,
            'roundName' => $round?->name,
            // Babak final diwarnai berbeda di lembar ini supaya lembar penyisihan
            // dan final sekolah yang sama tidak tertukar saat keduanya dicetak.
            'roundIsFinal' => (bool) $round?->isFinal(),
            // Nomor undian babak ini, bukan nomor fase grup: finalis punya
            // undian finalnya sendiri, dan yang belum diundi tampil kosong.
            'nomorUndian' => $registration->nomorUndian($round),
            'assessmentCategories' => $assessmentCategories,
            'criteriaTotals' => $criteriaTotals,
            'categoryTotals' => $categoryTotals,
            'categoryDeductions' => $categoryDeductions,
            'grandTotal' => $grandTotal,
            'judges' => $judges,
            'judgeScores' => $judgeScores,
            'judgeCategoryTotals' => $judgeCategoryTotals,
            'deductionCategories' => $deductionCategories,
            'scoreDeductions' => $scoreDeductions->keyBy('deduction_criteria_id'),
            'totalDeduction' => $totalDeduction,
            'finalScore' => $grandTotal + $totalDeduction,
        ];

        $pdf = Pdf::loadView('eventner.scoring.pdf_participant', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', '10mm')
            ->setOption('margin-bottom', '10mm')
            ->setOption('margin-left', '5mm')
            ->setOption('margin-right', '5mm');

        $name = str_replace(['/', '\\'], '-', $registration->display_name);
        $filename = 'Nilai_' . $name . '.pdf';
        return $pdf->download($filename);
    }
}
