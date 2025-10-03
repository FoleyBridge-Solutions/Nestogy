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
            'company_id' => 1,
        ];
    }
}
