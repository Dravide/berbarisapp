<?php

namespace Database\Factories;

use App\Models\EventFaq;
use App\Models\Eventner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventFaq>
 */
class EventFaqFactory extends Factory
{
    protected $model = EventFaq::class;

    public function definition(): array
    {
        return [
            'eventner_id' => Eventner::factory(),
            'question' => fake()->sentence() . '?',
            'answer' => fake()->paragraph(),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
