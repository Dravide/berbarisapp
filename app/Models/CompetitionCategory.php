<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetitionCategory extends Model
{
    use HasFactory;

    protected $fillable = ['eventner_id', 'parent_id', 'venue_id', 'name', 'tanggal_pelaksanaan', 'kuota', 'max_registrations_per_school', 'registration_fee', 'sort_order'];

    protected function casts(): array
    {
        return [
            'registration_fee' => 'decimal:2',
        ];
    }

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

    public function venue()
    {
        return $this->belongsTo(EventnerVenue::class, 'venue_id');
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
        if (!$this->kuota) {
            return PHP_INT_MAX; // unlimited
        }
        return max(0, $this->kuota - $this->registrations()->count());
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

    /**
     * Kategori yang boleh dipilih pendaftar: tingkat (anak), atau induk lama
     * tanpa anak dari data flat sebelum hierarki diperkenalkan.
     *
     * Induk yang PUNYA anak sengaja disaring. Menyaring di daftar formulir saja
     * tidak cukup: id kategori datang dari DOM, jadi id induk bisa tetap dikirim
     * dan membuat pendaftaran mendarat di tingkat induk — bukan di tingkat
     * lomba. Scope ini jadi satu-satunya definisi "boleh dipilih", dipakai
     * render (tampil), toggleCategory (masuk keranjang), dan submit (dibuat).
     */
    public function scopeSelectable($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('parent_id');

            $q->orWhere(function ($sq) {
                $sq->whereNull('parent_id')->whereDoesntHave('children');
            });
        });
    }
}
