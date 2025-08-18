<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\TaxEngine\TexasComptrollerDataService;
use Illuminate\Foundation\Application;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🏠 Testing Simple Address Data Parsing\n";
echo "=====================================\n\n";

try {
    $service = new TexasComptrollerDataService();
    
    // Test parseAddressCsvContent with just 10 lines
    $sampleCsv = file_get_contents('/tmp/sample_addresses.csv');
    
    echo "📊 Parsing sample CSV content...\n";
    $addresses = $service->parseAddressCsvContent($sampleCsv, '029');
    
    echo "✅ Parsed " . count($addresses) . " address records\n\n";
    
    // Show first address details
    if (!empty($addresses)) {
        $first = $addresses[0];
        echo "📍 First Address Example:\n";
        echo "   Street: {$first['street_name']}\n";
        echo "   ZIP: {$first['zip_code']}\n";
        echo "   County TAID: {$first['county_taid']}\n";
        echo "   City TAID: {$first['city_taid']}\n";
        echo "   Transit 1: {$first['transit1_taid']}\n";
        echo "   SPD 1: {$first['spd1_taid']}\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🎯 Parsing test completed\n";