#!/bin/bash

# ClientPortalSession
echo "Fixing ClientPortalSessionFactory..."
cat > database/factories/Domains/Portal/Models/ClientPortalSessionFactory.php << 'FACTORY'
<?php

namespace Database\Factories\Domains\Portal\Models;

use App\Domains\Portal\Models\ClientPortalSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ClientPortalSessionFactory extends Factory
{
    protected $model = ClientPortalSession::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'client_portal_user_id' => \App\Domains\Portal\Models\ClientPortalUser::factory(),
            'token' => Str::random(60),
            'ip_address' => $this->faker->ipv4,
            'user_agent' => $this->faker->userAgent,
            'last_activity' => now(),
        ];
    }
}
FACTORY

# ClientPortalUser
echo "Fixing ClientPortalUserFactory..."
cat > database/factories/Domains/Portal/Models/ClientPortalUserFactory.php << 'FACTORY'
<?php

namespace Database\Factories\Domains\Portal\Models;

use App\Domains\Portal\Models\ClientPortalUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class ClientPortalUserFactory extends Factory
{
    protected $model = ClientPortalUser::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'client_id' => \App\Domains\Client\Models\Client::factory(),
            'email' => $this->faker->unique()->safeEmail,
            'password' => Hash::make('password'),
            'is_active' => true,
        ];
    }
}
FACTORY

# CommunicationLog
echo "Fixing CommunicationLogFactory..."
cat > database/factories/Domains/Client/Models/CommunicationLogFactory.php << 'FACTORY'
<?php

namespace Database\Factories\Domains\Client\Models;

use App\Domains\Client\Models\CommunicationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommunicationLogFactory extends Factory
{
    protected $model = CommunicationLog::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Domains\Company\Models\Company::factory(),
            'client_id' => \App\Domains\Client\Models\Client::factory(),
            'user_id' => \App\Domains\Core\Models\User::factory(),
            'type' => $this->faker->randomElement(['email', 'phone', 'meeting']),
            'direction' => $this->faker->randomElement(['inbound', 'outbound']),
            'subject' => $this->faker->sentence,
            'body' => $this->faker->paragraph,
            'communication_date' => now(),
        ];
    }
}
FACTORY

echo "Done!"
