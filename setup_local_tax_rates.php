<?php

require_once __DIR__ . '/vendor/autoload.php';

// Boot Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Setting up Local Tax Rate System...\n\n";

try {
    // Check if we need to create the service_tax_rates table
    echo "Checking database structure...\n";
    
    $pdo = DB::connection()->getPdo();
    
    // Check if service_tax_rates table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'service_tax_rates'");
    $tableExists = $tableCheck->rowCount() > 0;
    
    echo "Service tax rates table: " . ($tableExists ? "✓ Exists" : "✗ Missing") . "\n";
    
    if (!$tableExists) {
        echo "Creating service_tax_rates table...\n";
        // The migration should handle this, but let's make sure
        $sql = "
        CREATE TABLE IF NOT EXISTS service_tax_rates (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            company_id BIGINT UNSIGNED NOT NULL,
            tax_name VARCHAR(255) NOT NULL,
            tax_code VARCHAR(50),
            tax_type ENUM('sales', 'use', 'excise', 'vat', 'other') DEFAULT 'sales',
            authority_name VARCHAR(255) NOT NULL,
            jurisdiction_level ENUM('federal', 'state', 'county', 'city', 'district') DEFAULT 'state',
            state_code CHAR(2),
            county_code VARCHAR(10),
            city_code VARCHAR(10),
            zip_codes JSON,
            percentage_rate DECIMAL(8,4) NOT NULL DEFAULT 0,
            flat_fee DECIMAL(10,2) DEFAULT 0,
            service_types JSON,
            is_active BOOLEAN DEFAULT TRUE,
            effective_date DATE,
            expiration_date DATE,
            regulatory_code VARCHAR(50),
            is_recoverable BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_company_state (company_id, state_code),
            INDEX idx_service_types (service_types),
            INDEX idx_active (is_active, effective_date, expiration_date)
        ) ENGINE=InnoDB;
        ";
        
        $pdo->exec($sql);
        echo "✓ Table created\n";
    }
    
    // Clear existing rates for company 1
    $pdo->exec("DELETE FROM service_tax_rates WHERE company_id = 1");
    
    // Insert realistic tax rates for common states
    echo "\nInserting realistic tax rates...\n";
    
    $taxRates = [
        // California (high tax state)
        [
            'company_id' => 1,
            'tax_name' => 'California State Sales Tax',
            'tax_code' => 'CA_STATE',
            'authority_name' => 'California Department of Tax and Fee Administration',
            'jurisdiction_level' => 'state',
            'state_code' => 'CA',
            'percentage_rate' => 7.25,
            'service_types' => json_encode(['equipment', 'tangible_goods'])
        ],
        [
            'company_id' => 1,
            'tax_name' => 'Los Angeles County Tax',
            'tax_code' => 'LA_COUNTY',
            'authority_name' => 'Los Angeles County',
            'jurisdiction_level' => 'county',
            'state_code' => 'CA',
            'county_code' => 'LA',
            'percentage_rate' => 0.25,
            'service_types' => json_encode(['equipment', 'tangible_goods'])
        ],
        [
            'company_id' => 1,
            'tax_name' => 'Los Angeles City Tax',
            'tax_code' => 'LA_CITY',
            'authority_name' => 'City of Los Angeles',
            'jurisdiction_level' => 'city',
            'state_code' => 'CA',
            'city_code' => 'LA',
            'percentage_rate' => 0.75,
            'service_types' => json_encode(['equipment', 'tangible_goods'])
        ],
        
        // New York
        [
            'company_id' => 1,
            'tax_name' => 'New York State Sales Tax',
            'tax_code' => 'NY_STATE',
            'authority_name' => 'New York State Department of Taxation and Finance',
            'jurisdiction_level' => 'state',
            'state_code' => 'NY',
            'percentage_rate' => 4.00,
            'service_types' => json_encode(['equipment', 'tangible_goods'])
        ],
        
        // Texas
        [
            'company_id' => 1,
            'tax_name' => 'Texas State Sales Tax',
            'tax_code' => 'TX_STATE',
            'authority_name' => 'Texas Comptroller of Public Accounts',
            'jurisdiction_level' => 'state',
            'state_code' => 'TX',
            'percentage_rate' => 6.25,
            'service_types' => json_encode(['equipment', 'tangible_goods'])
        ],
        
        // Florida (no state income tax, but sales tax)
        [
            'company_id' => 1,
            'tax_name' => 'Florida State Sales Tax',
            'tax_code' => 'FL_STATE',
            'authority_name' => 'Florida Department of Revenue',
            'jurisdiction_level' => 'state',
            'state_code' => 'FL',
            'percentage_rate' => 6.00,
            'service_types' => json_encode(['equipment', 'tangible_goods'])
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO service_tax_rates 
        (company_id, tax_name, tax_code, authority_name, jurisdiction_level, state_code, county_code, city_code, percentage_rate, service_types, is_active, effective_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, CURDATE())
    ");
    
    foreach ($taxRates as $rate) {
        $stmt->execute([
            $rate['company_id'],
            $rate['tax_name'],
            $rate['tax_code'],
            $rate['authority_name'],
            $rate['jurisdiction_level'],
            $rate['state_code'],
            $rate['county_code'] ?? null,
            $rate['city_code'] ?? null,
            $rate['percentage_rate'],
            $rate['service_types']
        ]);
        echo "✓ Added: " . $rate['tax_name'] . " (" . $rate['percentage_rate'] . "%)\n";
    }
    
    echo "\n✅ Local tax rate system set up successfully!\n\n";
    
    // Test calculation
    echo "Testing local tax calculation for California equipment sale...\n";
    $caRates = $pdo->query("
        SELECT tax_name, authority_name, percentage_rate 
        FROM service_tax_rates 
        WHERE company_id = 1 AND state_code = 'CA' AND is_active = 1
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
        
        echo "  " . $rate['authority_name'] . ": $" . number_format($taxAmount, 2) . " (" . $rate['percentage_rate'] . "%)\n";
    }
    
    echo "\nTotal tax: $" . number_format($totalTaxAmount, 2) . " (" . number_format($totalTaxRate, 2) . "%)\n";
    echo "Grand total: $" . number_format($subtotal + $totalTaxAmount, 2) . "\n\n";
    
    echo "🎯 Comparison with current quote:\n";
    echo "Current: $205.44 (8.25%)\n";
    echo "Calculated: $" . number_format($totalTaxAmount, 2) . " (" . number_format($totalTaxRate, 2) . "%)\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}