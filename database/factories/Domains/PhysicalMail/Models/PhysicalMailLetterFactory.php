<?php

namespace Database\Factories\Domains\PhysicalMail\Models;

use App\Domains\PhysicalMail\Models\PhysicalMailLetter;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhysicalMailLetterFactory extends Factory
{
    protected $model = PhysicalMailLetter::class;

    public function definition(): array
    {
        return [
            'to_contact_id' => null,
            'from_contact_id' => null,
            'template_id' => null,
            'content' => $this->faker->paragraphs(3, true),
            'color' => false,
            'double_sided' => true,
            'address_placement' => 'top_first_page',
            'size' => 'us_letter',
            'perforated_page' => null,
            'return_envelope_id' => null,
            'extra_service' => null,
            'merge_variables' => null,
            'idempotency_key' => $this->faker->uuid(),
        ];
    }

    public function color(): static
    {
        return $this->state(fn (array $attributes) => [
            'color' => true,
        ]);
    }

    public function singleSided(): static
    {
        return $this->state(fn (array $attributes) => [
            'double_sided' => false,
        ]);
    }
}
