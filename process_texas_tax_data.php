<?php

require_once __DIR__ . '/vendor/autoload.php';

// Boot Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\TaxEngine\TexasComptrollerDataService;
use App\Services\TaxEngine\LocalTaxRateService;

echo "Processing Official Texas Comptroller Tax Jurisdiction Rates...\n";
echo "Source: tax_jurisdiction_rates-2025Q3.csv\n\n";

try {
    $service = new TexasComptrollerDataService();
    
    // Read the official CSV file
    $csvPath = __DIR__ . '/tax_jurisdiction_rates-2025Q3.csv';
    
    if (!file_exists($csvPath)) {
        throw new Exception("Tax jurisdiction rates file not found: {$csvPath}");
    }
    
    $csvContent = file_get_contents($csvPath);
    $fileSize = strlen($csvContent);
    
    echo "✅ Found official tax jurisdiction rates file\n";
    echo "File size: " . number_format($fileSize) . " bytes\n";
    
    // Count total lines (excluding header)
    $lines = explode("\n", trim($csvContent));
    $totalJurisdictions = count($lines) - 1; // Subtract header
    
    echo "Total jurisdictions: " . number_format($totalJurisdictions) . "\n\n";
    
    // Parse the CSV content
    echo "1. Parsing official tax rates...\n";
    $parseResult = $service->parseTaxRatesFile($csvContent);
    
    if (!$parseResult['success']) {
        throw new Exception("Failed to parse tax rates: " . $parseResult['error']);
    }
    
    echo "✅ Successfully parsed tax rates\n";
    echo "Jurisdictions parsed: " . $parseResult['count'] . "\n\n";
    
    // Show sample of parsed data
    echo "Sample jurisdictions:\n";
    $sampleCount = min(10, count($parseResult['jurisdictions']));
    for ($i = 0; $i < $sampleCount; $i++) {
        $jurisdiction = $parseResult['jurisdictions'][$i];
        echo "  • " . $jurisdiction['name'] . " (ID: " . $jurisdiction['authority_id'] . "): " . number_format($jurisdiction['tax_rate'], 4) . "%\n";
    }
    echo "\n";
    
    // Update database with official rates
    echo "2. Importing official tax rates into database...\n";
    $updateResult = $service->updateDatabaseWithTexasRates($parseResult['jurisdictions']);
    
    if (!$updateResult['success']) {
        throw new Exception("Failed to update database: " . $updateResult['error']);
    }
    
    echo "✅ Successfully imported official tax rates!\n";
    echo "Inserted: " . number_format($updateResult['inserted']) . " tax rates\n";
    echo "Quarter: " . $updateResult['quarter'] . "\n\n";
    
    // Verify the import
    echo "3. Verifying import...\n";
    $configStatus = $service->getConfigurationStatus();
    
    if ($configStatus['configured']) {
        echo "✅ Texas Comptroller rates configured successfully\n";
        echo "Active rates in database: " . number_format($configStatus['texas_rates']) . "\n";
        echo "Data source: " . $configStatus['source'] . "\n";
        echo "Cost: " . $configStatus['cost'] . "\n\n";
    } else {
        echo "❌ Configuration verification failed\n\n";
    }
    
    // Test calculation with real data
    echo "4. Testing tax calculation with official data...\n";
    
    // Create test address in Texas
    $testAddress = [
        'line1' => '123 Main St',
        'city' => 'Austin',
        'state' => 'TX',
        'zip' => '73301'
    ];
    
    // Test with LocalTaxRateService using the new official data
    $localService = new LocalTaxRateService(1); // Company ID 1
    
    $testResult = $localService->calculateEquipmentTax(1000.00, $testAddress);
    
    if ($testResult['success']) {
        echo "✅ Tax calculation successful with official data\n";
        echo "Test amount: $1,000.00\n";
        echo "Tax amount: $" . number_format($testResult['tax_amount'], 2) . "\n";
        echo "Tax rate: " . number_format($testResult['tax_rate'], 4) . "%\n";
        echo "Total: $" . number_format($testResult['total'], 2) . "\n";
        echo "Jurisdictions applied: " . count($testResult['jurisdictions']) . "\n";
        
        if (!empty($testResult['jurisdictions'])) {
            echo "\nTax breakdown:\n";
            foreach ($testResult['jurisdictions'] as $jurisdiction) {
                echo "  • " . $jurisdiction['name'] . ": $" . number_format($jurisdiction['tax_amount'], 2) . " (" . number_format($jurisdiction['tax_rate'], 4) . "%)\n";
            }
        }
    } else {
        echo "❌ Tax calculation test failed: " . $testResult['error'] . "\n";
    }
    
    echo "\n=== TEXAS COMPTROLLER OFFICIAL DATA IMPORT SUMMARY ===\n";
    echo "✅ Official Q3 2025 tax jurisdiction rates imported\n";
    echo "✅ " . number_format($updateResult['inserted']) . " official tax rates now available\n";
    echo "✅ Real-time tax calculations using government data\n";
    echo "✅ NO monthly costs (completely FREE)\n";
    echo "✅ Production-grade official Texas Comptroller data\n";
    echo "🎯 Tax system now uses OFFICIAL government rates instead of sample data\n";
    
} catch (Exception $e) {
    echo "❌ Import failed: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}