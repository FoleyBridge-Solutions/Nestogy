<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'name' => $this->faker->words(3, true),
            'title' => null,
            'scope' => null
        ];
    }
}
