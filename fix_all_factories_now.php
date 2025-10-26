<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Get all factories
$factories = glob('database/factories/Domains/**/*Factory.php');

$fixes = 0;
foreach ($factories as $factory_path) {
    $content = file_get_contents($factory_path);
    
    // Extract model class
    if (preg_match('/protected \$model = ([^:]+)::class;/', $content, $matches)) {
        $model_class = trim($matches[1], '\\');
        
        if (class_exists($model_class)) {
            try {
                $model = new $model_class;
                $table = $model->getTable();
                
                // Get actual columns from database
                $columns = DB::getSchemaBuilder()->getColumnListing($table);
                
                // Extract factory definition
                if (preg_match('/public function definition\(\): array\s*\{(.*?)\}/s', $content, $def_matches)) {
                    $definition = $def_matches[1];
                    
                    // Find columns used in factory
                    preg_match_all("/'([^']+)'\s*=>/", $definition, $factory_cols);
                    $factory_columns = array_unique($factory_cols[1]);
                    
                    // Find invalid columns
                    $invalid = array_diff($factory_columns, $columns);
                    
                    if (!empty($invalid)) {
                        echo "FIXING: $factory_path\n";
                        echo "  Model: $model_class\n";
                        echo "  Table: $table\n";
                        echo "  Invalid columns: " . implode(', ', $invalid) . "\n";
                        echo "  Valid columns: " . implode(', ', $columns) . "\n";
                        $fixes++;
                    }
                }
            } catch (\Exception $e) {
                // Skip if model can't be instantiated
            }
        }
    }
}

echo "\nTotal factories needing fixes: $fixes\n";
