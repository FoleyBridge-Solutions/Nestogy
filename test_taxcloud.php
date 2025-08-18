<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\TaxEngine\TaxCloudApiClient;
use Illuminate\Support\Facades\App;

// Boot Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "Testing TaxCloud API Integration...\n\n";
    
    // Test credentials from environment
    $loginId = env('TAXCLOUD_API_LOGIN_ID');
    $apiKey = env('TAXCLOUD_API_KEY');
    $customerId = env('TAXCLOUD_CUSTOMER_ID');
    
    echo "Login ID: " . ($loginId ? "✓ Configured" : "✗ Missing") . "\n";
    echo "API Key: " . ($apiKey ? "✓ Configured" : "✗ Missing") . "\n";
    echo "Customer ID: " . ($customerId ? "✓ Configured" : "✗ Missing") . "\n\n";
    
    if (!$loginId || !$apiKey || !$customerId) {
        echo "❌ Missing TaxCloud credentials in .env file\n";
        exit(1);
    }
    
    // Initialize TaxCloud client
    $taxCloud = new TaxCloudApiClient(1); // Company ID 1
    
    // Test address verification
    echo "Testing address verification...\n";
    $testAddress = [
        'Address1' => '1234 Main St',
        'City' => 'Los Angeles',
        'State' => 'CA',
        'Zip5' => '90210'
    ];
    
    $verifiedAddress = $taxCloud->verifyAddress($testAddress);
    echo "Address verification: " . ($verifiedAddress['success'] ? "✓ Success" : "✗ Failed") . "\n";
    
    if ($verifiedAddress['success']) {
        echo "  Verified address: " . $verifiedAddress['verified_address']['Address1'] . ", " . 
             $verifiedAddress['verified_address']['City'] . ", " . 
             $verifiedAddress['verified_address']['State'] . " " . 
             $verifiedAddress['verified_address']['Zip5'] . "\n";
    }
    
    echo "\n";
    
    // Test tax calculation
    echo "Testing tax calculation for equipment sales...\n";
    $cartItems = [
        [
            'ItemID' => 'EQUIP-001',
            'TIC' => '00000', // General merchandise
            'Price' => 2490.00,
            'Qty' => 1
        ]
    ];
    
    $taxResult = $taxCloud->lookupTax($testAddress, $cartItems);
    echo "Tax calculation: " . ($taxResult['success'] ? "✓ Success" : "✗ Failed") . "\n";
    
    if ($taxResult['success']) {
        echo "  Subtotal: $" . number_format($taxResult['subtotal'], 2) . "\n";
        echo "  Tax Amount: $" . number_format($taxResult['tax_amount'], 2) . "\n";
        echo "  Total: $" . number_format($taxResult['total'], 2) . "\n";
        echo "  Tax Rate: " . number_format($taxResult['tax_rate'], 2) . "%\n";
        
        if (isset($taxResult['tax_details'])) {
            echo "  Jurisdiction breakdown:\n";
            foreach ($taxResult['tax_details'] as $detail) {
                echo "    - " . $detail['authority'] . ": $" . number_format($detail['tax_amount'], 2) . 
                     " (" . number_format($detail['rate'], 2) . "%)\n";
            }
        }
    } else {
        echo "  Error: " . ($taxResult['error'] ?? 'Unknown error') . "\n";
    }
    
    echo "\n✅ TaxCloud API test completed!\n";
    
} catch (Exception $e) {
    echo "❌ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}