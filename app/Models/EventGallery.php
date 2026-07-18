<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventGallery extends Model
{
    protected $fillable = ['eventner_id', 'image', 'caption', 'sort_order'];

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }
}
