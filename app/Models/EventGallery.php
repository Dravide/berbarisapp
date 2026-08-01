<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventGallery extends Model
{
    use HasFactory;

    protected $fillable = ['eventner_id', 'image', 'caption', 'sort_order'];

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }
}
