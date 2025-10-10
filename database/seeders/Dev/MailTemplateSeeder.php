<?php

namespace Database\Seeders\Dev;

use App\Models\Company;
use App\Models\MailTemplate;
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
                MailTemplate::factory()->create([
                    'company_id' => $company->id,
                    'name' => $templateName,
                ]);
            }
        }
        
        $this->command->info("✓ Created ".MailTemplate::count()." mail templates");
    }
}
