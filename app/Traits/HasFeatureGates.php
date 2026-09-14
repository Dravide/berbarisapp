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
     *
     * Multi-paket: eventner paid dengan saas_plan_id → fitur dari paket DB.
     * Legacy (plan 'paid' tanpa paket) & trial → semua terbuka.
     */
    public function canAccessFeature(string $feature): bool
    {
        // Paid plan legacy — semua fitur terbuka.
        //
        // Harus diperiksa SEBELUM jalur paket: saas_plan_id bisa yatim
        // (relasinya nullOnDelete, jadi paketnya ada yang dihapus admin), dan
        // eventner legacy memang tidak pernah punya paket. Dulu jalur paket
        // memakai `saas_plan_id` polos lalu membaca ->features, sehingga
        // eventner ber-paket-dihapus melempar error, bukan jatuh ke aturan
        // legacy di bawah.
        if ($this->plan === 'paid' && ! $this->saasPlan) {
            return true;
        }

        // Multi-paket via DB
        if ($this->plan === 'paid') {
            return $this->saasPlan->features->pluck('feature_key')->contains($feature);
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
        // Legacy / paket yatim — tidak ada paket untuk dibaca, jadi tidak ada
        // yang terkunci (lihat canAccessFeature()).
        if ($this->plan === 'paid' && ! $this->saasPlan) {
            return [];
        }

        // Multi-paket via DB
        if ($this->plan === 'paid') {
            $planKeys = $this->saasPlan->features->pluck('feature_key')->all();

            $locked = [];
            foreach (Config::get('eventner_features', []) as $key => $config) {
                if (!in_array($key, $planKeys, true)) {
                    $locked[$key] = $config['label'];
                }
            }

            return $locked;
        }

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
