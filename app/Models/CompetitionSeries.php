<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Seri urutan perlombaan di dalam satu tingkat lomba ("Seri A" / "Seri B").
 *
 * Sumbu ini lepas dari CompetitionGroup dengan sengaja: dua pasukan di grup
 * yang sama boleh ikut seri berbeda, dan satu seri boleh tersebar di beberapa
 * grup. Pembagian tugasnya —
 *
 *   CompetitionGroup  -> tabel peringkat + nomor undian
 *   CompetitionSeries -> lembar nilai (rubrik) + cakupan juri
 *
 * Karena itu judges() di sini bukan sekadar pelengkap seperti di grup: juri
 * memang terikat ke seri lewat rubrik yang menempel ke seri itu.
 */
class CompetitionSeries extends Model
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
        return $this->hasMany(Registration::class, 'competition_series_id');
    }

    public function assessmentCategories()
    {
        return $this->hasMany(AssessmentCategory::class, 'competition_series_id');
    }

    /**
     * Juri yang menilai seri ini, diturunkan dari rubrik yang menempel ke seri.
     *
     * Juri terikat ke rubrik (assessment_categories), bukan ke tingkat lomba —
     * jadi "juri berbeda per seri" tidak butuh pivot baru, persis seperti
     * "juri berbeda per grup" pada CompetitionGroup::judges().
     */
    public function judges()
    {
        return Judge::whereHas('assessmentCategories', function ($q) {
            $q->where('competition_series_id', $this->id);
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
            ->setDescriptionForEvent(fn (string $eventName) => "Seri {$this->name} telah di-{$eventName}");
    }
}
