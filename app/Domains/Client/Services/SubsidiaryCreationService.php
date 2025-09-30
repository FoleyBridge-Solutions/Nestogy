<?php

namespace App\Domains\Client\Services;

use App\Domains\Core\Services\BaseService;
use App\Models\Company;
use App\Models\CompanyHierarchy;
use App\Models\SubsidiaryPermission;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * SubsidiaryCreationService
 *
 * Handles the complex process of creating subsidiary companies including
 * hierarchy setup, user management, permission inheritance, and billing configuration.
 */
class SubsidiaryCreationService extends BaseService
{
    protected function initializeService(): void
    {
        $this->modelClass = Company::class;
        $this->defaultEagerLoad = ['parentCompany', 'users'];
        $this->searchableFields = ['name', 'email'];
    }

    /**
     * Create a new subsidiary company.
     */
    public function createSubsidiary(array $data, int $parentCompanyId): Company
    {
        return DB::transaction(function () use ($data, $parentCompanyId) {
            $parentCompany = Company::findOrFail($parentCompanyId);

            // Validate parent company can create subsidiaries
            $this->validateSubsidiaryCreation($parentCompany, $data);

            // Create the subsidiary company
            $subsidiary = $this->createSubsidiaryCompany($data, $parentCompany);

            // Add to hierarchy
            CompanyHierarchy::addToHierarchy($parentCompanyId, $subsidiary->id, 'subsidiary');

            // Create admin user if requested
            if (! empty($data['create_admin']) && $data['create_admin']) {
                $this->createSubsidiaryAdmin($subsidiary, $data);
            }

            // Handle permission inheritance
            if (! empty($data['inherit_permissions']) && $data['inherit_permissions']) {
                $this->inheritPermissions($subsidiary);
            }

            // Set up initial permissions
            if (! empty($data['initial_permissions'])) {
                $this->setupInitialPermissions($subsidiary, $data['initial_permissions']);
            }

            // Configure billing relationship
            $this->configureBilling($subsidiary, $parentCompany, $data);

            Log::info('Subsidiary company created successfully', [
                'subsidiary_id' => $subsidiary->id,
                'subsidiary_name' => $subsidiary->name,
                'parent_company_id' => $parentCompanyId,
                'created_by' => Auth::id(),
            ]);

            return $subsidiary->load(['parentCompany', 'users', 'childCompanies']);
        });
    }

    /**
     * Update an existing subsidiary.
     */
    public function updateSubsidiary(Company $subsidiary, array $data): Company
    {
        return DB::transaction(function () use ($subsidiary, $data) {
            // Handle hierarchy move if requested
            if (! empty($data['move_to_parent'])) {
                $this->moveSubsidiary($subsidiary, (int) $data['move_to_parent']);
                unset($data['move_to_parent']);
            }

            // Handle status changes
            if (isset($data['is_active']) && ! $data['is_active']) {
                $data['suspended_at'] = now();
                $data['suspension_reason'] = $data['suspension_reason'] ?? 'Administrative action';
            } elseif (isset($data['is_active']) && $data['is_active']) {
                $data['suspended_at'] = null;
                $data['suspension_reason'] = null;
            }

            // Update the company
            $subsidiary->update($data);

            Log::info('Subsidiary company updated successfully', [
                'subsidiary_id' => $subsidiary->id,
                'subsidiary_name' => $subsidiary->name,
                'updated_by' => Auth::id(),
            ]);

            return $subsidiary->fresh(['parentCompany', 'users', 'childCompanies']);
        });
    }

    /**
     * Remove a subsidiary from the hierarchy.
     */
    public function removeSubsidiary(Company $subsidiary): bool
    {
        return DB::transaction(function () use ($subsidiary) {
            // Check if subsidiary has children
            if ($subsidiary->childCompanies()->exists()) {
                throw new \InvalidArgumentException(
                    'Cannot remove subsidiary with active child companies.'
                );
            }

            // Check if subsidiary has active users
            if ($subsidiary->users()->where('status', true)->exists()) {
                throw new \InvalidArgumentException(
                    'Cannot remove subsidiary with active users.'
                );
            }

            // Revoke all permissions
            $subsidiary->grantedPermissions()->update(['is_active' => false]);
            $subsidiary->receivedPermissions()->update(['is_active' => false]);

            // Revoke cross-company access
            $subsidiary->crossCompanyUsers()->update(['is_active' => false]);

            // Remove from hierarchy
            CompanyHierarchy::removeFromHierarchy($subsidiary->id);

            // Optionally deactivate the company instead of deleting
            $subsidiary->update([
                'is_active' => false,
                'suspended_at' => now(),
                'suspension_reason' => 'Removed from organizational hierarchy',
                'parent_company_id' => null,
                'company_type' => 'root', // Convert back to root company
            ]);

            Log::info('Subsidiary removed from hierarchy', [
                'subsidiary_id' => $subsidiary->id,
                'subsidiary_name' => $subsidiary->name,
                'removed_by' => Auth::id(),
            ]);

            return true;
        });
    }

