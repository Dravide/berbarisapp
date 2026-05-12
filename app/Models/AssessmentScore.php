<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'eventner_id',
        'judge_id',
        'registration_id',
        'assessment_criteria_id',
        'score',
        'is_finalized',
    ];

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function judge()
    {
        return $this->belongsTo(Judge::class);
    }

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    public function assessmentCriteria()
    {
        return $this->belongsTo(AssessmentCriteria::class, 'assessment_criteria_id');
    }
}
