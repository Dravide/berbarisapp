<?php

namespace App\Traits;

use Illuminate\Support\Facades\Config;

trait HasFeatureGates
{
    /**
     * Cek apakah eventner sedang dalam masa trial (free plan).
     */
    public function isOnTrial(): bool
    {
        return $this->plan === 'free'
            && $this->trial_ends_at !== null
            && now()->lessThan($this->trial_ends_at);
    }

    /**
     * Cek apakah trial sudah berakhir.
     */
    public function isTrialExpired(): bool
    {
        return $this->plan === 'free'
            && $this->trial_ends_at !== null
            && now()->greaterThanOrEqualTo($this->trial_ends_at);
    }

    /**
     * Sisa hari trial (0 jika tidak dalam masa trial).
     */
    public function trialDaysLeft(): int
    {
        if (!$this->isOnTrial()) {
            return 0;
        }

        return max(0, now()->diffInDays($this->trial_ends_at) + 1);
    }

    /**
     * Cek apakah fitur tertentu bisa diakses.
     */
    public function canAccessFeature(string $feature): bool
    {
        // Paid plan — semua fitur terbuka
        if ($this->plan === 'paid') {
            return true;
        }

        // Free plan — cek trial
        if ($this->isOnTrial()) {
            return true;
        }

        // Trial expired atau tidak ada trial — cek config
        $featureConfig = Config::get("eventner_features.{$feature}");

        // Fitur tidak terdaftar di config → selalu tersedia
        if ($featureConfig === null) {
            return true;
        }

        // locked_free = false → selalu tersedia
        if (!($featureConfig['locked_free'] ?? true)) {
            return true;
        }

        // locked_free = true → terkunci
        return false;
    }

    /**
     * Daftar fitur yang terkunci (setelah trial expired).
     */
    public function lockedFeatures(): array
    {
        if ($this->plan === 'paid') {
            return [];
        }

        if ($this->isOnTrial()) {
            return [];
        }

        $locked = [];
        foreach (Config::get('eventner_features', []) as $key => $config) {
            if ($config['locked_free'] ?? false) {
                $locked[$key] = $config['label'];
            }
        }

        return $locked;
    }
}
