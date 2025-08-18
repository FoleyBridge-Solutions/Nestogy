<?php

require_once __DIR__ . '/vendor/autoload.php';

// Boot Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

echo "Direct Texas Comptroller API Test...\n\n";

try {
    $baseUrl = 'https://gis.cpa.texas.gov';
    
    echo "Making request to: {$baseUrl}/search/\n";
    
    $formData = [
        'Address' => '25334 TRIANGLE LOOP',
        'City' => 'SAN ANTONIO',
        'State' => 'TX',
        'ZipCode' => '78255'
    ];
    
    echo "Form data:\n";
    foreach ($formData as $key => $value) {
        echo "  {$key}: {$value}\n";
    }
    echo "\n";
    
    $response = Http::timeout(30)
        ->asForm()
        ->post("{$baseUrl}/search/", $formData);
    
    echo "Response Status: " . $response->status() . "\n";
    echo "Response Headers:\n";
    foreach ($response->headers() as $header => $values) {
        echo "  {$header}: " . implode(', ', $values) . "\n";
    }
    
    $body = $response->body();
    echo "\nResponse Body Length: " . strlen($body) . " characters\n";
    
    if ($response->successful()) {
        echo "✅ SUCCESS! Got response from Texas Comptroller\n\n";
        
        // Look for specific patterns in the response
        echo "Looking for tax rate data...\n";
        
        if (strpos($body, 'JURISDICTION NAME') !== false) {
            echo "✓ Found JURISDICTION NAME in response\n";
        } else {
            echo "✗ No JURISDICTION NAME found\n";
        }
        
        if (strpos($body, 'TOTAL TAX RATE') !== false) {
            echo "✓ Found TOTAL TAX RATE in response\n";
        } else {
            echo "✗ No TOTAL TAX RATE found\n";
        }
        
        if (strpos($body, 'TEXAS') !== false) {
            echo "✓ Found TEXAS in response\n";
        } else {
            echo "✗ No TEXAS found\n";
        }
        
        // Extract key parts of the response
        echo "\n=== RESPONSE SAMPLE ===\n";
        
        // Show first 2000 characters to see the structure
        echo substr($body, 0, 2000) . "\n";
        echo "...(truncated)\n\n";
        
        // Try to find and extract tax rate table
        if (preg_match('/JURISDICTION NAME.*?TOTAL TAX RATE\s+([\d.]+)/s', $body, $matches)) {
            echo "🎯 FOUND TAX RATE: " . $matches[1] . "\n";
            
            // Try to extract individual jurisdictions
            preg_match_all('/JURISDICTION NAME\s+([^\n]+)\s+Code\s+(\d+)\s+Type\s+([^\n]+)\s+Tax Rate\s+([\d.]+)/s', $body, $jurisdictionMatches, PREG_SET_ORDER);
            
            echo "Found " . count($jurisdictionMatches) . " jurisdictions:\n";
            foreach ($jurisdictionMatches as $match) {
                echo "  • " . trim($match[1]) . " (" . trim($match[3]) . "): " . trim($match[4]) . "\n";
            }
        } else {
            echo "❌ Could not parse tax rate from response\n";
            
            // Show lines that contain rate or tax
            $lines = explode("\n", $body);
            echo "\nLines containing 'rate' or 'tax':\n";
            foreach ($lines as $line) {
                if (stripos($line, 'rate') !== false || stripos($line, 'tax') !== false) {
                    echo "  " . trim($line) . "\n";
                }
            }
        }
        
    } else {
        echo "❌ API Request Failed\n";
        echo "Response body:\n" . $body . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}