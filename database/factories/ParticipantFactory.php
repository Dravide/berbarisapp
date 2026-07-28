<?php

namespace Database\Factories;

use App\Models\Participant;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Participant>
 */
class ParticipantFactory extends Factory
{
    protected $model = Participant::class;

    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'nama' => fake()->name(),
            'nisn' => (string) fake()->unique()->numberBetween(1000000000, 9999999999),
        ];
    }
}
