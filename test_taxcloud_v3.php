<?php

require_once __DIR__ . '/vendor/autoload.php';

// Boot Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\TaxEngine\TaxCloudV3ApiClient;

echo "Testing TaxCloud V3 API for Equipment Sales...\n\n";

try {
    // Initialize the V3 client
    $taxCloud = new TaxCloudV3ApiClient(1); // Company ID 1
    
    // Check configuration
    $config = $taxCloud->getConfigurationStatus();
    echo "Configuration Status:\n";
    foreach ($config as $key => $value) {
        echo "  {$key}: {$value}\n";
    }
    echo "\n";
    
    if (!$config['configured']) {
        echo "❌ TaxCloud V3 not properly configured\n";
        exit(1);
    }
    
    // Test the connection first
    echo "Testing connection...\n";
    $connectionTest = $taxCloud->testConnection();
    echo "Connection: " . ($connectionTest['success'] ? "✅ Success" : "❌ Failed") . "\n";
    
    if (!$connectionTest['success']) {
        echo "Error: " . $connectionTest['error'] . "\n\n";
    } else {
        echo "Test calculation result: $" . number_format($connectionTest['test_result']['tax_amount'], 2) . " tax on $100.00\n\n";
    }
    
    // Now test with the actual equipment quote amount ($2,490)
    echo "Testing equipment sales tax calculation...\n";
    echo "Equipment: $2,490.00\n";
    echo "Customer: BurkhartPeterson\n\n";
    
    // Use a realistic customer address (you can update this)
    $customerAddress = [
        'line1' => '1234 Business Blvd',
        'city' => 'Los Angeles',
        'state' => 'CA',
        'zip' => '90210'
    ];
    
    $result = $taxCloud->calculateEquipmentTax(
        2490.00,
        $customerAddress,
        null, // Use default origin
        'burkhart-peterson',
        [
            [
                'index' => 0,
                'itemId' => 'EQUIP-QUOTE-001',
                'tic' => 0, // General tangible personal property
                'price' => 2490.00,
                'quantity' => 1
            ]
        ]
    );
    
    echo "=== TAX CALCULATION RESULTS ===\n";
    
    if ($result['success']) {
        echo "✅ SUCCESS! Real TaxCloud V3 calculation:\n\n";
        echo "Subtotal: $" . number_format($result['subtotal'], 2) . "\n";
        echo "Tax Amount: $" . number_format($result['tax_amount'], 2) . "\n";
        echo "Total: $" . number_format($result['total'], 2) . "\n";
        echo "Tax Rate: " . number_format($result['tax_rate'], 2) . "%\n";
        echo "Cart ID: " . ($result['cart_id'] ?? 'N/A') . "\n\n";
        
        echo "🎯 COMPARISON:\n";
        echo "Current quote: $205.44 tax (8.25%)\n";
        echo "Real TaxCloud: $" . number_format($result['tax_amount'], 2) . " tax (" . number_format($result['tax_rate'], 2) . "%)\n";
        echo "Difference: $" . number_format(abs(205.44 - $result['tax_amount']), 2) . "\n\n";
        
        if (!empty($result['jurisdictions'])) {
            echo "📍 TAX BREAKDOWN BY JURISDICTION:\n";
            foreach ($result['jurisdictions'] as $jurisdiction) {
                echo "  • " . $jurisdiction['name'] . " (" . $jurisdiction['type'] . "): $" . 
                     number_format($jurisdiction['tax_amount'], 2) . 
                     " (" . number_format($jurisdiction['tax_rate'], 2) . "%)\n";
            }
        }
        
        // Test converting to order (what happens when customer completes purchase)
        if (isset($result['cart_id'])) {
            echo "\n🛒 Converting cart to order...\n";
            $orderResult = $taxCloud->convertCartToOrder(
                $result['cart_id'],
                'ORDER-' . time(),
                true // Mark as completed
            );
            
            echo "Order conversion: " . ($orderResult['success'] ? "✅ Success" : "❌ Failed") . "\n";
            if (!$orderResult['success']) {
                echo "Error: " . $orderResult['error'] . "\n";
            }
        }
        
    } else {
        echo "❌ Tax calculation failed:\n";
        echo "Error: " . $result['error'] . "\n";
        echo "Using fallback values.\n";
    }
    
    echo "\n=== NEXT STEPS ===\n";
    echo "1. ✅ TaxCloud V3 API is " . ($result['success'] ? "working!" : "configured but may need account setup") . "\n";
    echo "2. 🔄 Update tax engine to use real API results\n";
    echo "3. 📊 Replace hardcoded $205.44 with real calculation\n";
    echo "4. 🏢 Update jurisdiction breakdown display\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}