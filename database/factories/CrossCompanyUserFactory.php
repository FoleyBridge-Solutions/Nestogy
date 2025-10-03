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
            'role_in_company' => null,
            'access_type' => null,
            'access_permissions' => null,
            'access_restrictions' => null,
            'authorized_by' => null,
            'delegated_from' => null,
            'authorization_reason' => null,
            'is_active' => true,
            'access_granted_at' => null,
            'access_expires_at' => null,
            'last_accessed_at' => null,
            'require_re_auth' => null,
            'max_concurrent_sessions' => null,
            'allowed_features' => null,
            'audit_actions' => null,
            'compliance_settings' => null,
            'notes' => null,
            'created_by' => null,
            'updated_by' => null
        ];
    }
}
