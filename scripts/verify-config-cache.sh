#!/bin/bash

echo "Testing Laravel config caching..."

# Clear existing caches
php artisan config:clear
php artisan cache:clear

# Cache the configuration
echo "Caching configuration..."
php artisan config:cache

if [ $? -eq 0 ]; then
    echo "✅ Config cached successfully!"
    
    # Test that app still works with cached config
    echo "Testing application with cached config..."
    php artisan tinker --execute="echo 'App name: ' . config('app.name');"
    
    if [ $? -eq 0 ]; then
        echo "✅ Application works with cached config!"
    else
        echo "❌ Application failed with cached config"
        exit 1
    fi
else
    echo "❌ Config caching failed!"
    echo "This usually means there are env() calls outside of config files."
    exit 1
fi

echo "Config caching verification complete!"