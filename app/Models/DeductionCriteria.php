<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DeductionCriteria extends Model
{
    use LogsActivity;

    protected $fillable = ['deduction_category_id', 'name', 'deduction_options', 'sort_order'];

    protected $casts = [
        'deduction_options' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(DeductionCategory::class, 'deduction_category_id');
    }

    public function scoreDeductions()
    {
        return $this->hasMany(ScoreDeduction::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'deduction_options'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Kriteria Pengurangan {$this->name} telah di-{$eventName}");
    }
}
