<?php

namespace Database\Factories;

use App\Models\ComplianceCheck;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComplianceCheckFactory extends Factory
{
    protected $model = ComplianceCheck::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
        ];
    }
}
