# Comprehensive Authorization and Permissions System

This document outlines the newly implemented comprehensive authorization and permissions system for Nestogy ERP, which provides granular role-based access control across all domain functionality.

## Overview

The authorization system has been completely redesigned to provide:
- **Role-Based Access Control (RBAC)**: Flexible role and permission management
- **Domain-Specific Permissions**: Granular permissions for each business domain
- **Multi-Level Authorization**: Policies, gates, middleware, and controller-level checks
- **Export Controls**: Special permissions for data export operations
- **Approval Workflows**: Financial approval workflows with hierarchy
- **Company Scoping**: Multi-tenant security with company-level isolation
- **Permission-Aware Navigation**: UI elements respect user permissions

## Architecture

### Core Components

1. **Permission Models** (`app/Models/`)
   - `Permission`: Individual permissions (e.g., "clients.view", "financial.export")
   - `Role`: User roles with associated permissions
   - `PermissionGroup`: Logical groupings for UI organization
   - `User` (enhanced): Extended with permission functionality

2. **Authorization Traits** (`app/Traits/`)
   - `HasPermissions`: Core permission functionality for User model
   - `HasAuthorization`: Consistent authorization patterns for controllers
   - `HasExportControls`: Comprehensive export permission controls
   - `HasApprovalWorkflows`: Financial approval workflow functionality

3. **Policies** (`app/Policies/`)
   - `ClientPolicy`: Authorization for client management
   - `AssetPolicy`: Authorization for asset management
   - `FinancialPolicy`: Authorization for financial operations
   - `ProjectPolicy`: Authorization for project management
   - `ReportPolicy`: Authorization for reporting system
   - `UserPolicy`: Authorization for user management

4. **Middleware** (`app/Http/Middleware/`)
   - `PermissionMiddleware`: Route-level permission checking
   - `RoleMiddleware` (enhanced): Hierarchical role-based access

5. **Service Classes**
   - `NavigationService` (enhanced): Permission-aware navigation

## Permission Structure

### Domain Permissions

The system implements granular permissions across all domains:

#### Clients Domain
- `clients.view` - View client data
- `clients.create` - Create new clients
- `clients.edit` - Edit existing clients
- `clients.delete` - Delete clients
- `clients.manage` - Full client management
- `clients.export` - Export client data
- `clients.import` - Import client data

**Sub-modules:**
- `clients.contacts.*` - Contact management
- `clients.locations.*` - Location management
- `clients.documents.*` - Document management
- `clients.files.*` - File management
- `clients.licenses.*` - License management
- `clients.credentials.*` - Credential management
- `clients.networks.*` - Network management
- `clients.services.*` - Service management
- `clients.vendors.*` - Vendor management
- `clients.racks.*` - Rack management
- `clients.certificates.*` - Certificate management
- `clients.domains.*` - Domain management
- `clients.calendar-events.*` - Calendar event management
- `clients.quotes.*` - Quote management
- `clients.trips.*` - Trip management

#### Assets Domain
- `assets.view` - View asset data
- `assets.create` - Create new assets
- `assets.edit` - Edit existing assets
- `assets.delete` - Delete assets
- `assets.manage` - Full asset management
- `assets.export` - Export asset data
- `assets.maintenance.*` - Maintenance management
- `assets.warranties.*` - Warranty management
- `assets.depreciations.*` - Depreciation management

#### Financial Domain
- `financial.view` - View financial data
- `financial.create` - Create financial records
- `financial.edit` - Edit financial records
- `financial.delete` - Delete financial records
- `financial.manage` - Full financial management
- `financial.export` - Export financial data
- `financial.payments.*` - Payment management
- `financial.expenses.*` - Expense management
- `financial.expenses.approve` - **Approve expenses** (special workflow permission)
- `financial.invoices.*` - Invoice management

#### Projects Domain
- `projects.view` - View projects
- `projects.create` - Create new projects
- `projects.edit` - Edit existing projects
- `projects.delete` - Delete projects
- `projects.manage` - Full project management
- `projects.export` - Export project data
- `projects.tasks.*` - Task management
- `projects.members.*` - Team member management
- `projects.templates.*` - Project template management

