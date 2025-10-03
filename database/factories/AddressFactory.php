<?php

namespace Database\Factories;

use App\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'type' => $this->faker->numberBetween(1, 5),
            'address' => $this->faker->optional()->word,
            'address2' => $this->faker->optional()->word,
            'city' => $this->faker->optional()->word,
            'state' => $this->faker->optional()->word,
            'zip' => $this->faker->optional()->word,
            'country' => $this->faker->optional()->word,
            'is_primary' => $this->faker->boolean(70)
        ];
    }
}
