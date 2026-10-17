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

    /**
     * Alur siap pakai: lurus dari kiri-bawah, belok, lalu berhenti.
     *
     * Empat titik, bukan dua, supaya tes sudut benar-benar menguji perhitungan
     * dan bukan cuma satu garis mendatar.
     */
    public function beralur(): static
    {
        return $this->state(fn () => [
            'route_points' => [
                ['x' => 10, 'y' => 80, 'kind' => 'start', 'label' => 'Mulai'],
                ['x' => 40, 'y' => 80, 'kind' => 'turn', 'label' => ''],
                ['x' => 40, 'y' => 40, 'kind' => 'aba_aba', 'label' => 'Hadap kanan', 'heading' => 90],
                ['x' => 80, 'y' => 40, 'kind' => 'end', 'label' => 'Selesai'],
            ],
            'route_visible' => true,
        ]);
    }

    /** Ada alurnya, tapi disembunyikan dari halaman publik. */
    public function alurTersembunyi(): static
    {
        return $this->beralur()->state(fn () => ['route_visible' => false]);
    }
}
