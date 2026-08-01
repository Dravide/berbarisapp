<?php

namespace Database\Factories;

use App\Models\EventGallery;
use App\Models\Eventner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventGallery>
 */
class EventGalleryFactory extends Factory
{
    protected $model = EventGallery::class;

    public function definition(): array
    {
        return [
            'eventner_id' => Eventner::factory(),
            'image' => 'events/gallery/' . fake()->uuid() . '.jpg',
            'caption' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
