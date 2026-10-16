<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu kotak di atas denah: DP, arena, pos penilaian, tribun, sekretariat,
 * jalur masuk, panah, atau teks bebas.
 *
 * Posisi & ukuran disimpan sebagai persen papan (0–100) supaya satu denah
 * tampil utuh di editor, halaman publik, dan PDF mm tanpa disimpan tiga kali.
 *
 * Zona ini tidak menunjuk peserta mana pun — lihat CompetitionLayout.
 */
class LayoutZone extends Model
{
    use HasFactory;

    public const TYPE_DP = 'dp';

    public const TYPE_ARENA = 'arena';

    public const TYPE_POS_PENILAIAN = 'pos_penilaian';

    public const TYPE_TRIBUN = 'tribun';

    public const TYPE_SEKRETARIAT = 'sekretariat';

    public const TYPE_JALUR_MASUK = 'jalur_masuk';

    public const TYPE_PANAH = 'panah';

    public const TYPE_TEKS = 'teks';

    /** Label + kelas warna kanvas. Kelasnya dipakai editor DAN halaman publik
     *  supaya keduanya membaca denah dengan bahasa visual yang sama. */
    public const TIPE_LABEL = [
        self::TYPE_DP => 'Daerah Persiapan',
        self::TYPE_ARENA => 'Arena',
        self::TYPE_POS_PENILAIAN => 'Pos Penilaian',
        self::TYPE_TRIBUN => 'Tribun',
        self::TYPE_SEKRETARIAT => 'Sekretariat',
        self::TYPE_JALUR_MASUK => 'Jalur Masuk',
        self::TYPE_PANAH => 'Panah',
        self::TYPE_TEKS => 'Teks',
    ];

    public const TIPE_KELAS = [
        self::TYPE_DP => 'z-dp',
        self::TYPE_ARENA => 'z-arena',
        self::TYPE_POS_PENILAIAN => 'z-juri',
        self::TYPE_TRIBUN => 'z-tribun',
        self::TYPE_SEKRETARIAT => 'z-sekre',
        self::TYPE_JALUR_MASUK => 'z-jalur',
        self::TYPE_PANAH => 'z-arah',
        self::TYPE_TEKS => 'z-teks',
    ];

