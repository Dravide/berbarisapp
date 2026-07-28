<?php

namespace Database\Factories;

use App\Models\Eventner;
use App\Models\Sponsor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sponsor>
 */
class SponsorFactory extends Factory
{
    protected $model = Sponsor::class;

    public function definition(): array
    {
        return [
            'eventner_id' => Eventner::factory(),
            'name' => fake()->company(),
            'type' => fake()->randomElement(['platinum', 'gold', 'silver', 'bronze', 'media']),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attrs) => ['is_active' => false]);
    }
}
