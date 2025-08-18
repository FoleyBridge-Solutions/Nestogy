<?php

require_once __DIR__ . '/vendor/autoload.php';

// Boot Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing TaxCloud API with Proper Format...\n\n";

$loginId = env('TAXCLOUD_API_LOGIN_ID');
$apiKey = env('TAXCLOUD_API_KEY');

echo "✅ Credentials loaded from .env\n\n";

$client = new \GuzzleHttp\Client([
    'timeout' => 30,
    'verify' => false
]);

try {
    // Test with a proper address format
    $testData = [
        'apiLoginID' => $loginId,
        'apiKey' => $apiKey,
        'Address1' => '162 E Ave',
        'Address2' => '',
        'City' => 'Norwalk',
        'State' => 'CT',
        'Zip5' => '06851',
        'Zip4' => ''
    ];
    
    echo "Testing address verification with proper format...\n";
    echo "Address: 162 E Ave, Norwalk, CT 06851\n\n";
    
    $response = $client->post('https://api.taxcloud.com/1.0/TaxCloud/VerifyAddress', [
        'json' => $testData,
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ]
    ]);
    
    $body = $response->getBody()->getContents();
    $data = json_decode($body, true);
    
    echo "Response: " . $body . "\n\n";
    
    if (isset($data['ErrNumber'])) {
        if ($data['ErrNumber'] == 0) {
            echo "✅ SUCCESS! TaxCloud API is working!\n";
            echo "Verified address: " . $data['Address1'] . ", " . $data['City'] . ", " . $data['State'] . " " . $data['Zip5'] . "\n\n";
            
            // Now test tax calculation
            echo "Testing tax calculation...\n";
            
            $taxData = [
                'apiLoginID' => $loginId,
                'apiKey' => $apiKey,
                'customerID' => $loginId, // Using loginID as customerID
                'cartID' => 'cart_' . time(),
                'cartItems' => [
                    [
                        'Index' => 0,
                        'ItemID' => 'EQUIP-001',
                        'TIC' => 00000, // General tangible personal property
                        'Price' => 2490.00,
                        'Qty' => 1.0
                    ]
                ],
                'origin' => [
                    'Address1' => '123 Business St',
                    'Address2' => '',
                    'City' => 'Los Angeles',
                    'State' => 'CA',
                    'Zip5' => '90210',
                    'Zip4' => ''
                ],
                'destination' => [
                    'Address1' => '162 E Ave',
                    'Address2' => '',
                    'City' => 'Norwalk',
                    'State' => 'CT',
                    'Zip5' => '06851',
                    'Zip4' => ''
                ],
                'deliveredBySeller' => true
            ];
            
            $taxResponse = $client->post('https://api.taxcloud.com/1.0/TaxCloud/Lookup', [
                'json' => $taxData,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]);
            
            $taxBody = $taxResponse->getBody()->getContents();
            $taxResult = json_decode($taxBody, true);
            
            echo "Tax calculation response: " . $taxBody . "\n\n";
            
            if (isset($taxResult['CartItemsResponse']) && is_array($taxResult['CartItemsResponse'])) {
                $item = $taxResult['CartItemsResponse'][0];
                $taxAmount = $item['TaxAmount'] ?? 0;
                
                echo "✅ Tax Calculation Results:\n";
                echo "Subtotal: $2,490.00\n";
                echo "Tax Amount: $" . number_format($taxAmount, 2) . "\n";
                echo "Total: $" . number_format(2490 + $taxAmount, 2) . "\n";
                echo "Tax Rate: " . number_format(($taxAmount / 2490) * 100, 2) . "%\n\n";
                
                echo "🎯 COMPARISON:\n";
                echo "Current hardcoded tax: $205.44 (8.25%)\n";
                echo "Real TaxCloud tax: $" . number_format($taxAmount, 2) . " (" . number_format(($taxAmount / 2490) * 100, 2) . "%)\n";
                echo "Difference: $" . number_format(abs(205.44 - $taxAmount), 2) . "\n";
            }
            
        } else {
            echo "❌ TaxCloud Error " . $data['ErrNumber'] . ": " . $data['ErrDescription'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}