<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'eventner_id',
        'name',
        'logo',
        'description',
        'type',
        'is_active',
        'sort_order',
    ];

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }
}
