<?php

require_once __DIR__ . '/vendor/autoload.php';

// Boot Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing TaxCloud API Credentials...\n\n";

// Get credentials from environment
$loginId = env('TAXCLOUD_API_LOGIN_ID');
$apiKey = env('TAXCLOUD_API_KEY');
$customerId = env('TAXCLOUD_CUSTOMER_ID');

echo "Login ID: " . ($loginId ?: 'NOT SET') . "\n";
echo "API Key: " . ($apiKey ? substr($apiKey, 0, 10) . '...' : 'NOT SET') . "\n";
echo "Customer ID: " . ($customerId ?: 'NOT SET') . "\n\n";

if (!$loginId || !$apiKey || !$customerId) {
    echo "❌ Missing TaxCloud credentials\n";
    exit(1);
}

echo "✅ All TaxCloud credentials are configured!\n\n";

// Test direct HTTP call to TaxCloud
echo "Testing direct TaxCloud API call...\n";

$client = new \GuzzleHttp\Client([
    'timeout' => 30,
    'verify' => false // For testing only
]);

try {
    // Test with VerifyAddress endpoint (simpler than Lookup)
    $testData = [
        'apiLoginID' => $loginId,
        'apiKey' => $apiKey,
        'Address1' => '1234 Main St',
        'City' => 'Los Angeles',
        'State' => 'CA',
        'Zip5' => '90210'
    ];
    
    echo "Making request to TaxCloud VerifyAddress endpoint...\n";
    
    $response = $client->post('https://api.taxcloud.com/1.0/TaxCloud/VerifyAddress', [
        'json' => $testData,
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ]
    ]);
    
    $statusCode = $response->getStatusCode();
    $body = $response->getBody()->getContents();
    
    echo "Response Status: " . $statusCode . "\n";
    echo "Response Body: " . $body . "\n\n";
    
    if ($statusCode === 200) {
        $data = json_decode($body, true);
        
        if (isset($data['ErrNumber']) && $data['ErrNumber'] === 0) {
            echo "✅ TaxCloud API is working!\n";
            echo "Verified Address: " . $data['Address1'] . ", " . $data['City'] . ", " . $data['State'] . " " . $data['Zip5'] . "\n";
        } else {
            echo "⚠️ TaxCloud returned an error: " . ($data['ErrDescription'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "❌ HTTP error: " . $statusCode . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "This might be due to:\n";
    echo "- Invalid API credentials\n";
    echo "- Network connectivity issues\n";
    echo "- TaxCloud service unavailable\n";
}