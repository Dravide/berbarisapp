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
 * Papan + zona-zonanya, plus satu alur gerak pasukan. Sengaja tidak menyimpan
 * peserta, sekolah, maupun juri: ia gambar tata letak, bukan penugasan. Itu
 * yang membuatnya aman diterbitkan ke halaman publik, dan tak bisa menyimpang
 * dari daftar peserta.
 */
class CompetitionLayout extends Model
{
    use HasFactory, LogsActivity;

    /** Jenis titik alur, beserta labelnya di editor dan di daftar langkah. */
    public const TITIK_JENIS = [
        'start' => 'Titik Awal',
        'turn' => 'Titik Belok',
        'aba_aba' => 'Titik Aba-aba',
        'end' => 'Titik Akhir',
    ];

    protected $fillable = [
        'eventner_id',
        'competition_category_id',
        'eventner_venue_id',
        'name',
        'ground_width',
        'ground_height',
        'background_image',
        'route_points',
        'route_visible',
        'is_published',
        'published_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'route_visible' => 'boolean',
            'route_points' => 'array',
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

    /**
     * Ukuran papan siap baca, untuk pita ukuran di tepi kanvas.
     *
     * Angka bulat tak diberi ",0": "40 × 22 m" lebih enak dibaca daripada
     * "40,0 × 22,0 m", dan pita ukuran memang untuk dibaca sekilas.
     */
    public function getUkuranTeksAttribute(): string
    {
        $angka = fn ($nilai) => rtrim(rtrim(number_format((float) $nilai, 1, ',', '.'), '0'), ',');

        return $angka($this->ground_width).' × '.$angka($this->ground_height).' m';
    }

    /**
     * Tinggi papan siap baca, untuk pita ukuran di tepi kiri & kanan.
     *
     * Terpisah dari ukuran_teks karena pita tegak hanya memuat satu angka;
     * "40 × 22 m" di tepi setinggi 3% papan tak terbaca.
     */
    public function getTinggiTeksAttribute(): string
    {
        return rtrim(rtrim(number_format((float) $this->ground_height, 1, ',', '.'), '0'), ',').' m';
    }

    // ── Alur gerak pasukan ─────────────────────────────────────────────────

    /**
     * Titik-titik alur, sudah dibersihkan dan diurutkan.
     *
     * Titik yang rusak (bukan array, koordinat bukan angka) dibuang diam-diam,
     * bukan dilaporkan: kolomnya JSON bebas, dan satu titik cacat tak boleh
     * membuat seluruh denah gagal tampil di halaman publik.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAlurTitikAttribute(): array
    {
        $mentah = $this->route_points;

        if (! is_array($mentah)) {
            return [];
        }

        $titik = [];

        foreach ($mentah as $t) {
            if (! is_array($t) || ! isset($t['x'], $t['y'])) {
                continue;
            }

            if (! is_numeric($t['x']) || ! is_numeric($t['y'])) {
                continue;
            }

            $jenis = $t['kind'] ?? 'turn';

            $titik[] = [
                'x' => round(max(0, min(100, (float) $t['x'])), 3),
                'y' => round(max(0, min(100, (float) $t['y'])), 3),
                'kind' => array_key_exists($jenis, self::TITIK_JENIS) ? $jenis : 'turn',
                'label' => isset($t['label']) ? trim(strip_tags((string) $t['label'])) : '',
                'heading' => isset($t['heading']) && is_numeric($t['heading'])
                    ? ((int) round((float) $t['heading']) % 360 + 360) % 360
                    : null,
            ];
        }

        return $titik;
    }

    /** Denah ini punya alur yang bisa digambar? */
    public function getPunyaAlurAttribute(): bool
    {
        return count($this->alur_titik) >= 2;
    }

    /** Alur ditampilkan ke publik? Selalu benar selama alurnya memang ada. */
    public function getAlurTampilAttribute(): bool
    {
        return $this->punya_alur && $this->route_visible;
    }

    /**
     * Ruas garis antar titik berurutan, siap digambar.
     *
     * Sudut & panjangnya dihitung di sini, bukan di CSS, karena ruas garisnya
     * hidup di ruang persen: 10% lebar papan bukan 10% tingginya. Sudutnya
     * diambil dari ruang nyata (persen × ukuran papan), dan karena kanvasnya
     * sendiri ber-aspect-ratio sama, sudut itu tetap benar begitu digambar.
     *
     * @return array<int, array{x: float, y: float, panjang: float, sudut: float}>
     */
    public function alurSegmen(): array
    {
        $titik = $this->alur_titik;
        $rasio = $this->rasio > 0 ? $this->rasio : 16 / 9;

        // dy diukur dalam persen tinggi; dikali 1/rasio agar sebanding dengan
        // persen lebar. Tanpa itu, papan 40 × 22 m menggambar belokan yang
        // sudutnya berbeda dari yang dilihat panitia di lapangan.
        $skalaY = 1 / $rasio;

        $segmen = [];

        for ($i = 1; $i < count($titik); $i++) {
            $dx = $titik[$i]['x'] - $titik[$i - 1]['x'];
            $dy = ($titik[$i]['y'] - $titik[$i - 1]['y']) * $skalaY;

            $panjang = sqrt($dx * $dx + $dy * $dy);

            if ($panjang < 0.01) {
                continue;
            }

            $segmen[] = [
                'x' => $titik[$i - 1]['x'],
                'y' => $titik[$i - 1]['y'],
                'panjang' => round($panjang, 3),
                'sudut' => round(rad2deg(atan2($dy, $dx)), 2),
            ];
        }

        return $segmen;
    }

    /**
     * Langkah-langkah alur dalam bahasa panitia, untuk dibaca di bawah kanvas.
     *
     * Urutannya sama dengan urutan titik; titik belok tanpa label dilewati
     * karena "Belok" saja tak menambah informasi di samping gambarnya. Nomornya
     * karena itu berurutan pada daftar yang tampil, bukan nomor titik di editor
     * — daftar ini dibaca sebagai langkah, dan langkah 1, 2, 4 terlihat seperti
     * langkah yang hilang.
     *
     * @return array<int, array{nomor: int, jenis: string, judul: string, hadap: ?int}>
     */
    public function alurLangkah(): array
    {
        $langkah = [];

        foreach ($this->alur_titik as $t) {
            $jenis = self::TITIK_JENIS[$t['kind']];

            if ($t['kind'] === 'turn' && $t['label'] === '' && $t['heading'] === null) {
                continue;
            }

            $langkah[] = [
                'nomor' => count($langkah) + 1,
                'jenis' => $jenis,
                'judul' => $t['label'] !== '' ? $t['label'] : $jenis,
                'hadap' => $t['heading'],
            ];
        }

        return $langkah;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'is_published', 'ground_width', 'ground_height'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Denah {$this->name} telah di-{$eventName}");
    }
}
