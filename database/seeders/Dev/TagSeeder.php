<?php

namespace Database\Seeders\Dev;

use App\Domains\Core\Models\Tag;
use App\Domains\Company\Models\Company;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating Tag records...');
$companies = Company::where('id', '>', 1)->get();
        
        $tags = [
            'VIP', 'Priority', 'High Value', 'New Client', 'At Risk',
            'Renewal Due', 'Past Due', 'Contract', 'Project', 'Support'
        ];
        
        foreach ($companies as $company) {
            foreach ($tags as $tagName) {
                Tag::factory()->create([
                    'company_id' => $company->id,
                    'name' => $tagName,
                ]);
            }
        }
        
        $this->command->info("✓ Created ".Tag::count()." tags");
    }
}
