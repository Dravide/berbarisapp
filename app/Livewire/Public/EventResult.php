<?php

namespace App\Livewire\Public;

use App\Models\AssessmentScore;
use App\Models\ChampionCategory;
use App\Models\CompetitionGroup;
use App\Models\CompetitionRound;
use App\Models\CompetitionRoundRegistration;
use App\Models\Eventner;
use App\Models\Registration;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.frontend')]
class EventResult extends Component
{
    public $eventner;
    public $categories = [];
    public $selectedCategoryId;
    public $allRankings = [];

    /**
     * Grup yang sedang dilihat. '' = peringkat gabungan tingkat (perilaku lama).
     */
    public $selectedGroupId = '';

    /**
     * Tingkat terpilih sedang menyajikan hasil BABAK FINAL saja.
     *
     * Final adalah satu pool se-tingkat: tak ada grup yang bisa disaring, dan
     * nilai penyisihan tidak lagi ikut dijumlahkan — juara ditentukan nilai
     * final. Karena itu pemilih grup disembunyikan dan switchGroup() menolak.
     * Diisi ulang setiap calculateRankings(); lihat di sana kapan diset true.
     */
    public $finalOnly = false;

    public function mount($slug = null)
    {
        $resolved = app()->bound('current_eventner') ? app('current_eventner') : null;
        if ($resolved) {
            $this->eventner = $resolved;
        } else {
            $this->eventner = Eventner::approved()->where('slug', $slug)->firstOrFail();
        }

        $this->categories = $this->eventner->competitionCategories()
            ->whereNotNull('parent_id')
            ->with('parent')
            ->get()
            ->toArray();

        if (count($this->categories) > 0) {
            $this->selectedCategoryId = $this->categories[0]['id'];
        }

        $this->calculateRankings();
    }

