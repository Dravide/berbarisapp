<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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
     * Definisi "lembar nilai mana yang terbuka untuk peserta ini" — dipakai
     * halaman juri, panitia input nilai, dan finalisasi.
     *
     * Ini BUKAN definisi "rubrik mana yang boleh diisi juri ini". Pertanyaan
     * itu dijawab scopeBolehDinilaiOleh(), dan keduanya digabung oleh
     * rubrikUntukPeserta(). Sengaja dipisah: seri/babak menempel pada
     * PESERTA, sedangkan centang juri menempel pada RUBRIK — dua sumbu bebas,
     * dan menggabungkannya di sini membuat peserta Seri A ikut terlihat oleh
     * juri yang cuma memegang satu rubrik seri tetangga.
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
     * Babak FINAL mengecualikan saringannya: seri di sana diabaikan sama sekali,
     * jadi seluruh rubrik babak itu terbuka untuk semua finalis — berseri
     * maupun tidak. Alasannya dua: final memang satu pool se-tingkat (tanpa
     * pembagian grup maupun seri), dan rubrik final yang dibuat panitia
     * justru TIDAK bertanda seri (lihat ScoringController::assessmentCategoriesFor).
     * Sebelum ini finalis wajib berseri persis sama dengan rubrik finalnya agar
     * lembarnya terbuka — peserta dari seri lain mendapat lembar kosong.
     *
     * @see scopeForLevel() untuk perhitungan lintas-seri
     */
    public function scopeForEntry($query, ?int $competitionCategoryId, ?int $seriesId = null, ?int $roundId = null)
    {
        return $query
            ->forLevel($competitionCategoryId, $roundId)
            ->when(! static::roundIdIsFinal($roundId), function ($q) use ($seriesId) {
                $q->where(function ($sq) use ($seriesId) {
                    // Tanpa seri: hanya rubrik tanpa seri. Rubrik berseri sengaja
                    // TIDAK ikut — pemisahan seri jadi tak berarti kalau peserta
                    // yang belum dibagi tetap melihat semua rubrik seri.
                    if (! $seriesId) {
                        $sq->whereNull('competition_series_id');

                        return;
                    }

                    $sq->where('competition_series_id', $seriesId)->orWhereNull('competition_series_id');
                });
            });
    }

    /**
     * Apakah id ini merujuk baris babak bertipe final.
     *
     * Babak "ini final atau bukan" adalah sifat satu baris CompetitionRound,
     * bukan sifat tingkat — jadi jawabannya datang dari baris babaknya sendiri.
     * Dipisah jadi helper supaya berkas ini tak perlu tahu kolom `type`
     * CompetitionRound, dan supaya pemanggil yang hanya memegang id (bukan
     * modelnya) tetap bisa bertanya. Id yang tak ada / bukan babak final
     * mengembalikan false, jadi perilaku lama utuh.
     */
    private static function roundIdIsFinal(?int $roundId): bool
    {
        return (bool) $roundId
            && CompetitionRound::query()
                ->whereKey($roundId)
                ->where('type', CompetitionRound::TYPE_FINAL)
                ->exists();
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

    // ── Pembagian rubrik antar juri ────────────────────────────────────

    /**
     * Batas "rubrik ini diisi siapa".
     *
     * Rubrik TANPA baris juri = TIDAK dibatasi: semua juri dari baris penugasan
     * yang berlaku boleh mengisinya. Itu perilaku sebelum fitur pembagian ada,
     * dan sengaja dipertahankan supaya acara yang sudah berjalan tak berubah
     * begitu fitur ini rilis. Baris berarti pembatasan, bukan izin.
     *
     * whereDoesntHave/whereHas WAJIB dibungkus satu where(). forEntry() sudah
     * memakai OR di dalam klausa serinya; OR yang bocor ke luar akan membuat
     * saringan ini hilang begitu saja dan rubrik seri tetangga ikut kembali.
     *
     * Saringan eventner di dalam relasi bukan hiasan: satu baris pivot lintas
     * tenant membuat rubrik terbaca "sudah ditugaskan" lalu hilang dari SEMUA
     * juri tenant pemiliknya — kegagalan yang tampak seperti rubrik terhapus.
     */
    public function scopeBolehDinilaiOleh($query, int $eventnerId, int $judgeId)
    {
        return $query->where(function ($q) use ($eventnerId, $judgeId) {
            $q->whereDoesntHave('judges', fn ($j) => $j->where('judges.eventner_id', $eventnerId))
                ->orWhereHas('judges', fn ($j) => $j
                    ->where('judges.eventner_id', $eventnerId)
                    ->where('judges.id', $judgeId));
        });
    }

    /**
     * SATU-SATUNYA pintu "rubrik mana yang boleh dinilai juri ini untuk
     * peserta ini".
     *
     * Dipakai tablet juri, panel panitia, dan ScoreFinalizationService. Ketiga
     * jalur itu WAJIB menghasilkan himpunan kriteria yang sama: finalize()
     * menolak finalisasi selama ada satu kriteria yang belum terisi, jadi
     * kalau tablet merender satu kriteria lebih banyak daripada yang dituntut
     * finalize(), tombol finalisasi tak akan pernah bisa ditekan — tanpa satu
     * pun pesan yang menjelaskan sebabnya.
     *
     * $judgeId null = jangan saring per juri (panel panitia sebelum jurinya
     * dipilih, dan rekap yang memang menyatukan semua juri).
     */
    public static function rubrikUntukPeserta(
        int $eventnerId,
        ?int $levelId,
        ?int $seriesId,
        ?int $roundId,
        ?int $judgeId = null
    ): \Illuminate\Database\Eloquent\Builder {
        return static::with(['subCategories.criterias'])
            ->where('eventner_id', $eventnerId)
            ->forEntry($levelId, $seriesId, $roundId)
            ->when($judgeId, fn ($q) => $q->bolehDinilaiOleh($eventnerId, $judgeId));
    }

    /**
     * Varian tanpa peserta — lembar cetak per juri dan kartu akses.
     *
     * Seri sengaja tidak ikut: satu juri grup boleh memegang peserta dari
     * beberapa seri, jadi menyaring seri di sini akan membuang lembar yang
     * justru harus tercetak. Seri baru ditentukan saat pesertanya disebut.
     *
     * $levelId null berarti SEMUA tingkat, bukan "hanya rubrik global".
     * Perhatikan bedanya dengan forLevel(): di sana null ikut jadi nilai yang
     * dibandingkan, sehingga syaratnya menyusut jadi `competition_category_id
     * IS NULL`. Kartu akses memangil pintu ini tanpa tingkat saat jurinya
     * memegang lebih dari satu tingkat — dengan forLevel(null) setiap rubrik
     * bertingkat hilang dan kartunya tercetak "Belum ada tugas".
     */
    public static function rubrikUntukTingkat(int $eventnerId, ?int $levelId, ?int $judgeId = null)
    {
        $query = static::with(['subCategories.criterias', 'deductionCategories.criterias', 'competitionSeries'])
            ->where('eventner_id', $eventnerId);

        if ($levelId) {
            $query->forLevel($levelId);
        }

        return $query->when($judgeId, fn ($q) => $q->bolehDinilaiOleh($eventnerId, $judgeId));
    }

    /** Id juri yang dicentang pada rubrik ini. Kosong = belum dibagi. */
    public function rubricJudgeIds(): array
    {
        return DB::table('assessment_category_judge')
            ->where('assessment_category_id', $this->id)
            ->pluck('judge_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Satu-satunya jalur tulis ke assessment_category_judge.
     *
     * Hapus lalu tulis ulang, meniru CompetitionGroup::syncJudges(): pemanggil
     * tak perlu tahu keadaan sebelumnya, dan centang ulang atas himpunan yang
     * sama menghasilkan baris yang sama persis. Unique indexnya boleh
     * diandalkan di sini karena kedua kolomnya NOT NULL.
     *
     * @param  array<int>  $judgeIds
     */
    public function syncRubricJudges(array $judgeIds): void
    {
        $query = DB::table('assessment_category_judge')->where('assessment_category_id', $this->id);
        $query->delete();

        $now = now();
        $baris = collect($judgeIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->map(fn ($id) => [
                'judge_id' => $id,
                'assessment_category_id' => $this->id,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($baris !== []) {
            DB::table('assessment_category_judge')->insert($baris);
        }
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
