<?php

namespace Database\Factories;

use App\Models\Eventner;
use App\Models\RegistrationField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistrationField>
 */
class RegistrationFieldFactory extends Factory
{
    protected $model = RegistrationField::class;

    public function definition(): array
    {
        $label = fake()->unique()->words(2, true);

        return [
            'eventner_id' => Eventner::factory(),
            'field_key' => \Illuminate\Support\Str::slug($label, '_'),
            'label' => ucwords($label),
            'type' => 'text',
            'options' => null,
            'default_value' => null,
            'is_required' => false,
            'is_active' => true,
            'is_builtin' => false,
            'builtin_source' => null,
            'max_kb' => null,
            'help_text' => null,
            'sort_order' => 0,
        ];
    }

    public function wajib(): static
    {
        return $this->state(fn () => ['is_required' => true]);
    }

    public function nonaktif(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function tipe(string $tipe): static
    {
        return $this->state(fn () => ['type' => $tipe]);
    }

    public function key(string $key, string $label): static
    {
        return $this->state(fn () => ['field_key' => $key, 'label' => $label]);
    }
}
