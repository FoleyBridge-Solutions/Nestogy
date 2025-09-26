# Production Deployment Commands

Run these commands on your production server after pulling the latest changes:

## 1. Pull Latest Code
```bash
git pull origin main
```

## 2. Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

## 3. Run Database Migrations
```bash
php artisan migrate --force
```

## 4. Run Seeders for New Features
```bash
# Create default quick actions for existing companies
php artisan db:seed --class=QuickActionsSeeder --force

# Create mail settings for existing companies (if not already present)
php artisan db:seed --class=CompanyMailSettingsSeeder --force
```

## 5. Clear and Optimize Caches
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize
```

## 6. Restart Queue Workers (if using queues)
```bash
php artisan queue:restart
```

## 7. Set Permissions (if needed)
```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## Important Notes:

### New Database Tables Created:
- `custom_quick_actions` - Stores user-created quick action buttons
- `quick_action_favorites` - Tracks user's favorite actions
- `mail_queue` - Email queue for multi-tenant mail system
- `company_mail_settings` - Company-specific mail configurations
- `settings_configurations` - Unified settings storage

### Modified Tables:
- `contacts` - Added portal invitation fields
- `companies` - Now has observer that auto-creates quick actions

### Features That Need Configuration:
1. **Custom Quick Actions** - Already seeded with defaults, users can create their own
2. **Mail Settings** - Each company can configure their own SMTP/mail settings in Settings > Communication
3. **Portal Invitations** - Contacts can be invited via their detail page

### Rollback Plan (if needed):
```bash
php artisan migrate:rollback --step=5
git reset --hard HEAD~1
composer install
npm run build
php artisan optimize
```