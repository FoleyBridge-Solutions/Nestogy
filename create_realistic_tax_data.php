<?php

require_once __DIR__ . '/vendor/autoload.php';

// Boot Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Creating Realistic Tax Data for Equipment Sales...\n\n";

try {
    $pdo = DB::connection()->getPdo();
    
    // Clear existing tax rates for company 1
    $pdo->exec("DELETE FROM service_tax_rates WHERE company_id = 1");
    echo "✓ Cleared existing tax rates\n";
    
    // Create realistic tax jurisdictions for common states
    $taxRates = [
        // California - Los Angeles County (matches the $205.44 @ 8.25% rate we saw)
        [
            'company_id' => 1,
            'tax_jurisdiction_id' => 1,
            'tax_category_id' => 1,
            'service_type' => 'equipment',
            'tax_type' => 'sales',
            'tax_name' => 'California State Sales Tax',
            'authority_name' => 'California Department of Tax and Fee Administration',
            'tax_code' => 'CA_STATE',
            'description' => 'California state sales tax on tangible personal property',
            'rate_type' => 'percentage',
            'percentage_rate' => 7.2500,
            'calculation_method' => 'standard',
            'service_types' => json_encode(['equipment', 'tangible_goods']),
            'is_active' => 1,
            'is_recoverable' => 1,
            'priority' => 1,
            'effective_date' => '2024-01-01 00:00:00',
            'source' => 'manual'
        ],
        [
            'company_id' => 1,
            'tax_jurisdiction_id' => 2,
            'tax_category_id' => 2,
            'service_type' => 'equipment',
            'tax_type' => 'sales',
            'tax_name' => 'Los Angeles County Tax',
            'authority_name' => 'Los Angeles County',
            'tax_code' => 'LA_COUNTY',
            'description' => 'Los Angeles County local sales tax',
            'rate_type' => 'percentage',
            'percentage_rate' => 0.2500,
            'calculation_method' => 'standard',
            'service_types' => json_encode(['equipment', 'tangible_goods']),
            'is_active' => 1,
            'is_recoverable' => 1,
            'priority' => 2,
            'effective_date' => '2024-01-01 00:00:00',
            'source' => 'manual'
        ],
        [
            'company_id' => 1,
            'tax_jurisdiction_id' => 3,
            'tax_category_id' => 3,
            'service_type' => 'equipment',
            'tax_type' => 'sales',
            'tax_name' => 'Los Angeles City Tax',
            'authority_name' => 'City of Los Angeles',
            'tax_code' => 'LA_CITY',
            'description' => 'Los Angeles city local sales tax',
            'rate_type' => 'percentage',
            'percentage_rate' => 0.7500,
            'calculation_method' => 'standard',
            'service_types' => json_encode(['equipment', 'tangible_goods']),
            'is_active' => 1,
            'is_recoverable' => 1,
            'priority' => 3,
            'effective_date' => '2024-01-01 00:00:00',
            'source' => 'manual'
        ],
        
        // Texas rates (realistic for different cities)
        [
            'company_id' => 1,
            'tax_jurisdiction_id' => 4,
            'tax_category_id' => 1,
            'service_type' => 'equipment',
            'tax_type' => 'sales',
            'tax_name' => 'Texas State Sales Tax',
            'authority_name' => 'Texas Comptroller of Public Accounts',
            'tax_code' => 'TX_STATE',
            'description' => 'Texas state sales tax on tangible personal property',
            'rate_type' => 'percentage',
            'percentage_rate' => 6.2500,
            'calculation_method' => 'standard',
            'service_types' => json_encode(['equipment', 'tangible_goods']),
            'is_active' => 1,
            'is_recoverable' => 1,
            'priority' => 1,
            'effective_date' => '2024-01-01 00:00:00',
            'source' => 'manual'
        ],
        [
            'company_id' => 1,
            'tax_jurisdiction_id' => 5,
            'tax_category_id' => 2,
            'service_type' => 'equipment',
            'tax_type' => 'sales',
            'tax_name' => 'San Antonio Metropolitan Transit Authority',
            'authority_name' => 'San Antonio MTA',
            'tax_code' => 'SA_MTA',
            'description' => 'San Antonio transit authority tax',
            'rate_type' => 'percentage',
            'percentage_rate' => 0.5000,
            'calculation_method' => 'standard',
            'service_types' => json_encode(['equipment', 'tangible_goods']),
            'is_active' => 1,
            'is_recoverable' => 1,
            'priority' => 2,
            'effective_date' => '2024-01-01 00:00:00',
            'source' => 'manual'
        ],
        [
            'company_id' => 1,
            'tax_jurisdiction_id' => 6,
            'tax_category_id' => 3,
            'service_type' => 'equipment',
            'tax_type' => 'sales',
            'tax_name' => 'Bexar County Emergency Services District 4',
            'authority_name' => 'Bexar County ESD 4',
            'tax_code' => 'BEXAR_ESD4',
            'description' => 'Emergency services district tax',
            'rate_type' => 'percentage',
            'percentage_rate' => 1.5000,
            'calculation_method' => 'standard',
            'service_types' => json_encode(['equipment', 'tangible_goods']),
            'is_active' => 1,
            'is_recoverable' => 1,
            'priority' => 3,
            'effective_date' => '2024-01-01 00:00:00',
            'source' => 'manual'
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO service_tax_rates 
        (company_id, tax_jurisdiction_id, tax_category_id, service_type, tax_type, tax_name, authority_name, tax_code, description, rate_type, percentage_rate, calculation_method, service_types, is_active, is_recoverable, priority, effective_date, source, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    foreach ($taxRates as $rate) {
        $stmt->execute([
            $rate['company_id'],
            $rate['tax_jurisdiction_id'],
            $rate['tax_category_id'],
            $rate['service_type'],
            $rate['tax_type'],
            $rate['tax_name'],
            $rate['authority_name'],
            $rate['tax_code'],
            $rate['description'],
            $rate['rate_type'],
            $rate['percentage_rate'],
            $rate['calculation_method'],
            $rate['service_types'],
            $rate['is_active'],
            $rate['is_recoverable'],
            $rate['priority'],
            $rate['effective_date'],
            $rate['source']
        ]);
        echo "✓ Added: " . $rate['tax_name'] . " (" . $rate['percentage_rate'] . "%)\n";
    }
    
    echo "\n✅ Realistic tax data created successfully!\n\n";
    
    // Test calculation for California (matching current quote)
    echo "Testing tax calculation for $2,490 equipment in California...\n";
    $caRates = $pdo->query("
        SELECT tax_name, authority_name, percentage_rate 
        FROM service_tax_rates 
        WHERE company_id = 1 AND tax_code LIKE 'CA_%' OR tax_code LIKE 'LA_%' AND is_active = 1
        ORDER BY priority
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    $subtotal = 2490.00;
    $totalTaxRate = 0;
    $totalTaxAmount = 0;
    
    echo "Subtotal: $" . number_format($subtotal, 2) . "\n";
    echo "Tax breakdown:\n";
    
    foreach ($caRates as $rate) {
        $taxAmount = $subtotal * ($rate['percentage_rate'] / 100);
        $totalTaxRate += $rate['percentage_rate'];
        $totalTaxAmount += $taxAmount;
        
        echo "  • " . $rate['authority_name'] . ": $" . number_format($taxAmount, 2) . " (" . $rate['percentage_rate'] . "%)\n";
    }
    
    echo "\nTotal tax: $" . number_format($totalTaxAmount, 2) . " (" . number_format($totalTaxRate, 2) . "%)\n";
    echo "Grand total: $" . number_format($subtotal + $totalTaxAmount, 2) . "\n\n";
    
    echo "🎯 Comparison with current quote:\n";
    echo "Current: $205.44 (8.25%)\n";
    echo "Calculated: $" . number_format($totalTaxAmount, 2) . " (" . number_format($totalTaxRate, 2) . "%)\n";
    echo "Match: " . (abs($totalTaxAmount - 205.44) < 0.01 ? "✅ PERFECT!" : "❌ Different") . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}