<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoteTransaction extends Model
{
    protected $fillable = [
        'eventner_id',
        'registration_id',
        'xendit_invoice_id',
        'xendit_invoice_url',
        'amount',
        'votes_earned',
        'voter_name',
        'voter_email',
        'status',
        'paid_at',
    ];

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }
}