#### Reports Domain
- `reports.view` - View reports dashboard
- `reports.financial` - View financial reports
- `reports.tickets` - View ticket reports
- `reports.assets` - View asset reports
- `reports.clients` - View client reports
- `reports.projects` - View project reports
- `reports.users` - View user reports
- `reports.export` - Export reports

#### System Domain
- `system.settings.view` - View system settings
- `system.settings.manage` - Manage system settings
- `system.logs.view` - View system logs
- `system.backups.manage` - Manage backups
- `system.permissions.manage` - Manage roles and permissions

#### Users Domain
- `users.view` - View users
- `users.create` - Create new users
- `users.edit` - Edit existing users
- `users.delete` - Delete users
- `users.manage` - Full user management
- `users.export` - Export user data

### Role Hierarchy

The system maintains backward compatibility with existing roles while adding flexibility:

1. **Accountant** (Level 1)
   - Financial operations focus
   - Basic client operations
   - Limited reporting access

2. **Technician** (Level 2)
   - Full asset management
   - Ticket system access
   - Client management (except deletion)
   - Project participation

3. **Administrator** (Level 3)
   - Full system access
   - User management
   - System configuration
   - All permissions

## Implementation Details

### Database Schema

The permissions system uses the following tables:

```sql
-- Core permission tables
permissions (id, name, slug, domain, action, description, is_system, group_id)
roles (id, name, slug, description, level, is_system)
permission_groups (id, name, slug, description, sort_order)

-- Relationship tables
role_permissions (role_id, permission_id)
user_roles (user_id, role_id, company_id)
user_permissions (user_id, permission_id, company_id, granted)
```

### Usage Examples

#### Controller Authorization
```php
class ContactController extends Controller
{
    use HasAuthorization;

    public function __construct()
    {
        $this->applyAuthorizationMiddleware('clients.contacts');
    }

    public function index()
    {
        $this->authorizeAction('clients.contacts', 'view');
        // Controller logic
    }

    public function export()
    {
        $this->authorizeExport('clients.contacts');
        // Export logic with security checks
    }
}
```

#### Policy Authorization
```php
// In ClientPolicy
public function view(User $user, Client $client): bool
{
    return $user->hasPermission('clients.view') && 
           $this->sameCompany($user, $client);
}
```

#### Middleware Protection
```php
// In routes
Route::middleware('permission:clients.view')->group(function () {
    Route::get('/clients', [ClientController::class, 'index']);
});
```

#### User Permission Checking
```php
// Check single permission
if (auth()->user()->hasPermission('clients.create')) {
    // Show create button
}

// Check multiple permissions (any)
if (auth()->user()->hasAnyPermission(['clients.edit', 'clients.manage'])) {
    // Show edit functionality
}

// Check all permissions required
if (auth()->user()->hasAllPermissions(['financial.view', 'financial.export'])) {
    // Show advanced financial features
}
```

#### Gate Usage
```php
// Check gates in controllers
if (Gate::allows('approve-expenses')) {
    // Show expense approval functionality
}

// Complex authorization
if (Gate::allows('export-sensitive-data')) {
    // Allow sensitive data export
}
```

### Export Controls

The system implements comprehensive export controls:

#### Export Types
- **Basic**: Standard data export
- **Sensitive**: Personal or financial data
- **Financial**: Financial data with enhanced security
- **Bulk**: Large dataset exports
- **Scheduled**: Automated exports
- **Audit**: System audit data

#### Security Features
- Rate limiting (10 exports per hour by default)
- Permission-based filtering of exportable data
- Audit logging of all export attempts
- Format-specific restrictions (PDF, Excel require elevated permissions)
- Business hours restrictions for sensitive exports
- GDPR compliance logging for personal data

### Approval Workflows

Financial approval workflows implement multi-level authorization:

#### Expense Approval
- Amount-based approval limits by role
- Multi-approval requirements for large amounts
- Hierarchical approval chain
- Suspicious pattern detection

