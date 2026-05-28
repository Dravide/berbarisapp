<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sponsor extends Model
{
    use HasFactory;

    protected $fillable = [
        'eventner_id',
        'name',
        'logo',
        'link',
        'type',
        'is_active',
        'sort_order',
    ];

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }
}
