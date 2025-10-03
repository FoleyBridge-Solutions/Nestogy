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
            'description' => $this->faker->sentence,
            'contact_name' => $this->faker->words(3, true),
            'phone' => null,
            'extension' => null,
            'email' => $this->faker->safeEmail,
            'website' => null,
            'hours' => null,
            'sla' => null,
            'code' => null,
            'account_number' => null,
            'notes' => null,
            'template' => null,
            'accessed_at' => null
        ];
    }
}
