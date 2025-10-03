<?php

namespace Database\Factories;

use App\Models\ClientPortalSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientPortalSessionFactory extends Factory
{
    protected $model = ClientPortalSession::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
        ];
    }
}
