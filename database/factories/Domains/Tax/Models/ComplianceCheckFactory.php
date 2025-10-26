<?php

namespace Database\Factories\Domains\Tax\Models;

use App\Domains\Tax\Models\ComplianceCheck;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComplianceCheckFactory extends Factory
{
    protected $model = ComplianceCheck::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'compliance_requirement_id' => \App\Domains\Tax\Models\ComplianceRequirement::factory(),
            'check_type' => 'manual',
            'status' => 'compliant',
            'checked_at' => now(),
            'compliance_score' => 95.0,
            'risk_level' => 'low',
        ];
    }
}
