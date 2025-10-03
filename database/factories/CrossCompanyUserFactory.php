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
            'company_id' => 1,
            'role_in_company' => $this->faker->optional()->word,
            'access_type' => $this->faker->numberBetween(1, 5),
            'access_permissions' => $this->faker->optional()->word,
            'access_restrictions' => $this->faker->optional()->word,
            'authorized_by' => $this->faker->optional()->word,
            'delegated_from' => $this->faker->optional()->word,
            'authorization_reason' => $this->faker->optional()->word,
            'is_active' => $this->faker->boolean(70),
            'access_granted_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'access_expires_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'last_accessed_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'require_re_auth' => $this->faker->optional()->word,
            'max_concurrent_sessions' => $this->faker->optional()->word,
            'allowed_features' => $this->faker->optional()->word,
            'audit_actions' => $this->faker->optional()->word,
            'compliance_settings' => $this->faker->optional()->word,
            'notes' => $this->faker->optional()->sentence,
            'created_by' => $this->faker->optional()->word,
            'updated_by' => $this->faker->optional()->dateTimeBetween('-1 year', 'now')
        ];
    }
}
