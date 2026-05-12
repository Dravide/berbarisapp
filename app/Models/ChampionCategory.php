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
    ];

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function assessmentCategories()
    {
        return $this->belongsToMany(AssessmentCategory::class, 'champion_assessment');
    }

    public function rankTitles()
    {
        return $this->hasMany(ChampionRankTitle::class)->orderBy('sort_order')->orderBy('rank_start');
    }
}
