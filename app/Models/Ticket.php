<?php

namespace App\Models;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRGdImagePNG;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
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
        'autogopay_transaction_id',
        'qr_url',
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

    protected $casts = [
        'paid_at' => 'datetime',
        'checked_in_at' => 'datetime',
    ];

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

    /**
     * Klaim atomik PENDING → PAID, sekaligus memastikan QR masuk ikut terbuat.
     *
     * WAJIB dipakai semua jalur konfirmasi pembayaran tiket (webhook, job polling,
     * command, tombol konfirmasi manual). `Ticket::where(...)->update([...])` di
     * luar method ini tidak memicu model event, jadi tiket bisa jadi PAID tanpa
     * qr_code_path dan QR-nya tidak muncul di halaman pembeli.
     *
     * Return false kalau barisnya bukan PENDING lagi — artinya jalur lain
     * (webhook vs polling) sudah lebih dulu mengklaim, jadi jangan kirim email.
     */
    public function claimPaid(): bool
    {
        return (bool) static::where('id', $this->id)
            ->where('status', 'PENDING')
            ->update([
                'status' => 'PAID',
                'paid_at' => now(),
                'qr_code_path' => $this->qr_code_path ?: $this->generateEntryQr(),
            ]);
    }

    /**
     * Generate QR tiket masuk (PNG di disk public) dan kembalikan path-nya.
     * Idempoten: menulis ke path yang sama untuk order_code yang sama.
     */
    public function generateEntryQr(): string
    {
        $options = new QROptions;
        $options->outputInterface = QRGdImagePNG::class;
        $options->outputBase64 = false;
        $options->scale = 6;

        $path = 'tickets/' . $this->order_code . '.png';
        Storage::disk('public')->put($path, (new QRCode($options))->render($this->order_code));

        return $path;
    }
}
