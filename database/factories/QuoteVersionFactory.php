<?php

namespace Database\Factories;

use App\Models\QuoteVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteVersionFactory extends Factory
{
    protected $model = QuoteVersion::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'version_number' => $this->faker->optional()->word,
            'quote_data' => $this->faker->optional()->word,
            'changes' => $this->faker->optional()->word,
            'change_reason' => $this->faker->optional()->word,
            'created_by' => $this->faker->optional()->word
        ];
    }
}
