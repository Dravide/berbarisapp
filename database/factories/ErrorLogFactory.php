<?php

namespace Database\Factories;

use App\Models\ErrorLog;
use App\Support\ErrorCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ErrorLog>
 */
class ErrorLogFactory extends Factory
{
    protected $model = ErrorLog::class;

    public function definition(): array
    {
        $exception = fake()->randomElement([
            \RuntimeException::class,
            \ErrorException::class,
            \Illuminate\Database\QueryException::class,
        ]);

        return [
            'code' => ErrorCode::generate(),
            'message' => fake()->sentence(8),
            'exception_class' => $exception,
            'file' => 'app/Livewire/Admin/Dashboard.php',
            'line' => fake()->numberBetween(10, 400),
            'http_status' => 500,
            'url' => 'https://berbaris.app/admin/dashboard',
            'method' => 'GET',
            'user_agent' => 'Mozilla/5.0 (Test)',
            'ip' => fake()->ipv4(),
            'trace' => "#0 {main} thrown in app/Livewire/Admin/Dashboard.php",
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attrs) => [
            'resolved_at' => now(),
            'resolved_by' => \App\Models\User::factory()->admin(),
        ]);
    }
}
