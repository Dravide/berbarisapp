<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventFaq extends Model
{
    protected $fillable = ['eventner_id', 'question', 'answer', 'sort_order'];

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }
}
