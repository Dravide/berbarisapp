<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait FeatureGatedComponent
{
    /**
     * Panggil dari mount() — redirect ke /upgrade jika fitur terkunci.
     * Pastikan class yang menggunakan trait ini mendefinisikan:
     * protected string $requiredFeature = 'nama_fitur';
     */
    protected function bootFeatureGate(): void
    {
        $eventner = Auth::user()?->eventner;

        if (!$eventner || !$this->requiredFeature) {
            return;
        }

        if (!$eventner->canAccessFeature($this->requiredFeature)) {
            $label = config("eventner_features.{$this->requiredFeature}.label", 'Fitur ini');
            session()->flash('error', "{$label} hanya tersedia untuk paket berbayar. Upgrade untuk mengaktifkan.");

            $this->redirect(route('eventner.billing.upgrade'), navigate: true);
        }
    }
}
