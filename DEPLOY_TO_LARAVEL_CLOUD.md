# 🚀 Deploy to Laravel Cloud - Quick Setup

## In Laravel Cloud Dashboard

**Settings → Deployments**

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

## That's It!

The **one new line** you're adding is:
```bash
php artisan permissions:discover --sync
```

This automatically:
- ✅ Scans your policies for permissions
- ✅ Syncs them to the database
- ✅ Makes them available in your PermissionMatrix UI
- ✅ Adds ~2-5 seconds to deployment time

---

## Verify It Works

After deploying, check **Deployments → Logs** in Laravel Cloud.

You should see:
```
🔍 Discovering permissions from code...
✅ Sync complete!
```

Done! 🎉
