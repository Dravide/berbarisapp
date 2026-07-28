<?php

namespace Database\Factories;

use App\Models\CompetitionCategory;
use App\Models\Eventner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompetitionCategory>
 */
class CompetitionCategoryFactory extends Factory
{
    protected $model = CompetitionCategory::class;

    public function definition(): array
    {
        return [
            'eventner_id' => Eventner::factory(),
            'name' => fake()->word() . ' ' . fake()->randomElement(['Putra', 'Putri', 'Campuran']),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    /**
     * Create as a child of a parent category.
     */
    public function child(?CompetitionCategory $parent = null): static
    {
        return $this->state(fn (array $attrs) => [
            'parent_id' => $parent?->id ?? CompetitionCategory::factory(),
        ]);
    }
}
