<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Denah lapangan satu tingkat lomba (atau denah umum bila kategorinya null).
 *
 * Papan + zona-zonanya. Sengaja tidak menyimpan peserta, sekolah, maupun juri:
 * ia gambar tata letak, bukan penugasan. Itu yang membuatnya aman diterbitkan
 * ke halaman publik, dan tak bisa menyimpang dari daftar peserta.
 */
class CompetitionLayout extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'eventner_id',
        'competition_category_id',
        'eventner_venue_id',
        'name',
        'ground_width',
        'ground_height',
        'background_image',
        'is_published',
        'published_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'ground_width' => 'decimal:2',
            'ground_height' => 'decimal:2',
        ];
    }

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    /** null = denah umum lintas tingkat lomba. */
    public function competitionCategory()
    {
        return $this->belongsTo(CompetitionCategory::class, 'competition_category_id');
    }

    public function venue()
    {
        return $this->belongsTo(EventnerVenue::class, 'eventner_venue_id');
    }

    public function zones()
    {
        return $this->hasMany(LayoutZone::class)->orderBy('sort_order')->orderBy('id');
    }

    /** Denah yang boleh dilihat publik. */
    public function scopeTerbit(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /** Rasio papan (lebar ÷ tinggi); 16:9 dipakai bila ukurannya belum diisi. */
    public function getRasioAttribute(): float
    {
        $tinggi = (float) $this->ground_height;

        return $tinggi > 0 ? (float) $this->ground_width / $tinggi : 16 / 9;
    }

    /**
     * Ukuran kertas PDF yang cocok dengan rasio papan.
     *
     * Denah 40 × 22 m yang dipaksa ke A4 potret jadi tak terbaca, jadi
     * rasionya yang menentukan orientasinya.
     */
    public function getKertasPdfAttribute(): array
    {
        return $this->rasio >= 1 ? ['a4', 'landscape'] : ['a4', 'portrait'];
    }

    /** Ada minimal satu zona? Dipakai untuk gerbang "denah ini terisi". */
    public function sudahTerisi(): bool
    {
        return $this->zones()->exists();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'is_published', 'ground_width', 'ground_height'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Denah {$this->name} telah di-{$eventName}");
    }
}
