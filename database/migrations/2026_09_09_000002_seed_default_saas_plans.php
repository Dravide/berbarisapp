<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Paket legacy (plan free/paid tanpa saas_plan_id) arahkan ke paket bawaan
        $free = \App\Models\SaasPlan::create([
            'name' => 'Gratis',
            'slug' => 'gratis',
            'price' => 0,
            'registration_fee' => 0,
            'description' => 'Untuk mulai mengelola lomba',
            'is_active' => true,
            'is_free' => true,
            'sort_order' => 1,
        ]);

        $premiumKeys = collect(config('eventner_features', []))
            ->filter(fn ($c) => $c['locked_free'] ?? true)
            ->keys()
            ->all();

        $saved = json_decode(\App\Models\Setting::get('saas_pricing', '{}'), true) ?? [];
        if (is_array($saved['premium_features'] ?? null)) {
            $premiumKeys = $saved['premium_features'];
        }

        $full = \App\Models\SaasPlan::create([
            'name' => 'Event Penuh',
            'slug' => 'event-penuh',
            'price' => (int) \App\Models\Setting::get('eventner_plan_price', 150000),
            'registration_fee' => (int) \App\Models\Setting::get('eventner_registration_fee', 50000),
            'description' => 'Bayar sekali, aktif selama event',
            'is_active' => true,
            'is_free' => false,
            'highlight' => true,
            'sort_order' => 2,
        ]);
        $full->features()->createMany(
            collect($premiumKeys)->map(fn ($key) => ['feature_key' => $key])->all()
        );

        \App\Models\Eventner::whereNull('saas_plan_id')->update([
            'saas_plan_id' => $free->id,
        ]);
        \App\Models\Eventner::where('plan', 'paid')->update([
            'saas_plan_id' => $full->id,
        ]);
    }

    public function down(): void
    {
        \App\Models\Eventner::query()->update(['saas_plan_id' => null]);
    }
};
