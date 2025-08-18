<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\TaxEngine\AddressJurisdictionLookupService;
use Illuminate\Foundation\Application;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 Debug Address Lookup\n";
echo "======================\n\n";

$service = new AddressJurisdictionLookupService();

// Test address parsing
$testAddress = "17422 O'Connor Rd";
echo "📍 Original: {$testAddress}\n";

// Test the actual lookup
$result = $service->lookupJurisdictions($testAddress, "San Antonio", "TX", "78247");
echo "🔍 Lookup Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
if ($result['success']) {
    echo "   Jurisdiction IDs: " . implode(', ', $result['jurisdiction_ids']) . "\n";
    echo "   Count: " . count($result['jurisdiction_ids']) . "\n";
} else {
    echo "   Error: " . ($result['error'] ?? 'Unknown') . "\n";
}
echo "\n\n";

// Check what we have in database
echo "🗄️ Database Check:\n";
$query = "SELECT street_name, address_from, address_to, zip_code FROM address_tax_jurisdictions WHERE zip_code = '78247' AND street_name LIKE '%CONNOR%' ORDER BY street_name, address_from";
$results = DB::select($query);

foreach ($results as $row) {
    $covers = ($row->address_from <= 17422 && $row->address_to >= 17422) ? '✅ COVERS' : '❌ no';
    echo "   {$row->street_name}: {$row->address_from}-{$row->address_to} {$covers}\n";
}

echo "\n🎯 Debug completed\n";