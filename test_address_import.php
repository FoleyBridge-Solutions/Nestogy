<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\TaxEngine\TexasComptrollerDataService;
use Illuminate\Foundation\Application;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🏠 Testing Bexar County Address Data Import\n";
echo "==========================================\n\n";

try {
    $service = new TexasComptrollerDataService();
    
    echo "📁 Processing address file: texas_address_029_2025Q3.zip\n";
    
    $result = $service->processCountyAddressData('029', '2025Q3');
    
    if ($result['success']) {
        echo "✅ Successfully imported {$result['addresses']} addresses for Bexar County\n";
    } else {
        echo "❌ Failed to import addresses: " . $result['error'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🎯 Import test completed\n";