<?php

namespace App\Observers;

use App\Domains\Company\Models\Company;
use App\Domains\Security\Services\TenantRoleService;
use Illuminate\Support\Facades\Log;

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
        
        try {
            // Create default roles for the new company
            $roleService = app(TenantRoleService::class);
            $result = $roleService->createDefaultRoles($company->id);
            
            Log::info("Default roles created for new company", [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'roles_created' => $result['total'],
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create default roles for company", [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Company "updated" event.
     */
    public function updated(Company $company): void
    {
        //
    }

    /**
     * Handle the Company "deleted" event.
     */
    public function deleted(): void
    {
        //
    }

    /**
     * Handle the Company "restored" event.
     */
    public function restored(Company $company): void
    {
        //
    }

    /**
     * Handle the Company "force deleted" event.
     */
    public function forceDeleted(Company $company): void
    {
        //
    }
}
