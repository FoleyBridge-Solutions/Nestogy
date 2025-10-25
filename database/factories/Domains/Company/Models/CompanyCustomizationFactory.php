<?php

namespace Database\Factories\Domains\Company\Models;

use App\Domains\Company\Models\CompanyCustomization;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyCustomizationFactory extends Factory
{
    protected $model = CompanyCustomization::class;

    public function definition(): array
    {
        return ['company_id' => \App\Domains\Company\Models\Company::factory(),
            'customizations' => json_encode([])
        ];
    }
}
