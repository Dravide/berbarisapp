<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChampionRankTitle extends Model
{
    use HasFactory;

    protected $fillable = [
        'champion_category_id',
        'title',
        'rank_start',
        'rank_end',
        'sort_order',
    ];

    public function championCategory()
    {
        return $this->belongsTo(ChampionCategory::class);
    }

    /**
     * Check if a given rank falls within this title's range.
     */
    public function coversRank(int $rank): bool
    {
        return $rank >= $this->rank_start && $rank <= $this->rank_end;
    }
}