    protected $fillable = [
        'competition_layout_id',
        'name',
        'type',
        'x',
        'y',
        'w',
        'h',
        'dp_number',
        'capacity',
        'note',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'x' => 'decimal:3',
            'y' => 'decimal:3',
            'w' => 'decimal:3',
            'h' => 'decimal:3',
            'dp_number' => 'integer',
        ];
    }

    public function layout()
    {
        return $this->belongsTo(CompetitionLayout::class, 'competition_layout_id');
    }

    public function scopeDp(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_DP);
    }

    public function getLabelTipeAttribute(): string
    {
        return self::TIPE_LABEL[$this->type] ?? $this->type;
    }

    public function getKelasTipeAttribute(): string
    {
        return self::TIPE_KELAS[$this->type] ?? 'z-teks';
    }

    /** Geometri siap tulis ke atribut style blade. */
    public function getGayaAttribute(): string
    {
        return sprintf('left:%s%%;top:%s%%;width:%s%%;height:%s%%',
            $this->x, $this->y, $this->w, $this->h);
    }

    /** Ukuran zona dalam meter, dihitung dari ukuran papan. */
    public function ukuranMeter(CompetitionLayout $layout): string
    {
        $lebar = round((float) $this->w / 100 * (float) $layout->ground_width, 1);
        $tinggi = round((float) $this->h / 100 * (float) $layout->ground_height, 1);

        return ($lebar > 0 && $tinggi > 0) ? "{$lebar} × {$tinggi} m" : '';
    }

    // ── Susun DP otomatis ──────────────────────────────────────────────

    /**
     * Hitung barisan DP 1..N; DP 1 selalu di ujung yang paling dekat arena.
     *
     * Nomor DP disimpan di `dp_number`, bukan disimpulkan dari urutan gambar —
     * urutan gambar berubah begitu panitia menyeret zonanya.
     *
     * @return array<int, array<string, mixed>> baris zona siap insert
     */
    public static function susunDp(
        int $jumlah,
        string $letak,
        float $tinggiMeter,
        float $groundWidth,
        float $groundHeight,
        bool $denganPanah = true,
    ): array {
        $jumlah = max(1, min(12, $jumlah));
        $baris = [];

        if ($letak === 'samping') {
            $baris = static::barisanVertikal($jumlah, $tinggiMeter, $groundWidth, $denganPanah);
        } else {
            $baris = static::barisanHorizontal($jumlah, $tinggiMeter, $groundHeight, $letak === 'atas', $denganPanah);
        }

        return $baris;
    }

    /** Baris DP mendatar di bawah (atau di atas) arena. */
    private static function barisanHorizontal(int $jumlah, float $tinggiMeter, float $groundHeight, bool $atas, bool $denganPanah): array
    {
        $tinggi = static::meterKePersen($tinggiMeter, $groundHeight, 22);

        $kiri = 14.0;
        $lebarTotal = 62.0;
        $celah = 1.2;
        $lebarZona = ($lebarTotal - $celah * ($jumlah - 1)) / $jumlah;

        // Di bawah arena: DP 1 di kanan. Di atas arena: dicerminkan, DP 1 di
        // kiri — supaya barisannya selalu "menghadap" arena, bukan menjauh.
        $y = $atas ? 4.0 : 96.0 - $tinggi;

        $baris = [];

        for ($i = 1; $i <= $jumlah; $i++) {
            $indeks = $atas ? ($i - 1) : ($jumlah - $i);
            $baris[] = [
                'name' => "DP {$i}",
                'type' => self::TYPE_DP,
                'dp_number' => $i,
                'x' => round($kiri + $indeks * ($lebarZona + $celah), 3),
                'y' => round($y, 3),
                'w' => round($lebarZona, 3),
                'h' => round($tinggi, 3),
                'capacity' => '1 pasukan',
            ];
        }

        if ($denganPanah) {
            $baris[] = [
                'name' => 'Arah gerak pasukan',
                'type' => self::TYPE_PANAH,
                'x' => $kiri,
                'y' => round($atas ? $y + $tinggi + 1 : $y - 4, 3),
                'w' => $lebarTotal,
                'h' => 3.0,
                'capacity' => null,
            ];
        }

        return $baris;
    }

    /** Kolom DP menurun di samping kiri arena. */
    private static function barisanVertikal(int $jumlah, float $lebarMeter, float $groundWidth, bool $denganPanah): array
    {
        $lebar = static::meterKePersen($lebarMeter, $groundWidth, 12);

        $atas = 14.0;
        $tinggiTotal = 72.0;
        $celah = 1.2;
        $tinggiZona = ($tinggiTotal - $celah * ($jumlah - 1)) / $jumlah;

        $baris = [];

        for ($i = 1; $i <= $jumlah; $i++) {
            // DP 1 di paling atas — ujung yang paling dekat arena.
            $indeks = $i - 1;
            $baris[] = [
                'name' => "DP {$i}",
                'type' => self::TYPE_DP,
                'dp_number' => $i,
                'x' => 2.0,
                'y' => round($atas + $indeks * ($tinggiZona + $celah), 3),
                'w' => round($lebar, 3),
                'h' => round($tinggiZona, 3),
                'capacity' => '1 pasukan',
            ];
        }

        if ($denganPanah) {
            $baris[] = [
                'name' => 'Arah gerak pasukan',
                'type' => self::TYPE_PANAH,
                'x' => round(2.0 + $lebar + 1, 3),
                'y' => $atas,
                'w' => 3.0,
                'h' => $tinggiTotal,
                'capacity' => null,
            ];
        }

        return $baris;
    }

    /**
     * Meter → persen, dengan pagar.
     *
     * Ukuran papan bisa belum diisi (0) saat denah baru dibuat; tanpa pagar
     * pembagiannya meledak dan barisannya jadi tak terlihat.
     */
    private static function meterKePersen(float $meter, float $dimensi, float $bawaan): float
    {
        if ($meter <= 0 || $dimensi <= 0) {
            return $bawaan;
        }

        return max(4.0, min(40.0, $meter / $dimensi * 100));
    }

    /**
     * Zona arena bawaan, dipakai saat denah belum punya arena sama sekali.
     *
     * Tanpa arena, barisan DP tak punya apa pun untuk dijejeri. Arena yang
     * sudah digambar panitia tak pernah diganti.
     */
    public static function arenaBawaan(string $letak): array
    {
        return match ($letak) {
            'samping' => ['name' => 'Arena Utama', 'type' => self::TYPE_ARENA, 'x' => 24.0, 'y' => 14.0, 'w' => 70.0, 'h' => 72.0],
            'atas' => ['name' => 'Arena Utama', 'type' => self::TYPE_ARENA, 'x' => 14.0, 'y' => 30.0, 'w' => 62.0, 'h' => 50.0],
            default => ['name' => 'Arena Utama', 'type' => self::TYPE_ARENA, 'x' => 14.0, 'y' => 16.0, 'w' => 62.0, 'h' => 50.0],
        };
    }
}
