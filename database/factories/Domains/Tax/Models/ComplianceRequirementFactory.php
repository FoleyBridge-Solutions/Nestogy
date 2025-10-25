<?php

namespace Database\Factories\Domains\Tax\Models;

use App\Domains\Tax\Models\ComplianceRequirement;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComplianceRequirementFactory extends Factory
{
    protected $model = ComplianceRequirement::class;

    public function definition(): array
    {
        return ['company_id' => \App\Domains\Company\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
        ];
    }
}
