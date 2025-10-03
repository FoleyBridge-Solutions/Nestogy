<?php

namespace Database\Factories;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence,
            'contact_name' => $this->faker->words(3, true),
            'phone' => $this->faker->optional()->phoneNumber,
            'extension' => $this->faker->optional()->word,
            'email' => $this->faker->safeEmail,
            'website' => $this->faker->optional()->word,
            'hours' => $this->faker->optional()->word,
            'sla' => $this->faker->optional()->word,
            'code' => $this->faker->word,
            'account_number' => $this->faker->optional()->word,
            'notes' => $this->faker->optional()->sentence,
            'template' => $this->faker->optional()->word,
            'accessed_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now')
        ];
    }
}
