<?php

namespace Database\Factories\Domains\Core\Models;

use App\Domains\Core\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'title' => $this->faker->words(3, true),
            'entity_type' => $this->faker->numberBetween(1, 5),
            'only_owned' => $this->faker->boolean(),
            'options' => json_encode([])
        ];
    }
}
