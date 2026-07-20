<?php

namespace App\Console\Commands;

use App\Models\Eventner;
use App\Services\MailyService;
use Illuminate\Console\Command;

class SendTrialExpiryWarnings extends Command
{
    protected $signature = 'eventner:trial-expiry-warning';
    protected $description = 'Kirim peringatan trial akan berakhir ke eventner free plan';

    public function handle(): int
    {
        $nextDay = now()->addDay()->startOfDay();

        $expiring = Eventner::where('plan', 'free')
            ->whereNotNull('trial_ends_at')
            ->whereDate('trial_ends_at', $nextDay->toDateString())
            ->with('user')
            ->get();

        if ($expiring->isEmpty()) {
            $this->info('No expiring trials found.');
            return self::SUCCESS;
        }

        $mailService = app(MailyService::class);
        $sent = 0;

        foreach ($expiring as $eventner) {
            if (!$eventner->user || !$eventner->user->email) {
                continue;
            }

            $daysLeft = max(1, $eventner->trialDaysLeft());

            try {
                $ok = $mailService->sendTrialExpiring(
                    $eventner->user->email,
                    $eventner->user->name,
                    $eventner->nama_event,
                    $daysLeft
                );

                if ($ok) {
                    $sent++;
                    $this->info("Sent trial expiry warning to {$eventner->user->email}");
                }
            } catch (\Throwable $e) {
                $this->error("Failed to send to {$eventner->user->email}: {$e->getMessage()}");
            }
        }

        $this->info("Sent {$sent}/{$expiring->count()} trial expiry warnings.");
        return self::SUCCESS;
    }
}
