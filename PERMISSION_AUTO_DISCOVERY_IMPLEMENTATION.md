# Permission Auto-Discovery System - Implementation Plan

## Current Architecture Analysis

### ✅ What You Already Have (Strong Foundation)

**1. Multi-Tenant Permission System**
- **Bouncer Integration**: Already configured with `company_id` scoping
- **Wildcard Support**: Custom implementation in `User::can()` and `PermissionService`
- **Cross-Company Permissions**: `HierarchyPermissionService` for subsidiary relationships
- **26 Policies**: Well-structured authorization (AssetPolicy, ClientPolicy, etc.)
- **308+ Permissions**: Currently manually maintained across 7 seeders

**2. Current Permission Flow**
```
User Request
    ↓
Middleware (auth, verified) - No permission middleware
    ↓
Controller ($this->authorize() or direct can() checks)
    ↓
Policy (calls $user->can('permission.name'))
    ↓
User::can() override → checkWildcardPermission()
    ↓
PermissionService::userHasPermission()
    ↓
Bouncer (with company_id scope)
```

**3. Existing Files Structure**
```
app/
├── Domains/
│   ├── Security/
│   │   ├── Controllers/
│   │   │   ├── RoleController.php ✅ (Role CRUD + permission assignment)
│   │   │   └── PermissionController.php ✅ (Permission management)
│   │   ├── Services/
│   │   │   ├── PermissionService.php ✅ (Wildcard support)
│   │   │   ├── HierarchyPermissionService.php ✅ (Cross-company)
│   │   │   └── RoleService.php ✅ (Role stats/operations)
│   │   └── Models/ (none - uses Bouncer models)
│   ├── Core/Models/
│   │   ├── User.php ✅ (HasRolesAndAbilities, HasEnhancedPermissions)
│   │   └── Role.php ✅ (Backward compat wrapper for Bouncer)
│   └── [21 other domains with 209 models, 122 controllers]
├── Livewire/ (46+ components)
├── Policies/ (26 policies)
└── Traits/
    └── HasEnhancedPermissions.php ✅

database/seeders/
├── RolesAndPermissionsSeeder.php ⚠️ (Creates global roles/permissions)
├── PermissionSeeder.php
├── RoleSeeder.php
├── PermissionGroupSeeder.php
├── SubsidiaryPermissionSeeder.php
└── RemoteControlPermissionsSeeder.php

config/
└── bouncer.php ✅ (multi_tenant=true, scope_column=company_id)
```

**4. Current Seeding Process (THE PROBLEM)**
```php
// RolesAndPermissionsSeeder.php runs ONCE globally
// Creates abilities (NOT scoped to company):
Bouncer::ability()->firstOrCreate(['name' => 'clients.view']);
Bouncer::ability()->firstOrCreate(['name' => 'assets.create']);
// ...308+ permissions

// Creates roles (NOT scoped initially):
Bouncer::role()->firstOrCreate(['name' => 'admin']);
Bouncer::role()->firstOrCreate(['name' => 'tech']);

// Assigns permissions to roles:
Bouncer::allow('admin')->to(['clients.*', 'assets.*', ...]);
Bouncer::allow('tech')->to(['clients.view', 'tickets.*', ...]);
```

**Issue**: When you add a new feature (e.g., "HR Breaks"), you must:
1. Update RolesAndPermissionsSeeder with new abilities
2. Manually assign to each role
3. Run seeder (or manually create in production)
4. Repeat for EVERY tenant if they need customization

---

## 🎯 Solution Architecture

### Layer 1: Auto-Discovery (Eliminates Manual Seeder Updates)

**Goal**: When YOU write code (policies, controllers, Livewire), permissions auto-register.

#### Implementation Strategy

