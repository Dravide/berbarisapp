<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaasPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price',
        'registration_fee',
        'description',
        'is_active',
        'is_free',
        'highlight',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_free' => 'boolean',
        'highlight' => 'boolean',
        'price' => 'integer',
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
}
