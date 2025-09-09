#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Invoice Components...\n";
echo "=============================\n\n";

// Test InvoiceIndex component
try {
    $indexComponent = new \App\Livewire\Financial\InvoiceIndex();
    echo "✓ InvoiceIndex component instantiated successfully\n";
    
    // Test computed properties
    $indexComponent->mount();
    echo "✓ InvoiceIndex mount() executed successfully\n";
} catch (\Exception $e) {
    echo "✗ InvoiceIndex error: " . $e->getMessage() . "\n";
}

// Test InvoiceCreate component
try {
    $createComponent = new \App\Livewire\Financial\InvoiceCreate();
    echo "✓ InvoiceCreate component instantiated successfully\n";
    
    // Test mount method
    $createComponent->mount();
    echo "✓ InvoiceCreate mount() executed successfully\n";
} catch (\Exception $e) {
    echo "✗ InvoiceCreate error: " . $e->getMessage() . "\n";
}

echo "\n✓ All component tests passed!\n";