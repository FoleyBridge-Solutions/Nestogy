<?php

namespace Database\Factories\Domains\Contract\Models;

use App\Domains\Contract\Models\ContractTemplate;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractTemplateFactory extends Factory
{
    protected $model = ContractTemplate::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->words(3, true),
            'slug' => $this->faker->slug,
            'description' => $this->faker->optional()->sentence,
            'template_type' => $this->faker->randomElement(['standard', 'custom', 'compliance']),
            'category' => $this->faker->optional()->word,
            'tags' => $this->faker->optional()->words(3),
            'status' => $this->faker->randomElement(['draft', 'active', 'archived']),
            'version' => '1.0',
            'parent_template_id' => null,
            'is_default' => $this->faker->boolean(10),
            'variable_fields' => null,
            'default_values' => null,
            'required_fields' => ['client_id', 'start_date', 'end_date'],
            'voip_service_types' => null,
            'default_sla_terms' => null,
            'default_pricing_structure' => null,
            'compliance_templates' => null,
            'jurisdictions' => null,
            'regulatory_requirements' => null,
            'legal_disclaimers' => null,
            'customization_options' => null,
            'conditional_clauses' => null,
            'pricing_models' => null,
            'billing_model' => $this->faker->randomElement(['fixed', 'per_asset', 'per_contact', 'tiered', 'hybrid']),
            'asset_billing_rules' => null,
            'supported_asset_types' => null,
            'asset_service_matrix' => null,
            'default_per_asset_rate' => $this->faker->optional()->randomFloat(2, 10, 500),
            'contact_billing_rules' => null,
            'contact_access_tiers' => null,
            'default_per_contact_rate' => $this->faker->optional()->randomFloat(2, 5, 100),
            'calculation_formulas' => null,
            'auto_assignment_rules' => null,
            'billing_triggers' => null,
            'workflow_automation' => null,
            'notification_triggers' => null,
            'integration_hooks' => null,
            'usage_count' => 0,
            'last_used_at' => null,
            'success_rate' => null,
            'requires_approval' => $this->faker->boolean(20),
            'approval_workflow' => null,
            'last_reviewed_at' => null,
            'next_review_date' => null,
            'metadata' => null,
            'rendering_options' => null,
            'signature_settings' => null,
            'created_by' => null,
            'updated_by' => null,
            'approved_by' => null,
        ];
    }
}
