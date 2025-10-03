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
            'description' => $this->faker->sentence,
            'vlan' => null,
            'network' => null,
            'gateway' => null,
            'dhcp_range' => null,
            'notes' => null,
            'accessed_at' => null
        ];
    }
}
