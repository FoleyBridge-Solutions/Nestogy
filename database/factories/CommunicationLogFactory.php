<?php

namespace Database\Factories;

use App\Models\CommunicationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommunicationLogFactory extends Factory
{
    protected $model = CommunicationLog::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
        ];
    }
}
