<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Eventner extends Model
{
    use HasFactory;
    use \App\Traits\HasFeatureGates;

    protected $fillable = [
        'user_id',
        'status',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejection_reason',
        'plan',
        'trial_ends_at',
        'registration_source',
        'autogopay_transaction_id',
        'qr_url',
        'qr_string',
        'registration_paid_at',
        'subdomain',
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
        'ticket_start',
        'ticket_end',
        'ticket_price',
        'ticket_description',
        'ticket_max_per_order',
        'theme_config',
        'registration_status',
        'vote_active',
        'vote_price',
        'vote_start',
        'vote_end',
        'surat_tugas_required',
        'kwitansi_required',
        'checkin_token',
        'checkin_pin',
    ];

    protected $casts = [
        'theme_config' => 'array',
        'ticket_start' => 'datetime',
        'ticket_end' => 'datetime',
        'vote_start' => 'datetime',
        'vote_end' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'registration_paid_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->slug && $model->nama_event) {
                $baseSlug = \Illuminate\Support\Str::slug($model->nama_event);
                $slug = $baseSlug . '-' . \Illuminate\Support\Str::random(5);

                // Retry if collision occurs
                $retryCount = 0;
                while (static::where('slug', $slug)->exists() && $retryCount < 10) {
                    $slug = $baseSlug . '-' . \Illuminate\Support\Str::random(5 + $retryCount);
                    $retryCount++;
                }

                $model->slug = $slug;
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('nama_event')) {
                $baseSlug = \Illuminate\Support\Str::slug($model->nama_event);
                $slug = $baseSlug . '-' . $model->id;

                // Although id is unique, check if somehow there's a collision
                $retryCount = 0;
                while (static::where('slug', $slug)->where('id', '!=', $model->id)->exists() && $retryCount < 10) {
                    $slug = $baseSlug . '-' . $model->id . '-' . \Illuminate\Support\Str::random(3);
                    $retryCount++;
                }

                $model->slug = $slug;
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

    public function deductionCategories()
    {
        return $this->hasMany(DeductionCategory::class);
    }

    public function sponsors()
    {
        return $this->hasMany(Sponsor::class);
    }

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Generate public URL with subdomain if set, fallback to slug route.
     */
    public function publicUrl(string $route = 'detail', array $params = []): string
    {
        if ($this->subdomain) {
            $root = parse_url(config('app.url'), PHP_URL_HOST);
            $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?: 'http';
            $path = route("subdomain.{$route}", $params, false);
            return "{$scheme}://{$this->subdomain}.{$root}{$path}";
        }

        return route("event.{$route}", array_merge([$this->slug], $params));
    }
}
