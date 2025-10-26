<?php

namespace Database\Factories\Domains\Client\Models;

use App\Domains\Client\Models\ClientPortalUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientPortalUserFactory extends Factory
{
    protected $model = ClientPortalUser::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'client_id' => \App\Domains\Client\Models\Client::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail,
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'viewer',
            'session_timeout_minutes' => 30,
        ];
    }
}
