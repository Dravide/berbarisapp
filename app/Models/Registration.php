<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Registration extends Model
{
    use LogsActivity;

    protected $fillable = [
        'eventner_id',
        'competition_category_id',
        'nama_sekolah',
        'npsn',
        'nama_pelatih',
        'no_hp',
        'foto_pelatih',
        'magic_token',
        'logo_sekolah',
        'surat_tugas',
        'danton_nama',
        'danton_nisn',
        'danton_foto',
        'status_berkas',
        'bukti_pendaftaran',
        'is_finalized',
        'urutan_tampil',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->magic_token) {
                $model->magic_token = \Illuminate\Support\Str::random(16);
            }
        });
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nama_sekolah', 'status_berkas', 'is_finalized'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Pendaftaran {$this->nama_sekolah} telah di-{$eventName}");
    }
}
