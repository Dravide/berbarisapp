<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventRundown extends Model
{
    use HasFactory;

    protected $fillable = [
        'eventner_id',
        'title',
        'description',
        'start_time',
        'end_time',
        'duration_minutes',
        'sort_order',
        'source_category_id',
        'source_registration_id',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
        ];
    }

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function sourceCategory()
    {
        return $this->belongsTo(\App\Models\CompetitionCategory::class, 'source_category_id');
    }
}
