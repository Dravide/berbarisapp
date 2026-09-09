<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Sumber kebenaran tampilan harga & paket SaaS.
 * Admin mengatur via Setting 'saas_pricing' (JSON); fallback ke config.
 *
 * Catatan: guard akses fitur runtime tetap baca config/eventner_features.php
 * (HasFeatureGates) — setting ini hanya menentukan apa yang ditampilkan
 * di halaman harga.
 */
class Pricing
{
    public static function planPrice(): int
    {
        return (int) Setting::get('eventner_plan_price', 150000);
    }

    public static function registrationFee(): int
    {
        return (int) Setting::get('eventner_registration_fee', 50000);
    }

    /**
     * Daftar fitur premium (locked_free=true di config) yang DIPAMERKAN
     * di halaman harga — sesuai centang admin di Setting 'saas_pricing'.
     *
     * @return array<int, array{key: string, label: string}>
     */
    public static function premiumFeatures(): array
    {
        $config = collect(config('eventner_features', []))
            ->filter(fn ($c) => $c['locked_free'] ?? true)
            ->map(fn ($c, $key) => ['key' => $key, 'label' => $c['label']]);

        $setting = json_decode(Setting::get('saas_pricing', '{}'), true) ?? [];
        $included = $setting['premium_features'] ?? null;

        // Belum pernah diatur admin → pakai semua fitur config
        if (!is_array($included)) {
            return $config->values()->all();
        }

        return $config
            ->filter(fn ($f) => in_array($f['key'], $included, true))
            ->values()
            ->all();
    }
}