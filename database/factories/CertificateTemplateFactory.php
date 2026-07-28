<?php

namespace Database\Factories;

use App\Models\CertificateTemplate;
use App\Models\Eventner;
use Illuminate\Database\Eloquent\Factories\Factory;

class CertificateTemplateFactory extends Factory
{
    protected $model = CertificateTemplate::class;

    public function definition(): array
    {
        return [
            'eventner_id' => Eventner::factory(),
            'name' => fake()->words(3, true),
            'file_path' => 'certificate-templates/default.png',
            'width' => 297,
            'height' => 210,
            'is_active' => true,
        ];
    }
}