**1.1 Policy Scanner** (Highest ROI - You Already Use Policies)
```php
// app/Domains/Security/Scanners/PolicyScanner.php
class PolicyScanner
{
    public function scan(): array
    {
        $permissions = [];
        $policies = $this->findAllPolicies(); // Scan app/Policies
        
        foreach ($policies as $policyClass) {
            $reflection = new ReflectionClass($policyClass);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            
            // Extract resource from policy name: AssetPolicy → assets
            $resource = $this->getResourceFromPolicy($policyClass);
            
            foreach ($methods as $method) {
                if ($this->isPolicyMethod($method->name)) {
                    // viewAny → assets.view
                    // create → assets.create
                    // manageMaintenance → assets.maintenance.manage
                    $permission = $this->methodToPermission($resource, $method->name);
                    
                    $permissions[] = [
                        'name' => $permission,
                        'title' => $this->generateTitle($permission),
                        'category' => $resource,
                        'source' => 'policy',
                        'source_class' => $policyClass,
                        'source_method' => $method->name,
                    ];
                }
            }
        }
        
        return $permissions;
    }
    
    private function methodToPermission(string $resource, string $method): string
    {
        $mapping = [
            'viewAny' => "{$resource}.view",
            'view' => "{$resource}.view",
            'create' => "{$resource}.create",
            'update' => "{$resource}.edit",
            'delete' => "{$resource}.delete",
            'restore' => "{$resource}.restore",
            'forceDelete' => "{$resource}.force-delete",
            'export' => "{$resource}.export",
            'import' => "{$resource}.import",
            // Custom mappings from your policies:
            'manageMaintenance' => "{$resource}.maintenance.manage",
            'viewMaintenance' => "{$resource}.maintenance.view",
            'exportMaintenance' => "{$resource}.maintenance.export",
        ];
        
        return $mapping[$method] ?? "{$resource}.{$method}";
    }
}
```

**Example Output from AssetPolicy.php**:
```php
[
    ['name' => 'assets.view', 'title' => 'View Assets', 'category' => 'assets'],
    ['name' => 'assets.create', 'title' => 'Create Assets', 'category' => 'assets'],
    ['name' => 'assets.edit', 'title' => 'Edit Assets', 'category' => 'assets'],
    ['name' => 'assets.delete', 'title' => 'Delete Assets', 'category' => 'assets'],
    ['name' => 'assets.export', 'title' => 'Export Assets', 'category' => 'assets'],
    ['name' => 'assets.maintenance.manage', 'title' => 'Manage Asset Maintenance'],
    ['name' => 'assets.maintenance.view', 'title' => 'View Asset Maintenance'],
    ['name' => 'assets.remote.execute', 'title' => 'Execute Remote Commands on Assets'],
    // ... 17 total permissions from your AssetPolicy
]
```

**1.2 Controller Scanner** (Secondary - Explicit Permissions)
```php
// app/Domains/Security/Scanners/ControllerScanner.php
class ControllerScanner
{
    public function scan(): array
    {
        $permissions = [];
        
        // Scan all domain controllers
        $controllers = $this->findAllControllers();
        
        foreach ($controllers as $controllerClass) {
            $reflection = new ReflectionClass($controllerClass);
            
            // Look for $this->authorize() calls
            foreach ($reflection->getMethods() as $method) {
                $source = file_get_contents($method->getFileName());
                
                // Match: $this->authorize('viewAny', Role::class)
                // Match: $user->can('settings.edit')
                preg_match_all(
                    "/(?:authorize|can)\('([^']+)'/",
                    $source,
                    $matches
                );
                
                foreach ($matches[1] as $permission) {
                    $permissions[] = [
                        'name' => $permission,
                        'source' => 'controller',
                        'source_class' => $controllerClass,
                    ];
                }
            }
        }
        
        return $permissions;
    }
}
```

**1.3 Livewire Scanner** (Tertiary - Component Permissions)
```php
// app/Domains/Security/Scanners/LivewireScanner.php
class LivewireScanner
{
    public function scan(): array
    {
        // Scan app/Livewire for $this->authorize() or can() checks
        // Similar to ControllerScanner
    }
}
```

