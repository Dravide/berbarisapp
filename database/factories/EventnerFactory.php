<?php

namespace Database\Factories;

use App\Models\Eventner;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Eventner>
 */
class EventnerFactory extends Factory
{
    protected $model = Eventner::class;

    public function definition(): array
    {
        $nama = fake()->company() . ' Lomba';

        return [
            'user_id' => User::factory(),
            'status' => 'approved',
            'plan' => 'free',
            'subdomain' => Str::slug($nama) . '-' . Str::random(4),
            'nama_event' => $nama,
            'diselenggarakan_oleh' => fake()->company(),
            'lokasi' => fake()->city(),
            'venue' => fake()->streetName(),
            'tanggal' => fake()->dateTimeBetween('+1 week', '+2 months')->format('Y-m-d'),
            'deskripsi' => fake()->paragraph(),
            'slug' => Str::slug($nama) . '-' . Str::random(5),
            'registration_status' => 'open',
            'trial_ends_at' => now()->addDays(30),
        ];
    }

    /**
     * Eventner with paid plan — all features unlocked.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attrs) => [
            'plan' => 'paid',
            'trial_ends_at' => null,
        ]);
    }

    /**
     * Eventner with expired trial.
     */
    public function expiredTrial(): static
    {
        return $this->state(fn (array $attrs) => [
            'plan' => 'free',
            'trial_ends_at' => now()->subDay(),
        ]);
    }

    /**
     * Eventner pending approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => 'pending',
            'approved_at' => null,
        ]);
    }
}
