<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventnerBankAccount extends Model
{
    protected $fillable = [
        'eventner_id',
        'bank_name',
        'account_number',
        'account_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class, 'payment_bank_account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