    /**
     * Grup milik tingkat terpilih. Kosong saat tingkat itu final-only: babak
     * final tidak dibagi grup, jadi pemilih grup memang tak punya isi.
     */
    public function getGroupsProperty()
    {
        if (! $this->selectedCategoryId || $this->finalOnly) {
            return collect();
        }

        return CompetitionGroup::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->selectedCategoryId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function switchCategory($categoryId)
    {
        if ((string) $this->selectedCategoryId !== (string) $categoryId) {
            $this->selectedGroupId = '';
        }

        $this->selectedCategoryId = $categoryId;
        $this->calculateRankings();
    }

    /**
     * Ganti grup. Grup dari DOM wajib milik tingkat terpilih.
     *
     * Tingkat yang menyajikan hasil final tidak punya grup untuk disaring —
     * pemilihnya pun tidak dirender — jadi apa pun yang datang (mis. dari DOM
     * lama yang belum disegarkan) diabaikan dan tabel final dibiarkan utuh.
     */
    public function switchGroup($groupId)
    {
        $this->selectedGroupId = '';

        if ($this->finalOnly) {
            $this->resetErrorBag('selectedGroupId');
            $this->calculateRankings();

            return;
        }

        if ($groupId !== '' && $groupId !== null) {
            $ada = CompetitionGroup::where('eventner_id', $this->eventner->id)
                ->where('competition_category_id', $this->selectedCategoryId)
                ->find($groupId);

            if (! $ada) {
                $this->addError('selectedGroupId', 'Grup tidak ditemukan pada tingkat lomba ini.');

                return;
            }

            $this->selectedGroupId = (string) $ada->id;
        }

        $this->resetErrorBag('selectedGroupId');
        $this->calculateRankings();
    }

    public function calculateRankings()
    {
        $this->allRankings = [];

        // Only fetch champion categories that are marked as public.
        // Relasi bersarang ikut dimuat: babak rubrik dan bobotnya dibaca lewat
        // sub-kategori -> kategori, dan tanpa eager load itu jadi query per
        // kriteria di halaman publik.
        $championCategories = ChampionCategory::with([
            'assessmentSubCategories.category',
            'assessmentSubCategories.criterias',
            'rankTitles',
            'tiebreakSubCategories.criterias',
            'criterias.subCategory.category',
            'tiebreakCriterias.subCategory.category',
        ])
            ->where('eventner_id', $this->eventner->id)
            ->where('is_public', true)
            ->get();

        if ($championCategories->isEmpty()) {
            $this->finalOnly = false;

            return;
        }

        // Babak tingkat ini. Kategori juara tidak menyimpan babaknya sendiri —
        // babaknya hanya terbaca dari rubrik yang dipakai (boundRoundId()).
        $rounds = CompetitionRound::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->selectedCategoryId)
            ->get()
            ->keyBy('id');

        $finalRound = $rounds->first(fn ($round) => $round->isFinal());
        $championRoundId = null;

        // Tingkat bergrup yang sudah sampai babak final menyajikan hasil FINAL:
        // juara ditentukan nilai final, bukan jumlah nilai penyisihan. Halaman
        // ini lalu memuat kategori juara yang terikat babak final saja, dan
        // pemilih grup tidak lagi ditawarkan — final adalah satu pool
        // se-tingkat, jadi tak ada grup yang bisa disaring.
        //
        // Tingkat tanpa kategori juara berbabak-final tidak berubah sedikit pun:
        // kategori juara lama (rubriknya belum ditandai babak) tetap memakai
        // seluruh nilainya seperti dulu.
        if ($finalRound && $championCategories->contains(
            fn ($c) => (string) $c->boundRoundId() === (string) $finalRound->id
        )) {
            $championRoundId = $finalRound->id;

            $championCategories = $championCategories
                ->filter(fn ($c) => (string) $c->boundRoundId() === (string) $finalRound->id)
                ->values();
        }

        $this->finalOnly = $championRoundId !== null;

        if ($this->finalOnly) {
            $this->selectedGroupId = '';
        }

        $groupId = $this->selectedGroupId !== '' ? $this->selectedGroupId : null;

        // Saat grup dipilih, kategori juara yang rubriknya khusus grup lain
        // tidak ikut — supaya "Juara Grup A" dan "Juara Grup B" tidak saling
        // bercampur di satu tabel.
        if ($groupId !== null) {
            $championCategories = $championCategories
                ->filter(fn ($c) => $c->isVisibleFor($this->selectedCategoryId, $groupId, $championRoundId))
                ->values();
        }

        $participants = Registration::where('eventner_id', $this->eventner->id)
            ->where('competition_category_id', $this->selectedCategoryId)
            ->when($groupId !== null, fn ($q) => $q->where('competition_group_id', $groupId))
            ->when($this->finalOnly, fn ($q) => $q->whereIn('id', CompetitionRoundRegistration::where('eventner_id', $this->eventner->id)
                ->where('competition_round_id', $finalRound->id)
                ->pluck('registration_id')))
            ->orderBy('nama_sekolah')
            ->get();

        $allScores = AssessmentScore::where('eventner_id', $this->eventner->id)
            ->whereIn('registration_id', $participants->pluck('id'))
            ->get()
            ->groupBy('registration_id');

        $allDeductions = \App\Models\ScoreDeduction::where('eventner_id', $this->eventner->id)
            ->get()
            ->groupBy('registration_id');

        // Pengurangan ber-scope 'global' hanya berlaku di tingkat lombanya
        // sendiri — sanksi tingkat lain tidak boleh ikut terpotong.
        $deductionLevelMap = \App\Models\DeductionCategory::levelMapOfCriteria($this->eventner->id);
        $deductionRoundMap = \App\Models\DeductionCategory::roundMapOfCriteria($this->eventner->id);

        $allCriteriaWeightMap = \App\Models\AssessmentCriteria::whereIn(
            'assessment_sub_category_id',
            \App\Models\AssessmentSubCategory::whereIn(
                'assessment_category_id',
                \App\Models\AssessmentCategory::where('eventner_id', $this->eventner->id)->pluck('id')
            )->pluck('id')
        )->pluck('weight', 'id')->toArray();

        foreach ($championCategories as $champion) {
            $criteriaMap = $champion->scoringCriteriaWeights($championRoundId, $groupId);

            // Build tiebreak criteria map
            $tiebreakCriteriaMap = $champion->tiebreakCriteriaWeights($championRoundId, $groupId);

            $participantScores = [];
            foreach ($participants as $participant) {
                $scores = $allScores->get($participant->id, collect());

                $total = 0;
                $tiebreakTotal = 0;
                $otherTotal = 0;

                foreach ($scores as $score) {
                    $weight = $criteriaMap[$score->assessment_criteria_id] ?? null;
                    if ($weight !== null) {
                        $scoreVal = (int) $score->score * $weight;
                        $total += $scoreVal;
                    } elseif (! $this->finalOnly) {
                        // Kriteria di luar kategori juara ini hanya jadi kunci
                        // urutan terakhir. Di tampilan final ia dibuang, bukan
                        // ditampung: nilai penyisihan tak boleh menyentuh
                        // urutan juara final sekecil apa pun perannya.
                        $weightOther = $allCriteriaWeightMap[$score->assessment_criteria_id] ?? 1;
                        $otherTotal += (int) $score->score * $weightOther;
                    }

                    // Tiebreak score (separate calculation)
                    $tbWeight = $tiebreakCriteriaMap[$score->assessment_criteria_id] ?? null;
                    if ($tbWeight !== null) {
                        $tiebreakTotal += (int) $score->score * $tbWeight;
                    }
                }

                $deductions = $allDeductions->get($participant->id, collect());
                $deductions = \App\Models\DeductionCategory::applicableToLevel(
                    $deductions,
                    $participant->competition_category_id,
                    $deductionLevelMap
                );
                $deductions = \App\Models\DeductionCategory::applicableToRound(
                    $deductions,
                    $deductionRoundMap,
                    $championRoundId
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

            usort($participantScores, function ($a, $b) {
                if ($b['total'] !== $a['total']) {
                    return $b['total'] <=> $a['total'];
                }
                if ($b['tiebreak_total'] !== $a['tiebreak_total']) {
                    return $b['tiebreak_total'] <=> $a['tiebreak_total'];
                }
                if ($b['other_total'] !== $a['other_total']) {
                    return $b['other_total'] <=> $a['other_total'];
                }
                if ($a['deduction'] !== $b['deduction']) {
                    return $a['deduction'] <=> $b['deduction'];
                }
                return $a['urutan_tampil'] <=> $b['urutan_tampil'];
            });

            // Peserta tanpa nilai (skor 0) bukan juara — buang SEBELUM nomor
            // peringkat dihitung, kalau tidak mereka menggeser peringkat juara.
            $participantScores = array_values(array_filter(
                $participantScores,
                fn($ps) => $ps['total'] > 0
            ));

            foreach ($participantScores as $index => &$ps) {
                $rank = $index + 1;
                $ps['rank'] = $rank;
                $ps['title'] = $champion->titleForRank($rank);
            }
            unset($ps);

            if (count($participantScores) > 0) {
                $this->allRankings[] = [
                    'champion' => $champion,
                    'rankTitles' => $champion->rankTitles,
                    'participants' => array_values($participantScores),
                ];
            }
        }
    }

    public function render()
    {
        return view('livewire.public.event-result', [
            'eventner' => $this->eventner,
            'groups' => $this->groups,
            'selectedGroupId' => $this->selectedGroupId,
        ])->title('Hasil Perlombaan - ' . $this->eventner->nama_event)
            ->layoutData(['eventner' => $this->eventner]);
    }
}
