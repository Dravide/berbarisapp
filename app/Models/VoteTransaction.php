<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class VoteTransaction extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'paid_at'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Transaksi voting {$this->autogopay_transaction_id} telah di-{$eventName}");
    }

    protected $fillable = [
        'eventner_id',
        'registration_id',
        'autogopay_transaction_id',
        'qr_url',
        'amount',
        'votes_earned',
        'voter_name',
        'voter_email',
        'comment',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
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
