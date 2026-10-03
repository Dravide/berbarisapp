<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetitionRoundRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'eventner_id',
        'competition_round_id',
        'registration_id',
        'competition_group_id',
        'seed',
        'preliminary_total',
        'urutan_tampil',
    ];

    protected function casts(): array
    {
        return [
            'preliminary_total' => 'decimal:2',
        ];
    }

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function competitionRound()
    {
        return $this->belongsTo(CompetitionRound::class, 'competition_round_id');
    }

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    public function competitionGroup()
    {
        return $this->belongsTo(CompetitionGroup::class, 'competition_group_id');
    }
}
