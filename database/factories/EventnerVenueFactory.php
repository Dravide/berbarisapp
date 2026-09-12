<?php

namespace Database\Factories;

use App\Models\Eventner;
use App\Models\EventnerVenue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventnerVenue>
 */
class EventnerVenueFactory extends Factory
{
    protected $model = EventnerVenue::class;

    public function definition(): array
    {
        return [
            'eventner_id' => Eventner::factory(),
            'name' => fake()->randomElement(['SMA 1', 'SMA 2', 'GOR Rukibra', 'Lapangan Utama']),
            'alamat' => fake()->address(),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attrs) => ['is_active' => false]);
    }
}
