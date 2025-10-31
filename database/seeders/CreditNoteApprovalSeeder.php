<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\Financial\Models\CreditNote;
use App\Domains\Financial\Models\CreditNoteApproval;
use Illuminate\Database\Seeder;

class CreditNoteApprovalSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Credit Note Approval Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating credit note approvals for company: {$company->name}");

            $creditNotes = CreditNote::where('company_id', $company->id)->get();
            $users = User::where('company_id', $company->id)->pluck('id')->toArray();

            if ($creditNotes->isEmpty() || empty($users)) {
                $this->command->warn("No credit notes or users found for company: {$company->name}. Skipping.");
                continue;
            }

            // Create approvals for 70-80% of credit notes
            $noteCount = (int) ($creditNotes->count() * rand(70, 80) / 100);
            $selectedNotes = $creditNotes->random(min($noteCount, $creditNotes->count()));

            foreach ($selectedNotes as $creditNote) {
                CreditNoteApproval::factory()
                    ->for($company)
                    ->for($creditNote)
                    ->create([
                        'approved_by' => fake()->randomElement($users),
                    ]);
            }

            $this->command->info("Completed credit note approvals for company: {$company->name}");
        }

        $this->command->info('Credit Note Approval Seeder completed!');
    }
}
