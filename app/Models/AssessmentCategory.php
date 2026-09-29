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
        'competition_series_id',
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

    public function competitionSeries()
    {
        return $this->belongsTo(CompetitionSeries::class, 'competition_series_id');
    }

    /**
     * Rubrik yang berlaku untuk sebuah tingkat, disaring per seri dan babak.
     *
     * Satu-satunya definisi "rubrik mana yang boleh dinilai" — dipakai halaman
     * juri, panitia input nilai, dan finalisasi. Sengaja mengembalikan builder
     * yang sudah memuat klausa tingkat/seri/babak, supaya pemanggil boleh
     * menumpuk whereHas('judges') ATAU memakai daftar ini apa adanya untuk
     * cabang fallback. Kalau klausa seri hanya ditempel di cabang whereHas,
     * cabang fallback "rubrik kosong → semua rubrik tingkat" akan membocorkan
     * rubrik Seri A ke juri Seri B.
     *
     * Aturan seri: rubrik tanpa seri berlaku di mana saja; rubrik berseri hanya
     * berlaku untuk peserta berseri itu. Peserta yang belum dapat seri karena
     * itu hanya melihat rubrik tanpa seri — bukan rubrik semua seri. Sebelumnya
     * `series_id NULL` diperlakukan sebagai "berlaku semua seri", dan
     * konsekuensinya peserta yang belum berseri melihat kolom penilai Seri A
     * dan Seri B sekaligus padahal ia tak masuk seri mana pun.
     *
     * Konsekuensi yang perlu diketahui operator: tingkat yang rubriknya SUDAH
     * ditandai per seri tetapi pesertanya belum dibagi akan menampilkan lembar
     * kosong. Bagi serinya lebih dulu (di meja daftar ulang), atau biarkan
     * rubriknya tanpa seri.
     *
     * Seri sengaja menggantikan grup di sini — bukan menambah. Grup dan seri
     * dua sumbu bebas: grup menyusun tabel peringkat dan nomor undian
     * (ChampionCalculator, urutan_tampil), seri menentukan lembar nilai & juri.
     * Dua pasukan satu grup boleh berbeda seri, jadi menyaring memakai grup
     * akan mustahil memberi keduanya lembar nilai berbeda.
     *
     * NULL pada babak berbeda artinya: tanpa saringan babak. Tingkat tanpa
     * baris babak memang tidak punya babak untuk disaring, jadi rubriknya harus
     * tetap terpakai.
     *
     * @see scopeForLevel() untuk perhitungan lintas-serí
     */
    public function scopeForEntry($query, ?int $competitionCategoryId, ?int $seriesId = null, ?int $roundId = null)
    {
        return $query
            ->forLevel($competitionCategoryId, $roundId)
            ->where(function ($q) use ($seriesId) {
                // Tanpa seri: hanya rubrik tanpa seri. Rubrik berseri sengaja
                // TIDAK ikut — pemisahan seri jadi tak berarti kalau peserta
                // yang belum dibagi tetap melihat semua rubrik seri.
                if (! $seriesId) {
                    $q->whereNull('competition_series_id');

                    return;
                }

                $q->where('competition_series_id', $seriesId)->orWhereNull('competition_series_id');
            });
    }

    /**
     * Klausa tingkat + babak, TANPA dimensi seri.
     *
     * Dipakai perhitungan yang memang menyatukan seluruh seri dalam satu
     * tingkat — misalnya bobot rubrik untuk pratinjau "Loloskan Top-N", yang
     * memeringkat semua seri sekaligus lalu membaginya per grup. Alasannya
     * sengaja tidak disaring: menyaringnya ke satu seri akan membuang rubrik
     * seri lain dari peta bobot, padahal peringkat itu justru dipakai untuk
     * membandingkan antar seri.
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
