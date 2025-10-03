<?php

namespace Database\Factories;

use App\Models\UsagePool;
use Illuminate\Database\Eloquent\Factories\Factory;

class UsagePoolFactory extends Factory
{
    protected $model = UsagePool::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
        ];
    }
}
