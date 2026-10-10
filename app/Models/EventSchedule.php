<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'eventner_id',
        'competition_category_id',
        'competition_group_id',
        'competition_round_id',
        'eventner_venue_id',
        'title',
        'start_time',
        'end_time',
        'tanggal',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'tanggal' => 'date:Y-m-d',
        ];
    }

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    /** Tingkat lomba yang dipertandingkan. */
    public function category()
    {
        return $this->belongsTo(CompetitionCategory::class, 'competition_category_id');
    }

    public function group()
    {
        return $this->belongsTo(CompetitionGroup::class, 'competition_group_id');
    }

    public function round()
    {
        return $this->belongsTo(CompetitionRound::class, 'competition_round_id');
    }

    public function venue()
    {
        return $this->belongsTo(EventnerVenue::class, 'eventner_venue_id');
    }
}
