# Permissions System - Quick Reference

## 🎯 Common Tasks

### Check User Permission
```php
// In controller
if ($user->can('assets.view')) {
    // User has permission
}

// In policy
public function view(User $user, Asset $asset): bool
{
    return $user->can('assets.view');
}

// In Blade
@can('assets.view')
    <a href="{{ route('assets.index') }}">View Assets</a>
@endcan
```

### Assign Role to User
```php
// Assign default role
$user->assign('admin');
$user->assign('tech');

// Assign custom role
$user->assign('helpdesk');
```

### Create Custom Role
```php
use Silber\Bouncer\BouncerFacade as Bouncer;

// Set company scope
Bouncer::scope()->to($companyId);

// Create role
$role = Bouncer::role()->create([
    'name' => 'helpdesk',
    'title' => 'Help Desk Support',
]);

// Assign permissions
Bouncer::allow($role)->to([
    'tickets.*',
    'clients.view',
    'assets.view',
]);
```

### Grant Direct Permission to User
```php
// Give specific permission
Bouncer::allow($user)->to('tickets.approve');

// Give wildcard permission
Bouncer::allow($user)->to('assets.*');
```

## 🔍 Deployment Commands

```bash
# Validate permissions system (no changes)
php artisan permissions:ensure

# Sync permissions and validate
php artisan permissions:ensure --sync

# Discover permissions only
php artisan permissions:discover --sync
```

## 🛠️ Troubleshooting

### Permission Not Working?

1. **Check user has permission:**
```bash
php artisan tinker
$user = User::find(1);
$user->can('assets.view'); // Should return true/false
```

2. **Check Bouncer scope:**
```php
// Ensure scope is set
Bouncer::scope()->to($user->company_id);
$user->getAbilities(); // See what permissions user has
```

3. **Refresh cache:**
```bash
php artisan cache:clear
Bouncer::refresh(); // In tinker
```

### Role Not Showing Permissions?

```bash
php artisan tinker
$role = Bouncer::role()->where('name', 'admin')->first();
$role->getAbilities(); // See permissions assigned to role
```

## 📋 Wildcard Examples

```php
// User with 'assets.*' can:
$user->can('assets.view');         // ✅ true
$user->can('assets.create');       // ✅ true
$user->can('assets.edit');         // ✅ true
$user->can('assets.maintenance.view'); // ✅ true (nested wildcard)

// User with 'assets.maintenance.*' can:
$user->can('assets.maintenance.view');   // ✅ true
$user->can('assets.maintenance.manage'); // ✅ true
$user->can('assets.view');              // ❌ false (parent permission)
```

## 🎨 Default Roles

| Role | Permissions |
|------|-------------|
| **admin** | Full access (clients.*, assets.*, tickets.*, financial.*, etc.) |
| **tech** | Technical tasks (assets.*, tickets.*, limited client access) |
| **accountant** | Financial operations (financial.*, contracts.view, reports.*) |
| **sales** | Sales & leads (leads.*, clients.manage, quotes.*) |
| **marketing** | Campaigns (marketing.*, campaigns.*, leads.*) |
| **client** | Portal access (tickets.view, tickets.create, assets.view) |

## 🔐 Route Protection

```php
// In routes/web.php
Route::middleware('permission:assets.view')->group(function () {
    Route::get('/assets', [AssetController::class, 'index']);
});

// Multiple permissions (OR)
Route::middleware('permission:assets.view|tickets.view')->get(...);

// Multiple permissions (AND)
Route::middleware('permission:assets.view&assets.edit')->get(...);
```

## 📊 Check System Health

```bash
# Full validation
php artisan permissions:ensure --sync

# Output example:
# ✅ 153 permissions discovered
# ✅ All role template permissions exist
# ✅ Permissions system is ready
```

## 🚀 Files to Know

- **Config:** `config/bouncer.php` - Bouncer configuration
- **Templates:** `config/role-templates.php` - Default roles for new companies
- **Observer:** `app/Observers/CompanyObserver.php` - Auto-creates roles
- **Service:** `app/Domains/Security/Services/TenantRoleService.php` - Role management
- **Middleware:** `app/Http/Middleware/SetBouncerScope.php` - Sets company scope
