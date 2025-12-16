<?php

namespace Database\Factories\Domains\Contract\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractScheduleFactory extends Factory
{
    protected $model = ContractSchedule::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'contract_id' => Contract::factory(),
            'schedule_type' => 'A',
            'schedule_letter' => 'A',
            'title' => 'Schedule A - Infrastructure & SLA',
            'description' => $this->faker->optional()->sentence(),
            'content' => $this->faker->paragraphs(5, true),
            'variables' => null,
            'variable_values' => null,
            'required_fields' => null,
            'supported_asset_types' => ['server', 'workstation', 'network'],
            'service_levels' => null,
            'coverage_rules' => null,
            'sla_terms' => null,
            'response_times' => ['urgent' => '1 hour', 'high' => '4 hours', 'normal' => '24 hours'],
            'coverage_hours' => ['start' => '08:00', 'end' => '17:00', 'timezone' => 'America/New_York'],
            'escalation_procedures' => null,
            'pricing_structure' => null,
            'billing_rules' => null,
            'rate_tables' => null,
            'discount_structures' => null,
            'penalty_structures' => null,
            'asset_inclusion_rules' => null,
            'asset_exclusion_rules' => null,
            'location_coverage' => null,
            'client_tier_requirements' => null,
            'auto_assign_assets' => false,
            'require_manual_approval' => false,
            'automation_rules' => null,
            'assignment_triggers' => null,
            'status' => 'draft',
            'approval_status' => 'pending',
            'approval_notes' => null,
            'approved_at' => null,
            'approved_by' => null,
            'version' => '1.0',
            'parent_schedule_id' => null,
            'template_id' => null,
            'is_template' => false,
            'asset_count' => 0,
            'usage_count' => 0,
            'last_used_at' => null,
            'effectiveness_score' => null,
            'effective_date' => null,
            'expiration_date' => null,
            'last_reviewed_at' => null,
            'next_review_date' => null,
            'created_by' => null,
            'updated_by' => null,
            'metadata' => null,
            'notes' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'approval_status' => 'approved',
            'approved_at' => now(),
            'effective_date' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function pricing(): static
    {
        return $this->state(fn (array $attributes) => [
            'schedule_type' => 'B',
            'schedule_letter' => 'B',
            'title' => 'Schedule B - Pricing & Fees',
        ]);
    }
}
