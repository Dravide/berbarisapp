<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AssessmentCategory extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['eventner_id', 'name', 'sort_order'];

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function subCategories()
    {
        return $this->hasMany(AssessmentSubCategory::class, 'assessment_category_id')->orderBy('sort_order');
    }

    public function judges()
    {
        return $this->belongsToMany(Judge::class);
    }

    public function championCategories()
    {
        return $this->belongsToMany(ChampionCategory::class, 'champion_assessment');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'sort_order'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Kategori Penilaian {$this->name} telah di-{$eventName}");
    }
}
