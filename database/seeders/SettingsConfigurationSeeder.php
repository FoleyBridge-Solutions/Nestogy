<?php

namespace Database\Seeders;

use App\Domains\Core\Models\SettingsConfiguration;
use Illuminate\Database\Seeder;

class SettingsConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Settings Configuration Seeder...');

        $companies = \App\Domains\Company\Models\Company::all();
        
        $domains = ['general', 'billing', 'security', 'notifications', 'integrations'];
        $categories = ['system', 'user', 'company', 'feature'];
        
        foreach ($companies as $company) {
            // Create a few random domain/category combinations per company
            $combinationsToCreate = [];
            $maxConfigs = rand(3, 8);
            
            for ($i = 0; $i < $maxConfigs; $i++) {
                $domain = fake()->randomElement($domains);
                $category = fake()->randomElement($categories);
                $key = "{$domain}:{$category}";
                
                // Only add if we haven't already selected this combination
                if (!in_array($key, $combinationsToCreate)) {
                    $combinationsToCreate[] = $key;
                    
                    SettingsConfiguration::updateOrCreate(
                        [
                            'company_id' => $company->id,
                            'domain' => $domain,
                            'category' => $category,
                        ],
                        [
                            'settings' => json_encode([
                                'enabled' => fake()->boolean(80),
                                'value' => fake()->word(),
                            ]),
                            'metadata' => json_encode([
                                'created_by' => 'system',
                                'version' => '1.0',
                            ]),
                            'is_active' => fake()->boolean(90),
                            'last_modified_at' => fake()->optional()->dateTimeBetween('-1 year', 'now'),
                        ]
                    );
                }
            }
        }

        $this->command->info('Settings Configuration Seeder completed!');
    }
}