**1.4 Artisan Command** (Runs at Deployment)
```php
// app/Domains/Security/Console/Commands/DiscoverPermissionsCommand.php
class DiscoverPermissionsCommand extends Command
{
    protected $signature = 'permissions:discover 
                          {--sync : Sync discovered permissions to database}
                          {--report : Show discovery report}';
    
    public function handle()
    {
        $this->info('🔍 Scanning codebase for permissions...');
        
        // Run all scanners
        $policyPerms = app(PolicyScanner::class)->scan();
        $controllerPerms = app(ControllerScanner::class)->scan();
        $livewirePerms = app(LivewireScanner::class)->scan();
        
        // Merge and deduplicate
        $registry = app(PermissionRegistry::class);
        $discovered = $registry->merge([
            $policyPerms,
            $controllerPerms,
            $livewirePerms,
        ]);
        
        $this->table(
            ['Permission', 'Category', 'Source'],
            $discovered
        );
        
        $this->info("✅ Found {count($discovered)} permissions");
        
        if ($this->option('sync')) {
            $this->syncToDatabase($discovered);
        }
    }
    
    private function syncToDatabase(array $permissions)
    {
        foreach ($permissions as $perm) {
            // Create abilities in Bouncer (NOT company-scoped)
            Bouncer::ability()->firstOrCreate(
                ['name' => $perm['name']],
                [
                    'title' => $perm['title'],
                    'category' => $perm['category'],
                ]
            );
        }
        
        $this->info('✅ Synced to database');
    }
}
```

**1.5 Permission Registry** (Cache Discovery Results)
```php
// app/Domains/Security/Registry/PermissionRegistry.php
class PermissionRegistry
{
    public function getAll(): array
    {
        return Cache::remember('permission_registry', 3600, function () {
            return $this->discover();
        });
    }
    
    public function discover(): array
    {
        // Run scanners and merge results
    }
    
    public function groupByCategory(): array
    {
        return collect($this->getAll())
            ->groupBy('category')
            ->map(function ($perms) {
                return $perms->pluck('name', 'title');
            })
            ->toArray();
    }
}
```

---

### Layer 2: Tenant Role Templates (Company-Specific Defaults)

**Goal**: Each NEW company gets default roles they can customize.

#### Current Problem
```php
// RolesAndPermissionsSeeder.php creates GLOBAL roles
Bouncer::role()->firstOrCreate(['name' => 'admin']);
// This creates ONE admin role shared by all companies (scoped by company_id)
```

#### Solution: Company Observer Hook
```php
// app/Observers/CompanyObserver.php (MODIFY EXISTING)
class CompanyObserver
{
    public function created(Company $company): void
    {
        // Existing quick actions seeder
        $seeder = new QuickActionsSeeder;
        $seeder->createDefaultActionsForCompany($company->id);
        
        // NEW: Create default roles for this company
        app(TenantRoleService::class)->createDefaultRoles($company->id);
    }
}
```

#### Tenant Role Service
```php
// app/Domains/Security/Services/TenantRoleService.php
class TenantRoleService
{
    public function createDefaultRoles(int $companyId): void
    {
        // Set Bouncer scope to this company
        Bouncer::scope()->to($companyId);
        
        // Get role templates from config
        $templates = config('role-templates');
        
        foreach ($templates as $roleName => $config) {
            // Create role scoped to company
            $role = Bouncer::role()->firstOrCreate(
                ['name' => $roleName, 'scope' => $companyId],
                ['title' => $config['title']]
            );
            
            // Assign permissions from template
            foreach ($config['permissions'] as $permission) {
                Bouncer::allow($role)->to($permission);
            }
        }
        
        Bouncer::refresh();
    }
}
```

