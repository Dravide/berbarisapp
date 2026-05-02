<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AssessmentCriteria extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['assessment_sub_category_id', 'name', 'score_options', 'weight', 'sort_order'];

    protected $casts = [
        'score_options' => 'array',
        'weight' => 'decimal:2',
    ];

    public function subCategory()
    {
        return $this->belongsTo(AssessmentSubCategory::class, 'assessment_sub_category_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'score_options', 'weight'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Kriteria {$this->name} telah di-{$eventName}");
    }
}
