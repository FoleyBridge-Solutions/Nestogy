<?php

namespace Database\Seeders\Dev;

use App\Domains\Core\Models\InAppNotification;
use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use Illuminate\Database\Seeder;

class InAppNotificationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating InAppNotification records...');
$companies = Company::where('id', '>', 1)->get();
        $count = 0;
        
        foreach ($companies as $company) {
            $users = User::where('company_id', $company->id)->get();
            
            foreach ($users as $user) {
                $notifCount = rand(20, 100);
                for ($i = 0; $i < $notifCount; $i++) {
                    InAppNotification::factory()->create([
                        'company_id' => $company->id,
                        'user_id' => $user->id,
                        'created_at' => fake()->dateTimeBetween('-3 months', 'now'),
                    ]);
                    $count++;
                }
            }
        }
        
        $this->command->info("✓ Created {$count} in-app notifications");
    }
}
