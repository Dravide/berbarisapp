<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OverlaySetting extends Model
{
    protected $fillable = [
        'eventner_id',
        'components',
        'show_header',
        'show_vote_leaderboard',
        'show_kegiatan',
        'show_footer',
        'marquee_text',
    ];

    protected $casts = [
        'components' => 'array',
        'show_header' => 'boolean',
        'show_vote_leaderboard' => 'boolean',
        'show_kegiatan' => 'boolean',
        'show_footer' => 'boolean',
    ];

    public function eventner()
    {
        $this->belongsTo(Eventner::class);
    }
}