#### Approval Limits
- **Accountant**: $1,000 expense limit
- **Technician**: $5,000 expense limit
- **Administrator**: $50,000 expense limit

#### Workflow Features
- Automatic escalation for amounts exceeding limits
- Required approvals tracking
- Audit trail for all approval actions
- Business rules enforcement

### Navigation Integration

The navigation system is fully integrated with permissions:

#### Features
- Domain tabs hidden if user lacks access
- Navigation items filtered by permissions
- Badge counts respect permission boundaries
- Breadcrumbs adjust based on access levels

#### Implementation
```php
// Check domain access
if (NavigationService::canAccessDomain($user, 'financial')) {
    // Show financial navigation
}

// Get filtered navigation items
$items = NavigationService::getFilteredNavigationItems('clients');

// Check specific item access
if (NavigationService::canAccessNavigationItem($user, 'clients', 'export')) {
    // Show export option
}
```

## Security Features

### Company Scoping
- All data access is company-scoped
- Users can only access their company's data
- Permissions are company-specific
- Cross-company access prevented

### Multi-Level Authorization
1. **Middleware Level**: Route protection
2. **Policy Level**: Model-based authorization
3. **Gate Level**: Complex business logic
4. **Controller Level**: Action-specific checks
5. **View Level**: UI element visibility

### Audit Logging
- All permission grants/denials logged
- Export attempts tracked
- Approval workflow actions recorded
- Security events monitored

### Rate Limiting
- Export operations limited per user per hour
- Sensitive operations have additional restrictions
- Suspicious activity detection and blocking

## Migration and Backward Compatibility

### Migration Path
1. Run permission system migrations
2. Seed default roles and permissions
3. Migrate existing user roles
4. Update navigation and policies
5. Test authorization across all domains

### Backward Compatibility
- Existing role methods (`isAdmin()`, `isTech()`, etc.) still work
- Current role hierarchy maintained
- Gradual migration possible
- No breaking changes to existing functionality

## Testing

### Test Coverage
- Unit tests for all permission models
- Policy tests for authorization logic
- Integration tests for middleware
- Feature tests for controller authorization
- Navigation permission tests

### Testing Commands
```bash
# Run permission system tests
php artisan test --filter=PermissionTest

# Test specific domain authorization
php artisan test --filter=ClientAuthorizationTest

# Test export controls
php artisan test --filter=ExportControlsTest

# Test approval workflows
php artisan test --filter=ApprovalWorkflowTest
```

## Performance Considerations

### Optimization Strategies
- Permission caching at user level
- Role hierarchy caching
- Batch permission checking
- Efficient database queries
- Minimal authorization overhead

### Caching
- User permissions cached for 1 hour
- Role definitions cached indefinitely
- Navigation filters cached per user
- Badge counts cached for 15 minutes

## Troubleshooting

### Common Issues

1. **Permission Denied Errors**
   - Check user has required permission
   - Verify company scoping
   - Check role assignments

2. **Navigation Items Missing**
   - Verify permission grants
   - Check NavigationService filtering
   - Clear permission cache

3. **Export Failures**
   - Check export permissions
   - Verify rate limits not exceeded
   - Check business hours restrictions

### Debug Commands
```bash
# Check user permissions
php artisan tinker
>>> auth()->user()->getAllPermissions()->pluck('slug')

# Clear permission cache
php artisan cache:clear

# Check role assignments
>>> auth()->user()->roles

# Verify company scoping
>>> auth()->user()->company_id
```

## Future Enhancements

### Planned Features
- Permission templates for common role combinations
- Advanced audit reporting
- Permission delegation
- Temporary permission grants
- API key-based permissions
- Advanced workflow automation

### Extensibility
The system is designed for easy extension:
- New domains can be added with minimal configuration
- Custom permissions are fully supported
- Additional approval workflows can be implemented
- Export controls can be customized per domain

## Conclusion

The comprehensive authorization and permissions system provides enterprise-grade security and flexibility while maintaining ease of use and performance. It supports the complex requirements of a modern ERP system while providing clear audit trails and compliance features.

For implementation details and API documentation, refer to the code comments and PHPDoc blocks in the respective classes.