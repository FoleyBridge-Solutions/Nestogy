<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\TaxEngine\AddressJurisdictionLookupService;
use App\Services\TaxEngine\LocalTaxRateService;
use Illuminate\Foundation\Application;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🎯 Testing Exact Address Lookup for Texas Comptroller Match\n";
echo "=========================================================\n\n";

// Test the exact address from the Texas Comptroller lookup: 17422 O'Connor Rd, San Antonio, TX 78247
$testAddress = "17422 O'Connor Rd";
$testCity = "San Antonio";
$testState = "TX";
$testZip = "78247";

echo "📍 Target Address: {$testAddress}, {$testCity}, {$testState} {$testZip}\n";
echo "🎯 Expected Tax Rate: 8.25% (per official Texas Comptroller lookup)\n\n";

try {
    // Test address lookup performance
    $lookupService = new AddressJurisdictionLookupService();
    $performanceResult = $lookupService->testPerformance($testAddress, $testCity, $testState, $testZip);
    
    echo "⚡ Performance Test:\n";
    echo "   Execution Time: {$performanceResult['execution_time_ms']}ms\n";
    echo "   Target: {$performanceResult['performance_target']}\n";
    echo "   Meets Target: " . ($performanceResult['meets_target'] ? '✅ YES' : '❌ NO') . "\n\n";
    
    $result = $performanceResult['result'];
    
    if ($result['success'] && !empty($result['jurisdiction_ids'])) {
        echo "✅ Address Found - Jurisdiction IDs: " . implode(', ', $result['jurisdiction_ids']) . "\n";
        
        // Get jurisdiction details
        $jurisdictions = $lookupService->getJurisdictionDetailsByIds($result['jurisdiction_ids']);
        echo "\n📊 Found Jurisdictions:\n";
        foreach ($jurisdictions as $jurisdiction) {
            echo "   - {$jurisdiction->jurisdiction_name} (Code: {$jurisdiction->jurisdiction_code}, Type: {$jurisdiction->jurisdiction_type})\n";
        }
        
        // Test tax calculation
        echo "\n💰 Tax Calculation Test:\n";
        $taxService = new LocalTaxRateService(1); // Company ID 1
        $taxResult = $taxService->calculateEquipmentTax(100.00, [
            'line1' => $testAddress,
            'city' => $testCity,
            'state' => $testState,
            'zip' => $testZip
        ]);
        
        if ($taxResult['success']) {
            echo "   Subtotal: $" . number_format($taxResult['subtotal'], 2) . "\n";
            echo "   Tax Amount: $" . number_format($taxResult['tax_amount'], 2) . "\n";
            echo "   Total: $" . number_format($taxResult['total'], 2) . "\n";
            echo "   Tax Rate: " . number_format($taxResult['tax_rate'], 2) . "%\n";
            echo "   Source: {$taxResult['source']}\n\n";
            
            // Check if we match the target rate
            $targetRate = 8.25;
            $actualRate = round($taxResult['tax_rate'], 2);
            $rateMatch = abs($actualRate - $targetRate) < 0.01;
            
            echo "🎯 Target Verification:\n";
            echo "   Expected: {$targetRate}%\n";
            echo "   Actual: {$actualRate}%\n";
            echo "   Match: " . ($rateMatch ? '✅ PERFECT MATCH!' : '❌ MISMATCH') . "\n\n";
            
            // Show jurisdiction breakdown
            if (!empty($taxResult['jurisdictions'])) {
                echo "📋 Tax Breakdown by Jurisdiction:\n";
                foreach ($taxResult['jurisdictions'] as $jurisdiction) {
                    $jurTax = number_format($jurisdiction['tax_amount'], 2);
                    $jurRate = number_format($jurisdiction['tax_rate'], 2);
                    echo "   - {$jurisdiction['name']}: ${jurTax} ({$jurRate}%)\n";
                }
            }
            
        } else {
            echo "❌ Tax calculation failed: " . ($taxResult['error'] ?? 'Unknown error') . "\n";
        }
        
    } else {
        echo "❌ Address not found in database\n";
        echo "   This means the address is not in our imported Bexar County dataset\n";
        echo "   Error: " . ($result['error'] ?? 'No matching address found') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🏁 Exact lookup test completed\n";