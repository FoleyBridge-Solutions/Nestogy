<?php

namespace Database\Factories;

use App\Models\QuoteVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteVersionFactory extends Factory
{
    protected $model = QuoteVersion::class;

    public function definition(): array
    {
        return ['company_id' => \App\Models\Company::factory(),
            'quote_id' => \App\Models\Quote::factory(),
            'version_number' => $this->faker->numberBetween(1, 100),
            'quote_data' => json_encode(['items' => [], 'total' => 0]),
            'changes' => $this->faker->optional()->randomNumber(),
            'change_reason' => $this->faker->optional()->randomNumber(),
            'created_by' => \App\Models\User::factory()
        ];
    }
}
