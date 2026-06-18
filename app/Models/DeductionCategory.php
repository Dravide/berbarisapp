<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DeductionCategory extends Model
{
    use LogsActivity;

    protected $fillable = ['eventner_id', 'assessment_category_id', 'name', 'sort_order'];

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function assessmentCategory()
    {
        return $this->belongsTo(AssessmentCategory::class);
    }

    public function criterias()
    {
        return $this->hasMany(DeductionCriteria::class)->orderBy('sort_order');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'sort_order'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Kategori Pengurangan {$this->name} telah di-{$eventName}");
    }
}
