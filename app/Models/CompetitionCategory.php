<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetitionCategory extends Model
{
    use HasFactory;

    protected $fillable = ['eventner_id', 'name', 'tanggal_pelaksanaan', 'kuota', 'max_registrations_per_school'];

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function judges()
    {
        return $this->belongsToMany(Judge::class);
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function remainingSlots(): int
    {
        return max(0, ($this->kuota ?? 0) - $this->registrations()->count());
    }
}
