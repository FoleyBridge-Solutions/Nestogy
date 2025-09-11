# Advanced Permissions System - Nestogy ERP

## Overview

We've implemented a comprehensive permissions system that combines:
1. **Bouncer's role-based permissions** - For role and ability management
2. **Wildcard support** - For simplified permission granting
3. **Client-based filtering** - Technicians only see assigned clients
4. **Resource-level permissions** - Fine-grained access control

## Key Features Implemented

### 1. Wildcard Permissions
- **Full module access**: `assets.*`, `tickets.*`, `clients.*`
- **Feature-level access**: `assets.equipment.*`, `clients.networks.*`
- **Automatic expansion**: Wildcards are expanded for UI display
- **Efficient checking**: Hierarchical permission checking

### 2. Client-Technician Assignment System
- **Database**: `user_clients` pivot table tracks assignments
- **Access Levels**: 
  - `view` - Read-only access
  - `manage` - Edit/update capabilities
  - `admin` - Full administrative access
- **Primary Technician**: Each client can have a designated primary tech
- **Temporary Assignments**: Support for time-limited access with expiry dates
- **Assignment Notes**: Track special instructions or context

### 3. Enhanced User Model Methods
```php
// Check if user is assigned to a client
$user->isAssignedToClient($clientId);

// Get user's access level for a client
$user->getClientAccessLevel($clientId);

// Get all accessible clients (respects role hierarchy)
$user->accessibleClients();

// Check permissions with wildcard support
$user->hasPermission('assets.edit');  // Checks assets.edit, assets.*, and *
```

### 4. Blade Directives
```blade
{{-- Single permission with wildcards --}}
@permission('assets.view')
    <button>View Assets</button>
@endpermission

{{-- Multiple permissions --}}
@anyPermission('assets.edit', 'assets.*')
    <button>Edit Asset</button>
@endanyPermission

{{-- Resource-level check --}}
@canAccess('tickets.edit', $ticket)
    <button>Edit This Ticket</button>
@endcanAccess
```

### 5. Controller Filtering
Controllers use the `FiltersClientsByAssignment` trait to automatically filter resources:
```php
class TicketController extends Controller
{
    use FiltersClientsByAssignment;
    
    public function index()
    {
        // Automatically filters tickets by assigned clients
        $tickets = $this->applyClientFilter(Ticket::query());
    }
}
```

## Permission Hierarchy

### User Roles & Access Levels

1. **Super Admin** (Level 4)
   - Full system access
   - Can manage all roles
   - Sees all clients and resources
   - No restrictions

2. **Admin** (Level 3)
   - Company-wide access
   - Can manage roles below their level
   - Sees all clients in their company
   - Cannot access Super Admin functions

3. **Mid-Level Roles** (Level 2)
   - Technician, Accountant, Sales, Marketing
   - Limited to assigned clients
   - Can only manage User roles
   - Resource-specific permissions

4. **Basic Users** (Level 1)
   - User, Client User
   - Most restricted access
   - Cannot manage roles
   - Limited to assigned resources

## Client Assignment UI

### Livewire Component: `ClientTechnicianAssignment`
Located in client management pages, allows admins to:
- Assign technicians to clients
- Set access levels (view/manage/admin)
- Designate primary technicians
- Set temporary assignments with expiry dates
- Add notes for context

### Usage in Views
```blade
<livewire:client-technician-assignment :client-id="$client->id" />
```

## Database Schema

### user_clients Table
```sql
- id (primary key)
- user_id (foreign key to users)
- client_id (foreign key to clients)
- access_level (enum: view, manage, admin)
- is_primary (boolean)
- assigned_at (date)
- expires_at (nullable date)
- notes (text)
- timestamps
```

## Security Considerations

1. **Role Hierarchy Enforcement**: Users cannot assign roles higher than their own
2. **Company Isolation**: Multi-tenant separation via Bouncer scopes
3. **Resource-Level Checks**: Beyond permissions, checks ownership/assignment
4. **Audit Trail**: All permission changes are logged
5. **Temporary Access**: Automatic expiry of time-limited assignments

## Migration Path

### From Basic Permissions
```php
// Old way
Bouncer::allow($role)->to('assets.view');
Bouncer::allow($role)->to('assets.create');
Bouncer::allow($role)->to('assets.edit');

// New way with wildcards
$permissionService->grantPermission($role, 'assets.*');
```

### Adding Client Assignments
```php
// Assign technician to client
$client->assignTechnician($user, [
    'access_level' => 'manage',
    'is_primary' => true,
    'expires_at' => null, // Permanent
    'notes' => 'Primary support contact'
]);
```

## Best Practices

1. **Use Wildcards for Role Templates**: Simplify role creation with module-level permissions
2. **Be Specific for Sensitive Operations**: Use exact permissions for financial/security functions
3. **Leverage Client Assignments**: Don't give technicians company-wide access unnecessarily
4. **Set Primary Technicians**: Helps with ticket routing and escalations
5. **Use Temporary Assignments**: For contractors or project-based access
6. **Regular Audits**: Review assignments and permissions periodically

## Troubleshooting

### Common Issues

1. **Technician Can't See Client Resources**
   - Check if technician is assigned to the client
   - Verify assignment hasn't expired
   - Ensure proper access level is set

2. **Wildcard Permissions Not Working**
   - Use `$user->hasPermission()` instead of `$user->can()`
   - Check permission hierarchy in PermissionService

3. **Role Changes Not Reflected**
   - Clear Bouncer cache: `Bouncer::refresh()`
   - Check Bouncer scope is set correctly

## Future Enhancements

- [ ] Department-based permissions
- [ ] Time-of-day access restrictions
- [ ] IP-based access control
- [ ] Permission delegation system
- [ ] Advanced audit dashboard
- [ ] Bulk assignment tools
- [ ] Client group assignments
- [ ] Role inheritance system