<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\CompanyHierarchy;
use Illuminate\Database\Seeder;

class CompanyHierarchySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Company Hierarchy Seeder...');

        $companies = Company::where('id', '>', 1)->get();

        if ($companies->count() < 2) {
            $this->command->warn('Need at least 2 companies for hierarchies. Skipping.');
            return;
        }

        // Create parent-child relationships between companies
        foreach ($companies->take(5) as $index => $parentCompany) {
            // Each company can have 1-2 child companies
            $childCount = rand(1, 2);
            
            for ($i = 0; $i < $childCount; $i++) {
                $childCompany = $companies->where('id', '!=', $parentCompany->id)->random();
                
                CompanyHierarchy::factory()->create([
                    'ancestor_id' => $parentCompany->id,
                    'descendant_id' => $childCompany->id,
                    'depth' => 1,
                ]);
            }
        }

        $this->command->info('Company Hierarchy Seeder completed!');
    }
}
