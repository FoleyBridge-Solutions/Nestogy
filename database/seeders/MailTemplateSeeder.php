<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\MailTemplate;
use Illuminate\Database\Seeder;

class MailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating MailTemplate records...');
        $companies = Company::where('id', '>', 1)->get();
        
        $templates = [
            'Invoice Sent', 'Payment Received', 'Ticket Created', 'Ticket Resolved',
            'Welcome Email', 'Password Reset', 'Quote Sent', 'Contract Renewal'
        ];
        
        foreach ($companies as $company) {
            foreach ($templates as $templateName) {
                // Make name unique by appending company ID
                $uniqueName = $templateName . ' - Company ' . $company->id;
                
                // Skip if already exists
                if (MailTemplate::where('name', $uniqueName)->exists()) {
                    continue;
                }
                
                MailTemplate::factory()->create([
                    'company_id' => $company->id,
                    'name' => $uniqueName,
                    'display_name' => $templateName,
                ]);
            }
        }
        
        $this->command->info("✓ Created ".MailTemplate::count()." mail templates");
    }
}
