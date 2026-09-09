<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaasPlanFeature extends Model
{
    public $timestamps = false;

    protected $table = 'saas_plan_feature';

    protected $fillable = ['saas_plan_id', 'feature_key'];
}
