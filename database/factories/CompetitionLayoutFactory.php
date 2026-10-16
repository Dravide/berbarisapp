<?php

namespace Database\Factories;

use App\Models\CompetitionLayout;
use App\Models\Eventner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompetitionLayout>
 */
class CompetitionLayoutFactory extends Factory
{
    protected $model = CompetitionLayout::class;

    public function definition(): array
    {
        return [
            'eventner_id' => Eventner::factory(),
            'competition_category_id' => null,
            'eventner_venue_id' => null,
            'name' => 'Denah ' . fake()->word(),
            'ground_width' => 40,
            'ground_height' => 22,
            'background_image' => null,
            'is_published' => false,
            'published_at' => null,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function terbit(): static
    {
        return $this->state(fn () => [
            'is_published' => true,
            'published_at' => now(),
        ]);
    }
}
