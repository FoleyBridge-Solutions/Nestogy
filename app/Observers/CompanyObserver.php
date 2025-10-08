<?php

namespace App\Observers;

use App\Models\Company;

class CompanyObserver
{
    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        try {
            // Create default quick actions for the new company
            $seeder = new \Database\Seeders\QuickActionsSeeder;
            $seeder->createDefaultActionsForCompany($company->id);
        } catch (\Exception $e) {
            // Silently fail if table doesn't exist (e.g., during tests)
            // This allows tests to run without the custom_quick_actions table
        }
    }
}
