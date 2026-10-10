<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Log error tingkat platform: satu baris per error server (5xx) tak
 * terduga. Kode publik (ER-XXXXXX) ditampilkan ke user; sisanya hanya
 * untuk admin di /admin/error-log.
 */
class ErrorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'message',
        'exception_class',
        'file',
        'line',
        'http_status',
        'url',
        'method',
        'user_id',
        'eventner_id',
        'user_agent',
        'ip',
        'trace',
        'resolved_at',
        'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'line' => 'integer',
            'http_status' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function eventner(): BelongsTo
    {
        return $this->belongsTo(Eventner::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeBelumSelesai(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }
}
