<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu bukti pembayaran biaya pendaftaran — uang muka atau pelunasan.
 *
 * Model ini sengaja tipis. Yang mengubah status pendaftaran bukan model ini,
 * melainkan App\Services\PaymentRecorder: kalau `save()` di sini ikut menulis
 * `registrations.amount_paid`, akan ada dua jalur yang bisa menghasilkan angka
 * berbeda untuk data yang sama — persis kelas bug yang sudah pernah terjadi di
 * proyek ini (lihat catatan trait MelaporKePengguna).
 */
class RegistrationPayment extends Model
{
    use HasFactory;

    public const KIND_DP = 'dp';
    public const KIND_PELUNASAN = 'pelunasan';

    public const STATUS_PENDING = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'registration_id',
        'kind',
        'amount',
        'proof',
        'status',
        'bank_account_id',
        'verified_at',
        'verified_by',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'verified_at' => 'datetime',
        ];
    }

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(EventnerBankAccount::class, 'bank_account_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    public function isDp(): bool
    {
        return $this->kind === self::KIND_DP;
    }

    public function labelJenis(): string
    {
        return $this->isDp() ? 'DP' : 'Pelunasan';
    }
}
