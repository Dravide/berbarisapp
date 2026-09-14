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

    /**
     * Klaim atomik transaksi ini sebagai lunas.
     *
     * Menerima PENDING **dan** EXPIRED. QRIS bisa kedaluwarsa di sisi
     * AutoGoPay sementara pembeli tetap menyelesaikan pembayarannya (QR
     * sudah terlanjur discan, atau webhook kedaluwarsanya telat sampai).
     * Kalau hanya PENDING yang diterima, uang yang benar-benar masuk tidak
     * pernah dikreditkan sebagai vote dan transaksinya nyangkut selamanya.
     *
     * PENDING → PAID dan EXPIRED → PAID sama-sama sah: satu baris = satu
     * transaksi AutoGoPay, jadi tidak ada risiko dobel kredit. Return false
     * kalau barisnya sudah PAID/FAILED — artinya jalur lain lebih dulu
     * mengklaim, jangan diproses lagi.
     */
    public function claimPaid(): bool
    {
        return (bool) static::where('id', $this->id)
            ->whereIn('status', ['PENDING', 'EXPIRED'])
            ->update(['status' => 'PAID', 'paid_at' => now()]);
    }

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }
}
