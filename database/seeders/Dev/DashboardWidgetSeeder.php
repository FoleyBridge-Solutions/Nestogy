<?php

namespace Database\Seeders\Dev;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\DashboardWidget;
use App\Domains\Core\Models\User;
use Illuminate\Database\Seeder;

class DashboardWidgetSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating DashboardWidget records...');
$companies = Company::where('id', '>', 1)->get();
        $count = 0;
        
        foreach ($companies as $company) {
            $users = User::where('company_id', $company->id)->get();
            
            foreach ($users as $user) {
                $widgetCount = rand(3, 8);
                for ($i = 0; $i < $widgetCount; $i++) {
                    DashboardWidget::factory()->create([
                        'company_id' => $company->id,
                        'user_id' => $user->id,
                    ]);
                    $count++;
                }
            }
        }
        
        $this->command->info("✓ Created {$count} dashboard widgets");
    }
}
