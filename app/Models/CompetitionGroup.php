<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Grup peserta — tabel peringkat, nomor undian, DAN penugasan juri.
 *
 * Pembagian tugas antar sumbu, sesudah penugasan juri pindah ke sini:
 *
 *   CompetitionGroup  -> peringkat + nomor undian + SIAPA YANG MENILAI
 *   CompetitionSeries -> lembar nilai (rubrik) saja
 *   CompetitionRound  -> babak; baris `final` di tabel penugasan
 *
 * Juri terikat pada grup, bukan pada peserta: peserta yang pindah grup tidak
 * membawa penugasannya, dan Grup A tetap dinilai orang yang sama walau isinya
 * berganti. Rubriknya menyusul dari seri masing-masing peserta, jadi satu juri
 * grup memang menilai lebih dari satu lembar.
 */
class CompetitionGroup extends Model
{
    use HasFactory, LogsActivity;

    /** Penugasan ke satu grup peserta. */
    public const SCOPE_GROUP = 'group';

    /** Penugasan ke babak final tingkat itu — bukan ke grup mana pun. */
    public const SCOPE_FINAL = 'final';

    /** Penugasan ke peserta yang belum dibagi grup. */
    public const SCOPE_UNGROUPED = 'ungrouped';

    /** Penugasan ke seluruh peserta tingkat yang memang tidak punya grup. */
    public const SCOPE_LEVEL = 'level';

