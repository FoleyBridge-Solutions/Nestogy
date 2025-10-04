<?php

namespace Database\Factories;

use App\Models\CompanyCustomization;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyCustomizationFactory extends Factory
{
    protected $model = CompanyCustomization::class;

    public function definition(): array
    {
        return ['company_id' => \App\Models\Company::factory(),
            'customizations' => json_encode([])
        ];
    }
}
