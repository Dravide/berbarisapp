<?php

namespace App\Notifications;

use App\Models\Registration;
use App\Services\FcmService;

class BerkasDiverifikasi
{
    public function __construct(
        private Registration $registration,
    ) {}

    public function send(): int
    {
        $event = $this->registration->eventner;

        return app(FcmService::class)->sendToModel(
            $this->registration,
            'Berkas Terverifikasi ✓',
            "Pendaftaran {$event->nama_event} — {$this->registration->nama_sekolah} telah diverifikasi.",
            [
                'type' => 'berkas_verified',
                'registration_id' => (string) $this->registration->id,
                'event_slug' => $event->slug,
            ]
        );
    }
}
