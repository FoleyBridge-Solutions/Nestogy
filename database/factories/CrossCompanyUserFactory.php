<?php

namespace Database\Factories;

use App\Models\CrossCompanyUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class CrossCompanyUserFactory extends Factory
{
    protected $model = CrossCompanyUser::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'primary_company_id' => \App\Models\Company::factory(),
            'company_id' => \App\Models\Company::factory(),
            'role_in_company' => $this->faker->numberBetween(1, 10),
            'access_type' => $this->faker->randomElement(['full', 'limited', 'view_only']),
            'access_permissions' => json_encode([]),
            'access_restrictions' => json_encode([]),
            'authorized_by' => null,
            'delegated_from' => null,
            'authorization_reason' => $this->faker->optional()->sentence,
            'is_active' => $this->faker->boolean(70),
            'access_granted_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'access_expires_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'last_accessed_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'require_re_auth' => $this->faker->boolean(),
            'max_concurrent_sessions' => $this->faker->numberBetween(1, 10),
            'allowed_features' => json_encode([]),
            'audit_actions' => $this->faker->boolean(),
            'compliance_settings' => json_encode([]),
            'notes' => $this->faker->optional()->sentence,
            'created_by' => \App\Models\User::factory(),
            'updated_by' => null
        ];
    }
}
