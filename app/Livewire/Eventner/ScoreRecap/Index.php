<?php

namespace App\Livewire\Eventner\ScoreRecap;

use App\Models\AssessmentCategory;
use App\Models\AssessmentScore;
use App\Models\CompetitionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\CompetitionRoundRegistration;
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

    /**
     * Grup yang sedang direkap. '' = seluruh tingkat (perilaku lama).
     */
    public $selectedGroupId = '';

    protected $queryString = [
        'selectedCategoryId' => ['except' => ''],
        'selectedGroupId' => ['except' => ''],
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
        if ((string) $this->selectedCategoryId !== (string) $id) {
            $this->selectedGroupId = '';
        }

        $this->selectedCategoryId = $id;
    }

    /**
     * Ganti grup. Grup dari klien wajib milik tingkat terpilih — kalau tidak,
     * rekap bisa menampilkan peserta dan nilai tenant/tingkat lain.
     */
    public function selectGroup($id)
    {
        $this->selectedGroupId = '';

        if ($id !== '' && $id !== null) {
            $ada = CompetitionGroup::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $this->selectedCategoryId)
                ->find($id);

            if (! $ada) {
                $this->addError('selectedGroupId', 'Grup tidak ditemukan pada tingkat lomba ini.');

                return;
            }

            $this->selectedGroupId = (string) $ada->id;
        }

        $this->resetErrorBag('selectedGroupId');
    }

    public function updatedSelectedCategoryId()
    {
        // selectedCategoryId juga bisa datang dari klien — scope ulang.
        if ($this->selectedCategoryId
            && !CompetitionCategory::where('eventner_id', $this->eventner->id)->find($this->selectedCategoryId)) {
            $this->selectedCategoryId = null;
        }

        // Grup lama milik tingkat lama, jadi wajib dilepas.
        if ($this->selectedGroupId !== '' && ! CompetitionGroup::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->selectedCategoryId)
            ->whereKey($this->selectedGroupId)
            ->exists()) {
            $this->selectedGroupId = '';
        }
    }

    public function updatedSelectedGroupId()
    {
        if ($this->selectedGroupId === '' || $this->selectedGroupId === null) {
            $this->selectedGroupId = '';

            return;
        }

        $ada = CompetitionGroup::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->selectedCategoryId)
            ->find($this->selectedGroupId);

        $this->selectedGroupId = $ada ? (string) $ada->id : '';
    }

    public function render()
    {
        $categories = $this->eventner->competitionCategories()
            ->whereNotNull('parent_id')
            ->withCount('registrations')
            ->get();

        $selectedCategory = null;
        $scoringData = collect();
        $sections = [];
        $hasRounds = false;
        $roundOptions = collect();

        if ($this->selectedCategoryId) {
            // Scoping ke eventner sendiri — cegah baca data kategori/registrasi tenant lain.
            $selectedCategory = CompetitionCategory::where('eventner_id', $this->eventner->id)
                ->find($this->selectedCategoryId);

            $groups = CompetitionGroup::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $this->selectedCategoryId)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            $participants = Registration::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $this->selectedCategoryId)
                ->when($this->selectedGroupId !== '', fn ($q) => $q->where('competition_group_id', $this->selectedGroupId))
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

            $globalCriteriaIds = $this->globalDeductionCriteriaIds();

            // Tingkat bergrup dan/atau berbabak dipecah jadi bagian terpisah:
            // satu tabel per babak, dan di dalamnya satu tabel per grup. Nilai
            // akhir hanya masuk akal di dalam pemecahan itu — peserta Grup A
            // bukan pembanding peserta Grup B, dan baris penyisihan bukan
            // pembanding baris final.
            $rounds = CompetitionRound::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $this->selectedCategoryId)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
            $hasRounds = $rounds->isNotEmpty();
            $roundOptions = $rounds;

            $sections = $hasRounds
                ? $this->sectionsPerRound($rounds, $participants, $allScores, $allDeductions, $globalCriteriaIds)
                : $this->sectionsPerGroup($participants, $allScores, $allDeductions, $globalCriteriaIds, $groups);

            // $scoringData dipertahankan untuk pemakaian lama (cetak/ekspor yang
            // masih membacanya): gabungan semua bagian. Bagian berbabak belum
            // memuat 'data' — barisnya ada satu tingkat di bawah, di tiap grup.
            $scoringData = collect($sections)
                ->flatMap(fn ($s) => $s['data'] ?? collect($s['groups'] ?? [])->flatMap(fn ($g) => $g['data']))
                ->values();
        }

        return view('livewire.eventner.score-recap.index', [
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'sections' => $sections,
            'hasRounds' => $hasRounds,
            'rounds' => $roundOptions,
            'scoringData' => $scoringData,
            'groups' => $groups ?? collect(),
        ])->title('Rekap Nilai - ' . $this->eventner->nama_event);
    }

    /** Id kriteria pengurangan ber-scope global milik tingkat terpilih. */
    private function globalDeductionCriteriaIds(): array
    {
        return \App\Models\DeductionCriteria::whereHas('category', function ($q) {
                $q->where('eventner_id', $this->eventner->id)
                  ->where('scope', \App\Models\DeductionCategory::SCOPE_GLOBAL)
                  ->forLevel($this->selectedCategoryId);
            })
            ->pluck('id')
            ->flip()
            ->toArray();
    }

    /**
     * Satu bagian per babak — tiap babak memakai rubriknya sendiri.
     *
     * Nilai penyisihan dan nilai final tinggal di baris kriteria yang berbeda,
     * jadi menjumlahkan keduanya menghasilkan angka yang tak pernah dinilai
     * siapa pun. Di dalam babak, peserta dan rubriknya tetap dipecah per grup.
     */
    private function sectionsPerRound($rounds, $participants, $allScores, $allDeductions, array $globalCriteriaIds): array
    {
        // Rubrik per babak diambil sekali, lalu dipakai ulang untuk semua grup
        // babak itu. forLevel, bukan forEntry: rubrik fase grup justru BERGrup,
        // dan forEntry(grup null) membuang justru rubrik-rubrik itu.
        $rubrics = AssessmentCategory::with(['subCategories.criterias'])
            ->where('eventner_id', $this->eventner->id)
            ->forLevel($this->selectedCategoryId)
            ->get()
            ->groupBy('competition_round_id');

        $groups = CompetitionGroup::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->selectedCategoryId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $sections = [];

        foreach ($rounds as $round) {
            $roundRubrics = $rubrics->get($round->id, collect());

            if ($roundRubrics->isEmpty()) {
                continue;
            }

            // Babak final hanya menilai peserta yang lolos: menampilkan seluruh
            // tingkat di tabel final membuat sekolah yang tidak pernah dinilai
            // final muncul dengan nilai nol di peringkat final.
            $roundParticipants = $round->isFinal()
                ? $this->finalistsOf($round, $participants)
                : $participants;

            // Rubrik babak ini tidak mengenal grup (final biasanya begitu: satu
            // set rubrik untuk semua finalis). Memecahnya per grup hanya
            // mengulang kolom yang sama persis dan memecah peringkat jadi
            // potongan yang tak dibandingkan siapa pun — padahal juara final
            // ditentukan lintas finalis, bukan lintas grup. Satu tabel saja.
            //
            // Babak tanpa peserta dibiarkan tanpa bagian: pesannya sudah ada di
            // tampilan ("Belum ada peserta yang lolos"), dan itu lebih jujur
            // daripada satu tabel kosong berjudul sendiri.
            if ($roundRubrics->whereNotNull('competition_group_id')->isEmpty()) {
                $sections[] = [
                    'label' => $round->name,
                    'badge' => $round->isFinal() ? 'warning text-dark' : 'primary',
                    'groups' => $roundParticipants->isEmpty() ? [] : [[
                        'label' => 'Seluruh Finalis',
                        'group' => null,
                        'show_label' => true,
                        'round_id' => $round->id,
                        'assessmentCategories' => $roundRubrics,
                        'data' => $this->rankRows(
                            $roundParticipants,
                            $allScores,
                            $allDeductions,
                            $globalCriteriaIds,
                            $this->deductionTargetMap($roundRubrics),
                            $roundRubrics,
                        ),
                    ]],
                ];

                continue;
            }

            $sections[] = [
                'label' => $round->name,
                // Final dibedakan warnanya dari penyisihan supaya mata tidak
                // perlu membaca namanya untuk tahu babak mana yang dirapatkan:
                // juara final ditentukan babak ini, bukan angka gabungan.
                'badge' => $round->isFinal() ? 'warning text-dark' : 'primary',
                'groups' => $this->sectionsPerGroup(
                    $roundParticipants,
                    $allScores,
                    $allDeductions,
                    $globalCriteriaIds,
                    $groups,
                    $roundRubrics,
                    $round->id,
                ),
            ];
        }

        return $sections;
    }

    /** Peserta yang tercatat lolos ke babak ini, dijaga ke tingkat yang sama. */
    private function finalistsOf($round, $participants)
    {
        $ids = CompetitionRoundRegistration::where('eventner_id', $this->eventner->id)
            ->where('competition_round_id', $round->id)
            ->pluck('registration_id');

        return $participants->whereIn('id', $ids)->values();
    }

    /**
     * Pecah peserta jadi bagian per grup, masing-masing dengan peringkat sendiri.
     *
     * $rubrics boleh dikirim pemanggil (mode per babak) supaya kolomnya memakai
     * rubrik babak itu; tanpa itu diambil rubrik tingkat apa adanya.
     *
     * Rubrik bergrup disaring PER bagian: tanpa itu tabel Grup A menampilkan
     * kolom Grup B yang tak pernah dinilai untuk peserta di tabel ini — nama
     * kolomnya bahkan sama ("PBB"), jadi terbaca sebagai tiga kolom PBB.
     */
    private function sectionsPerGroup($participants, $allScores, $allDeductions, array $globalCriteriaIds, $groups, $rubrics = null, ?int $roundId = null): array
    {
        $allRubrics = $rubrics ?? AssessmentCategory::with(['subCategories.criterias'])
            ->where('eventner_id', $this->eventner->id)
            ->forLevel($this->selectedCategoryId)
            ->get();

        $buckets = [];

        // Grup yang tidak punya peserta tidak perlu jadi tabel kosong; tanpa
        // saringan ini tingkat bergrup selalu menampilkan satu tabel hampa.
        foreach ($groups as $group) {
            $buckets[] = ['label' => $group->name, 'group' => $group, 'peserta' => collect()];
        }

        foreach ($participants as $participant) {
            $gid = $participant->competition_group_id;

            if ($gid === null) {
                // Belum dibagi grup: jalur sendiri, bukan dilebur ke grup mana
                // pun — kalau dilebur, peringkatnya bersaing dengan peserta
                // yang sudah dibagi padahal ia belum masuk pool mana pun.
                $key = '__ungrouped__';
                if (! isset($buckets[$key])) {
                    $buckets[$key] = ['label' => 'Belum Bergrup', 'group' => null, 'peserta' => collect()];
                }
                $buckets[$key]['peserta']->push($participant);

                continue;
            }

            foreach ($buckets as $i => $bucket) {
                if ($bucket['group'] && (int) $bucket['group']->id === (int) $gid) {
                    $buckets[$i]['peserta']->push($participant);
                    break;
                }
            }
        }

        $sections = [];
        foreach ($buckets as $bucket) {
            if ($bucket['peserta']->isEmpty()) {
                continue;
            }

            $assessmentCategories = $this->rubricsForGroup($allRubrics, $bucket['group']);

            // Tingkat yang belum punya rubrik sama sekali tetap didaftarkan:
            // daftar pesertanya masih berguna (dan begitulah perilaku lama),
            // sedangkan tabel tanpa kolom di tingkat BERGrup cuma tabel hampa
            // yang menyesatkan.
            if ($assessmentCategories->isEmpty() && $allRubrics->isNotEmpty()) {
                continue;
            }

            $sections[] = [
                'label' => $bucket['label'],
                'group' => $bucket['group'],
                // Nama grup hanya perlu jadi judul kalau tingkat ini memang
                // dibagi grup. Tingkat tanpa grup tetap satu tabel tanpa
                // judul "Belum Bergrup" — itu perilaku lama, dan labelnya
                // justru menyesatkan di tingkat yang memang tak punya grup.
                'show_label' => $groups->isNotEmpty(),
                // Babak bagian ini, kalau ada: tautan PDF-nya wajib mencetak
                // lembar babak yang sama — kalau tidak, tabel final menautkan
                // lembar penyisihan dan angkanya tidak cocok dengan kolomnya.
                'round_id' => $roundId,
                'assessmentCategories' => $assessmentCategories,
                'data' => $this->rankRows(
                    $bucket['peserta'],
                    $allScores,
                    $allDeductions,
                    $globalCriteriaIds,
                    $this->deductionTargetMap($assessmentCategories),
                    $assessmentCategories,
                ),
            ];
        }

        return $sections;
    }

    /**
     * Rubrik yang berlaku di satu bagian: rubrik tanpa grup + rubrik grup itu.
     *
     * $group null (peserta belum bergrup) hanya mendapat rubrik tanpa grup —
     * sama dengan aturan forEntry(), supaya kolom di rekap tidak berbeda dari
     * yang boleh dinilai juri.
     */
    private function rubricsForGroup($allRubrics, $group)
    {
        return $allRubrics
            ->filter(function ($rubric) use ($group) {
                if ($rubric->competition_group_id === null) {
                    return true;
                }

                return $group && (int) $rubric->competition_group_id === (int) $group->id;
            })
            ->values();
    }

    /** Peta deduction_criteria_id => assessment_category_id untuk rubrik ini. */
    private function deductionTargetMap($assessmentCategories): array
    {
        return \App\Models\DeductionCriteria::whereHas('category', function ($q) use ($assessmentCategories) {
                $q->where('eventner_id', $this->eventner->id)
                  ->where('scope', \App\Models\DeductionCategory::SCOPE_CATEGORY)
                  ->whereIn('assessment_category_id', $assessmentCategories->pluck('id'));
            })
            ->with('category:deduction_categories.id,assessment_category_id')
            ->get()
            ->pluck('category.assessment_category_id', 'id')
            ->toArray();
    }

    /**
     * Hitung baris nilai + peringkat untuk satu kumpulan peserta.
     *
     * Peringkat selalu dihitung di dalam kumpulan yang dikirim — itulah yang
     * membuat Grup A dan Grup B punya peringkat sendiri-sendiri.
     */
    private function rankRows($participants, $allScores, $allDeductions, array $globalCriteriaIds, array $critToAssessment, $assessmentCategories): array
    {
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

            // Total pengurangan = kolom kategori + tingkat, supaya angka di
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

        return $data;
    }
}
