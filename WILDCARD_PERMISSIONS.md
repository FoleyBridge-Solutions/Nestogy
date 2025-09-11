# Wildcard Permissions in Nestogy ERP

## Why We Use Bouncer Over Spatie

1. **Better Multi-tenancy**: Built-in scoping for company/client isolation
2. **Cleaner API**: More readable syntax (`$user->isA('admin')`)
3. **Flexible Permission Model**: Abilities can be assigned to users OR roles
4. **Better Performance**: Efficient caching and lazy loading

## Wildcard Permission Support

We've enhanced Bouncer with wildcard support through our `PermissionService`:

### How It Works

```php
// In your controllers/policies
$user->hasPermission('assets.view');  // Checks:
// 1. Direct permission: 'assets.view'
// 2. Wildcard: 'assets.*'
// 3. Full wildcard: '*'

// Grant wildcard permissions
$permissionService->grantPermission($role, 'assets.*');  // Full asset access
$permissionService->grantPermission($role, 'tickets.priority.*');  // All priority levels
```

### Available Wildcards

- `*` - Full system access (super admin)
- `assets.*` - Full asset management access
- `tickets.*` - Full ticket management access
- `clients.*` - Full client management access
- `financial.*` - Full financial access
- `users.*` - Full user management access
- `settings.*` - Full settings access

### Blade Directives

```blade
{{-- Single permission check with wildcard support --}}
@permission('assets.view')
    <button>View Assets</button>
@endpermission

{{-- Check any permission --}}
@anyPermission('assets.edit', 'assets.*')
    <button>Edit Asset</button>
@endanyPermission

{{-- Check all permissions --}}
@allPermissions('assets.view', 'assets.edit')
    <button>Manage Assets</button>
@endallPermissions

{{-- Resource-level check --}}
@canAccess('assets.edit', $asset)
    <button>Edit This Asset</button>
@endcanAccess
```

### In Controllers

```php
// Simple permission check
if ($user->hasPermission('assets.edit')) {
    // User can edit assets
}

// Resource-level check
if ($user->canAccessResource('tickets.edit', $ticket)) {
    // User can edit this specific ticket
}

// Get all effective permissions (with wildcards expanded)
$permissions = $user->getEffectivePermissions();
```

### Creating Roles with Wildcards

```php
use App\Services\PermissionService;

$permissionService = app(PermissionService::class);

// Create a role with full module access
$permissionService->grantPermission('network-admin', 'assets.*');
$permissionService->grantPermission('network-admin', 'clients.networks.*');

// The service automatically expands wildcards for UI display
// So 'assets.*' creates: assets.view, assets.create, assets.edit, etc.
```

### Migration from Basic Permissions

Instead of:
```php
Bouncer::allow($role)->to('assets.view');
Bouncer::allow($role)->to('assets.create');
Bouncer::allow($role)->to('assets.edit');
Bouncer::allow($role)->to('assets.delete');
```

Use:
```php
$permissionService->grantPermission($role, 'assets.*');
```

## Permission Hierarchy

The system supports hierarchical permissions:

1. `*` - Everything
2. `module.*` - Full module access (e.g., `assets.*`)
3. `module.feature.*` - Feature access (e.g., `assets.equipment.*`)
4. `module.feature.action` - Specific action (e.g., `assets.equipment.view`)

## Resource-Level Permissions

Beyond wildcards, we support resource-level checks:

- Users can only edit assets for their assigned clients
- Technicians can only view tickets assigned to them
- Managers can view all resources in their department

This is handled automatically by the `canAccessResource()` method.

## Best Practices

1. **Use wildcards for role templates**: Define broad permissions for common roles
2. **Be specific for sensitive operations**: Use exact permissions for financial/security operations
3. **Test permission cascading**: Verify wildcard expansions work as expected
4. **Document custom permissions**: Keep track of any domain-specific permissions

## Future Enhancements

- [ ] Time-based permissions (temporary access)
- [ ] Conditional permissions (based on resource state)
- [ ] Permission delegation (users granting subset of their permissions)
- [ ] Audit trail for permission changes