<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChampionCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'eventner_id',
        'name',
        'description',
        'quantity',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function assessmentSubCategories()
    {
        return $this->belongsToMany(AssessmentSubCategory::class, 'champion_assessment', 'champion_category_id', 'assessment_sub_category_id');
    }

    public function rankTitles()
    {
        return $this->hasMany(ChampionRankTitle::class)->orderBy('sort_order')->orderBy('rank_start');
    }

    /**
     * Gelar untuk satu peringkat, mis. "Juara Utama 1". Satu gelar yang
     * mencakup lebih dari satu peringkat (mis. Rank 1-3 = "Juara Utama")
     * dipecah per posisi supaya tiap juara dapat gelar yang berbeda; gelar
     * yang hanya mencakup satu peringkat dipakai apa adanya.
     *
     * Dipakai bersama oleh PDF rekap, /hasil, /champions publik, scoreboard,
     * dan sertifikat. Null bila tidak ada gelar yang mencakup peringkat itu.
     */
    public function titleForRank(int $rank): ?string
    {
        foreach ($this->rankTitles as $rt) {
            if ($rt->coversRank($rank)) {
                return $rt->rank_start !== $rt->rank_end
                    ? $rt->title . ' ' . ($rank - $rt->rank_start + 1)
                    : $rt->title;
            }
        }

        return null;
    }

    public function tiebreakSubCategories()
    {
        return $this->belongsToMany(AssessmentSubCategory::class, 'champion_tiebreak', 'champion_category_id', 'assessment_sub_category_id');
    }

    /**
     * Kriteria terpilih untuk perhitungan juara. Kosong berarti "seluruh
     * kriteria dari sub-kategori yang dicentang" — lihat scoringCriteriaWeights().
     */
    public function criterias()
    {
        return $this->belongsToMany(AssessmentCriteria::class, 'champion_criteria', 'champion_category_id', 'assessment_criteria_id');
    }

    /**
     * Kriteria terpilih untuk tie break. Aturan kosong/terisi sama dengan criterias().
     */
    public function tiebreakCriterias()
    {
        return $this->belongsToMany(AssessmentCriteria::class, 'champion_tiebreak_criteria', 'champion_category_id', 'assessment_criteria_id');
    }

    /**
     * Peta [criteria_id => bobot] yang masuk perhitungan juara.
     *
     * Kriteria yang dicentang eksplisit menang. Bila belum ada satupun yang
     * dicentang — kondisi semua kategori juara sebelum fitur ini ada — pakai
     * seluruh kriteria dari sub-kategori yang dicentang, supaya arti kategori
     * juara lama tidak berubah setelah rilis.
     *
     * $roundId menyaring kriteria ke rubrik babak itu. Tanpa penyaringan,
     * kategori juara yang mencakup rubrik Penyisihan DAN Final menjumlahkan
     * keduanya — padahal nilai final adalah penentu juara dan nilai penyisihan
     * hanya penentu siapa yang lolos. Rubrik tanpa babak (perilaku lama) selalu
     * ikut, sehingga penyaringan tidak mengubah kategori juara yang rubriknya
     * belum ditandai.
     *
     * $groupId sengaja TIDAK lagi dipakai menyaring. Dulu ia menyaring rubrik
     * bergrup supaya tabel tiap grup hanya menjumlahkan kriterianya sendiri;
     * sejak lembar nilai ditentukan SERI — dan satu grup boleh memuat beberapa
     * seri — saringan itu justru membuang rubrik seri lain, dan total pasukan
     * Seri B jadi tidak sebanding di tabel Grup A. Cakupan grup tetap bekerja,
     * tapi sebagai saringan PESERTA di ChampionCalculator::rankOrdered().
     * Parameternya dibiarkan ada demi kompatibilitas pemanggil.
     *
     * @return array<int, mixed>
     */
    public function scoringCriteriaWeights($roundId = null, $groupId = null): array
    {
        return $this->resolveCriteriaWeights($this->criterias, $this->assessmentSubCategories, $roundId);
    }

    /**
     * Peta [criteria_id => bobot] untuk tie break. Lihat scoringCriteriaWeights().
     *
     * @return array<int, mixed>
     */
    public function tiebreakCriteriaWeights($roundId = null, $groupId = null): array
    {
        return $this->resolveCriteriaWeights($this->tiebreakCriterias, $this->tiebreakSubCategories, $roundId);
    }

    /**
     * @param  \Illuminate\Support\Collection  $explicit  kriteria yang dicentang langsung
     * @param  \Illuminate\Support\Collection  $fallbackSubs  sub-kategori yang dicentang
     */
    private function resolveCriteriaWeights($explicit, $fallbackSubs, $roundId = null): array
    {
        // Rubrik induk tiap sub-kategori membawa babaknya. Dimuat di sini
        // supaya penyaringan tidak menambah query per kriteria.
        // concat, bukan +: Collection Eloquent tidak mendukung operator union.
        $rubrics = $fallbackSubs->map(fn ($sub) => $sub->category)
            ->concat($explicit->map(fn ($crit) => $crit->subCategory?->category))
            ->filter()
            ->keyBy('id');

        $allowed = function ($rubric) use ($roundId) {
            if (! $rubric) {
                return true;
            }

            if ($roundId && $rubric->competition_round_id !== null
                && (string) $rubric->competition_round_id !== (string) $roundId) {
                return false;
            }

            return true;
        };

        if ($explicit->isNotEmpty()) {
            return $explicit
                ->filter(fn ($crit) => $allowed($rubrics->get($crit->subCategory?->category?->id)))
                ->mapWithKeys(fn ($crit) => [$crit->id => $crit->weight ?? 1])
                ->all();
        }

        $weights = [];
        foreach ($fallbackSubs as $sub) {
            if (! $allowed($sub->category)) {
                continue;
            }

            foreach ($sub->criterias as $crit) {
                $weights[$crit->id] = $crit->weight ?? 1;
            }
        }

        return $weights;
    }

    /**
     * Babak yang mengikat kategori juara ini, atau null bila tak bisa
     * dipastikan.
     *
     * Kategori juara tidak punya kolom babak sendiri — babaknya hanya terbaca
     * dari rubrik yang dipakai. Bila rubriknya menunjuk SATU babak, itulah
     * babaknya. Rubrik tanpa babak tidak dihitung, supaya kategori juara lama
     * (yang dibuat sebelum babak ada) tetap berperilaku seperti dulu, dan
     * campuran dua babak menghasilkan null: kategori lintas babak tidak boleh
     * diam-diam dianggap milik salah satunya.
     *
     * Dipakai halaman publik untuk tahu bahwa sebuah tingkat hanya menyajikan
     * hasil babak final — lihat EventResult::finalOnly.
     */
    public function boundRoundId(): ?int
    {
        $ids = $this->assessmentSubCategories->map(fn ($sub) => $sub->category)
            ->concat($this->criterias->map(fn ($crit) => $crit->subCategory?->category))
            ->concat($this->tiebreakCriterias->map(fn ($crit) => $crit->subCategory?->category))
            ->filter()
            ->pluck('competition_round_id')
            ->filter(fn ($id) => $id !== null)
            ->unique()
            ->values();

        return $ids->count() === 1 ? (int) $ids->first() : null;
    }

    /**
     * Kategori juara relevan di tingkat lomba ini? True bila:
     * belum punya rubrik, punya rubrik global (competition_category_id null),
     * atau punya rubrik milik tingkat tsb.
     *
     * Kirim $competitionRoundId untuk hal yang sama pada babak: kategori juara
     * yang rubriknya khusus babak Final tidak ikut tampil saat halaman dibuka
     * pada babak Penyisihan. Rubrik tanpa babak selalu dianggap.
     *
     * $competitionGroupId sengaja tidak lagi dipakai menyaring rubrik — alasan
     * lengkapnya di scoringCriteriaWeights(). Ia masih diterima demi pemanggil
     * lama; cakupan grup tetap bekerja sebagai saringan peserta.
     */
    public function isVisibleFor($competitionCategoryId, $competitionGroupId = null, $competitionRoundId = null): bool
    {
        $subs = $this->assessmentSubCategories;
        if ($subs->isEmpty()) {
            return true;
        }

        return $subs->contains(function ($sub) use ($competitionCategoryId, $competitionRoundId) {
            $cat = $sub->category;
            if (!$cat) {
                return true;
            }

            $levelMatch = $cat->competition_category_id === null
                || (string) $cat->competition_category_id === (string) $competitionCategoryId;

            if (!$levelMatch) {
                return false;
            }

            if ($competitionRoundId
                && $cat->competition_round_id !== null
                && (string) $cat->competition_round_id !== (string) $competitionRoundId) {
                return false;
            }

            return true;
        });
    }
}
