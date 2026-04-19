<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentCategory extends Model
{
    use HasFactory;

    protected $fillable = ['eventner_id', 'name'];

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function subCategories()
    {
        return $this->hasMany(AssessmentSubCategory::class, 'assessment_category_id');
    }

    public function judges()
    {
        return $this->belongsToMany(Judge::class);
    }
}
