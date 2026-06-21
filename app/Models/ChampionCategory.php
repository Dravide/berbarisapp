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

    public function tiebreakSubCategories()
    {
        return $this->belongsToMany(AssessmentSubCategory::class, 'champion_tiebreak', 'champion_category_id', 'assessment_sub_category_id');
    }
}
