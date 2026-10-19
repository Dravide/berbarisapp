<?php

namespace Database\Factories;

use App\Models\CompetitionLayout;
use App\Models\LayoutZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LayoutZone>
 */
class LayoutZoneFactory extends Factory
{
    protected $model = LayoutZone::class;

    public function definition(): array
    {
        return [
            'competition_layout_id' => CompetitionLayout::factory(),
            'name' => 'Zona ' . fake()->word(),
            'type' => LayoutZone::TYPE_ARENA,
            'x' => 14,
            'y' => 16,
            'w' => 62,
            'h' => 50,
            'width_m' => null,
            'height_m' => null,
            'is_locked' => false,
            'dp_number' => null,
            'capacity' => null,
            'note' => null,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function dp(int $nomor): static
    {
        return $this->state(fn () => [
            'name' => "DP {$nomor}",
            'type' => LayoutZone::TYPE_DP,
            'dp_number' => $nomor,
            'capacity' => '1 pasukan',
        ]);
    }

    /** Arena berukuran tetap: angkanya diisi, bukan disimpulkan dari papan. */
    public function arenaMeter(float $lebar, float $tinggi): static
    {
        return $this->state(fn () => [
            'type' => LayoutZone::TYPE_ARENA,
            'width_m' => $lebar,
            'height_m' => $tinggi,
        ]);
    }

    /** Zona yang posisinya sudah pas dan tak boleh tergeser lagi. */
    public function terkunci(): static
    {
        return $this->state(fn () => ['is_locked' => true]);
    }
}
