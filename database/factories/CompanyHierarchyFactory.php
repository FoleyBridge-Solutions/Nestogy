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
            'company_id' => 1,
        ];
    }
}
