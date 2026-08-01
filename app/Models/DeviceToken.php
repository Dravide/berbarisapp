<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceToken extends Model
{
    protected $fillable = ['token', 'platform', 'registration_id', 'user_id'];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
