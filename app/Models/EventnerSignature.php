<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventnerSignature extends Model
{
    protected $fillable = [
        'eventner_id',
        'name',
        'image',
    ];

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    /**
     * URL absolut file gambar TTD/stempel (disk public).
     */
    public function getImageUrlAttribute(): string
    {
        return asset('storage/' . $this->image);
    }
}
