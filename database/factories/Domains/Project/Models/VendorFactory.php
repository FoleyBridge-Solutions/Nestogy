<?php

namespace Database\Factories\Domains\Project\Models;

use App\Domains\Project\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        return ['company_id' => \App\Domains\Company\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence,
            'contact_name' => $this->faker->words(3, true),
            'phone' => $this->faker->optional()->phoneNumber,
            'extension' => $this->faker->optional()->randomNumber(),
            'email' => $this->faker->safeEmail,
            'website' => $this->faker->optional()->randomNumber(),
            'hours' => $this->faker->optional()->randomNumber(),
            'sla' => $this->faker->optional()->randomNumber(),
            'code' => $this->faker->word,
            'account_number' => $this->faker->optional()->numberBetween(1, 100),
            'notes' => $this->faker->optional()->sentence,
            'template' => $this->faker->boolean(),
            'accessed_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now')
        ];
    }
}