#### Role Templates Config
```php
// config/role-templates.php
return [
    'admin' => [
        'title' => 'Administrator',
        'description' => 'Full system access',
        'permissions' => [
            'clients.*',
            'assets.*',
            'tickets.*',
            'financial.*',
            'users.*',
            'settings.*',
        ],
    ],
    
    'tech' => [
        'title' => 'Technician',
        'description' => 'Technical support and asset management',
        'permissions' => [
            'clients.view',
            'assets.*',
            'tickets.*',
            'projects.view',
            'projects.manage',
            'knowledge.*',
        ],
    ],
    
    'accountant' => [
        'title' => 'Accountant',
        'description' => 'Financial operations',
        'permissions' => [
            'clients.view',
            'financial.*',
            'contracts.view',
            'reports.financial',
        ],
    ],
];
```

---

### Layer 3: Self-Service UI (Customer Role Management)

**Goal**: Customers manage roles/permissions via UI (no support tickets).

#### Integration Points

**3.1 Existing Role Controller** (Already 80% done!)
```php
// app/Domains/Security/Controllers/RoleController.php
// ✅ You already have:
// - index() → List roles
// - create() → Create role
// - store() → Save role with permissions
// - update() → Edit role permissions
// - destroy() → Delete role

// ✅ Already uses Bouncer correctly:
$role = Bouncer::role()->create(['name' => $request->name]);
Bouncer::allow($role->name)->to($request->abilities);

// 🆕 Add: Clone role functionality
public function clone(Request $request, $roleId)
{
    $sourceRole = Bouncer::role()->findOrFail($roleId);
    
    $newRole = Bouncer::role()->create([
        'name' => $request->name,
        'title' => $request->title,
        'scope' => auth()->user()->company_id,
    ]);
    
    // Copy all permissions
    $permissions = $sourceRole->getAbilities();
    foreach ($permissions as $ability) {
        Bouncer::allow($newRole)->to($ability->name);
    }
    
    return redirect()->route('settings.roles.index');
}
```

**3.2 Livewire Component** (NEW - Permission Matrix UI)
```php
// app/Livewire/Security/PermissionMatrix.php
class PermissionMatrix extends Component
{
    public $roleId;
    public $permissions = [];
    public $selectedPermissions = [];
    
    public function mount($roleId = null)
    {
        $this->roleId = $roleId;
        
        // Get all discovered permissions grouped by category
        $registry = app(PermissionRegistry::class);
        $this->permissions = $registry->groupByCategory();
        
        if ($roleId) {
            $role = Bouncer::role()->find($roleId);
            $this->selectedPermissions = $role->getAbilities()
                ->pluck('name')
                ->toArray();
        }
    }
    
    public function save()
    {
        $role = Bouncer::role()->find($this->roleId);
        
        // Bouncer doesn't have direct sync, so:
        // 1. Remove all current permissions
        $current = $role->getAbilities();
        foreach ($current as $ability) {
            Bouncer::disallow($role)->to($ability->name);
        }
        
        // 2. Add selected permissions
        foreach ($this->selectedPermissions as $permission) {
            Bouncer::allow($role)->to($permission);
        }
        
        Bouncer::refresh();
        session()->flash('success', 'Role updated');
    }
    
    public function render()
    {
        return view('livewire.security.permission-matrix');
    }
}
```

**3.3 Blade View** (NEW - Checkbox Tree)
```blade
<!-- resources/views/livewire/security/permission-matrix.blade.php -->
<div>
    @foreach($permissions as $category => $perms)
        <div class="permission-category mb-6">
            <h3 class="font-bold mb-2">{{ ucfirst($category) }}</h3>
            
            <div class="grid grid-cols-3 gap-4">
                @foreach($perms as $title => $name)
                    <label class="flex items-center">
                        <input 
                            type="checkbox" 
                            wire:model="selectedPermissions" 
                            value="{{ $name }}"
                            class="mr-2"
                        >
                        <span>{{ $title }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    @endforeach
    
    <button wire:click="save" class="btn-primary">
        Save Permissions
    </button>
</div>
```

