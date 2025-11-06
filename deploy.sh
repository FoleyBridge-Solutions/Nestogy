#!/usr/bin/env bash
set -e

echo "🚀 Laravel Cloud Deployment Started..."

# Install dependencies
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Run migrations
php artisan migrate --force

# ✨ Auto-discover and sync permissions
php artisan permissions:discover --sync

# Cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers
php artisan queue:restart

echo "✅ Deployment Complete!"
