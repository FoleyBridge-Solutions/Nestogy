<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 Debug Jurisdiction IDs\n";
echo "========================\n\n";

// Get the actual address record
$query = "SELECT state_jurisdiction_id, county_jurisdiction_id, city_jurisdiction_id, primary_transit_id FROM address_tax_jurisdictions WHERE street_name = 'O\'CONNOR' AND zip_code = '78247' AND address_from <= 17422 AND address_to >= 17422 LIMIT 1";
$result = DB::select($query);

if (!empty($result)) {
    $record = $result[0];
    echo "📊 Raw Database Values:\n";
    echo "   State ID: " . var_export($record->state_jurisdiction_id, true) . " (type: " . gettype($record->state_jurisdiction_id) . ")\n";
    echo "   County ID: " . var_export($record->county_jurisdiction_id, true) . " (type: " . gettype($record->county_jurisdiction_id) . ")\n";
    echo "   City ID: " . var_export($record->city_jurisdiction_id, true) . " (type: " . gettype($record->city_jurisdiction_id) . ")\n";
    echo "   Transit ID: " . var_export($record->primary_transit_id, true) . " (type: " . gettype($record->primary_transit_id) . ")\n\n";
    
    // Test array filtering
    $jurisdictions = [];
    if ($record->state_jurisdiction_id) {
        $jurisdictions[] = $record->state_jurisdiction_id;
        echo "✅ Added state: {$record->state_jurisdiction_id}\n";
    }
    if ($record->county_jurisdiction_id) {
        $jurisdictions[] = $record->county_jurisdiction_id;
        echo "✅ Added county: {$record->county_jurisdiction_id}\n";
    }
    if ($record->city_jurisdiction_id) {
        $jurisdictions[] = $record->city_jurisdiction_id;
        echo "✅ Added city: {$record->city_jurisdiction_id}\n";
    }
    if ($record->primary_transit_id) {
        $jurisdictions[] = $record->primary_transit_id;
        echo "✅ Added transit: {$record->primary_transit_id}\n";
    }
    
    echo "\n📋 Final Array: " . var_export($jurisdictions, true) . "\n";
} else {
    echo "❌ No matching address record found\n";
}

echo "\n🎯 Debug completed\n";