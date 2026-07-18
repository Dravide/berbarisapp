<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoteBooster extends Model
{
    protected $fillable = [
        'eventner_id',
        'starts_at',
        'ends_at',
        'vote_multiplier',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'vote_multiplier' => 'integer',
    ];

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }
}
