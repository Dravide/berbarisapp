<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AssessmentCategory extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'eventner_id',
        'competition_category_id',
        'competition_group_id',
        'competition_round_id',
        'name',
        'sort_order',
    ];

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function competitionCategory()
    {
        return $this->belongsTo(CompetitionCategory::class, 'competition_category_id');
    }

    public function competitionGroup()
    {
        return $this->belongsTo(CompetitionGroup::class, 'competition_group_id');
    }

    public function competitionRound()
    {
        return $this->belongsTo(CompetitionRound::class, 'competition_round_id');
    }

    /**
     * Rubrik yang berlaku untuk sebuah tingkat, disaring per grup dan babak.
     *
     * Satu-satunya definisi "rubrik mana yang boleh dinilai" — dipakai halaman
     * juri, panitia input nilai, dan finalisasi. Sengaja mengembalikan builder
     * yang sudah memuat klausa tingkat/grup/babak, supaya pemanggil boleh
     * menumpuk whereHas('judges') ATAU memakai daftar ini apa adanya untuk
     * cabang fallback. Kalau klausa grup hanya ditempel di cabang whereHas,
     * cabang fallback "rubrik kosong → semua rubrik tingkat" akan membocorkan
     * rubrik Grup A ke juri Grup B.
     *
     * Aturan grup: rubrik tanpa grup berlaku di mana saja; rubrik bergrup hanya
     * berlaku untuk peserta bergrup itu. Peserta yang belum dibagi grup karena
     * itu hanya melihat rubrik tanpa grup — bukan rubrik semua grup. Sebelumnya
     * `group_id NULL` diperlakukan sebagai "berlaku semua grup", dan
     * konsekuensinya peserta yang belum bergrup melihat kolom penilai Grup A
     * dan Grup B sekaligus padahal ia tak masuk grup mana pun.
     *
     * Konsekuensi yang perlu diketahui operator: tingkat yang rubriknya SUDAH
     * ditandai per grup tetapi pesertanya belum dibagi akan menampilkan lembar
     * kosong. Bagi grupnya lebih dulu, atau biarkan rubriknya tanpa grup.
     *
     * NULL pada babak berbeda artinya: tanpa saringan babak. Tingkat tanpa
     * baris babak memang tidak punya babak untuk disaring, jadi rubriknya harus
     * tetap terpakai.
     *
     * @see scopeForLevel() untuk perhitungan lintas-grup
     */
    public function scopeForEntry($query, ?int $competitionCategoryId, ?int $groupId = null, ?int $roundId = null)
    {
        return $query
            ->forLevel($competitionCategoryId, $roundId)
            ->where(function ($q) use ($groupId) {
                // Tanpa grup: hanya rubrik tanpa grup. Rubrik bergrup sengaja
                // TIDAK ikut — pemisahan grup jadi tak berarti kalau peserta
                // yang belum dibagi tetap melihat semua rubrik grup.
                if (! $groupId) {
                    $q->whereNull('competition_group_id');

                    return;
                }

                $q->where('competition_group_id', $groupId)->orWhereNull('competition_group_id');
            });
    }

    /**
     * Klausa tingkat + babak, TANPA dimensi grup.
     *
     * Dipakai perhitungan yang memang menyatukan seluruh grup dalam satu
     * tingkat — misalnya bobot rubrik untuk pratinjau "Loloskan Top-N", yang
     * memeringkat semua grup sekaligus lalu membaginya per grup. Alasannya
     * sengaja tidak disaring: menyaringnya ke satu grup akan membuang rubrik
     * grup lain dari peta bobot, padahal peringkat itu justru dipakai untuk
     * membandingkan antar grup.
     *
     * Untuk penilaian per peserta selalu pakai forEntry(), bukan ini.
     */
    public function scopeForLevel($query, ?int $competitionCategoryId, ?int $roundId = null)
    {
        return $query
            ->where(function ($q) use ($competitionCategoryId) {
                $q->where('competition_category_id', $competitionCategoryId)
                    ->orWhereNull('competition_category_id');
            })
            ->when($roundId, function ($q) use ($roundId) {
                $q->where(function ($sq) use ($roundId) {
                    $sq->where('competition_round_id', $roundId)->orWhereNull('competition_round_id');
                });
            });
    }

    public function subCategories()
    {
        return $this->hasMany(AssessmentSubCategory::class, 'assessment_category_id')->orderBy('sort_order');
    }

    public function judges()
    {
        return $this->belongsToMany(Judge::class);
    }

    public function deductionCategories()
    {
        return $this->hasMany(DeductionCategory::class)->orderBy('sort_order');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'sort_order'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Kategori Penilaian {$this->name} telah di-{$eventName}");
    }
}
