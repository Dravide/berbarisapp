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

    protected $fillable = ['eventner_id', 'assessment_category_id', 'competition_category_id', 'competition_round_id', 'scope', 'name', 'sort_order'];

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
     * Babak yang dibatasi kelompok ini. NULL = berlaku di semua babak.
     *
     * Sama artinya dengan AssessmentCategory::$competition_round_id, dan
     * sengaja begitu: pengurangan per kategori ikut babak lewat rubriknya,
     * sedangkan pengurangan tingkat tidak menempel ke rubrik mana pun — tanpa
     * kolom ini sanksi fase grup ikut memotong NILAI AKHIR di fase final.
     */
    public function competitionRound()
    {
        return $this->belongsTo(CompetitionRound::class, 'competition_round_id');
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
     *
     * $roundId menyaring ke babak tertentu; kelompok tanpa babak (NULL) tetap
     * ikut karena berlaku di semua babak — klausa yang sama dengan
     * AssessmentCategory::scopeForLevel(). Tanpa $roundId saringannya mati,
     * untuk pemanggil yang memang tidak punya konteks babak (tingkat tanpa
     * babak, atau halaman publik yang meranking lintas babak).
     */
    public function scopeForLevel(Builder $query, $id, ?int $roundId = null): Builder
    {
        $query->where('competition_category_id', $id);

        if ($roundId) {
            $query->where(function ($q) use ($roundId) {
                $q->where('competition_round_id', $roundId)
                    ->orWhereNull('competition_round_id');
            });
        }

        return $query;
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

    /**
     * Peta deduction_criteria_id => babak, untuk SEMUA kelompok pengurangan
     * milik satu event. NULL = berlaku di semua babak.
     *
     * Pengurangan per kategori ikut babak lewat rubrik yang ditempelinya;
     * pengurangan tingkat lewat kolomnya sendiri. Dua sumber itu disatukan di
     * sini supaya pembaca yang punya konteks babak tak perlu tahu bedanya.
     *
     * @return array<int, int|null>
     */
    public static function roundMapOfCriteria(int $eventnerId): array
    {
        return DeductionCriteria::whereHas('category', fn ($q) => $q->where('eventner_id', $eventnerId))
            ->with([
                'category:id,assessment_category_id,competition_round_id,scope',
                'category.assessmentCategory:id,competition_round_id',
            ])
            ->get()
            ->mapWithKeys(function ($c) {
                $cat = $c->category;

                if (! $cat) {
                    return [];
                }

                $round = $cat->isGlobal()
                    ? $cat->competition_round_id
                    : $cat->assessmentCategory?->competition_round_id;

                return [$c->id => $round !== null ? (int) $round : null];
            })
            ->all();
    }

    /**
     * Saring pengurangan agar hanya menyisakan yang berlaku di satu babak.
     *
     * $roundId NULL = pemanggil tidak punya konteks babak (mis. peringkat
     * lintas babak), jadi saringannya mati dan seluruh baris tetap ikut —
     * perilaku sebelum kolom babak ada. Kriteria yang tidak ada di peta ikut
     * apa adanya: peta hanya memuat pengurangan milik event ini.
     *
     * @param  iterable  $deductions  koleksi ScoreDeduction
     * @param  array<int, int|null>  $roundMap  hasil roundMapOfCriteria()
     * @return \Illuminate\Support\Collection
     */
    public static function applicableToRound(iterable $deductions, array $roundMap, ?int $roundId)
    {
        if ($roundId === null) {
            return collect($deductions);
        }

        return collect($deductions)->reject(fn ($d) => ($roundMap[$d->deduction_criteria_id] ?? null) !== null
            && (int) $roundMap[$d->deduction_criteria_id] !== $roundId
        )->values();
    }

    public function criterias()
    {
        return $this->hasMany(DeductionCriteria::class)->orderBy('sort_order');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'sort_order', 'scope', 'competition_category_id', 'competition_round_id'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Kategori Pengurangan {$this->name} telah di-{$eventName}");
    }
}
