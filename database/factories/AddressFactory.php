<?php

namespace Database\Factories;

use App\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return ['company_id' => \App\Models\Company::factory(),
            'client_id' => \App\Models\Client::factory(),
            'type' => $this->faker->randomElement(['billing', 'shipping', 'service', 'other']),
            'address' => $this->faker->streetAddress(),
            'address2' => $this->faker->optional()->secondaryAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'zip' => $this->faker->postcode(),
            'country' => 'US',
            'is_primary' => $this->faker->boolean(70)
        ];
    }
}
