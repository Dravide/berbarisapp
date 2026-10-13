<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class JudgeNote extends Model
{
    use HasFactory;

    protected $table = 'assessment_judge_notes';

    protected $fillable = [
        'eventner_id',
        'registration_id',
        'judge_id',
        'competition_round_id',
        'audio_path',
        'duration_seconds',
        'mime_type',
        'notes',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
    ];

    protected static function booted()
    {
        static::deleted(function (JudgeNote $note) {
            if ($note->audio_path) {
                Storage::disk('public')->delete($note->audio_path);
            }
        });
    }

    public function eventner()
    {
        return $this->belongsTo(Eventner::class);
    }

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    public function judge()
    {
        return $this->belongsTo(Judge::class);
    }

    public function competitionRound()
    {
        return $this->belongsTo(CompetitionRound::class);
    }

    public function audioUrl(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->audio_path) {
                return null;
            }

            // Path root-relative agar portabel antar host & subdomain (entry.berbaris.local,
            // berbaris.local) dan bebas dari kendala Mixed Content (HTTPS vs HTTP) serta CORS.
            return '/storage/' . ltrim($this->audio_path, '/');
        });
    }

    public function formattedDuration(): Attribute
    {
        return Attribute::get(function () {
            $seconds = (int) ($this->duration_seconds ?? 0);
            $minutes = floor($seconds / 60);
            $secs = $seconds % 60;

            return sprintf('%02d:%02d', $minutes, $secs);
        });
    }
}
