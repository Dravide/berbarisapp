<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeductionCriteria extends Model
{
    protected $fillable = ['deduction_category_id', 'name', 'deduction_options', 'sort_order'];

    protected $casts = [
        'deduction_options' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(DeductionCategory::class, 'deduction_category_id');
    }

    public function scoreDeductions()
    {
        return $this->hasMany(ScoreDeduction::class);
    }
}
