<?php

namespace Database\Factories;

use App\Models\LandingPartner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LandingPartner>
 */
class LandingPartnerFactory extends Factory
{
    protected $model = LandingPartner::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'logo' => null,
            'link' => null,
            'type' => 'sponsor',
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }

    public function medpart(): static
    {
        return $this->state(fn (array $attrs) => ['type' => 'medpart']);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attrs) => ['is_active' => false]);
    }
}
