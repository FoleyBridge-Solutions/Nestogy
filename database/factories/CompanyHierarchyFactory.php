<?php

namespace Database\Factories;

use App\Models\CompanyHierarchy;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyHierarchyFactory extends Factory
{
    protected $model = CompanyHierarchy::class;

    public function definition(): array
    {
        return [
            'ancestor_id' => \App\Models\Company::factory(),
            'descendant_id' => \App\Models\Company::factory(),
            'depth' => $this->faker->numberBetween(0, 5),
            'path' => $this->faker->optional()->numerify('1.2.3'),
            'path_names' => $this->faker->optional()->words(3, true),
            'relationship_type' => $this->faker->randomElement(['parent_child', 'division', 'branch', 'subsidiary']),
            'relationship_metadata' => json_encode([]),
        ];
    }
}
