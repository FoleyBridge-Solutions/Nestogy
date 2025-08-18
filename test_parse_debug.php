<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 Debug Address Parsing\n";
echo "=======================\n\n";

// Test the exact pattern matching from our method
$address = "17422 O'Connor Rd";
echo "📍 Original: '{$address}'\n";

// Apply the exact pattern from our code
$clean = trim(strtoupper($address));
echo "📍 Uppercase: '{$clean}'\n";

$pattern = '/^(\d+)\s+(.+?)(?:\s+(ST|AVE|RD|BLVD|DR|LN|WAY|CT|PL|PKWY|CIR|TRL|PATH))?$/';

if (preg_match($pattern, $clean, $matches)) {
    echo "✅ Pattern matched!\n";
    echo "   Street Number: {$matches[1]}\n";
    echo "   Street Name (raw): '{$matches[2]}'\n";
    echo "   Street Suffix: " . ($matches[3] ?? 'null') . "\n\n";
    
    // Apply street name normalization
    $streetName = $matches[2];
    echo "🔧 Normalizing street name...\n";
    
    // Remove common street type words
    $cleanWords = ['STREET', 'AVENUE', 'ROAD', 'BOULEVARD', 'DRIVE', 'LANE', 'RD', 'ST', 'AVE', 'BLVD', 'DR', 'LN'];
    foreach ($cleanWords as $word) {
        $streetName = str_replace(' ' . $word, '', $streetName);
    }
    echo "   After removing street words: '{$streetName}'\n";
    
    // Handle O'Connor special case
    $streetName = str_replace("O'CONNOR", 'OCONNOR', $streetName);
    $streetName = str_replace('O CONNOR', 'OCONNOR', $streetName);
    echo "   After O'Connor normalization: '{$streetName}'\n";
    
    // Remove apostrophes and special characters
    $streetName = str_replace("'", '', $streetName);
    $streetName = preg_replace('/[^A-Z0-9\s]/', '', $streetName);
    $streetName = trim($streetName);
    echo "   Final normalized: '{$streetName}'\n\n";
    
    // Test database query
    $streetNumber = intval($matches[1]);
    $zip = '78247';
    $state = 'TX';
    
    echo "🔍 Database Query:\n";
    echo "   Looking for: street_name='{$streetName}', zip='{$zip}', state='{$state}'\n";
    echo "   Address range: {$streetNumber}\n\n";
    
    $query = "SELECT street_name, address_from, address_to, zip_code FROM address_tax_jurisdictions WHERE state_code = ? AND zip_code = ? AND street_name = ? AND address_from <= ? AND address_to >= ? LIMIT 1";
    $result = DB::select($query, [$state, $zip, $streetName, $streetNumber, $streetNumber]);
    
    if (!empty($result)) {
        $record = $result[0];
        echo "✅ Found matching record!\n";
        echo "   Street: {$record->street_name}\n";
        echo "   Range: {$record->address_from}-{$record->address_to}\n";
        echo "   ZIP: {$record->zip_code}\n";
    } else {
        echo "❌ No matching record found\n";
        
        // Try to find close matches
        echo "\n🔍 Looking for similar streets...\n";
        $similarQuery = "SELECT street_name, address_from, address_to, zip_code FROM address_tax_jurisdictions WHERE zip_code = ? AND street_name LIKE ? LIMIT 5";
        $similar = DB::select($similarQuery, [$zip, "%{$streetName}%"]);
        
        foreach ($similar as $sim) {
            echo "   Similar: {$sim->street_name} ({$sim->address_from}-{$sim->address_to})\n";
        }
    }
    
} else {
    echo "❌ Pattern did not match!\n";
}

echo "\n🎯 Parsing debug completed\n";