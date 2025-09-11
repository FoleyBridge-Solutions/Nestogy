<?php

require_once __DIR__ . '/vendor/autoload.php';

// Boot Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\TaxEngine\TaxJarService;

echo "Testing TaxJar Service...\n\n";

try {
    $service = new TaxJarService();
    $service->setCompanyId(1);

    // Test with a Texas zip code
    $params = [
        'amount' => 100.00,
        'service_address' => [
            'address' => '123 Main St',
            'city' => 'San Antonio',
            'zip_code' => '78255',
            'country' => 'US'
        ],
        'service_type' => 'general'
    ];

    echo "Testing tax calculation for $100 in San Antonio, TX (78255)...\n";

    $result = $service->calculateTaxes($params);

    // Uncomment to debug API response:
    // echo "\n=== API RESPONSE ===\n";
    // if (isset($result['tax_data'])) {
    //     echo json_encode($result['tax_data'], JSON_PRETTY_PRINT) . "\n";
    // }

    echo "\n=== RESULTS ===\n";
    echo "Base Amount: $" . number_format($result['base_amount'], 2) . "\n";
    echo "Total Tax: $" . number_format($result['total_tax_amount'], 2) . "\n";
    echo "Final Amount: $" . number_format($result['final_amount'], 2) . "\n";
    echo "Tax Breakdown:\n";

    foreach ($result['tax_breakdown'] as $tax) {
        echo "  - {$tax['tax_name']}: {$tax['rate']}% = $" . number_format($tax['tax_amount'], 2) . "\n";
    }

    if (isset($result['fallback_used'])) {
        echo "\n⚠️  Fallback calculation was used\n";
    } else {
        echo "\n✅ TaxJar API calculation successful\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}