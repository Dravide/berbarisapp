<?php

namespace App\Notifications;

use App\Models\Registration;
use App\Services\FcmService;

class NilaiFinal
{
    public function __construct(
        private Registration $registration,
    ) {}

    public function send(): int
    {
        $event = $this->registration->eventner;

        return app(FcmService::class)->sendToModel(
            $this->registration,
            'Nilai Final Released',
            "Nilai lomba {$event->nama_event} untuk {$this->registration->nama_sekolah} sudah final.",
            [
                'type' => 'nilai_final',
                'registration_id' => (string) $this->registration->id,
                'event_slug' => $event->slug,
            ]
        );
    }
}
