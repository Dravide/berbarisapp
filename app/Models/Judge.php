<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Judge extends Model
{
    use HasFactory;

    protected $fillable = ['eventner_id', 'name', 'phone_number'];

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function assessmentCategories()
    {
        return $this->belongsToMany(AssessmentCategory::class);
    }

    public function competitionCategories()
    {
        return $this->belongsToMany(CompetitionCategory::class);
    }
}
