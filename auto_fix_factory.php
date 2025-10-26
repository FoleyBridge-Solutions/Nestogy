<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$factory_file = $argv[1] ?? null;
if (!$factory_file || !file_exists($factory_file)) {
    echo "Usage: php auto_fix_factory.php <factory_file>\n";
    exit(1);
}

// Extract model class from factory
$content = file_get_contents($factory_file);
if (preg_match('/protected \$model = ([^:]+)::class;/', $content, $matches)) {
    $model_class = trim($matches[1], '\\');
    
    // Get table name
    if (class_exists($model_class)) {
        $model = new $model_class;
        $table = $model->getTable();
        
        echo "Model: $model_class\n";
        echo "Table: $table\n";
        
        // Get actual columns
        $columns = DB::getSchemaBuilder()->getColumnListing($table);
        echo "Columns: " . implode(', ', $columns) . "\n";
    }
}
