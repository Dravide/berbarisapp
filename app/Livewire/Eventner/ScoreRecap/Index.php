<?php

namespace App\Livewire\Eventner\ScoreRecap;

use App\Models\AssessmentCategory;
use App\Models\AssessmentScore;
use App\Models\CompetitionCategory;
use App\Models\Registration;
use App\Models\ScoreDeduction;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Index extends Component
{
    public $eventner;
    public $selectedCategoryId;

    protected $queryString = [
        'selectedCategoryId' => ['except' => ''],
    ];

    public function mount()
    {
        $this->eventner = Auth::user()->eventner;

        if (!$this->eventner) {
            abort(403, 'Anda belum memiliki data Event terdaftar.');
        }

        // Default ke kategori pertama yang punya peserta. Dulu memakai
        // competitionCategories->first() apa adanya — itu bisa kategori
        // INDUK, sedangkan registrasi selalu menempel ke kategori anak, dan
        // pilihan di layar hanya menampilkan anak. Akibatnya rekap tampak
        // kosong saat halaman pertama dibuka.
        if (!$this->selectedCategoryId) {
            $daftar = $this->eventner->competitionCategories()
                ->whereNotNull('parent_id')
                ->withCount('registrations')
                ->orderBy('sort_order')
                ->get();

            $first = $daftar->firstWhere('registrations_count', '>', 0) ?? $daftar->first();

            if ($first) {
                $this->selectedCategoryId = $first->id;
            }
        }
    }

    public function selectCategory($id)
    {
        $this->selectedCategoryId = $id;
    }

    public function updatedSelectedCategoryId()
    {
        // selectedCategoryId juga bisa datang dari klien — scope ulang.
        if ($this->selectedCategoryId
            && !CompetitionCategory::where('eventner_id', $this->eventner->id)->find($this->selectedCategoryId)) {
            $this->selectedCategoryId = null;
        }
    }

    public function render()
    {
        $categories = $this->eventner->competitionCategories()
            ->whereNotNull('parent_id')
            ->withCount('registrations')
            ->get();
        $selectedCategory = null;
        $scoringData = collect();

        // Format nilai hanya yang terikat kategori lomba terpilih + yang umum —
        // sama seperti halaman Input Nilai.
        $assessmentCategories = AssessmentCategory::with(['subCategories.criterias'])
            ->where('eventner_id', $this->eventner->id)
            ->when($this->selectedCategoryId, function ($q) {
                $q->where(function ($sq) {
                    $sq->where('competition_category_id', $this->selectedCategoryId)
                       ->orWhereNull('competition_category_id');
                });
            })
            ->get();

        if ($this->selectedCategoryId) {
            // Scoping ke eventner sendiri — cegah baca data kategori/registrasi tenant lain.
            $selectedCategory = CompetitionCategory::where('eventner_id', $this->eventner->id)
                ->find($this->selectedCategoryId);

            $participants = Registration::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $this->selectedCategoryId)
                ->orderBy('nama_sekolah')
                ->get();

            $allScores = AssessmentScore::with('assessmentCriteria')
                ->where('eventner_id', $this->eventner->id)
                ->whereIn('registration_id', $participants->pluck('id'))
                ->get()
                ->groupBy('registration_id');

            // Load deductions per registration
            $allDeductions = ScoreDeduction::where('eventner_id', $this->eventner->id)
                ->whereIn('registration_id', $participants->pluck('id'))
                ->get()
                ->groupBy('registration_id');

            // Map deduction_criteria_id => assessment_category_id (target kategori penilaian).
            // Hanya kategori penilaian yang ikut halaman ini — kalau tidak,
            // pengurangan dari format nilai kategori lain ikut terhitung di
            // kolom Total Pengurangan padahal tidak muncul di kolom kategori
            // mana pun, sehingga kolom-kolomnya tidak lagi saling menjumlah.
            $assessmentCategoryIds = $assessmentCategories->pluck('id');

            $critToAssessment = \App\Models\DeductionCriteria::whereHas('category', function ($q) use ($assessmentCategoryIds) {
                    $q->where('eventner_id', $this->eventner->id)
                      ->where('scope', \App\Models\DeductionCategory::SCOPE_CATEGORY)
                      ->whereIn('assessment_category_id', $assessmentCategoryIds);
                })
                ->with('category:deduction_categories.id,assessment_category_id')
                ->get()
                ->pluck('category.assessment_category_id', 'id')
                ->toArray();

            // Pengurangan global tidak menempel pada kolom kategori mana pun,
            // jadi ia tidak muncul di $critToAssessment. Daftar id kriterianya
            // dipakai untuk memisahkannya dari pengurangan per kategori —
            // nilainya tetap mengurangi NILAI AKHIR.
            $globalCriteriaIds = \App\Models\DeductionCriteria::whereHas('category', function ($q) {
                    $q->where('eventner_id', $this->eventner->id)
                      ->where('scope', \App\Models\DeductionCategory::SCOPE_GLOBAL);
                })
                ->pluck('id')
                ->flip()
                ->toArray();

            $data = [];
            foreach ($participants as $participant) {
                $participantScores = $allScores->get($participant->id, collect());

                // Sum scores per criteria across all judges, weighted
                $criteriaTotals = [];
                foreach ($participantScores as $score) {
                    $cid = $score->assessment_criteria_id;
                    $criteriaWeight = $score->assessmentCriteria->weight ?? 1;
                    $criteriaTotals[$cid] = ($criteriaTotals[$cid] ?? 0) + ((int) $score->score * $criteriaWeight);
                }

                // Distribusikan pengurangan ke kategori penilaian targetnya.
                // Pengurangan global dikumpulkan terpisah.
                $participantDeductions = $allDeductions->get($participant->id, collect());
                $deductionByCat = [];
                $globalDeduction = 0;
                foreach ($participantDeductions as $d) {
                    // Magnitude: tanda di DB tidak dipercaya, selalu dikurangkan.
                    if (isset($globalCriteriaIds[$d->deduction_criteria_id])) {
                        $globalDeduction += $d->magnitude;

                        continue;
                    }

                    $aid = $critToAssessment[$d->deduction_criteria_id] ?? null;
                    if ($aid !== null) {
                        $deductionByCat[$aid] = ($deductionByCat[$aid] ?? 0) - $d->magnitude;
                    }
                }

                $categoryTotals = [];
                $categoryDeductions = [];
                $grandTotal = 0;
                $finalScore = 0;

                foreach ($assessmentCategories as $cat) {
                    $catTotal = 0;
                    foreach ($cat->subCategories as $sub) {
                        foreach ($sub->criterias as $crit) {
                            $catTotal += $criteriaTotals[$crit->id] ?? 0;
                        }
                    }
                    $catDeduction = $deductionByCat[$cat->id] ?? 0; // negatif
                    $categoryTotals[$cat->id] = $catTotal;
                    $categoryDeductions[$cat->id] = $catDeduction;
                    $grandTotal += $catTotal;
                    $finalScore += $catTotal + $catDeduction;
                }

                $finalScore -= $globalDeduction;

                // Total pengurangan = kolom kategori + global, supaya angka di
                // kolom Pengurangan cocok dengan selisih grandTotal - finalScore.
                $totalDeduction = array_sum($deductionByCat) - $globalDeduction; // negatif

                $data[] = [
                    'participant' => $participant,
                    'criteriaTotals' => $criteriaTotals,
                    'categoryTotals' => $categoryTotals,
                    'categoryDeductions' => $categoryDeductions,
                    'grandTotal' => $grandTotal,
                    'globalDeduction' => $globalDeduction,
                    'totalDeduction' => $totalDeduction,
                    'finalScore' => $finalScore,
                ];
            }

            // Sort by final score descending
            usort($data, fn($a, $b) => $b['finalScore'] <=> $a['finalScore']);

            // Peringkat seri: nilai akhir sama berarti peringkat sama, dan
            // peringkat berikutnya melompat — sama seperti papan skor publik.
            // Dulu nomor urut array, jadi dua peserta bernilai identik tetap
            // ditulis peringkat 1 dan 2.
            $rank = 1;
            $previousScore = null;
            foreach ($data as $index => &$row) {
                if ($previousScore !== null && $row['finalScore'] < $previousScore) {
                    $rank = $index + 1;
                }
                $row['rank'] = $rank;
                $previousScore = $row['finalScore'];
            }
            unset($row);

            $scoringData = collect($data);
        }

        return view('livewire.eventner.score-recap.index', [
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'assessmentCategories' => $assessmentCategories,
            'scoringData' => $scoringData,
        ])->title('Rekap Nilai - ' . $this->eventner->nama_event);
    }
}
