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

    /**
     * Berlaku untuk semua KATEGORI PENILAIAN di dalam satu tingkat lomba —
     * memotong NILAI AKHIR di luar kolom kategori. Tingkat lomba pemiliknya
     * ada di competition_category_id; sanksi satu tingkat tidak boleh ikut
     * memotong nilai tingkat lain.
     */
    public const SCOPE_GLOBAL = 'global';

    protected $fillable = ['eventner_id', 'assessment_category_id', 'competition_category_id', 'scope', 'name', 'sort_order'];

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function assessmentCategory()
    {
        return $this->belongsTo(AssessmentCategory::class);
    }

    /** Tingkat lomba pemilik kelompok global. NULL untuk scope 'category'. */
    public function competitionCategory()
    {
        return $this->belongsTo(CompetitionCategory::class);
    }

    /**
     * Rubrik pengurangan global: tidak menempel ke kategori penilaian mana pun,
     * tetapi terikat pada satu tingkat lomba (competition_category_id).
     * Selalu dipakai untuk membaca scope ini — jangan menebak dari
     * assessment_category_id NULL, karena NULL juga berarti data lama
     * yang belum ditentukan targetnya.
     */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->where('scope', self::SCOPE_GLOBAL);
    }

    /**
     * Kelompok global milik satu tingkat lomba. $id boleh NULL untuk
     * menandai "belum ditentukan tingkatnya".
     */
    public function scopeForLevel(Builder $query, $id): Builder
    {
        return $query->where('competition_category_id', $id);
    }

    public function scopeCategory(Builder $query): Builder
    {
        return $query->where('scope', self::SCOPE_CATEGORY);
    }

    public function isGlobal(): bool
    {
        return $this->scope === self::SCOPE_GLOBAL;
    }

    /**
     * Peta deduction_criteria_id => tingkat lomba, hanya untuk kelompok yang
     * ber-scope 'global'. Dipakai pembaca nilai agar sanksi satu tingkat tidak
     * ikut memotong nilai peserta tingkat lain.
     *
     * @return array<int, int|null>
     */
    public static function levelMapOfCriteria(int $eventnerId): array
    {
        return DeductionCriteria::whereHas('category', function ($q) use ($eventnerId) {
            $q->where('eventner_id', $eventnerId)->where('scope', self::SCOPE_GLOBAL);
        })
            ->with('category:id,competition_category_id')
            ->get()
            ->mapWithKeys(fn ($c) => [$c->id => $c->category?->competition_category_id])
            ->all();
    }

    /**
     * Saring pengurangan agar hanya menyisakan yang berlaku untuk satu tingkat
     * lomba: pengurangan per kategori selalu ikut, pengurangan tingkat hanya
     * bila tingkatnya sama dengan milik peserta.
     *
     * @param  iterable  $deductions  koleksi ScoreDeduction
     * @param  array<int, int|null>  $levelMap  hasil levelMapOfCriteria()
     * @return \Illuminate\Support\Collection
     */
    public static function applicableToLevel(iterable $deductions, $levelId, array $levelMap)
    {
        return collect($deductions)->reject(fn ($d) => array_key_exists($d->deduction_criteria_id, $levelMap)
            && (string) $levelMap[$d->deduction_criteria_id] !== (string) $levelId
        )->values();
    }

    public function criterias()
    {
        return $this->hasMany(DeductionCriteria::class)->orderBy('sort_order');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'sort_order', 'scope', 'competition_category_id'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Kategori Pengurangan {$this->name} telah di-{$eventName}");
    }
}
