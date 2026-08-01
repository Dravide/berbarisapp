<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;

class FcmService
{
    private $messaging;

    public function __construct()
    {
        $this->messaging = app(Messaging::class);
    }

    /**
     * Kirim notifikasi ke semua device token milik sebuah model (MorphTo).
     * Token invalid (FCM unregistered) otomatis dihapus.
     */
    public function sendToModel(object $model, string $title, string $body, array $data = []): int
    {
        $relation = match (true) {
            $model instanceof \App\Models\Registration => 'registration_id',
            $model instanceof \App\Models\User => 'user_id',
            default => null,
        };

        if (!$relation) return 0;

        $tokens = DeviceToken::where($relation, $model->id)->pluck('token')->toArray();

        if (empty($tokens)) {
            return 0;
        }

        return $this->sendRaw($tokens, $title, $body, $data);
    }

    /**
     * Kirim notifikasi broadcast (semua token semua model).
     */
    public function broadcast(string $title, string $body, array $data = []): int
    {
        $tokens = DeviceToken::pluck('token')->toArray();
        if (empty($tokens)) return 0;

        return $this->sendRaw($tokens, $title, $body, $data);
    }

    /**
     * Kirim notifikasi ke semua device token peserta (Registration) sebuah event.
     */
    public function sendToEvent(\App\Models\Eventner $eventner, string $title, string $body, array $data = []): int
    {
        $tokens = DeviceToken::whereIn(
            'registration_id',
            \App\Models\Registration::where('eventner_id', $eventner->id)->pluck('id')
        )->pluck('token')->toArray();

        if (empty($tokens)) return 0;

        return $this->sendRaw($tokens, $title, $body, $data);
    }

    private function sendRaw(array $tokens, string $title, string $body, array $data): int
    {
        $message = CloudMessage::new()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData($data);

        $sent = 0;
        foreach (array_chunk($tokens, 500) as $chunk) {
            try {
                $report = $this->messaging->sendAll(
                    array_map(fn ($t) => $message->withToken($t), $chunk)
                );
                $sent += $report->successes()->count();

                foreach ($report->failures() as $failure) {
                    $token = $failure->target()->value();
                    DeviceToken::where('token', $token)->delete();
                }
            } catch (\Exception $e) {
                Log::error('FCM broadcast failed', ['error' => $e->getMessage()]);
            }
        }

        return $sent;
    }
}
