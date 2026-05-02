<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Eventner extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nama_event',
        'diselenggarakan_oleh',
        'lokasi',
        'venue',
        'tanggal',
        'tanggal_pendaftaran',
        'technical_meeting',
        'tingkat_perlombaan',
        'logo_event',
        'poster',
        'link_instagram',
        'link_tiktok',
        'link_whatsapp',
        'link_livestreaming',
        'slug',
        'drawing_code',
        'scoring_code',
        'deskripsi',
        'latitude',
        'longitude',
        'ticket_active',
        'ticket_price',
        'ticket_description',
        'ticket_max_per_order',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->slug && $model->nama_event) {
                // Ensure unique slug by appending random string if needed or just use id-based later.
                // Since id is not available on creation, we use unique string for now.
                $model->slug = \Illuminate\Support\Str::slug($model->nama_event) . '-' . \Illuminate\Support\Str::random(5);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('nama_event')) {
                $model->slug = \Illuminate\Support\Str::slug($model->nama_event) . '-' . $model->id;
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function competitionCategories()
    {
        return $this->hasMany(CompetitionCategory::class);
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function voteTransactions()
    {
        return $this->hasMany(VoteTransaction::class);
    }

    public function assessmentScores()
    {
        return $this->hasMany(AssessmentScore::class);
    }

    public function assessmentCategories()
    {
        return $this->hasMany(AssessmentCategory::class);
    }

    public function judges()
    {
        return $this->hasMany(Judge::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
