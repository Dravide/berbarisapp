<?php

namespace App\Notifications;

use App\Models\Registration;
use App\Services\FcmService;

/**
 * Nota "nilai sudah final" ke pendaftar.
 *
 * Pemanggilnya cuma satu: ScoreFinalizationService::notifyIfComplete(), dan ia
 * memakai pola `app(NilaiFinal::class)->construct($registration)->send()`. Pola
 * itu menuntut konstruktornya boleh dipanggil tanpa argumen — dan tanpa
 * argumen, properti $registration tak boleh langsung disetel ke null.
 *
 * Sebelumnya di sini `private Registration $registration;` yang wajib diisi:
 * `new NilaiFinal()` langsung melempar TypeError sebelum construct() sempat
 * dipanggil, dan karena pemanggilnya membungkusnya try/catch, kegagalannya
 * jadi tak terlihat — nota nilai final tak pernah terkirim sama sekali, tanpa
 * satu pun galat di layar. Kontraknya kini: nullable, dan send() menolak dengan
 * jelas kalau registration-nya memang belum dipasang.
 */
class NilaiFinal
{
    private ?Registration $registration = null;

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
        if (! $this->registration) {
            throw new \LogicException('NilaiFinal::send() butuh registration — panggil construct() lebih dulu.');
        }

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
