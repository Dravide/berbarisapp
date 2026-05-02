<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AssessmentSubCategory extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['assessment_category_id', 'name', 'sort_order'];

    public function category()
    {
        return $this->belongsTo(AssessmentCategory::class, 'assessment_category_id');
    }

    public function criterias()
    {
        return $this->hasMany(AssessmentCriteria::class, 'assessment_sub_category_id')->orderBy('sort_order');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'assessment_category_id'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Sub-Kategori {$this->name} telah di-{$eventName}");
    }
}
