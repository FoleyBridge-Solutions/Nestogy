<?php

namespace Database\Seeders\Dev;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating audit logs (2 years of history)...');

        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $users = User::where('company_id', $company->id)->get();
            
            if ($users->isEmpty()) {
                continue;
            }

            $logCount = rand(500, 2000);

            for ($i = 0; $i < $logCount; $i++) {
                AuditLog::factory()->create([
                    'company_id' => $company->id,
                    'user_id' => $users->random()->id,
                    'created_at' => fake()->dateTimeBetween('-2 years', 'now'),
                ]);
            }
        }

        $this->command->info('✓ Created '.AuditLog::count().' audit logs');
    }
}
