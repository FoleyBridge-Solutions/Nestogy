<?php

namespace Database\Seeders\Dev;

use App\Models\Document;
use App\Models\Company;
use App\Domains\Client\Models\Client;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating Document records...');
$companies = Company::where('id', '>', 1)->get();
        $count = 0;
        
        foreach ($companies as $company) {
            $clients = Client::where('company_id', $company->id)->get();
            
            foreach ($clients as $client) {
                $docCount = rand(5, 15);
                for ($i = 0; $i < $docCount; $i++) {
                    Document::factory()->create([
                        'company_id' => $company->id,
                        'documentable_type' => Client::class,
                        'documentable_id' => $client->id,
                        'created_at' => fake()->dateTimeBetween('-2 years', 'now'),
                    ]);
                    $count++;
                }
            }
        }
        
        $this->command->info("✓ Created {$count} documents");
    }
}
