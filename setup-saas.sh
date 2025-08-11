#!/bin/bash

# Nestogy SaaS Setup Script
# This script helps set up the SaaS functionality for Nestogy

echo "🚀 Setting up Nestogy SaaS functionality..."
echo ""

# Check if .env file exists
if [ ! -f .env ]; then
    echo "❌ .env file not found. Please copy .env.example to .env first."
    exit 1
fi

# Check if composer dependencies are installed
if [ ! -d "vendor" ]; then
    echo "📦 Installing Composer dependencies..."
    composer install
fi

# Check if npm dependencies are installed
if [ ! -d "node_modules" ]; then
    echo "📦 Installing NPM dependencies..."
    npm install
fi

# Run database migrations
echo "🗄️  Running database migrations..."
php artisan migrate

# Seed subscription plans
echo "📊 Seeding subscription plans..."
php artisan db:seed --class=SubscriptionPlanSeeder

# Create storage link
echo "🔗 Creating storage link..."
php artisan storage:link

# Generate application key if not set
if grep -q "APP_KEY=$" .env; then
    echo "🔑 Generating application key..."
    php artisan key:generate
fi

# Clear and cache configurations
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear

echo "✅ SaaS setup complete!"
echo ""
echo "📝 Next steps:"
echo "1. Set up your Stripe keys in .env file:"
echo "   STRIPE_KEY=pk_test_your_publishable_key"
echo "   STRIPE_SECRET=sk_test_your_secret_key"
echo "   STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret"
echo ""
echo "2. Create subscription plans in your Stripe dashboard"
echo ""
echo "3. Set up a webhook endpoint in Stripe dashboard:"
echo "   URL: https://yourdomain.com/webhooks/stripe"
echo "   Events: customer.*, subscription.*, invoice.*, payment_method.*"
echo ""
echo "4. Configure your email settings for trial notifications"
echo ""
echo "5. Set up queue workers for background jobs:"
echo "   php artisan queue:work --daemon"
echo ""
echo "🎉 Your SaaS platform is ready to go!"