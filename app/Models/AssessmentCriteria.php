<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentCriteria extends Model
{
    use HasFactory;

    protected $fillable = ['assessment_sub_category_id', 'name', 'score_options'];

    protected $casts = [
        'score_options' => 'array',
    ];

    public function subCategory()
    {
        return $this->belongsTo(AssessmentSubCategory::class, 'assessment_sub_category_id');
    }
}
