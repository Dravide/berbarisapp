<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Registration extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'eventner_id',
        'competition_category_id',
        'label_pasukan',
        'nama_sekolah',
        'npsn',
        'nama_pelatih',
        'no_hp',
        'school_email',
        'foto_pelatih',
        'magic_token',
        'password',
        'logo_sekolah',
        'surat_tugas',
        'danton_nama',
        'danton_nisn',
        'danton_foto',
        'status_berkas',
        'bukti_pendaftaran',
        'is_finalized',
        'urutan_tampil',
        'total_fee',
        'payment_status',
        'payment_proof',
        'payment_bank_account_id',
        'payment_verified_at',
        'payment_verified_by',
    ];

    protected function casts(): array
    {
        return [
            'total_fee' => 'decimal:2',
            'payment_verified_at' => 'datetime',
        ];
    }

    protected $hidden = ['password'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->magic_token) {
                $model->magic_token = \Illuminate\Support\Str::random(16);
            }
            if (!$model->status_berkas) {
                $model->status_berkas = 'booking';
            }
        });
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'npsn', 'npsn');
    }

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function competitionCategory()
    {
        return $this->belongsTo(CompetitionCategory::class);
    }

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }

    public function voteTransactions()
    {
        return $this->hasMany(VoteTransaction::class);
    }

    public function scoreDeductions()
    {
        return $this->hasMany(ScoreDeduction::class);
    }

    public function paymentBankAccount()
    {
        return $this->belongsTo(EventnerBankAccount::class, 'payment_bank_account_id');
    }

    public function paymentVerifiedBy()
    {
        return $this->belongsTo(User::class, 'payment_verified_by');
    }

    public function isUnpaid(): bool
    {
        return $this->payment_status === 'unpaid';
    }

    public function isPaymentPending(): bool
    {
        return $this->payment_status === 'pending_verification';
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isFree(): bool
    {
        return $this->payment_status === 'free';
    }

    public function isBooking(): bool
    {
        return $this->status_berkas === 'booking';
    }

    public function isConfirmed(): bool
    {
        return $this->status_berkas === 'confirmed';
    }

    public function isVerified(): bool
    {
        return $this->status_berkas === 'Terverifikasi';
    }

    /**
     * Resolve a certificate text field value for this registration.
     */
    public function resolveCertificateField(string $fieldKey, array $context = []): string
    {
        $eventner = $context['eventner'] ?? $this->eventner;
        $winner = $context['winner'] ?? null;
        $championCategory = $context['championCategory'] ?? null;
        $competitionCategory = $context['competitionCategory'] ?? null;

        return match ($fieldKey) {
            'nama_sekolah'  => $this->nama_sekolah,
            'gelar_juara'   => $winner['title'] ?? ($winner['rank'] ?? ''),
            'kategori_juara' => $championCategory?->name ?? '',
            'kategori_lomba' => $competitionCategory?->name ?? $this->competitionCategory?->name ?? '',
            'nama_event'    => $eventner?->nama_event ?? '',
            'tanggal'       => $eventner?->tanggal
                ? \Carbon\Carbon::parse($eventner->tanggal)->translatedFormat('d F Y')
                : '',
            'venue'         => $eventner?->venue ?? '',
            'nama_pelatih'  => $this->nama_pelatih ?? '',
            'nama_peserta'  => $this->participants->pluck('nama')->join(', '),
            'diselenggarakan_oleh' => $eventner?->diselenggarakan_oleh ?? '',
            default         => '',
        };
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nama_sekolah', 'status_berkas', 'is_finalized'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Pendaftaran {$this->nama_sekolah} telah di-{$eventName}");
    }
}