---

## 🔄 Complete Workflow

### Scenario 1: You Add a New Feature (HR Breaks)

**OLD WAY (Manual Hell)**:
1. Write `HRBreakPolicy.php` with methods
2. Open `RolesAndPermissionsSeeder.php`
3. Add `'hr.breaks.view'`, `'hr.breaks.create'`, etc.
4. Add to each role's permissions array
5. Run seeder in dev
6. Deploy to production
7. Run seeder in production (or manually create)
8. Support tickets from customers wanting different permissions

**NEW WAY (Automatic)**:
1. Write `HRBreakPolicy.php` with methods:
```php
class HRBreakPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('hr.breaks.view');
    }
    
    public function create(User $user): bool
    {
        return $user->can('hr.breaks.create');
    }
    
    public function approve(User $user, Break $break): bool
    {
        return $user->can('hr.breaks.approve');
    }
}
```

2. Deploy code

3. Deployment runs: `php artisan permissions:discover --sync`
   - Scans `HRBreakPolicy`
   - Finds: `viewAny`, `create`, `approve`
   - Auto-creates abilities: `hr.breaks.view`, `hr.breaks.create`, `hr.breaks.approve`

4. Optionally update `config/role-templates.php` to add to default roles:
```php
'admin' => [
    'permissions' => [
        // ... existing
        'hr.breaks.*', // Add this one line
    ],
],
```

5. Existing companies:
   - Permissions available immediately
   - Admins see new permissions in UI
   - Can assign to any role via checkbox

6. New companies:
   - Get `hr.breaks.*` in admin role automatically

**Result**: Zero manual seeder updates. Zero support tickets.

---

### Scenario 2: New Company Onboarded

**Flow**:
```
1. Company::create(['name' => 'Acme MSP'])
   ↓
2. CompanyObserver::created() fires
   ↓
3. TenantRoleService::createDefaultRoles($companyId)
   ↓
4. Reads config/role-templates.php
   ↓
5. Creates roles scoped to company_id=123:
   - admin (with clients.*, assets.*, etc.)
   - tech (with clients.view, tickets.*, etc.)
   - accountant (with financial.*, etc.)
   ↓
6. First admin user created
   ↓
7. User::assignRole('admin')
   ↓
8. User logs in, can customize roles via UI
```

---

### Scenario 3: Customer Wants Custom Role

**UI Flow**:
```
1. Admin navigates to Settings → Roles
2. Clicks "Create Custom Role"
3. Name: "Help Desk L1"
4. Sees permission matrix (checkboxes grouped by category):
   
   Clients:
   ☑ View Clients
   ☐ Create Clients
   ☐ Edit Clients
   
   Tickets:
   ☑ View Tickets
   ☑ Create Tickets
   ☑ Edit Tickets
   ☐ Delete Tickets
   ☑ Assign Tickets
   ☐ Close Tickets
   
   Assets:
   ☑ View Assets
   ☐ Edit Assets
   
5. Clicks Save
6. RoleController::store() creates role with Bouncer
7. Role available for assignment to users
```

**Alternative: Clone Existing Role**
```
1. Admin clicks "Clone" on "Technician" role
2. Name: "Junior Technician"
3. Uncheck: assets.delete, tickets.delete
4. Save
5. Done
```

---

## 📁 Files to Create/Modify

### NEW Files (18 files)