    /**
     * Move a subsidiary to a new parent.
     */
    public function moveSubsidiary(Company $subsidiary, int $newParentId): bool
    {
        return DB::transaction(function () use ($subsidiary, $newParentId) {
            $newParent = Company::findOrFail($newParentId);

            // Validate the move
            $this->validateSubsidiaryMove($subsidiary, $newParent);

            // Move in hierarchy
            $moved = CompanyHierarchy::moveCompany($subsidiary->id, $newParentId);

            if ($moved) {
                // Update company record
                $subsidiary->update([
                    'parent_company_id' => $newParentId,
                    'organizational_level' => $newParent->organizational_level + 1,
                ]);

                // Re-inherit permissions from new parent
                $this->inheritPermissions($subsidiary);

                Log::info('Subsidiary moved in hierarchy', [
                    'subsidiary_id' => $subsidiary->id,
                    'old_parent_id' => $subsidiary->parent_company_id,
                    'new_parent_id' => $newParentId,
                    'moved_by' => Auth::id(),
                ]);
            }

            return $moved;
        });
    }

    /**
     * Validate subsidiary creation requirements.
     */
    protected function validateSubsidiaryCreation(Company $parentCompany, array $data): void
    {
        if (! $parentCompany->canCreateSubsidiaries()) {
            throw new \InvalidArgumentException(
                'Parent company is not allowed to create subsidiaries.'
            );
        }

        if ($parentCompany->hasReachedMaxSubsidiaryDepth()) {
            throw new \InvalidArgumentException(
                'Parent company has reached maximum subsidiary depth.'
            );
        }

        // Check for name conflicts within the same parent
        $existingSubsidiary = $parentCompany->childCompanies()
            ->where('name', $data['name'])
            ->exists();

        if ($existingSubsidiary) {
            throw new \InvalidArgumentException(
                'A subsidiary with this name already exists under the parent company.'
            );
        }
    }

