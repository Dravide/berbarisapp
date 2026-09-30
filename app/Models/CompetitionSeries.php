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
 *   CompetitionGroup  -> tabel peringkat + nomor undian + siapa yang menilai
 *   CompetitionSeries -> lembar nilai (rubrik) saja
 *
 * Seri tidak menentukan juri. Juri ditugaskan ke grup (dan ke rubriknya masing
 * masing), jadi "juri seri apa" bukan pertanyaan yang punya jawaban.
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
     * Juri yang menilai seri ini.
     *
     * Bukan relasi Eloquent: `$seri->judges` melempar LogicException karena ini
     * Builder polos. Dipertahankan sebagai pemanggilan method supaya penelepon
     * yang butuh terpaksa sadar bentuknya.
     *
     * Isinya pun sudah tak bermakna: juri ditugaskan ke grup, bukan ke seri,
     * lalu dibagi lagi per rubrik. Dihitung dari rubrik berseri ini, hasilnya
     * justru menyesatkan — rubrik seri yang belum dibagi akan melaporkan
     * seluruh juri tingkat, sedangkan rubrik yang sudah dibagi hanya
     * melaporkan sebagiannya. Nol pemanggil sejak penugasan pindah ke grup;
     * sisakan hanya kalau kelak ada layar ringkasan yang memang butuh.
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
