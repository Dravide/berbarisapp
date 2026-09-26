<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CompetitionGroup extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['eventner_id', 'competition_category_id', 'name', 'sort_order'];

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function competitionCategory()
    {
        return $this->belongsTo(CompetitionCategory::class, 'competition_category_id');
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class, 'competition_group_id');
    }

    public function assessmentCategories()
    {
        return $this->hasMany(AssessmentCategory::class, 'competition_group_id');
    }

    public function roundRegistrations()
    {
        return $this->hasMany(CompetitionRoundRegistration::class, 'competition_group_id');
    }

    /**
     * Juri yang menilai grup ini, diturunkan dari rubrik yang menempel ke grup.
     * Juri terikat ke rubrik (assessment_categories), bukan ke tingkat lomba —
     * jadi tidak perlu pivot baru untuk "juri berbeda per grup".
     */
    public function judges()
    {
        return Judge::whereHas('assessmentCategories', function ($q) {
            $q->where('competition_group_id', $this->id);
        });
    }

    public function getFullNameAttribute(): string
    {
        $level = $this->competitionCategory?->full_name;

        return $level ? $level . ' — ' . $this->name : $this->name;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'sort_order'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Grup {$this->name} telah di-{$eventName}");
    }
}
