<?php

namespace Database\Factories;

use App\Domains\Tax\Models\ComplianceCheck;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComplianceCheckFactory extends Factory
{
    protected $model = ComplianceCheck::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'name' => $this->faker->words(3, true),
            'evidence_documents' => json_encode([]),
            'checked_by' => \App\Domains\Core\Models\User::factory(),
            'checked_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'next_check_date' => $this->faker->optional()->dateTimeBetween('now', '+180 days'),
            'compliance_score' => $this->faker->optional()->numberBetween(0, 100),
            'risk_level' => $this->faker->optional()->randomElement(['low', 'medium', 'high', 'critical']),
            'metadata' => json_encode([]),
        ];
    }
}