    /** Urutan baris di layar penugasan, lengkap dengan namanya. */
    public const SCOPE_LABELS = [
        self::SCOPE_UNGROUPED => 'Belum Bergrup',
        self::SCOPE_FINAL => 'Final',
        self::SCOPE_LEVEL => 'Seluruh Tingkat',
    ];

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
        return $this->hasMany(Registration::class, 'competition_group_id');
    }

    public function assessmentCategories()
    {
        return $this->hasMany(AssessmentCategory::class, 'competition_group_id');
    }

    public function roundRegistrations()
    {
        return $this->hasMany(CompetitionRoundRegistration::class, 'competition_group_id');
    }

    public function judges(): BelongsToMany
    {
        return $this->belongsToMany(Judge::class, 'competition_group_judge')
            ->wherePivot('scope', self::SCOPE_GROUP);
    }

    // ── Penugasan juri ─────────────────────────────────────────────────

    /**
     * Keputusan tunggal "peserta ini dinilai lewat baris penugasan yang mana".
     *
     * Sengaja satu tempat, dan dipakai dua arah: judgesForRegistration()
     * mencari jurinya, saringDinilaiOleh() mencari pesertanya. Kalau keduanya
     * menghitung sendiri-sendiri, kartu di layar menjanjikan angka yang berbeda
     * dari isi daftarnya — cacat yang sudah pernah terjadi di tablet juri.
     *
     * Urutannya pendek dan itu disengaja:
     *  1. babak final dengan penugasan `final` -> semua finalis, lepas dari grup;
     *  2. peserta bergrup                       -> juri grup itu;
     *  3. peserta belum bergrup                 -> baris `ungrouped`;
     *  4. sisanya                               -> baris `level`.
     *
     * @return array{scope: ?string, group_id: ?int}
     */
    public static function penugasanUntukPeserta(Registration $registration, ?int $roundId = null): array
    {
        $levelId = $registration->competition_category_id;

        if (! $levelId) {
            return ['scope' => null, 'group_id' => null];
        }

        if (static::babakFinal($registration->eventner_id, $roundId)
            && static::adaPenugasanTingkat($levelId, self::SCOPE_FINAL)) {
            return ['scope' => self::SCOPE_FINAL, 'group_id' => null];
        }

        if ($registration->competition_group_id) {
            return ['scope' => self::SCOPE_GROUP, 'group_id' => (int) $registration->competition_group_id];
        }

        if (static::adaPenugasanTingkat($levelId, self::SCOPE_UNGROUPED)) {
            return ['scope' => self::SCOPE_UNGROUPED, 'group_id' => null];
        }

        // Tingkat ini tidak punya grup sama sekali (tiga belas event lama).
        // Tanpa cabang ini peserta mereka tak punya baris penugasan apa pun,
        // dan panel jurinya kosong begitu layar centang rubrik dihapus.
        return static::adaPenugasanTingkat($levelId, self::SCOPE_LEVEL)
            ? ['scope' => self::SCOPE_LEVEL, 'group_id' => null]
            : ['scope' => null, 'group_id' => null];
    }

    /** Juri yang berhak menilai satu peserta. */
    public static function judgesForRegistration(Registration $registration, ?int $roundId = null): Collection
    {
        $t = static::penugasanUntukPeserta($registration, $roundId);

        if ($t['scope'] === null) {
            return collect();
        }

        return Judge::where('eventner_id', $registration->eventner_id)
            ->whereIn('id', static::queryPenugasan($registration->competition_category_id, $t['scope'], $t['group_id'])->pluck('judge_id'))
            ->orderBy('name')
            ->get();
    }

    /**
     * Cerminan judgesForRegistration() dari sisi sebaliknya: saring query
     * Registration ke peserta yang boleh dilihat juri ini.
     *
     * Aturan final sengaja dihitung dari penugasan TINGKAT, bukan dari
     * penugasan juri ini. Begitu ada satu juri final di tingkat itu, seluruh
     * peserta finalis pindah ke baris `final` — termasuk bagi juri grup yang
     * tak ditugaskan ke sana, yang memang lalu tak melihat siapa pun.
     */
    public static function saringDinilaiOleh($query, int $judgeId, int $levelId, ?int $roundId = null)
    {
        if (static::babakFinal(null, $roundId, $levelId)
            && static::adaPenugasanTingkat($levelId, self::SCOPE_FINAL)) {
            return static::adaPenugasanTingkat($levelId, self::SCOPE_FINAL, $judgeId)
                ? $query
                : $query->whereRaw('1 = 0');
        }

        $grupIds = static::queryPenugasan($levelId, self::SCOPE_GROUP, semuaGrup: true)
            ->where('judge_id', $judgeId)->pluck('competition_group_id')
            ->filter()->map(fn ($id) => (int) $id)->all();

        $lihatTanpaGrup = static::adaPenugasanTingkat($levelId, self::SCOPE_UNGROUPED, $judgeId)
            || static::adaPenugasanTingkat($levelId, self::SCOPE_LEVEL, $judgeId);

        return $query->where(function ($q) use ($grupIds, $lihatTanpaGrup) {
            if ($grupIds !== []) {
                $q->where(function ($g) use ($grupIds) {
                    $g->whereNotNull('competition_group_id')
                        ->whereIn('competition_group_id', $grupIds);
                });
            }

            if ($lihatTanpaGrup) {
                $q->orWhereNull('competition_group_id');
            }

            // Juri yang hanya punya penugasan grup tak melihat peserta tanpa
            // grup; juri tanpa penugasan apa pun tak melihat siapa pun.
            if ($grupIds === [] && ! $lihatTanpaGrup) {
                $q->whereRaw('1 = 0');
            }
        });
    }

    /**
     * Ganti seluruh penugasan satu baris.
     *
     * Satu-satunya jalur tulis ke pivot ini. Unique index tak bisa diandalkan:
     * MySQL maupun SQLite menganggap NULL tidak sama dengan NULL di dalamnya,
     * jadi baris `final`/`ungrouped`/`level` yang kembar akan lolos begitu saja.
     *
     * @param  array<int>  $judgeIds
     */
    public static function syncJudges(int $levelId, string $scope, ?int $groupId, array $judgeIds): void
    {
        $query = DB::table('competition_group_judge')
            ->where('competition_category_id', $levelId)
            ->where('scope', $scope)
            ->when($groupId === null,
                fn ($q) => $q->whereNull('competition_group_id'),
                fn ($q) => $q->where('competition_group_id', $groupId));

        $query->delete();

        $now = now();
        $baris = collect($judgeIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->map(fn ($id) => [
                'competition_category_id' => $levelId,
                'competition_group_id' => $groupId,
                'judge_id' => $id,
                'scope' => $scope,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($baris !== []) {
            DB::table('competition_group_judge')->insert($baris);
        }
    }

    /** Juri yang ditugaskan pada satu baris penugasan. */
    public static function judgeIdsUntuk(int $levelId, string $scope, ?int $groupId = null): array
    {
        return static::queryPenugasan($levelId, $scope, $groupId)
            ->pluck('judge_id')->map(fn ($id) => (int) $id)->all();
    }

    /** Apakah ada juri pada satu baris penugasan (seluruhnya, atau satu juri). */
    public static function adaPenugasanTingkat(int $levelId, string $scope, ?int $judgeId = null): bool
    {
        return static::queryPenugasan($levelId, $scope)
            ->when($judgeId !== null, fn ($q) => $q->where('judge_id', $judgeId))
            ->exists();
    }

    /**
     * Query satu baris penugasan.
     *
     * `$semuaGrup` membedakan dua pertanyaan yang mirip: "baris tak-bergrup
     * yang mana" (group_id NULL, dipakai baris final/ungrouped/level) versus
     * "semua baris grup milik juri ini, grup apa pun" (dipakai
     * saringDinilaiOleh). Tanpa penanda itu, pencarian grup ikut tersaring
     * whereNull dan mengembalikan nol baris.
     */
    private static function queryPenugasan(int $levelId, string $scope, ?int $groupId = null, bool $semuaGrup = false)
    {
        return DB::table('competition_group_judge')
            ->where('competition_category_id', $levelId)
            ->where('scope', $scope)
            ->when(! $semuaGrup, fn ($q) => $groupId === null
                ? $q->whereNull('competition_group_id')
                : $q->where('competition_group_id', $groupId));
    }

    /**
     * Apakah $roundId adalah babak final. $levelId opsional: dipanggil dari
     * saringDinilaiOleh() yang tidak selalu memegang pendaftarannya.
     */
    private static function babakFinal(?int $eventnerId, ?int $roundId, ?int $levelId = null): bool
    {
        if (! $roundId) {
            return false;
        }

        return CompetitionRound::query()
            ->where('id', $roundId)
            ->where('type', CompetitionRound::TYPE_FINAL)
            ->when($eventnerId, fn ($q) => $q->where('eventner_id', $eventnerId))
            ->when($levelId, fn ($q) => $q->where('competition_category_id', $levelId))
            ->exists();
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
            ->setDescriptionForEvent(fn (string $eventName) => "Grup {$this->name} telah di-{$eventName}");
    }
}
