# Permission Auto-Discovery - Quick Start

## ✅ Phase 1 Complete: Auto-Discovery is Working!

### What You Can Do NOW

1. **View all auto-discovered permissions in your UI**:
   - Navigate to: Settings → Roles (or wherever your PermissionMatrix is)
   - You'll see 210 permissions organized by category
   - 153 were auto-discovered from your 26 policies!

2. **Add a new feature and see permissions auto-appear**:
   ```bash
   # 1. Write your policy with can() checks
   # 2. Run discovery:
   php artisan permissions:discover --sync
   
   # 3. Done! Permission appears in your PermissionMatrix UI
   ```

3. **No more manual seeder updates**:
   - ❌ Before: Update RolesAndPermissionsSeeder.php manually
   - ✅ Now: Write policy → run command → done

### Commands

```bash
# See discovered permissions (read-only)
php artisan permissions:discover

# See detailed report
php artisan permissions:discover --report

# Preview what would be synced
php artisan permissions:discover --dry-run

# Sync to database (run this after adding new policies)
php artisan permissions:discover --sync
```

### How It Works

```
Your Policy File:
┌────────────────────────────────────────┐
│ public function approve(User $user) {  │
│   return $user->can('hr.breaks.approve');│
│ }                                       │
└────────────────────────────────────────┘
                 ↓
    php artisan permissions:discover --sync
                 ↓
┌────────────────────────────────────────┐
│ Database (bouncer_abilities):          │
│ - Name: hr.breaks.approve              │
│ - Title: Approve HR Breaks             │
│ - Category: hr                          │
└────────────────────────────────────────┘
                 ↓
┌────────────────────────────────────────┐
│ Your PermissionMatrix UI:              │
│ ☐ Approve HR Breaks                    │
│   (Shows automatically!)                │
└────────────────────────────────────────┘
```

### Deployment

Add to your `.forge-deploy` file or CI/CD:
```bash
php artisan permissions:discover --sync
php artisan config:cache
```

### Summary

**You're working from POLICIES, not policy methods.**

The scanner finds permission strings like `'assets.view'` from your `$user->can()` calls. This is perfect because:
- ✅ It discovers what you're actually using
- ✅ Works with your custom methods (`manageMaintenance`, etc.)
- ✅ Handles nested permissions (`assets.maintenance.view`)
- ✅ No guessing needed

**Result**: 153 permissions discovered, 107 created, 0 manual seeder updates needed! 🎉
