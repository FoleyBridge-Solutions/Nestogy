<?php

namespace Database\Factories;

use App\Models\PermissionGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionGroupFactory extends Factory
{
    protected $model = PermissionGroup::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
        ];
    }
}
