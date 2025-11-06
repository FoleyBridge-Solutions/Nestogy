# Laravel Cloud Deployment Configuration

## 🎯 What You Need to Add

In your **Laravel Cloud Dashboard** → **Settings** → **Deployments**, configure:

### Build Commands
```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

### Deploy Commands
```bash
php artisan migrate --force
php artisan permissions:discover --sync
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan optimize
```

---

## 📋 Step-by-Step Setup

### 1. Navigate to Laravel Cloud Dashboard
- Go to your project
- Select your environment (production/staging)
- Click **Settings** → **Deployments**

### 2. Configure Build Commands
In the **Build Commands** section, add:
```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

### 3. Configure Deploy Commands
In the **Deploy Commands** section, add:
```bash
php artisan migrate --force
php artisan permissions:discover --sync
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan optimize
```

### 4. Save Configuration
- Click **Save & Deploy** to test immediately
- Or click **Save** and deploy later

---

## 🔍 What Each Command Does

### Build Phase
| Command | Purpose |
|---------|---------|
| `composer install --no-dev --optimize-autoloader` | Install PHP dependencies optimized for production |
| `npm ci && npm run build` | Build frontend assets |

### Deploy Phase
| Command | Purpose |
|---------|---------|
| `php artisan migrate --force` | Run database migrations without prompts |
| `php artisan permissions:discover --sync` | ✨ **NEW**: Auto-discover permissions from your code |
| `php artisan config:cache` | Cache configuration files |
| `php artisan route:cache` | Cache route definitions |
| `php artisan view:cache` | Pre-compile Blade templates |
| `php artisan queue:restart` | Restart queue workers (if using queues) |
| `php artisan optimize` | Run all optimization commands |

---

## 🚀 Deployment Flow

When you push code to your branch:

```
1. Push code to Git
   ↓
2. Laravel Cloud detects push
   ↓
3. BUILD PHASE (runs in Docker)
   ├── Installs composer dependencies
   └── Builds frontend assets
   ↓
4. DEPLOY PHASE (zero downtime)
   ├── Runs migrations
   ├── Auto-discovers permissions ✨
   ├── Caches config/routes/views
   └── Restarts queue workers
   ↓
5. New deployment goes live
   Old deployment gracefully terminates
```

---

## ✅ Verify It's Working

After your next deployment, check the **Deployment Logs** in Laravel Cloud:

You should see:
```
🔍 Discovering permissions from code...
📊 Discovery Statistics:
+-------------------+-------+
| Total Permissions | 153   |
| From Policies     | 153   |
| ...
✅ Sync complete!
```

---

## 🔧 Optional: Deploy Hooks

If you want to deploy from CI/CD:

1. Enable **Deploy Hooks** in Settings → Deployments
2. Copy the provided URL
3. Add to GitHub Actions:

```yaml
# .github/workflows/deploy.yml
name: Deploy to Laravel Cloud

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Deploy
        run: |
          curl -X POST "${{ secrets.LARAVEL_CLOUD_DEPLOY_HOOK }}?commit_hash=${{ github.sha }}"
```

---

## 🎯 What This Achieves

**Before Every Deployment:**
- ❌ Had to manually update permission seeders
- ❌ Risk of forgetting new permissions
- ❌ Production and dev could get out of sync

**After Every Deployment:**
- ✅ Permissions auto-discovered from your code
- ✅ Database automatically synced
- ✅ Zero manual intervention needed
- ✅ Permission Matrix UI always up-to-date

---

## 💡 Pro Tips

### 1. Test in Staging First
Configure staging environment with these commands first, test thoroughly, then apply to production.

### 2. Monitor First Deployment
Watch the deployment logs carefully on your first deploy with the new commands to ensure everything runs smoothly.

### 3. Rollback Plan
If something goes wrong, Laravel Cloud allows you to redeploy previous deployments from the dashboard.

### 4. Environment Variables
Make sure all required environment variables are set in Laravel Cloud dashboard (no .env file needed).

---

## 📊 Deployment Time Impact

Adding `permissions:discover --sync` adds approximately **2-5 seconds** to your deployment:
- Scans 26 policies: ~1 second
- Scans controllers: ~1 second  
- Syncs to database: ~1-3 seconds
- **Total overhead**: 2-5 seconds (negligible)

---

## 🆘 Troubleshooting

### Command not found
**Error**: `permissions:discover: command not found`

**Fix**: Make sure you've committed and pushed the new command files:
```bash
git add app/Console/Commands/DiscoverPermissionsCommand.php
git add app/Domains/Security/Scanners/
git commit -m "Add permission auto-discovery"
git push
```

### Permissions not syncing
**Error**: Permissions discovered but not showing in UI

**Fix**: Clear all caches after deployment:
```bash
php artisan cache:clear
php artisan config:clear
```

### Build fails
**Error**: Composer dependencies fail

**Fix**: Ensure all new files are in your `composer.json` autoload:
```json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "Database\\Factories\\": "database/factories/",
        "Database\\Seeders\\": "database/seeders/"
    }
}
```

Then run: `composer dump-autoload`

---

## 📝 Summary

**Add to Laravel Cloud Dashboard:**

**Build Commands:**
```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

**Deploy Commands:**
```bash
php artisan migrate --force
php artisan permissions:discover --sync
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan optimize
```

**That's it!** Every deployment will now automatically discover and sync permissions.

---

**Questions?** Check the logs in Laravel Cloud dashboard under Deployments → [Your Deployment] → Logs
