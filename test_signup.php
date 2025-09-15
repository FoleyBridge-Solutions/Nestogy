<?php

// Test script to simulate signup process
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Start the application
$app->boot();

echo "Testing signup process...\n";
echo "Memory usage: " . memory_get_usage(true) . " bytes\n";

// Test basic database connection
try {
    $planCount = \App\Models\SubscriptionPlan::count();
    echo "✓ Database connected - found {$planCount} subscription plans\n";
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test Stripe configuration
echo "Stripe Key: " . substr(config('services.stripe.key'), 0, 20) . "...\n";
echo "Stripe Secret: " . substr(config('services.stripe.secret'), 0, 20) . "...\n";

// Test Stripe SDK initialization
try {
    \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
    echo "✓ Stripe SDK initialized\n";
} catch (Exception $e) {
    echo "✗ Stripe error: " . $e->getMessage() . "\n";
    exit(1);
}

// Simulate the signup data
$testData = [
    'company_name' => 'Test Company ' . time(),
    'company_email' => 'test' . time() . '@example.com',
    'company_phone' => '555-123-4567',
    'company_website' => 'https://test.com',
    'admin_name' => 'Test Admin',
    'admin_email' => 'admin' . time() . '@example.com',
    'admin_password' => 'password123',
    'admin_password_confirmation' => 'password123',
    'subscription_plan_id' => 1, // Free plan
    'payment_method_id' => 'pm_card_visa', // Test payment method
    'terms_accepted' => 1,
];

echo "Test data prepared\n";
echo "Memory usage: " . memory_get_usage(true) . " bytes\n";

echo "\nTest completed successfully!\n";