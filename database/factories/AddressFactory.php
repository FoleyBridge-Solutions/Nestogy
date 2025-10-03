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
            'type' => null,
            'address' => null,
            'address2' => null,
            'city' => null,
            'state' => null,
            'zip' => null,
            'country' => null,
            'is_primary' => true
        ];
    }
}
