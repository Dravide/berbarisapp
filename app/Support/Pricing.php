<?php

namespace App\Support;

use App\Models\SaasPlan;
use App\Models\Setting;

/**
 * Sumber kebenaran tampilan harga & paket SaaS.
 * Admin mengelola paket via tabel saas_plans (/admin/pricing-settings).
 *
 * Catatan: guard akses fitur runtime baca paket DB via HasFeatureGates —
 * helper ini hanya menentukan apa yang ditampilkan di halaman harga.
 */
class Pricing
{
    /**
     * Paket aktif untuk halaman harga (urut sort_order).
     * Fallback legacy: belum ada paket → paket gratis + Event Penuh dari Setting.
     */
    public static function plans(): array
    {
        $plans = SaasPlan::query()
            ->with('features')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (SaasPlan $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'price' => (int) $p->price,
                'description' => $p->description,
                'is_free' => $p->is_free,
                'is_contact' => $p->is_contact,
                'contact_url' => $p->contact_url,
                'highlight' => $p->highlight,
                'features' => $p->featureKeys(),
            ]);

        if ($plans->isEmpty()) {
            return [self::legacyFreePlan(), self::legacyFullPlan()];
        }

        return $plans->all();
    }

    /**
     * Fitur yang terbuka di paket mana pun, termasuk paket gratis.
     *
     * Dipakai dua tempat: daftar cek di kartu paket gratis dan tabel
     * perbandingan. Sebelum ini daftarnya ditulis ulang di dalam Blade kartu
     * harga; begitu tabel perbandingan menyusul, dua salinan itu pasti
     * berbeda diam-diam.
     */
    public static function baseFeatures(): array
    {
        return [
            'Dashboard event & profil',
            'Kategori lomba & pendaftaran peserta',
            'Manajemen juri & input nilai',
            'Rekap nilai & scoreboard publik',
            'QR check-in peserta',
        ];
    }

    /**
     * Matriks perbandingan paket: baris = fitur, kolom = paket.
     *
     * Dua lapis baris: fitur dasar (selalu terbuka) dan fitur premium dari
     * config. Sel premium dihitung dari paket DB yang sama dengan halaman
     * harga, jadi paket yang baru dibuat admin langsung muncul jadi kolom
     * tanpa perubahan kode.
     */
    public static function comparisonMatrix(): array
    {
        $plans = self::plans();

        $rows = [];
        foreach (self::baseFeatures() as $label) {
            $rows[] = ['label' => $label, 'cells' => array_fill(0, count($plans), true)];
        }

        foreach (config('eventner_features', []) as $key => $config) {
            // locked_free = false → selalu terbuka, bukan fitur jualan paket.
            $terbuka = ! ($config['locked_free'] ?? true);

            $rows[] = [
                'label' => $config['label'],
                'cells' => array_map(
                    fn ($plan) => $terbuka || (! $plan['is_free'] && in_array($key, $plan['features'], true)),
                    $plans
                ),
            ];
        }

        return ['plans' => $plans, 'rows' => $rows];
    }

    public static function planPrice(): int
    {
        $plan = self::paidPlan();

        return $plan?->price ?? (int) Setting::get('eventner_plan_price', 150000);
    }

    private static function paidPlan(): ?SaasPlan
    {
        return SaasPlan::query()
            ->where('is_active', true)
            ->where('is_free', false)
            ->where('is_contact', false)
            ->orderBy('sort_order')
            ->first();
    }

    /**
     * Daftar fitur premium (locked_free=true di config) yang DIPAMERKAN
     * di halaman harga — dari paket berbayar aktif pertama.
     */
    public static function premiumFeatures(): array
    {
        $config = collect(config('eventner_features', []))
            ->filter(fn ($c) => $c['locked_free'] ?? true)
            ->map(fn ($c, $key) => ['key' => $key, 'label' => $c['label']]);

        $plan = self::paidPlan()->loadMissing('features');

        if ($plan) {
            $included = $plan->featureKeys();

            return $config
                ->filter(fn ($f) => in_array($f['key'], $included, true))
                ->values()
                ->all();
        }

        $setting = json_decode(Setting::get('saas_pricing', '{}'), true) ?? [];
        $legacy = $setting['premium_features'] ?? null;

        // Belum pernah diatur admin → pakai semua fitur config
        if (!is_array($legacy)) {
            return $config->values()->all();
        }

        return $config
            ->filter(fn ($f) => in_array($f['key'], $legacy, true))
            ->values()
            ->all();
    }

    private static function legacyFreePlan(): array
    {
        return [
            'id' => null,
            'name' => 'Gratis',
            'slug' => 'gratis',
            'price' => 0,
            'description' => 'Untuk mulai mengelola lomba',
            'is_free' => true,
            'highlight' => false,
            'features' => [],
        ];
    }

    private static function legacyFullPlan(): array
    {
        return [
            'id' => null,
            'name' => 'Event Penuh',
            'slug' => 'event-penuh',
            'price' => self::planPrice(),
            'description' => 'Bayar sekali, aktif selama event',
            'is_free' => false,
            'highlight' => true,
            'is_contact' => false,
            'contact_url' => null,
            'features' => collect(self::premiumFeatures())->pluck('key')->all(),
        ];
    }
}
