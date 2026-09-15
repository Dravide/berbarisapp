<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DeductionCategory extends Model
{
    use LogsActivity;

    /** Menempel pada satu Kategori Penilaian — hanya memotong kolom kategori itu. */
    public const SCOPE_CATEGORY = 'category';

    /** Berlaku semua tingkat lomba — memotong NILAI AKHIR di luar kolom kategori. */
    public const SCOPE_GLOBAL = 'global';

    protected $fillable = ['eventner_id', 'assessment_category_id', 'scope', 'name', 'sort_order'];

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function assessmentCategory()
    {
        return $this->belongsTo(AssessmentCategory::class);
    }

    /**
     * Rubrik pengurangan global: tidak menempel ke kategori mana pun.
     * Selalu dipakai untuk membaca scope ini — jangan menebak dari
     * assessment_category_id NULL, karena NULL juga berarti data lama
     * yang belum ditentukan targetnya.
     */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->where('scope', self::SCOPE_GLOBAL);
    }

    public function scopeCategory(Builder $query): Builder
    {
        return $query->where('scope', self::SCOPE_CATEGORY);
    }

    public function isGlobal(): bool
    {
        return $this->scope === self::SCOPE_GLOBAL;
    }

    public function criterias()
    {
        return $this->hasMany(DeductionCriteria::class)->orderBy('sort_order');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'sort_order', 'scope'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Kategori Pengurangan {$this->name} telah di-{$eventName}");
    }
}
