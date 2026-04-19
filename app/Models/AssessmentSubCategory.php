<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentSubCategory extends Model
{
    use HasFactory;

    protected $fillable = ['assessment_category_id', 'name'];

    public function category()
    {
        return $this->belongsTo(AssessmentCategory::class, 'assessment_category_id');
    }

    public function criterias()
    {
        return $this->hasMany(AssessmentCriteria::class, 'assessment_sub_category_id');
    }
}
