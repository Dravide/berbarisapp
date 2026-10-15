<?php

namespace Database\Factories;

use App\Models\CompetitionCategory;
use App\Models\CompetitionFeeWave;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompetitionFeeWave>
 */
class CompetitionFeeWaveFactory extends Factory
{
    protected $model = CompetitionFeeWave::class;

    public function definition(): array
    {
        // Rentang default sengaja MENCAKUP hari ini: gelombang dibuat justru
        // supaya berlaku sekarang, jadi tes yang tidak menyebut tanggal
        // mendapat gelombang yang benar-benar dipakai.
        return [
            'eventner_id' => fn (array $attrs) => CompetitionCategory::find($attrs['competition_category_id'])?->eventner_id
                ?? \App\Models\Eventner::factory(),
            'competition_category_id' => CompetitionCategory::factory(),
            'name' => 'Gelombang ' . fake()->unique()->numberBetween(1, 999),
            'starts_on' => now()->subDays(3)->toDateString(),
            'ends_on' => now()->addDays(3)->toDateString(),
            'amount' => fake()->randomElement([100000, 125000, 150000]),
        ];
    }

    /** Rentang eksplisit — untuk tes batas tanggal. */
    public function rentang(string $mulai, string $selesai): static
    {
        return $this->state(fn () => [
            'starts_on' => $mulai,
            'ends_on' => $selesai,
        ]);
    }

    /** Gelombang di masa depan: berlaku nanti, bukan hari ini. */
    public function akanDatang(): static
    {
        return $this->state(fn () => [
            'starts_on' => now()->addDays(5)->toDateString(),
            'ends_on' => now()->addDays(20)->toDateString(),
        ]);
    }
}
