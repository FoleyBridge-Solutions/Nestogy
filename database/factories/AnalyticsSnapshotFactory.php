<?php

namespace Database\Factories;

use App\Models\AnalyticsSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnalyticsSnapshotFactory extends Factory
{
    protected $model = AnalyticsSnapshot::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
        ];
    }
}
