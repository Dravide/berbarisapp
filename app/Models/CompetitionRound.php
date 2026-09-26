<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CompetitionRound extends Model
{
    use HasFactory, LogsActivity;

    public const TYPE_PRELIMINARY = 'preliminary';

    public const TYPE_FINAL = 'final';

    protected $fillable = ['eventner_id', 'competition_category_id', 'name', 'type', 'sort_order'];

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function competitionCategory()
    {
        return $this->belongsTo(CompetitionCategory::class, 'competition_category_id');
    }

    public function assessmentCategories()
    {
        return $this->hasMany(AssessmentCategory::class, 'competition_round_id');
    }

    public function roundRegistrations()
    {
        return $this->hasMany(CompetitionRoundRegistration::class, 'competition_round_id');
    }

    /** Peserta yang lolos ke babak ini. */
    public function finalists()
    {
        return $this->belongsToMany(
            Registration::class,
            'competition_round_registrations',
            'competition_round_id',
            'registration_id'
        )->withPivot(['competition_group_id', 'seed', 'preliminary_total'])->withTimestamps();
    }

    public function scopeFinal($query)
    {
        return $query->where('type', self::TYPE_FINAL);
    }

    public function isFinal(): bool
    {
        return $this->type === self::TYPE_FINAL;
    }

    public function getFullNameAttribute(): string
    {
        $level = $this->competitionCategory?->full_name;

        return $level ? $level . ' — ' . $this->name : $this->name;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'type', 'sort_order'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Babak {$this->name} telah di-{$eventName}");
    }
}