```
app/Domains/Security/
├── Attributes/
│   └── RequiresPermission.php          [Optional enhancement]
├── Registry/
│   ├── PermissionRegistry.php          [Core - caching/merging]
│   └── PermissionDiscovery.php         [Core - orchestration]
├── Scanners/
│   ├── PolicyScanner.php               [Core - 80% of permissions]
│   ├── ControllerScanner.php           [Secondary]
│   └── LivewireScanner.php             [Secondary]
├── Services/
│   └── TenantRoleService.php           [Core - company role setup]
└── Console/Commands/
    └── DiscoverPermissionsCommand.php  [Core - deployment command]

app/Livewire/Security/
├── PermissionMatrix.php                [UI for permission selection]
└── RoleCloner.php                      [UI for cloning roles]

resources/views/livewire/security/
├── permission-matrix.blade.php         [Checkbox tree UI]
└── role-cloner.blade.php               [Clone dialog]

config/
└── role-templates.php                  [Role definitions]

database/migrations/
└── 2024_xx_xx_add_category_to_abilities.php [Optional metadata]

tests/Feature/Security/
├── PermissionDiscoveryTest.php
└── TenantRoleTest.php
```

### MODIFIED Files (2 files)

```
app/Observers/
└── CompanyObserver.php                 [Add TenantRoleService call]

app/Domains/Security/Controllers/
└── RoleController.php                  [Add clone() method]
```

---

## 🚀 Implementation Phases

### Phase 1: Auto-Discovery Foundation
**Goal**: Stop manually updating seeders

**Tasks**:
1. ✅ Create `PolicyScanner.php` (scan 26 policies)
2. ✅ Create `PermissionRegistry.php` (merge/cache results)
3. ✅ Create `DiscoverPermissionsCommand.php`
4. ✅ Test: `php artisan permissions:discover`
5. ⏳ Add to deployment script

**Success**: Run command, see all permissions from policies

### Phase 2: Tenant Role Templates
**Goal**: New companies get default roles

**Tasks**:
1. ⏳ Create `config/role-templates.php`
2. ⏳ Create `TenantRoleService.php`
3. ⏳ Modify `CompanyObserver.php`
4. ⏳ Test: Create company, check roles created

**Success**: New company has admin/tech/accountant roles

### Phase 3: Self-Service UI
**Goal**: Customers manage permissions

**Tasks**:
1. ✅ **ALREADY EXISTS** - `PermissionMatrix.php` Livewire component
2. ✅ **ALREADY EXISTS** - Checkbox tree view with Flux UI
3. ✅ **ALREADY EXISTS** - `RolesList.php` for role CRUD
4. ⏳ Add `clone()` to `RoleController.php` (optional)
5. ⏳ Connect auto-discovery to existing UI

**Success**: Admin can create/edit/clone roles via UI - **ALREADY WORKING!**

### Phase 4: Enhancement (Optional)
**Goal**: Additional scanners + polish

**Tasks**:
1. ⏳ Add `ControllerScanner.php`
2. ⏳ Add `LivewireScanner.php`
3. ⏳ Add `RequiresPermission` attribute (annotations)
4. ⏳ Performance testing

---

## ⚠️ Migration Strategy

**You said "don't worry about migrating existing tenants"**, but here's how it WOULD work:

### Option A: No Migration (Safest)
- Auto-discovery creates NEW permissions
- Existing company roles untouched
- New permissions available but not assigned
- Admins assign via UI as needed

### Option B: Update Existing Roles (Optional)
```php
php artisan permissions:discover --sync
php artisan roles:sync-templates  // NEW command

// Syncs config/role-templates.php to ALL existing companies
// Only adds NEW permissions, doesn't remove existing
```

**Recommendation**: Option A. Let each company customize.

---

## 🎯 Success Metrics

**Before**:
- ❌ Add feature → update 7 seeders manually
- ❌ Customer wants custom role → submit support ticket
- ❌ Deploy → pray seeders run correctly
- ❌ 308+ permissions maintained by hand

**After**:
- ✅ Add feature → permissions auto-discovered
- ✅ Customer creates custom role → self-service UI
- ✅ Deploy → `permissions:discover` runs automatically
- ✅ Permissions discovered from code (26 policies = ~200+ permissions)

---

## 🔍 Integration Examples

