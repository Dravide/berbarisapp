<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationVoucher extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'max_discount',
        'max_uses',
        'saas_plan_id',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'value' => 'integer',
            'max_discount' => 'integer',
            'max_uses' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(SaasPlan::class, 'saas_plan_id');
    }

    public function eventners()
    {
        return $this->hasMany(Eventner::class, 'registration_voucher_id');
    }

    /** Selalu bandingkan kode dalam bentuk tersimpan (uppercase). */
    public function setCodeAttribute($value): void
    {
        $this->attributes['code'] = strtoupper(trim($value));
    }

    /**
     * Jumlah pemakaian sah: eventner yang SUDAH membayar.
     * Yang sekadar daftar (belum settlement) tidak mengonsumsi kuota.
     */
    public function hitungPemakaian(): int
    {
        return $this->eventners()->whereNotNull('registration_paid_at')->count();
    }

    public function sisaKuota(): ?int
    {
        if ($this->max_uses === null) {
            return null;
        }

        return max(0, $this->max_uses - $this->hitungPemakaian());
    }

    /**
     * Kenapa kode ini ditolak untuk paket sekarang — null berarti sah.
     * Pesan per sebab dipakai apa adanya di form pendaftaran.
     */
    public function alasanDitolak(?int $planId): ?string
    {
        if (! $this->is_active) {
            return 'Kode promo sudah tidak aktif.';
        }

        $now = now();
        if ($this->starts_at && $now->isBefore($this->starts_at)) {
            return 'Kode promo belum mulai berlaku.';
        }
        if ($this->ends_at && $now->isAfter($this->ends_at)) {
            return 'Kode promo sudah kadaluarsa.';
        }

        if ($this->max_uses !== null && $this->hitungPemakaian() >= $this->max_uses) {
            return 'Kuota kode promo sudah habis.';
        }

        if ($this->saas_plan_id && (int) $this->saas_plan_id !== (int) $planId) {
            return 'Kode promo tidak berlaku untuk paket ini.';
        }

        return null;
    }

    /**
     * Nominal potongan untuk satu paket — selalu dari effective_price,
     * bukan price mentah: harga efektif adalah satu-satunya dasar tagihan
     * (preseden bug registration_fee).
     */
    public function diskonUntuk(SaasPlan $plan): int
    {
        $dasar = $plan->effective_price;

        if ($this->type === 'flat') {
            return (int) min($this->value, $dasar);
        }

        $potongan = (int) round($dasar * $this->value / 100);

        return (int) min($potongan, $this->max_discount ?? $potongan, $dasar);
    }
}
