<?php

namespace Database\Factories;

use App\Models\SubsidiaryPermission;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubsidiaryPermissionFactory extends Factory
{
    protected $model = SubsidiaryPermission::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'granter_company_id' => \App\Models\Company::factory(),
            'grantee_company_id' => \App\Models\Company::factory(),
            'resource_type' => $this->faker->randomElement(['ticket', 'invoice', 'client', 'asset']),
            'permission_type' => $this->faker->randomElement(['view', 'edit', 'create', 'delete']),
            'name' => $this->faker->words(3, true),
        ];
    }
}
