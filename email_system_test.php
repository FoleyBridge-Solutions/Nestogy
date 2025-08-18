<?php

/**
 * Comprehensive Email System Test Script
 * 
 * This script tests all email-dependent features in Nestogy
 * Run with: php artisan tinker < email_system_test.php
 */

echo "🔍 NESTOGY EMAIL SYSTEM VERIFICATION\n";
echo "====================================\n\n";

// Test 1: Basic Email Configuration
echo "1. Testing Email Configuration...\n";
try {
    $mailConfig = config('mail');
    echo "   ✓ Mail driver: " . $mailConfig['default'] . "\n";
    echo "   ✓ SMTP Host: " . config('mail.mailers.smtp.host') . "\n";
    echo "   ✓ SMTP Port: " . config('mail.mailers.smtp.port') . "\n";
    echo "   ✓ From Address: " . config('mail.from.address') . "\n";
} catch (Exception $e) {
    echo "   ✗ Configuration Error: " . $e->getMessage() . "\n";
}

// Test 2: EmailService Basic Functionality
echo "\n2. Testing EmailService...\n";
try {
    $emailService = app(App\Services\EmailService::class);
    echo "   ✓ EmailService instantiated successfully\n";
    
    // Test basic email sending
    $testResult = $emailService->testConnection();
    echo "   ✓ Connection test: " . ($testResult ? "PASSED" : "FAILED") . "\n";
    
} catch (Exception $e) {
    echo "   ✗ EmailService Error: " . $e->getMessage() . "\n";
}

// Test 3: Quote Email System
echo "\n3. Testing Quote Email System...\n";
try {
    // Get a sample quote to test with
    $quote = App\Models\Quote::with('client')->first();
    if ($quote && $quote->client) {
        echo "   ✓ Found test quote: #" . $quote->id . "\n";
        echo "   ✓ Client: " . $quote->client->name . "\n";
        echo "   ✓ Client Email: " . ($quote->client->email ?? 'NOT SET') . "\n";
        
        if ($quote->client->email) {
            echo "   → Quote email system is ready for testing\n";
        } else {
            echo "   ⚠ Warning: Client has no email address set\n";
        }
    } else {
        echo "   ⚠ No quotes found for testing\n";
    }
} catch (Exception $e) {
    echo "   ✗ Quote Email Error: " . $e->getMessage() . "\n";
}

// Test 4: Invoice Email System  
echo "\n4. Testing Invoice Email System...\n";
try {
    $invoice = App\Models\Invoice::with('client')->first();
    if ($invoice && $invoice->client) {
        echo "   ✓ Found test invoice: #" . $invoice->id . "\n";
        echo "   ✓ Client: " . $invoice->client->name . "\n";
        echo "   ✓ Client Email: " . ($invoice->client->email ?? 'NOT SET') . "\n";
    } else {
        echo "   ⚠ No invoices found for testing\n";
    }
} catch (Exception $e) {
    echo "   ✗ Invoice Email Error: " . $e->getMessage() . "\n";
}

// Test 5: Notification System
echo "\n5. Testing Notification System...\n";
try {
    $notificationDispatcher = app(App\Services\Notification\NotificationDispatcher::class);
    echo "   ✓ NotificationDispatcher instantiated\n";
    
    $emailChannel = app(App\Services\Notification\Channels\EmailChannel::class);
    echo "   ✓ EmailChannel instantiated\n";
    
} catch (Exception $e) {
    echo "   ✗ Notification System Error: " . $e->getMessage() . "\n";
}

// Test 6: Marketing Email System
echo "\n6. Testing Marketing Email System...\n";
try {
    $campaignEmailService = app(App\Domains\Marketing\Services\CampaignEmailService::class);
    echo "   ✓ CampaignEmailService instantiated\n";
} catch (Exception $e) {
    echo "   ✗ Marketing Email Error: " . $e->getMessage() . "\n";
}

// Test 7: User Authentication Emails
echo "\n7. Testing User Authentication Emails...\n";
try {
    $user = App\Models\User::first();
    if ($user) {
        echo "   ✓ Found test user: " . $user->name . "\n";
        echo "   ✓ User Email: " . $user->email . "\n";
        echo "   ✓ Email Verified: " . ($user->email_verified_at ? 'YES' : 'NO') . "\n";
    } else {
        echo "   ⚠ No users found for testing\n";
    }
} catch (Exception $e) {
    echo "   ✗ User Auth Email Error: " . $e->getMessage() . "\n";
}

// Test 8: Settings Configuration
echo "\n8. Testing Email Settings Storage...\n";
try {
    $company = App\Models\Company::first();
    if ($company && $company->setting) {
        $setting = $company->setting;
        echo "   ✓ Company: " . $company->name . "\n";
        echo "   ✓ SMTP Host: " . ($setting->smtp_host ?? 'NOT SET') . "\n";
        echo "   ✓ SMTP Username: " . ($setting->smtp_username ?? 'NOT SET') . "\n";
        echo "   ✓ SMTP Password: " . (!empty($setting->smtp_password) ? 'SET' : 'NOT SET') . "\n";
        echo "   ✓ From Email: " . ($setting->mail_from_email ?? 'NOT SET') . "\n";
    } else {
        echo "   ⚠ No company settings found\n";
    }
} catch (Exception $e) {
    echo "   ✗ Settings Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "EMAIL SYSTEM VERIFICATION COMPLETE\n";
echo str_repeat("=", 50) . "\n";

// Instructions for manual testing
echo "\n📋 MANUAL TESTING RECOMMENDATIONS:\n";
echo "1. Send a test quote email to a real email address\n";
echo "2. Send a test invoice email to verify formatting\n";
echo "3. Create a test ticket to verify notifications\n";
echo "4. Test password reset email functionality\n";
echo "5. Test user invitation emails\n";
echo "6. Test marketing campaign emails (if used)\n";

echo "\n🔧 NEXT STEPS:\n";
echo "1. Run: php artisan queue:work (if using queued emails)\n";
echo "2. Check email logs: tail -f storage/logs/laravel.log\n";
echo "3. Monitor email delivery in your SMTP service dashboard\n";

echo "\n";