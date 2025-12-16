<?php

namespace Database\Factories\Domains\Client\Models;

use App\Domains\Client\Models\Client;
use App\Domains\Client\Models\ClientRack;
use App\Domains\Company\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientRackFactory extends Factory
{
    protected $model = ClientRack::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'client_id' => Client::factory(),
            'name' => 'Rack ' . $this->faker->randomNumber(2),
            'description' => $this->faker->optional()->sentence(),
            'location' => $this->faker->city(),
            'rack_number' => $this->faker->unique()->numberBetween(1, 999),
            'height_units' => $this->faker->randomElement([12, 18, 24, 42, 45, 48]),
            'width_inches' => 19.00,
            'depth_inches' => $this->faker->randomElement([24.00, 30.00, 36.00, 42.00]),
            'max_weight_lbs' => $this->faker->randomElement([2000, 3000, 4000]),
            'power_capacity_watts' => $this->faker->randomElement([2000, 3000, 5000, 10000]),
            'power_used_watts' => $this->faker->numberBetween(500, 3000),
            'cooling_requirements' => $this->faker->randomElement(['standard', 'precision', 'liquid']),
            'network_connections' => $this->faker->numberBetween(1, 4),
            'status' => 'active',
            'temperature_celsius' => $this->faker->randomFloat(1, 18, 24),
            'humidity_percent' => $this->faker->randomFloat(1, 40, 60),
            'manufacturer' => $this->faker->randomElement(['Dell', 'HP', 'APC', 'Tripp Lite']),
            'model' => $this->faker->bothify('??-####'),
            'serial_number' => $this->faker->bothify('SN-########'),
            'purchase_date' => $this->faker->dateTimeBetween('-5 years', '-1 year'),
            'warranty_expiry' => $this->faker->dateTimeBetween('now', '+3 years'),
            'maintenance_schedule' => null,
            'notes' => null,
            'custom_fields' => null,
            'accessed_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
