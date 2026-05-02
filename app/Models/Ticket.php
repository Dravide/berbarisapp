<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'eventner_id',
        'order_code',
        'buyer_name',
        'buyer_email',
        'buyer_phone',
        'quantity',
        'price_per_ticket',
        'total_amount',
        'xendit_invoice_id',
        'xendit_invoice_url',
        'qr_code_path',
        'status',
        'paid_at',
        'checked_in_at',
        'checked_in_by',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->order_code) {
                $model->order_code = 'TKT-' . strtoupper(Str::random(8));
            }
        });
    }

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'PAID');
    }

    public function scopeCheckedIn($query)
    {
        return $query->where('status', 'CHECKED_IN');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }
}
