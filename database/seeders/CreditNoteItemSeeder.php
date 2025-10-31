<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Financial\Models\CreditNote;
use App\Domains\Financial\Models\CreditNoteItem;
use Illuminate\Database\Seeder;

class CreditNoteItemSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Credit Note Item Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating credit note items for company: {$company->name}");

            $creditNotes = CreditNote::where('company_id', $company->id)->get();

            if ($creditNotes->isEmpty()) {
                $this->command->warn("No credit notes found for company: {$company->name}. Skipping.");
                continue;
            }

            // Create 1-5 items per credit note
            foreach ($creditNotes as $creditNote) {
                $itemCount = rand(1, 5);

                CreditNoteItem::factory()
                    ->count($itemCount)
                    ->for($company)
                    ->for($creditNote)
                    ->create();
            }

            $this->command->info("Completed credit note items for company: {$company->name}");
        }

        $this->command->info('Credit Note Item Seeder completed!');
    }
}
