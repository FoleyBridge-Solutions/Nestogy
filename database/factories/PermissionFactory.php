<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'name' => $this->faker->words(3, true),
            'title' => null,
            'entity_type' => null,
            'only_owned' => null,
            'options' => null
        ];
    }
}
