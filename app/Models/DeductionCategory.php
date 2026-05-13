<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeductionCategory extends Model
{
    protected $fillable = ['eventner_id', 'name', 'sort_order'];

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function criterias()
    {
        return $this->hasMany(DeductionCriteria::class)->orderBy('sort_order');
    }
}
