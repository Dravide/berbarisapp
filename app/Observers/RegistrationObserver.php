<?php

namespace App\Observers;

use App\Models\Registration;
use App\Notifications\BerkasDiverifikasi;
use Illuminate\Support\Facades\Log;

class RegistrationObserver
{
    public function updated(Registration $registration): void
    {
        if (!$registration->wasChanged('status_berkas')) return;

        try {
            match ($registration->status_berkas) {
                'Terverifikasi' => (new BerkasDiverifikasi($registration))->send(),
                'Ditolak' => app(\App\Services\FcmService::class)->sendToModel(
                    $registration,
                    'Berkas Ditolak',
                    "Pendaftaran {$registration->nama_sekolah} perlu diperbaiki. Silakan cek informasi dari panitia.",
                    ['type' => 'berkas_rejected', 'registration_id' => (string) $registration->id]
                ),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::warning('FCM notification failed (RegistrationObserver)', [
                'id' => $registration->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
