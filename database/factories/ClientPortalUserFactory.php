<?php

namespace Database\Factories;

use App\Models\ClientPortalUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientPortalUserFactory extends Factory
{
    protected $model = ClientPortalUser::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
        ];
    }
}
