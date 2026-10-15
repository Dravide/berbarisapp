<?php

namespace Database\Factories;

use App\Models\EventDocument;
use App\Models\Eventner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventDocument>
 */
class EventDocumentFactory extends Factory
{
    protected $model = EventDocument::class;

    public function definition(): array
    {
        return [
            'eventner_id' => Eventner::factory(),
            'title' => 'Juknis '.fake()->word(),
            'file' => 'documents/'.fake()->uuid().'.pdf',
            'original_name' => 'juknis-'.fake()->word().'.pdf',
            'file_size' => fake()->numberBetween(50_000, 5_000_000),
            'description' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(1, 100),
            'is_primary' => false,
            'download_count' => 0,
        ];
    }
}
