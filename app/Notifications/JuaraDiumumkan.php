<?php

namespace App\Notifications;

use App\Models\Registration;
use App\Services\FcmService;

class JuaraDiumumkan
{
    public function __construct(
        private Registration $registration,
        private string $juaraLabel, // e.g. "Juara 1", "Juara 2"
    ) {}

    public function send(): int
    {
        $event = $this->registration->eventner;

        return app(FcmService::class)->sendToModel(
            $this->registration,
            "Selamat! {$this->juaraLabel} {$event->nama_event}! 🏆",
            "{$this->registration->nama_sekolah} berhasil meraih {$this->juaraLabel}.",
            [
                'type' => 'juara',
                'registration_id' => (string) $this->registration->id,
                'event_slug' => $event->slug,
                'juara' => $this->juaraLabel,
            ]
        );
    }
}
