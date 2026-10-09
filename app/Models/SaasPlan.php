<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaasPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price',
        'discount_percent',
        'registration_fee',
        'description',
        'is_active',
        'is_free',
        'is_contact',
        'contact_url',
        'highlight',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_free' => 'boolean',
        'is_contact' => 'boolean',
        'highlight' => 'boolean',
        'price' => 'integer',
        'discount_percent' => 'integer',
        'registration_fee' => 'integer',
    ];

    public function features()
    {
        return $this->hasMany(SaasPlanFeature::class, 'saas_plan_id');
    }

    public function featureKeys(): array
    {
        return $this->features->pluck('feature_key')->all();
    }

    public function eventners()
    {
        return $this->hasMany(Eventner::class, 'saas_plan_id');
    }

    /**
     * Harga yang ditagih & divalidasi webhook: price dikurangi diskon.
     *
     * SEMUA penagihan QRIS dan cek nominal webhook wajib lewat sini —
     * kalau penagih memakai `price` mentah sementara webhook memakai
     * accessor (atau sebaliknya), settlement ditolak diam-diam karena
     * nominal tidak cocok (preseden bug registration_fee).
     */
    public function getEffectivePriceAttribute(): int
    {
        if ($this->discount_percent <= 0) {
            return (int) $this->price;
        }

        return (int) round($this->price * (100 - $this->discount_percent) / 100);
    }

    public function getHasDiscountAttribute(): bool
    {
        return $this->discount_percent > 0 && $this->effective_price < $this->price;
    }
}
