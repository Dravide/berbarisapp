<?php

namespace App\Notifications;

use App\Models\Registration;
use App\Services\FcmService;

class JuaraDiumumkan
{
    private Registration $registration;
    private string $juaraLabel;

    public function __construct(?Registration $registration = null, ?string $juaraLabel = null)
    {
        $this->registration = $registration;
        $this->juaraLabel = $juaraLabel ?? '';
    }

    public function construct(Registration $registration, string $juaraLabel): static
    {
        $this->registration = $registration;
        $this->juaraLabel = $juaraLabel;
        return $this;
    }

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
