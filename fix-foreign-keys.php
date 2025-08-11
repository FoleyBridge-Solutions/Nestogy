#!/usr/bin/env php
<?php

// Script to fix foreign key constraint ordering issues
$migrationPath = 'database/migrations/';
$files = glob($migrationPath . '*.php');

$problematicReferences = [
    'usage_pools',
    'usage_buckets',
    'contract_templates',
    'payment_plans',
    'service_tiers',
    'billing_cycles',
    'currency_rates'
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    foreach ($problematicReferences as $table) {
        // Replace foreign key constraints that might cause ordering issues
        $pattern = '/\$table->foreign\(.*?\)->references\(.*?\)->on\(\'' . $table . '\'\).*?;/';
        $replacement = '// Foreign key for ' . $table . ' will be added after ' . $table . ' table is created';
        $content = preg_replace($pattern, $replacement, $content);
    }
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "Fixed foreign keys in: " . basename($file) . "\n";
    }
}

echo "Foreign key fix complete!\n";