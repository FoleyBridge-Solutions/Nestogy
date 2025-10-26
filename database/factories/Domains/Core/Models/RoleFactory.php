<?php

namespace Database\Factories\Domains\Core\Models;

use App\Domains\Core\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'title' => $this->faker->words(3, true),
            'scope' => $this->faker->optional()->randomNumber()
        ];
    }
}
