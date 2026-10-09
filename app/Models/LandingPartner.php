<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Sponsor & media partner yang tampil di laman depan platform (bukan per-event).
 */
class LandingPartner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'logo',
        'link',
        'type',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
