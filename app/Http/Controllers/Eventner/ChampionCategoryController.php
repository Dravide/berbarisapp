<?php

namespace App\Http\Controllers\Eventner;

use App\Http\Controllers\Controller;
use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentScore;
use App\Models\AssessmentSubCategory;
use App\Models\ChampionCategory;
use App\Models\CompetitionCategory;
use App\Models\Registration;
use App\Models\ScoreDeduction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ChampionCategoryController extends Controller
{
    public function downloadPdf(Request $request)
    {
        $data = $this->pdfData($request);

        $pdf = Pdf::loadView('eventner.champion-category.pdf_ranking', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', '10mm')
            ->setOption('margin-bottom', '10mm')
            ->setOption('margin-left', '5mm')
            ->setOption('margin-right', '5mm');

        $catName = $data['competitionCategory']
            ? str_replace(['/', '\\'], '-', $data['competitionCategory']->name)
            : 'Semua';

        return $pdf->download('Rekap_Juara_' . $catName . '.pdf');
    }

    /**
     * Data untuk view eventner.champion-category.pdf_ranking.
     *
     * @return array<string, mixed>
     */
    public function pdfData(Request $request): array
    {
        $eventner = Auth::user()->eventner;
        if (!$eventner) {
            abort(403, 'Anda bukan Eventner yang sah.');
        }

        $competitionCategoryId = $request->query('competition_category_id');
        $championCategoryId = $request->query('champion_category_id');

        $championCategories = ChampionCategory::with(['assessmentSubCategories.criterias', 'assessmentSubCategories.category'])
            ->where('eventner_id', $eventner->id)
            ->when($championCategoryId, fn($q) => $q->where('id', $championCategoryId))
            ->get();

        // Kategori juara yang rubriknya milik tingkat lain tidak relevan —
        // nilainya tidak akan pernah terisi untuk peserta tingkat terpilih.
        // Sama seperti filter laman admin (ChampionCategory/Index::render).
        if ($competitionCategoryId) {
            $championCategories = $championCategories
                ->filter(fn($c) => $c->isVisibleFor($competitionCategoryId))
                ->values();
        }

        if ($championCategoryId && $championCategories->isEmpty()) {
            abort(404, 'Kategori juara tidak ditemukan.');
        }

        // Ambil data deduction. Pengurangan ber-scope 'global' hanya berlaku di
        // tingkat lombanya sendiri.
        $allDeductions = ScoreDeduction::where('eventner_id', $eventner->id)
            ->get()
            ->groupBy('registration_id');
        $deductionLevelMap = \App\Models\DeductionCategory::levelMapOfCriteria($eventner->id);

        // Ambil semua kriteria beserta bobotnya untuk menghitung other_total
        $allCriteriaWeightMap = AssessmentCriteria::whereIn(
            'assessment_sub_category_id',
            AssessmentSubCategory::whereIn(
                'assessment_category_id',
                AssessmentCategory::where('eventner_id', $eventner->id)->pluck('id')
            )->pluck('id')
        )->pluck('weight', 'id')->toArray();

        $allScores = AssessmentScore::where('eventner_id', $eventner->id)
            ->get()
            ->groupBy('registration_id');

        $registrations = Registration::where('eventner_id', $eventner->id)
            ->orderBy('nama_sekolah')
            ->get();
        $registrationsByLevel = $registrations->groupBy(fn($r) => (string) $r->competition_category_id);

        // Filter tingkat dipilih -> satu bagian. Tanpa filter -> tiap tingkat
        // jadi bagiannya sendiri, supaya ranking tidak tercampur antar tingkat.
        $sections = $competitionCategoryId
            ? collect([[
                'level' => CompetitionCategory::where('eventner_id', $eventner->id)->with('parent')->find($competitionCategoryId),
                'champions' => $championCategories,
            ]])
            : static::groupByLevel($championCategories, $eventner);

        // Peserta yang dibandingkan untuk sebuah kategori juara = gabungan
        // peserta SELURUH tingkat yang rubriknya tercakup, bukan hanya tingkat
        // bagian tempat ia ditampilkan — kalau tidak, kategori juara lintas
        // tingkat akan dihitung terhadap separuh pesertanya saja.
        $rankings = [];
        foreach ($championCategories as $champion) {
            if ($competitionCategoryId) {
                $participants = $registrationsByLevel->get((string) $competitionCategoryId, collect());
            } else {
                $levelIds = $champion->assessmentSubCategories
                    ->map(fn($s) => $s->category?->competition_category_id)
                    ->filter()
                    ->unique();

                // Tanpa tingkat yang bisa dicocokkan (rubrik global) tidak ada
                // peserta yang bisa dibandingkan — biarkan kosong.
                $participants = $levelIds->isEmpty()
                    ? collect()
                    : $levelIds->flatMap(fn($id) => $registrationsByLevel->get((string) $id, collect()));
            }

            $rankings[$champion->id] = $this->rankParticipants(
                $champion,
                $participants,
                $allScores,
                $allDeductions,
                $allCriteriaWeightMap,
                $deductionLevelMap
            );
        }

        $competitionCategory = $competitionCategoryId
            ? CompetitionCategory::where('eventner_id', $eventner->id)->with('parent')->find($competitionCategoryId)
            : null;

        return [
            'eventner' => $eventner,
            'competitionCategory' => $competitionCategory,
            'championCategories' => $championCategories,
            'sections' => $sections,
            'rankings' => $rankings,
        ];
    }

    /**
     * Kelompokkan kategori juara per tingkat lomba rubriknya. Kategori juara
     * tanpa rubrik (atau yang rubriknya lintas tingkat) masuk kelompok global
     * dengan level null. Kelompok tingkat diurut nama, kelompok global paling akhir.
     *
     * @param  Collection<int, ChampionCategory>  $championCategories
     * @return Collection<int, array{level: ?CompetitionCategory, champions: Collection<int, ChampionCategory>}>
     */
    public static function groupByLevel(Collection $championCategories, $eventner): Collection
    {
        // Tingkat yang dicakup tiap kategori juara. Satu kategori juara bisa
        // mencakup lebih dari satu tingkat bila rubriknya menempel pada
        // beberapa sub kategori dari tingkat berbeda.
        $levelsOf = $championCategories->mapWithKeys(function ($c) {
            $ids = $c->assessmentSubCategories
                ->map(fn($s) => $s->category?->competition_category_id)
                ->filter()
                ->unique()
                ->values();

            return [$c->id => $ids];
        });

        $levelIds = $levelsOf->flatten()->unique()->values();

        $levels = CompetitionCategory::where('eventner_id', $eventner->id)
            ->with('parent')
            ->whereIn('id', $levelIds)
            ->get()
            ->keyBy(fn($l) => (string) $l->id);

        $sections = collect();

        // Satu bagian per tingkat; kategori juara yang rubriknya mencakup
        // tingkat itu ikut di dalamnya. Dulu hanya tingkat PERTAMA yang
        // dipakai (->first()), jadi kategori juara lintas tingkat hanya
        // muncul di satu bagian dan hilang dari bagian tingkat lainnya.
        foreach ($levels as $level) {
            $champions = $championCategories
                ->filter(fn($c) => $levelsOf[$c->id]->contains($level->id))
                ->values();

            if ($champions->isNotEmpty()) {
                $sections->push(['level' => $level, 'champions' => $champions]);
            }
        }

        // Kategori juara tanpa rubrik (atau rubriknya tanpa tingkat) masuk
        // kelompok global — ditempatkan paling akhir.
        $global = $championCategories
            ->filter(fn($c) => $levelsOf[$c->id]->isEmpty())
            ->values();

        if ($global->isNotEmpty()) {
            $sections->push(['level' => null, 'champions' => $global]);
        }

        return $sections
            ->sortBy(fn($s) => $s['level'] ? $s['level']->full_name : "\u{FFFF}")
            ->values();
    }

    /**
     * Hitung + urutkan peringkat peserta untuk satu kategori juara.
     *
     * @param  Collection<int, Registration>  $participants
     * @param  Collection<string, Collection<int, AssessmentScore>>  $allScores
     * @param  Collection<string, Collection<int, ScoreDeduction>>  $allDeductions
     * @param  array<int, int|float|null>  $allCriteriaWeightMap
     * @param  array<int, int|null>  $deductionLevelMap  tingkat lomba tiap kriteria pengurangan ber-scope 'global'
     * @return array<int, array<string, mixed>>
     */
    private function rankParticipants(
        ChampionCategory $champion,
        Collection $participants,
        Collection $allScores,
        Collection $allDeductions,
        array $allCriteriaWeightMap,
        array $deductionLevelMap = []
    ): array {
        $criteriaMap = [];
        foreach ($champion->assessmentSubCategories as $sub) {
            foreach ($sub->criterias as $crit) {
                $criteriaMap[$crit->id] = $crit->weight ?? 1;
            }
        }

        // Kriteria untuk subkategori pertama (prioritas tie-break)
        $firstSub = $champion->assessmentSubCategories->first();
        $firstSubCriteriaIds = $firstSub ? $firstSub->criterias->pluck('id')->toArray() : [];

        $participantScores = [];
        foreach ($participants as $participant) {
            $scores = $allScores->get($participant->id, collect());

            $total = 0;
            $firstSubTotal = 0;
            $otherTotal = 0;

            foreach ($scores as $score) {
                $weight = $criteriaMap[$score->assessment_criteria_id] ?? null;
                if ($weight !== null) {
                    $scoreVal = (int) $score->score * $weight;
                    $total += $scoreVal;

                    if (in_array($score->assessment_criteria_id, $firstSubCriteriaIds)) {
                        $firstSubTotal += $scoreVal;
                    }
                } else {
                    $weightOther = $allCriteriaWeightMap[$score->assessment_criteria_id] ?? 1;
                    $otherTotal += (int) $score->score * $weightOther;
                }
            }

            // abs() per-baris: opsi pengurangan bisa tersimpan -5 maupun 5.
            $deductions = \App\Models\DeductionCategory::applicableToLevel(
                $allDeductions->get($participant->id, collect()),
                $participant->competition_category_id,
                $deductionLevelMap
            );
            $totalDeduction = $deductions->sum(fn ($d) => $d->magnitude);

            $participantScores[] = [
                'participant' => $participant,
                'total' => $total - $totalDeduction, // nilai bersih: dipakai sort + tampil
                'gross_total' => $total,
                'first_sub_total' => $firstSubTotal,
                'other_total' => $otherTotal,
                'deduction' => $totalDeduction,
                'urutan_tampil' => $participant->urutan_tampil ?? 999999,
            ];
        }

        usort($participantScores, function ($a, $b) {
            if ($b['total'] !== $a['total']) {
                return $b['total'] <=> $a['total'];
            }
            if ($b['first_sub_total'] !== $a['first_sub_total']) {
                return $b['first_sub_total'] <=> $a['first_sub_total'];
            }
            if ($b['other_total'] !== $a['other_total']) {
                return $b['other_total'] <=> $a['other_total'];
            }
            if ($a['deduction'] !== $b['deduction']) {
                return $a['deduction'] <=> $b['deduction'];
            }
            return $a['urutan_tampil'] <=> $b['urutan_tampil'];
        });

        // Limit by quantity
        $participantScores = array_slice($participantScores, 0, $champion->quantity);

        foreach ($participantScores as $index => &$ps) {
            $ps['rank'] = $index + 1;
        }
        unset($ps);

        return $participantScores;
    }
}
