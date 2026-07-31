<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait FeatureGatedComponent
{
    /**
     * Panggil dari mount() — redirect ke dashboard jika fitur terkunci.
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
            session()->flash('error', 'Fitur ini hanya tersedia untuk paket berbayar. Trial Anda telah berakhir.');
            abort(403);
        }
    }
}
