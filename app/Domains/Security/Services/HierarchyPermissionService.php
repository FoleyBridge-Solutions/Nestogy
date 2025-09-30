<?php

namespace App\Domains\Security\Services;

use App\Domains\Core\Services\BaseService;
use App\Models\Company;
use App\Models\CompanyHierarchy;
use App\Models\CrossCompanyUser;
use App\Models\SubsidiaryPermission;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * HierarchyPermissionService
 *
 * Manages complex permission operations across company hierarchies including
 * permission inheritance, delegation, cross-company access, and bulk operations.
 */
class HierarchyPermissionService extends BaseService
{
    protected function initializeService(): void
    {
        $this->modelClass = SubsidiaryPermission::class;
        $this->defaultEagerLoad = ['granterCompany', 'granteeCompany', 'user'];
        $this->searchableFields = ['resource_type', 'permission_type', 'notes'];
    }

    /**
     * Grant permission from one company to another.
     */
    public function grantPermission(array $permissionData): SubsidiaryPermission
    {
        return DB::transaction(function () use ($permissionData) {
            $granterCompanyId = $permissionData['granter_company_id'];
            $granteeCompanyId = $permissionData['grantee_company_id'];

            // Validate permission grant
            $this->validatePermissionGrant($granterCompanyId, $granteeCompanyId, $permissionData);

            // Check if permission already exists
            $existingPermission = SubsidiaryPermission::where([
                'granter_company_id' => $granterCompanyId,
                'grantee_company_id' => $granteeCompanyId,
                'resource_type' => $permissionData['resource_type'],
                'permission_type' => $permissionData['permission_type'],
                'scope' => $permissionData['scope'] ?? 'all',
            ])->first();

            if ($existingPermission) {
                if ($existingPermission->is_active) {
                    throw new \InvalidArgumentException('Permission already exists and is active.');
                }

                // Reactivate existing permission
                $existingPermission->update([
                    'is_active' => true,
                    'granted_at' => now(),
                    'granted_by' => Auth::id(),
                    'expires_at' => $permissionData['expires_at'] ?? null,
                    'notes' => $permissionData['notes'] ?? $existingPermission->notes,
                    'can_delegate' => $permissionData['can_delegate'] ?? false,
                ]);

                $permission = $existingPermission;
            } else {
                // Create new permission
                $permission = SubsidiaryPermission::create([
                    'granter_company_id' => $granterCompanyId,
                    'grantee_company_id' => $granteeCompanyId,
                    'resource_type' => $permissionData['resource_type'],
                    'permission_type' => $permissionData['permission_type'],
                    'scope' => $permissionData['scope'] ?? 'all',
                    'granted_by' => Auth::id(),
                    'granted_at' => now(),
                    'expires_at' => $permissionData['expires_at'] ?? null,
                    'is_active' => true,
                    'is_inherited' => false,
                    'can_delegate' => $permissionData['can_delegate'] ?? false,
                    'notes' => $permissionData['notes'] ?? null,
                ]);
            }

            Log::info('Permission granted', [
                'permission_id' => $permission->id,
                'granter_company_id' => $granterCompanyId,
                'grantee_company_id' => $granteeCompanyId,
                'resource_type' => $permissionData['resource_type'],
                'permission_type' => $permissionData['permission_type'],
                'granted_by' => Auth::id(),
            ]);

            return $permission;
        });
    }

