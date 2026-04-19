<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    protected $fillable = [
        'registration_id',
        'nama',
        'nisn',
        'foto',
    ];

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }
}
