<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Gelombang harga biaya pendaftaran: satu rentang tanggal, satu angka.
 *
 * Rentangnya INKLUSIF dua ujung — gelombang 1–31 Okt masih berlaku pada
 * tanggal 31 Oktober. Yang menentukan harga yang ditagih bukan model ini
 * melainkan `CompetitionCategory::biayaPada()`, supaya gelombang dan harga
 * biasa tidak pernah dihitung di tempat yang berbeda.
 *
 * Rentang yang tumpang tindih dicegah di layar pengaturan
 * (`CompetitionCategory::alasanGelombangBentrok()`), bukan oleh constraint
 * DB: MySQL tidak punya exclusion constraint, dan gelombang berdampingan
 * (berakhir 31 Okt, mulai 1 Nov) justru sah.
 */
class CompetitionFeeWave extends Model
{
    use HasFactory;

    protected $fillable = [
        'eventner_id',
        'competition_category_id',
        'name',
        'starts_on',
        'ends_on',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function category()
    {
        return $this->belongsTo(CompetitionCategory::class, 'competition_category_id');
    }

    /** Hari ini masuk rentang gelombang ini? Batas dua ujung ikut dihitung. */
    public function mencakup(\DateTimeInterface $tanggal): bool
    {
        $hari = \Carbon\Carbon::parse($tanggal)->startOfDay();

        return $this->starts_on->copy()->startOfDay()->lte($hari)
            && $this->ends_on->copy()->startOfDay()->gte($hari);
    }

    /** Rangkuman rentang untuk pesan galat: "1 Okt 2026 – 31 Okt 2026". */
    public function labelRentang(): string
    {
        return $this->starts_on->translatedFormat('d M Y')
            . ' – '
            . $this->ends_on->translatedFormat('d M Y');
    }
}
