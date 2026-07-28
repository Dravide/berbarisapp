<?php

namespace Database\Factories;

use App\Models\Eventner;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'eventner_id' => Eventner::factory(),
            'name' => fake()->company() . ' Stand',
            'type' => fake()->randomElement(['kuliner', 'fashion', 'craft', 'service']),
            'description' => fake()->sentence(),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attrs) => ['is_active' => false]);
    }
}
