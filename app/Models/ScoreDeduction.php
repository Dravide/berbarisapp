<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScoreDeduction extends Model
{
    protected $fillable = [
        'eventner_id',
        'registration_id',
        'deduction_criteria_id',
        'amount',
        'note',
    ];

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    public function deductionCriteria()
    {
        return $this->belongsTo(DeductionCriteria::class);
    }
}
