<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetitionCategory extends Model
{
    use HasFactory;

    protected $fillable = ['eventner_id', 'parent_id', 'name', 'tanggal_pelaksanaan', 'kuota', 'max_registrations_per_school'];

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function judges()
    {
        return $this->belongsToMany(Judge::class);
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function assessmentCategories()
    {
        return $this->hasMany(AssessmentCategory::class, 'competition_category_id');
    }

    public function remainingSlots(): int
    {
        return max(0, ($this->kuota ?? 0) - $this->registrations()->count());
    }

    public function getFullNameAttribute(): string
    {
        if ($this->parent_id && $this->parent) {
            return $this->parent->name . ' — ' . $this->name;
        }
        return $this->name;
    }

    public function isParent(): bool
    {
        return is_null($this->parent_id);
    }

    public function isChild(): bool
    {
        return !is_null($this->parent_id);
    }
}
