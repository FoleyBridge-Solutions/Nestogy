<?php

namespace Database\Factories\Domains\Client\Models;

use App\Domains\Client\Models\ClientPortalUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientPortalUserFactory extends Factory
{
    protected $model = ClientPortalUser::class;

    public function definition(): array
    {
        return ['company_id' => \App\Domains\Company\Models\Company::factory(),
            'name' => $this->faker->name(),
        ];
    }
}
