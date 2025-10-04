<?php

namespace Database\Factories;

use App\Models\Network;
use Illuminate\Database\Eloquent\Factories\Factory;

class NetworkFactory extends Factory
{
    protected $model = Network::class;

    public function definition(): array
    {
        $baseIp = $this->faker->numberBetween(1, 254) . '.' . $this->faker->numberBetween(0, 255) . '.' . $this->faker->numberBetween(0, 255);
        $network = $baseIp . '.0/24';
        $gateway = $baseIp . '.1';
        
        return ['company_id' => \App\Models\Company::factory(),
            'client_id' => \App\Models\Client::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence,
            'vlan' => $this->faker->optional()->numberBetween(1, 4094),
            'network' => $network,
            'gateway' => $gateway,
            'dhcp_range' => json_encode([$baseIp . '.10', $baseIp . '.250']),
            'notes' => $this->faker->optional()->sentence,
            'accessed_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now')
        ];
    }
}
