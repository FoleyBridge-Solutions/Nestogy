#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$modelsToProcess = [
    'Account', 'Address', 'AuditLog', 'Category', 'ClientDocument', 'CompanyCustomization',
    'CompanyMailSettings', 'CompanySubscription', 'ContractConfiguration', 'CrossCompanyUser',
    'CustomQuickAction', 'DashboardWidget', 'Document', 'ExpenseCategory', 'File',
    'MailQueue', 'MailTemplate', 'Network', 'PaymentMethod', 'Permission',
    'PhysicalMailSettings', 'PortalNotification', 'PricingRule', 'ProductBundle',
    'ProductTaxData', 'QuickActionFavorite', 'Quote', 'QuoteApproval', 'QuoteTemplate',
    'QuoteVersion', 'Recurring', 'Role', 'Service', 'ServiceTaxRate', 'Setting',
    'SettingsConfiguration', 'Tag', 'Tax', 'TaxApiQueryCache', 'TaxApiSettings',
    'TaxCalculation', 'TaxProfile', 'TicketRating', 'TimeEntry', 'UsageRecord',
    'UsageTier', 'UserSetting', 'Vendor'
];

foreach ($modelsToProcess as $modelName) {
    $class = "App\\Models\\{$modelName}";
    
    if (!class_exists($class)) {
        echo "SKIP: {$modelName} (class doesn't exist)\n";
        continue;
    }
    
    try {
        $instance = new $class;
        $table = $instance->getTable();
        $fillable = $instance->getFillable();
        
        // Build factory definition
        $definition = ["'company_id' => 1"];
        
        foreach ($fillable as $field) {
            if ($field === 'company_id') continue;
            if (str_ends_with($field, '_id')) continue; // Skip foreign keys
            
            if (str_contains($field, 'email')) {
                $definition[] = "'{$field}' => \$this->faker->safeEmail";
            } elseif (str_contains($field, 'name')) {
                $definition[] = "'{$field}' => \$this->faker->words(3, true)";
            } elseif (str_contains($field, 'description')) {
                $definition[] = "'{$field}' => \$this->faker->sentence";
            } elseif (str_contains($field, 'status')) {
                $definition[] = "'{$field}' => 'active'";
            } elseif (str_contains($field, 'is_') || str_contains($field, 'active')) {
                $definition[] = "'{$field}' => true";
            } else {
                $definition[] = "'{$field}' => null";
            }
        }
        
        $defString = implode(",\n            ", $definition);
        
        $factoryContent = <<<PHP
<?php

namespace Database\Factories;

use App\Models\\{$modelName};
use Illuminate\Database\Eloquent\Factories\Factory;

class {$modelName}Factory extends Factory
{
    protected \$model = {$modelName}::class;

    public function definition(): array
    {
        return [
            {$defString}
        ];
    }
}

PHP;
        
        $factoryPath = __DIR__ . "/../database/factories/{$modelName}Factory.php";
        file_put_contents($factoryPath, $factoryContent);
        echo "CREATED: {$modelName}Factory (table: {$table})\n";
        
    } catch (Exception $e) {
        echo "ERROR: {$modelName} - " . $e->getMessage() . "\n";
    }
}
