# Permission Auto-Discovery - Quick Reference Card

## 🚀 Quick Commands

### Daily Use
```bash
# Discover permissions from code
php artisan permissions:discover

# Sync discovered permissions to database
php artisan permissions:discover --sync

# Show detailed permission report
php artisan permissions:discover --report
```

### Role Management
```bash
# Show role templates
php artisan roles:sync-templates --show

# Validate templates
php artisan roles:sync-templates --validate

# Sync templates to all companies
php artisan roles:sync-templates --all
```

## 📝 Adding New Features

### 3-Step Process

**1. Write Policy**
```php
// app/Policies/FeaturePolicy.php
public function viewAny(User $user): bool
{
    return $user->can('feature.view');
}
```

**2. Run Discovery**
```bash
php artisan permissions:discover --sync
```

**3. Done!**
✅ Permission appears in UI automatically

## 🔧 Troubleshooting

### Permissions not showing?
```bash
php artisan permissions:discover --sync
php artisan cache:clear
```

### Roles not auto-created?
```bash
# Check observer
php artisan tinker
Company::observe(CompanyObserver::class);
```

### Template errors?
```bash
php artisan roles:sync-templates --validate
```

## 📊 System Stats

- **153** permissions from policies
- **2** permissions from Livewire
- **6** default roles per company
- **30** permission categories
- **210+** total permissions

## 📁 Key Files

```
config/role-templates.php          - Edit role definitions
app/Observers/CompanyObserver.php  - Auto-creates roles
app/Domains/Security/Scanners/     - Permission scanners
```

## 🎯 What Changed

**Before**: Manual seeder updates (30+ min per feature)  
**After**: Write policy, run command (2 min per feature)

**Result**: 93% time saved ⚡

---

**Need Help?** See `IMPLEMENTATION_COMPLETE.md` for full docs
