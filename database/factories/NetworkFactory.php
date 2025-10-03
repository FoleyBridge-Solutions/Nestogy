<?php

namespace Database\Factories;

use App\Models\Network;
use Illuminate\Database\Eloquent\Factories\Factory;

class NetworkFactory extends Factory
{
    protected $model = Network::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence,
            'vlan' => $this->faker->optional()->word,
            'network' => $this->faker->optional()->word,
            'gateway' => $this->faker->optional()->word,
            'dhcp_range' => $this->faker->optional()->word,
            'notes' => $this->faker->optional()->sentence,
            'accessed_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now')
        ];
    }
}