    /**
     * Create the subsidiary company record.
     */
    protected function createSubsidiaryCompany(array $data, Company $parentCompany): Company
    {
        $subsidiaryData = [
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'website' => $data['website'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'zip' => $data['zip'] ?? null,
            'country' => $data['country'] ?? $parentCompany->country,
            'currency' => $data['currency'] ?? $parentCompany->currency,
            'locale' => $data['locale'] ?? $parentCompany->locale,

            // Hierarchy fields
            'parent_company_id' => $parentCompany->id,
            'company_type' => 'subsidiary',
            'organizational_level' => $parentCompany->organizational_level + 1,
            'access_level' => $data['access_level'] ?? 'limited',
            'billing_type' => $data['billing_type'] ?? 'parent_billed',
            'billing_parent_id' => $data['billing_type'] === 'parent_billed' ? $parentCompany->id : null,
            'can_create_subsidiaries' => $data['can_create_subsidiaries'] ?? false,
            'max_subsidiary_depth' => $data['max_subsidiary_depth'] ??
                max(0, $parentCompany->max_subsidiary_depth - 1),
            'subsidiary_settings' => $data['subsidiary_settings'] ?? null,
            'is_active' => true,
        ];

        return Company::create($subsidiaryData);
    }

    /**
     * Create an admin user for the subsidiary.
     */
    protected function createSubsidiaryAdmin(Company $subsidiary, array $data): User
    {
        $adminData = [
            'company_id' => $subsidiary->id,
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'password' => Hash::make($data['admin_password']),
            'status' => true,
            'email_verified_at' => now(),
        ];

        $admin = User::create($adminData);

        // Create user settings with subsidiary admin role
        UserSetting::createDefaultForUser(
            $admin->id,
            UserSetting::ROLE_SUBSIDIARY_ADMIN,
            $subsidiary->id
        );

        Log::info('Subsidiary admin user created', [
            'user_id' => $admin->id,
            'subsidiary_id' => $subsidiary->id,
            'created_by' => Auth::id(),
        ]);

        return $admin;
    }

    /**
     * Inherit permissions from parent company.
     */
    protected function inheritPermissions(Company $subsidiary): void
    {
        SubsidiaryPermission::inheritPermissions($subsidiary->id);

        Log::info('Permissions inherited for subsidiary', [
            'subsidiary_id' => $subsidiary->id,
        ]);
    }

    /**
     * Set up initial permissions for the subsidiary.
     */
    protected function setupInitialPermissions(Company $subsidiary, array $permissions): void
    {
        $parentCompanyId = $subsidiary->parent_company_id;

        foreach ($permissions as $permission) {
            SubsidiaryPermission::grantPermission([
                'granter_company_id' => $parentCompanyId,
                'grantee_company_id' => $subsidiary->id,
                'resource_type' => $permission['resource_type'] ?? '*',
                'permission_type' => $permission['permission_type'] ?? 'view',
                'scope' => $permission['scope'] ?? 'all',
                'is_active' => true,
                'can_delegate' => $permission['can_delegate'] ?? false,
                'notes' => 'Initial permission granted during subsidiary creation',
            ]);
        }

        Log::info('Initial permissions set up for subsidiary', [
            'subsidiary_id' => $subsidiary->id,
            'permissions_count' => count($permissions),
        ]);
    }

    /**
     * Configure billing relationship for the subsidiary.
     */
    protected function configureBilling(Company $subsidiary, Company $parentCompany, array $data): void
    {
        $billingType = $data['billing_type'] ?? 'parent_billed';

        switch ($billingType) {
            case 'parent_billed':
                // Subsidiary billing goes through parent
                $subsidiary->update([
                    'billing_parent_id' => $parentCompany->id,
                ]);

                // Create client record under Company 1 if parent is billed through Company 1
                if ($parentCompany->getEffectiveBillingParent()->id === 1) {
                    $this->createBillingClientRecord($subsidiary, $parentCompany);
                }
                break;

            case 'independent':
                // Subsidiary handles its own billing
                $this->createBillingClientRecord($subsidiary);
                break;

            case 'shared':
                // Shared billing pool with parent
                $subsidiary->update([
                    'billing_parent_id' => $parentCompany->getEffectiveBillingParent()->id,
                ]);
                break;
        }

        Log::info('Billing configured for subsidiary', [
            'subsidiary_id' => $subsidiary->id,
            'billing_type' => $billingType,
        ]);
    }

    /**
     * Create a client record for billing purposes.
     */
    protected function createBillingClientRecord(Company $subsidiary, ?Company $parentCompany = null): void
    {
        $clientData = [
            'company_id' => 1, // Always under Company 1 for billing
            'name' => $subsidiary->name.' (Admin)',
            'company_name' => $subsidiary->name,
            'email' => $subsidiary->email ?? ($parentCompany?->email ?? 'admin@'.strtolower(str_replace(' ', '', $subsidiary->name)).'.com'),
            'phone' => $subsidiary->phone ?? $parentCompany?->phone,
            'address' => $subsidiary->address ?? $parentCompany?->address,
            'city' => $subsidiary->city ?? $parentCompany?->city,
            'state' => $subsidiary->state ?? $parentCompany?->state,
            'zip_code' => $subsidiary->zip ?? $parentCompany?->zip,
            'country' => $subsidiary->country ?? $parentCompany?->country,
            'status' => 'active',
            'type' => 'subsidiary_company',
            'company_link_id' => $subsidiary->id,
        ];

        $client = \App\Models\Client::create($clientData);

        // Link back to subsidiary
        $subsidiary->update(['client_record_id' => $client->id]);

        Log::info('Billing client record created for subsidiary', [
            'subsidiary_id' => $subsidiary->id,
            'client_id' => $client->id,
        ]);
    }

    /**
     * Validate subsidiary move operation.
     */
    protected function validateSubsidiaryMove(Company $subsidiary, Company $newParent): void
    {
        // Cannot move to itself
        if ($subsidiary->id === $newParent->id) {
            throw new \InvalidArgumentException('Cannot move company to itself.');
        }

        // Cannot move to a descendant (circular reference)
        if (CompanyHierarchy::isDescendant($newParent->id, $subsidiary->id)) {
            throw new \InvalidArgumentException('Cannot move company to one of its descendants.');
        }

        // New parent must allow subsidiary creation
        if (! $newParent->canCreateSubsidiaries()) {
            throw new \InvalidArgumentException('Target parent company cannot have subsidiaries.');
        }

        // Check depth limits
        $newDepth = $newParent->organizational_level + 1;
        $subsidiaryTreeDepth = $this->getSubsidiaryTreeDepth($subsidiary->id);

        if (($newDepth + $subsidiaryTreeDepth) > $newParent->max_subsidiary_depth) {
            throw new \InvalidArgumentException('Move would exceed maximum subsidiary depth.');
        }

        // Check for name conflicts
        $existingSubsidiary = $newParent->childCompanies()
            ->where('name', $subsidiary->name)
            ->where('id', '!=', $subsidiary->id)
            ->exists();

        if ($existingSubsidiary) {
            throw new \InvalidArgumentException(
                'A subsidiary with this name already exists under the target parent.'
            );
        }
    }

    /**
     * Get the depth of a subsidiary's tree.
     */
    protected function getSubsidiaryTreeDepth(int $companyId): int
    {
        $descendants = CompanyHierarchy::getDescendants($companyId);

        return $descendants->max('depth') ?: 0;
    }

    /**
     * Create a subsidiary with full organizational setup.
     */
    public function createCompleteSubsidiary(array $data, int $parentCompanyId): array
    {
        $subsidiary = $this->createSubsidiary($data, $parentCompanyId);

        // Get additional information for response
        $result = [
            'subsidiary' => $subsidiary,
            'hierarchy_path' => CompanyHierarchy::where('descendant_id', $subsidiary->id)
                ->with('ancestor')
                ->get(),
            'inherited_permissions_count' => $subsidiary->receivedPermissions()
                ->where('is_inherited', true)
                ->active()
                ->count(),
        ];

        // Add admin user info if created
        $adminUser = $subsidiary->users()
            ->whereHas('settings', function ($query) {
                $query->where('role', UserSetting::ROLE_SUBSIDIARY_ADMIN);
            })
            ->first();

        if ($adminUser) {
            $result['admin_user'] = $adminUser;
        }

        return $result;
    }
}
