<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Judge extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['eventner_id', 'name', 'phone_number', 'photo', 'access_token'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Token akses tablet juri — secret, dipakai di /juri/{token}.
            if (!$model->access_token) {
                $model->access_token = \Illuminate\Support\Str::random(16);
            }
        });
    }

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

    /**
     * Grup yang dinilai juri ini.
     *
     * Relasi sungguhan, berbeda dari CompetitionGroup::judges() jaman dulu
     * yang mengembalikan Builder dan tak bisa diakses sebagai properti.
     * Scope-nya disaring ke 'group' saja: baris final/ungrouped/level tak
     * punya competition_group_id, jadi tanpa saringan ini pivotnya kosong.
     */
    public function competitionGroups()
    {
        return $this->belongsToMany(CompetitionGroup::class, 'competition_group_judge')
            ->wherePivot('scope', 'group');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'phone_number'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Juri {$this->name} telah di-{$eventName}");
    }
}
