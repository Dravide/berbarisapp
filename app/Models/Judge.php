<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Judge extends Model
{
    use HasFactory, LogsActivity;

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'phone_number'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Juri {$this->name} telah di-{$eventName}");
    }
}
