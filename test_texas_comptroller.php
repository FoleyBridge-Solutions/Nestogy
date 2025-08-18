<?php

require_once __DIR__ . '/vendor/autoload.php';

// Boot Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\TaxEngine\TexasComptrollerApiClient;

echo "Testing Texas Comptroller API for Real Tax Rates...\n\n";

try {
    // Initialize the Texas Comptroller client
    $texasClient = new TexasComptrollerApiClient(1);
    
    // Check configuration
    $config = $texasClient->getConfigurationStatus();
    echo "Configuration Status:\n";
    foreach ($config as $key => $value) {
        echo "  {$key}: {$value}\n";
    }
    echo "\n";
    
    // Test the connection first
    echo "Testing connection with San Antonio address...\n";
    $connectionTest = $texasClient->testConnection();
    echo "Connection: " . ($connectionTest['success'] ? "✅ Success" : "❌ Failed") . "\n";
    
    if (!$connectionTest['success']) {
        echo "Error: " . ($connectionTest['error'] ?? 'Unknown error') . "\n\n";
    } else {
        $testResult = $connectionTest['test_result'];
        echo "Test calculation result: $" . number_format($testResult['tax_amount'], 2) . " tax on $100.00\n";
        echo "Tax rate: " . number_format($testResult['tax_rate'], 2) . "%\n\n";
        
        if (!empty($testResult['jurisdictions'])) {
            echo "Jurisdiction breakdown:\n";
            foreach ($testResult['jurisdictions'] as $jurisdiction) {
                echo "  • " . $jurisdiction['name'] . " (" . $jurisdiction['type'] . "): " . 
                     number_format($jurisdiction['tax_rate'], 4) . "%\n";
            }
            echo "\n";
        }
    }
    
    // Now test with a realistic business address for equipment quote
    echo "Testing equipment sales tax calculation for business address...\n";
    echo "Equipment: $2,490.00\n";
    echo "Texas Business Address\n\n";
    
    // Use a realistic Texas business address
    $businessAddress = [
        'line1' => '1234 Business Blvd',
        'city' => 'Austin',
        'state' => 'TX',
        'zip' => '78701'
    ];
    
    $result = $texasClient->calculateTexasTax(
        2490.00,
        $businessAddress
    );
    
    echo "=== TEXAS TAX CALCULATION RESULTS ===\n";
    
    if ($result['success']) {
        echo "✅ SUCCESS! Real Texas Comptroller calculation:\n\n";
        echo "Subtotal: $" . number_format($result['subtotal'], 2) . "\n";
        echo "Tax Amount: $" . number_format($result['tax_amount'], 2) . "\n";
        echo "Total: $" . number_format($result['total'], 2) . "\n";
        echo "Tax Rate: " . number_format($result['tax_rate'], 2) . "%\n";
        echo "Address: " . ($result['address'] ?? 'N/A') . "\n\n";
        
        echo "🎯 COMPARISON:\n";
        echo "Current quote: $205.44 tax (8.25%)\n";
        echo "Real Texas rate: $" . number_format($result['tax_amount'], 2) . " tax (" . number_format($result['tax_rate'], 2) . "%)\n";
        echo "Difference: $" . number_format(abs(205.44 - $result['tax_amount']), 2) . "\n\n";
        
        if (!empty($result['jurisdictions'])) {
            echo "📍 TAX BREAKDOWN BY JURISDICTION:\n";
            foreach ($result['jurisdictions'] as $jurisdiction) {
                $jurisdictionTax = 2490.00 * ($jurisdiction['tax_rate'] / 100);
                echo "  • " . $jurisdiction['name'] . " (" . $jurisdiction['type'] . "): $" . 
                     number_format($jurisdictionTax, 2) . 
                     " (" . number_format($jurisdiction['tax_rate'], 4) . "%)\n";
            }
        }
        
    } else {
        echo "❌ Tax calculation failed:\n";
        echo "Error: " . ($result['error'] ?? 'Unknown error') . "\n";
        echo "This may be because the address is not found in Texas.\n";
    }
    
    echo "\n=== NEXT STEPS ===\n";
    echo "1. ✅ Texas Comptroller API is " . ($result['success'] ? "working and FREE!" : "configured but may need valid Texas address") . "\n";
    echo "2. 🔄 Update tax engine to use real Texas API results\n";
    echo "3. 📊 Replace hardcoded tax rates with real calculations\n";
    echo "4. 🏢 Update jurisdiction breakdown display with real data\n";
    echo "5. 💰 SAVE $800/month by using free Texas Comptroller API!\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}