<?php

namespace Database\Factories;

use App\Models\ComplianceRequirement;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComplianceRequirementFactory extends Factory
{
    protected $model = ComplianceRequirement::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
        ];
    }
}
