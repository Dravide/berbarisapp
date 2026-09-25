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
     * Kategori juara relevan di tingkat lomba ini? True bila:
     * belum punya rubrik, punya rubrik global (competition_category_id null),
     * atau punya rubrik milik tingkat tsb.
     */
    public function isVisibleFor($competitionCategoryId): bool
    {
        $subs = $this->assessmentSubCategories;
        if ($subs->isEmpty()) {
            return true;
        }

        return $subs->contains(function ($sub) use ($competitionCategoryId) {
            $cat = $sub->category;
            if (!$cat) {
                return true;
            }

            return $cat->competition_category_id === null
                || (string) $cat->competition_category_id === (string) $competitionCategoryId;
        });
    }
}
