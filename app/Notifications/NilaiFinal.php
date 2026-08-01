<?php

namespace App\Notifications;

use App\Models\Registration;
use App\Services\FcmService;

class NilaiFinal
{
    private Registration $registration;

    public function __construct(?Registration $registration = null)
    {
        $this->registration = $registration;
    }

    public function construct(Registration $registration): static
    {
        $this->registration = $registration;
        return $this;
    }

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
