<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get all test files
$test_files = glob('tests/Unit/Models/*Test.php');

$missing = [];
foreach ($test_files as $test_file) {
    $model_name = basename($test_file, 'Test.php');
    
    // Find factory
    $factory_path = exec("find database/factories -name '{$model_name}Factory.php' 2>/dev/null");
    
    if (empty($factory_path)) {
        $missing[] = $model_name;
    }
}

if (!empty($missing)) {
    echo "Missing factories:\n";
    foreach ($missing as $m) {
        echo "  - $m\n";
    }
    echo "\nTotal: " . count($missing) . "\n";
} else {
    echo "All factories exist!\n";
}
