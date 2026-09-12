<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventnerVenue extends Model
{
    use HasFactory;

    protected $fillable = [
        'eventner_id',
        'name',
        'alamat',
        'latitude',
        'longitude',
        'google_maps_url',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function competitionCategories()
    {
        return $this->hasMany(CompetitionCategory::class, 'venue_id');
    }

    /** Link peta: pakai URL manual kalau ada, kalau tidak susun dari koordinat. */
    public function getMapsUrlAttribute(): ?string
    {
        if ($this->google_maps_url) {
            return $this->google_maps_url;
        }

        if ($this->latitude && $this->longitude) {
            return 'https://www.google.com/maps?q=' . $this->latitude . ',' . $this->longitude;
        }

        return null;
    }

    /** Satu baris siap cetak: "SMA 1 — Jl. Melati 3". */
    public function getLabelAttribute(): string
    {
        return $this->alamat ? $this->name . ' — ' . $this->alamat : $this->name;
    }
}
