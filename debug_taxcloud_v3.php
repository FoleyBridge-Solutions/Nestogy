<?php

require_once __DIR__ . '/vendor/autoload.php';

// Boot Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

echo "Direct TaxCloud V3 API Test...\n\n";

$connectionId = env('TAXCLOUD_CONNECTION_ID');
$apiKey = env('TAXCLOUD_V3_API_KEY');

echo "Connection ID: " . ($connectionId ?: 'NOT SET') . "\n";
echo "API Key: " . ($apiKey ? substr($apiKey, 0, 10) . '...' : 'NOT SET') . "\n\n";

if (!$connectionId || !$apiKey) {
    echo "❌ Missing V3 credentials\n";
    exit(1);
}

try {
    $url = "https://api.v3.taxcloud.com/tax/connections/{$connectionId}/carts";
    
    $payload = [
        'items' => [
            [
                'currency' => [
                    'currencyCode' => 'USD'
                ],
                'customerId' => 'test-customer',
                'destination' => [
                    'line1' => '1234 Main St',
                    'city' => 'Los Angeles',
                    'state' => 'CA',
                    'zip' => '90210'
                ],
                'origin' => [
                    'line1' => '123 Business St',
                    'city' => 'Los Angeles',
                    'state' => 'CA',
                    'zip' => '90210'
                ],
                'lineItems' => [
                    [
                        'index' => 0,
                        'itemId' => 'test-item',
                        'tic' => 0,
                        'price' => 2490.00,
                        'quantity' => 1
                    ]
                ]
            ]
        ]
    ];
    
    echo "Making request to: {$url}\n";
    echo "Payload: " . json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";
    
    $response = Http::timeout(30)
        ->withHeaders([
            'X-API-KEY' => $apiKey,
            'Content-Type' => 'application/json'
        ])
        ->post($url, $payload);
    
    echo "Response Status: " . $response->status() . "\n";
    echo "Response Headers:\n";
    foreach ($response->headers() as $header => $values) {
        echo "  {$header}: " . implode(', ', $values) . "\n";
    }
    
    $body = $response->body();
    echo "\nResponse Body:\n" . $body . "\n\n";
    
    if ($response->successful()) {
        $data = $response->json();
        echo "✅ SUCCESS! TaxCloud V3 is working!\n\n";
        
        if (isset($data['items'][0]['lineItems'][0]['tax'])) {
            $tax = $data['items'][0]['lineItems'][0]['tax'];
            $taxAmount = $tax['amount'] ?? 0;
            $taxRate = ($tax['rate'] ?? 0) * 100;
            
            echo "🎯 REAL TAX CALCULATION:\n";
            echo "Subtotal: $2,490.00\n";
            echo "Tax Amount: $" . number_format($taxAmount, 2) . "\n";
            echo "Tax Rate: " . number_format($taxRate, 2) . "%\n";
            echo "Total: $" . number_format(2490 + $taxAmount, 2) . "\n\n";
            
            echo "📊 COMPARISON:\n";
            echo "Current hardcoded: $205.44 (8.25%)\n";
            echo "Real TaxCloud V3: $" . number_format($taxAmount, 2) . " (" . number_format($taxRate, 2) . "%)\n";
            echo "Difference: $" . number_format(abs(205.44 - $taxAmount), 2) . "\n";
        }
    } else {
        echo "❌ API Request Failed\n";
        
        $errorData = $response->json();
        if ($errorData) {
            echo "Error details:\n";
            print_r($errorData);
        }
        
        // Check common issues
        if ($response->status() === 401) {
            echo "\n🔍 Authentication issue - check your API key\n";
        } elseif ($response->status() === 404) {
            echo "\n🔍 Connection ID not found - check your connection ID\n";
        } elseif ($response->status() === 400) {
            echo "\n🔍 Bad request - check payload format\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}