    /**
     * Revoke a specific permission.
     */
    public function revokePermission(int $permissionId, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($permissionId, $reason) {
            $permission = SubsidiaryPermission::findOrFail($permissionId);

            // Validate revocation rights
            $this->validatePermissionRevocation($permission);

            $permission->update([
                'is_active' => false,
                'revoked_at' => now(),
                'revoked_by' => Auth::id(),
                'revocation_reason' => $reason ?? 'Manual revocation',
            ]);

            // Revoke delegated permissions if this was delegated
            if ($permission->can_delegate) {
                $this->revokeDelegatedPermissions($permission);
            }

            Log::info('Permission revoked', [
                'permission_id' => $permission->id,
                'granter_company_id' => $permission->granter_company_id,
                'grantee_company_id' => $permission->grantee_company_id,
                'revoked_by' => Auth::id(),
                'reason' => $reason,
            ]);

            return true;
        });
    }

    /**
     * Inherit permissions from parent company to subsidiary.
     */
    public function inheritPermissions(int $subsidiaryId): array
    {
        return DB::transaction(function () use ($subsidiaryId) {
            $subsidiary = Company::findOrFail($subsidiaryId);
            $parentCompanyId = $subsidiary->parent_company_id;

            if (! $parentCompanyId) {
                throw new \InvalidArgumentException('Company has no parent to inherit from.');
            }

            $inheritedCount = 0;
            $skippedCount = 0;

            // Get parent's received permissions that can be inherited
            $parentPermissions = SubsidiaryPermission::where('grantee_company_id', $parentCompanyId)
                ->where('is_active', true)
                ->where('can_delegate', true)
                ->get();

            foreach ($parentPermissions as $parentPermission) {
                // Check if subsidiary already has this permission
                $existingPermission = SubsidiaryPermission::where([
                    'granter_company_id' => $parentPermission->granter_company_id,
                    'grantee_company_id' => $subsidiaryId,
                    'resource_type' => $parentPermission->resource_type,
                    'permission_type' => $parentPermission->permission_type,
                    'scope' => $parentPermission->scope,
                ])->first();

                if ($existingPermission && $existingPermission->is_active) {
                    $skippedCount++;

                    continue;
                }

                // Create inherited permission
                $inheritedPermission = SubsidiaryPermission::create([
                    'granter_company_id' => $parentPermission->granter_company_id,
                    'grantee_company_id' => $subsidiaryId,
                    'resource_type' => $parentPermission->resource_type,
                    'permission_type' => $parentPermission->permission_type,
                    'scope' => $parentPermission->scope,
                    'granted_by' => Auth::id(),
                    'granted_at' => now(),
                    'expires_at' => $parentPermission->expires_at,
                    'is_active' => true,
                    'is_inherited' => true,
                    'parent_permission_id' => $parentPermission->id,
                    'can_delegate' => false, // Inherited permissions cannot be delegated further
                    'notes' => 'Inherited from parent company',
                ]);

                $inheritedCount++;
            }

            Log::info('Permissions inherited for subsidiary', [
                'subsidiary_id' => $subsidiaryId,
                'parent_company_id' => $parentCompanyId,
                'inherited_count' => $inheritedCount,
                'skipped_count' => $skippedCount,
            ]);

            return [
                'inherited_count' => $inheritedCount,
                'skipped_count' => $skippedCount,
            ];
        });
    }

    /**
     * Grant cross-company user access.
     */
    public function grantUserAccess(array $accessData): CrossCompanyUser
    {
        return DB::transaction(function () use ($accessData) {
            $userId = $accessData['user_id'];
            $companyId = $accessData['company_id'];

            // Validate user access grant
            $this->validateUserAccessGrant($userId, $companyId, $accessData);

            // Check if access already exists
            $existingAccess = CrossCompanyUser::where([
                'user_id' => $userId,
                'company_id' => $companyId,
            ])->first();

            if ($existingAccess) {
                if ($existingAccess->is_active) {
                    throw new \InvalidArgumentException('User already has access to this company.');
                }

                // Reactivate existing access
                $existingAccess->update([
                    'is_active' => true,
                    'role_in_company' => $accessData['role_in_company'],
                    'access_type' => $accessData['access_type'],
                    'authorized_by' => Auth::id(),
                    'access_granted_at' => now(),
                    'access_expires_at' => $accessData['expires_at'] ?? null,
                ]);

                $crossCompanyUser = $existingAccess;
            } else {
                // Create new access
                $crossCompanyUser = CrossCompanyUser::create([
                    'user_id' => $userId,
                    'company_id' => $companyId,
                    'primary_company_id' => $accessData['primary_company_id'],
                    'role_in_company' => $accessData['role_in_company'],
                    'access_type' => $accessData['access_type'],
                    'authorized_by' => Auth::id(),
                    'access_granted_at' => now(),
                    'access_expires_at' => $accessData['expires_at'] ?? null,
                    'is_active' => true,
                ]);
            }

            Log::info('Cross-company user access granted', [
                'user_id' => $userId,
                'company_id' => $companyId,
                'access_type' => $accessData['access_type'],
                'authorized_by' => Auth::id(),
            ]);

            return $crossCompanyUser;
        });
    }

    /**
     * Bulk grant permissions to multiple companies.
     */
    public function bulkGrantPermissions(array $companyIds, array $permissionData): array
    {
        return DB::transaction(function () use ($companyIds, $permissionData) {
            $results = [];
            $granterCompanyId = $permissionData['granter_company_id'];

            foreach ($companyIds as $granteeCompanyId) {
                try {
                    $permission = $this->grantPermission(array_merge($permissionData, [
                        'grantee_company_id' => $granteeCompanyId,
                    ]));

                    $results[] = [
                        'company_id' => $granteeCompanyId,
                        'status' => 'success',
                        'permission_id' => $permission->id,
                    ];
                } catch (\Exception $e) {
                    $results[] = [
                        'company_id' => $granteeCompanyId,
                        'status' => 'failed',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return $results;
        });
    }

    /**
     * Get effective permissions for a company (including inherited).
     */
    public function getEffectivePermissions(int $companyId): array
    {
        $directPermissions = SubsidiaryPermission::where('grantee_company_id', $companyId)
            ->where('is_active', true)
            ->with(['granterCompany'])
            ->get();

        $permissions = [];

        foreach ($directPermissions as $permission) {
            $key = $permission->resource_type.':'.$permission->permission_type.':'.$permission->scope;

            $permissions[$key] = [
                'resource_type' => $permission->resource_type,
                'permission_type' => $permission->permission_type,
                'scope' => $permission->scope,
                'granter_company' => $permission->granterCompany->name,
                'is_inherited' => $permission->is_inherited,
                'can_delegate' => $permission->can_delegate,
                'expires_at' => $permission->expires_at,
                'granted_at' => $permission->granted_at,
            ];
        }

        return array_values($permissions);
    }

    /**
     * Check if a company has a specific permission.
     */
    public function hasPermission(int $companyId, string $resourceType, string $permissionType, string $scope = 'all'): bool
    {
        return SubsidiaryPermission::where([
            'grantee_company_id' => $companyId,
            'resource_type' => $resourceType,
            'permission_type' => $permissionType,
            'scope' => $scope,
            'is_active' => true,
        ])->exists();
    }

    /**
     * Get permission hierarchy for a company tree.
     */
    public function getHierarchyPermissions(int $rootCompanyId): array
    {
        $companies = CompanyHierarchy::where('ancestor_id', $rootCompanyId)
            ->with(['descendant.grantedPermissions.granteeCompany', 'descendant.receivedPermissions.granterCompany'])
            ->get();

        $hierarchy = [];

        foreach ($companies as $relation) {
            $company = $relation->descendant;

            $hierarchy[] = [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'depth' => $relation->depth,
                'granted_permissions' => $company->grantedPermissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'grantee' => $permission->granteeCompany->name,
                        'resource_type' => $permission->resource_type,
                        'permission_type' => $permission->permission_type,
                        'is_active' => $permission->is_active,
                    ];
                }),
                'received_permissions' => $company->receivedPermissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'granter' => $permission->granterCompany->name,
                        'resource_type' => $permission->resource_type,
                        'permission_type' => $permission->permission_type,
                        'is_inherited' => $permission->is_inherited,
                        'is_active' => $permission->is_active,
                    ];
                }),
            ];
        }

        return $hierarchy;
    }

    /**
     * Validate permission grant requirements.
     */
    protected function validatePermissionGrant(int $granterCompanyId, int $granteeCompanyId, array $permissionData): void
    {
        // Cannot grant to self
        if ($granterCompanyId === $granteeCompanyId) {
            throw new \InvalidArgumentException('Cannot grant permission to the same company.');
        }

        // Check if granter company exists and has the right to grant permissions
        $granterCompany = Company::find($granterCompanyId);
        if (! $granterCompany) {
            throw new \InvalidArgumentException('Granter company not found.');
        }

        // Check if grantee company exists
        $granteeCompany = Company::find($granteeCompanyId);
        if (! $granteeCompany) {
            throw new \InvalidArgumentException('Grantee company not found.');
        }

        // Validate user has permission to grant on behalf of granter company
        $user = Auth::user();
        if ($user->company_id !== $granterCompanyId &&
            ! CrossCompanyUser::canUserManageCompany($user->id, $granterCompanyId)) {
            throw new \InvalidArgumentException('User does not have permission to grant permissions for this company.');
        }

        // Validate resource type and permission type
        $validResourceTypes = ['*', 'users', 'clients', 'tickets', 'assets', 'invoices', 'contracts'];
        if (! in_array($permissionData['resource_type'], $validResourceTypes)) {
            throw new \InvalidArgumentException('Invalid resource type.');
        }

        $validPermissionTypes = ['view', 'create', 'edit', 'delete', 'manage'];
        if (! in_array($permissionData['permission_type'], $validPermissionTypes)) {
            throw new \InvalidArgumentException('Invalid permission type.');
        }
    }

    /**
     * Validate permission revocation requirements.
     */
    protected function validatePermissionRevocation(SubsidiaryPermission $permission): void
    {
        $user = Auth::user();

        // User must be from granter company or have management access to it
        if ($user->company_id !== $permission->granter_company_id &&
            ! CrossCompanyUser::canUserManageCompany($user->id, $permission->granter_company_id)) {
            throw new \InvalidArgumentException('User does not have permission to revoke this permission.');
        }

        if (! $permission->is_active) {
            throw new \InvalidArgumentException('Permission is already inactive.');
        }
    }

    /**
     * Validate user access grant requirements.
     */
    protected function validateUserAccessGrant(int $userId, int $companyId, array $accessData): void
    {
        $user = User::find($userId);
        if (! $user) {
            throw new \InvalidArgumentException('User not found.');
        }

        $company = Company::find($companyId);
        if (! $company) {
            throw new \InvalidArgumentException('Company not found.');
        }

        $authUser = Auth::user();

        // User must have management permissions for the target company
        if ($authUser->company_id !== $companyId &&
            ! CrossCompanyUser::canUserManageCompany($authUser->id, $companyId)) {
            throw new \InvalidArgumentException('You do not have permission to grant access to this company.');
        }

        // Cannot grant access to user's own company
        if ($user->company_id === $companyId) {
            throw new \InvalidArgumentException('User already belongs to this company.');
        }
    }

    /**
     * Revoke delegated permissions when parent permission is revoked.
     */
    protected function revokeDelegatedPermissions(SubsidiaryPermission $parentPermission): int
    {
        $revokedCount = 0;

        $delegatedPermissions = SubsidiaryPermission::where('parent_permission_id', $parentPermission->id)
            ->where('is_active', true)
            ->get();

        foreach ($delegatedPermissions as $delegatedPermission) {
            $delegatedPermission->update([
                'is_active' => false,
                'revoked_at' => now(),
                'revoked_by' => Auth::id(),
                'revocation_reason' => 'Parent permission revoked',
            ]);

            $revokedCount++;

            // Recursively revoke further delegated permissions
            $revokedCount += $this->revokeDelegatedPermissions($delegatedPermission);
        }

        return $revokedCount;
    }
}