### Example 1: Existing Policy (AssetPolicy.php)
**Current Code** (no changes needed):
```php
public function view(User $user, Asset $asset): bool
{
    return $user->can('assets.view') && $this->sameCompany($user, $asset);
}

public function manageMaintenance(User $user, Asset $asset): bool
{
    return $user->can('assets.maintenance.manage') && $this->sameCompany($user, $asset);
}
```

**Auto-Discovery Output**:
```
✅ Discovered: assets.view (from AssetPolicy::view)
✅ Discovered: assets.create (from AssetPolicy::create)
✅ Discovered: assets.maintenance.manage (from AssetPolicy::manageMaintenance)
...
```

### Example 2: Existing Controller (RoleController.php)
**Current Code**:
```php
public function index()
{
    $this->authorize('viewAny', Role::class);
    // ...
}
```

**Auto-Discovery**: Scans for `$this->authorize()` calls, finds `viewAny`

### Example 3: Livewire (AssetsList.php)
**Current Code**:
```php
public function deleteAsset($assetId)
{
    if (auth()->user()->can('delete', $asset)) {
        $asset->delete();
    }
}
```

**Auto-Discovery**: Scans for `can()` calls

---

## 🛠️ Deployment Integration

### Add to `.forge-deploy` or CI/CD:
```bash
cd /opt/nestogy

# Run auto-discovery
php artisan permissions:discover --sync

# Cache results
php artisan cache:clear
php artisan config:cache
```

---

## 💡 Future Enhancements

1. **Attribute-Based Permissions** (Laravel-style)
```php
#[RequiresPermission('assets.view')]
public function index() { }
```

2. **Permission Descriptions** (Auto-generate docs)
```
php artisan permissions:document
// Generates: docs/permissions.md
```

3. **Permission Analytics**
- Which permissions are used most?
- Which permissions are never assigned?
- Orphaned permissions cleanup

4. **Multi-Level Wildcards**
```php
// Already supported in your PermissionService!
'assets.*' → matches 'assets.view', 'assets.create'
'assets.maintenance.*' → matches 'assets.maintenance.view'
```

---

## 📊 Estimated Effort

| Phase | Tasks | Complexity | Time |
|-------|-------|------------|------|
| Phase 1: Auto-Discovery | PolicyScanner + Registry + Command | Medium | 2-3 days |
| Phase 2: Tenant Templates | Config + Service + Observer hook | Low | 1-2 days |
| Phase 3: Self-Service UI | Livewire + Views + Controller | Medium | 2-3 days |
| Phase 4: Polish | Additional scanners + docs | Low | 1-2 days |
| **Total** | | | **6-10 days** |

---

## 🎉 Bottom Line

You're **80% there already**:
- ✅ Bouncer multi-tenant working
- ✅ Wildcard support implemented
- ✅ Policies well-structured
- ✅ RoleController functional
- ✅ CompanyObserver pattern exists

**Missing pieces**:
- 🆕 PolicyScanner (auto-discovers from your 26 policies)
- 🆕 TenantRoleService (creates roles on company creation)
- 🆕 PermissionMatrix UI (checkbox tree for role permissions)
- 🆕 `php artisan permissions:discover` command

**Result**: Add features → permissions auto-register → zero manual seeder updates → customers self-manage roles.

---

## 🎉 UPDATE: You Already Have the UI!

Great news! You already have:
- ✅ **PermissionMatrix.php** - Full permission matrix with checkbox grid
- ✅ **RolesList.php** - Role management with create/edit
- ✅ **Flux UI** - Beautiful checkbox tree interface
- ✅ **Live updates** - Livewire real-time permission toggling
- ✅ **Category grouping** - Permissions organized by domain
- ✅ **Audit logging** - Permission changes tracked

**What's needed**: Just the backend auto-discovery to populate `Bouncer::ability()` automatically!

This simplifies the implementation to **2 main phases**:
1. Phase 1: Auto-Discovery Backend (PolicyScanner + Command)
2. Phase 2: Tenant Role Templates (Observer hook)

Your existing UI will automatically show all discovered permissions!
