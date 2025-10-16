#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$modelMap = [
    'Account' => 'App\\Domains\\Company\\Models\\Account',
    'Address' => 'App\\Domains\\Client\\Models\\Address',
    'AuditLog' => 'App\\Domains\\Core\\Models\\AuditLog',
    'Category' => 'App\\Domains\\Financial\\Models\\Category',
    'ClientDocument' => 'App\\Domains\\Client\\Models\\ClientDocument',
    'CompanyCustomization' => 'App\\Domains\\Company\\Models\\CompanyCustomization',
    'CompanyMailSettings' => 'App\\Domains\\Company\\Models\\CompanyMailSettings',
    'CompanySubscription' => 'App\\Domains\\Company\\Models\\CompanySubscription',
    'ContractConfiguration' => 'App\\Domains\\Contract\\Models\\ContractConfiguration',
    'CrossCompanyUser' => 'App\\Domains\\Company\\Models\\CrossCompanyUser',
    'CustomQuickAction' => 'App\\Domains\\Core\\Models\\CustomQuickAction',
    'DashboardWidget' => 'App\\Domains\\Core\\Models\\DashboardWidget',
    'Document' => 'App\\Domains\\Core\\Models\\Document',
    'ExpenseCategory' => 'App\\Domains\\Financial\\Models\\Category',
    'File' => 'App\\Domains\\Core\\Models\\File',
    'MailQueue' => 'App\\Domains\\Core\\Models\\MailQueue',
    'MailTemplate' => 'App\\Domains\\Core\\Models\\MailTemplate',
    'Network' => 'App\\Domains\\Client\\Models\\ClientNetwork',
    'PaymentMethod' => 'App\\Domains\\Financial\\Models\\PaymentMethod',
    'Permission' => 'App\\Domains\\Core\\Models\\Permission',
    'PhysicalMailSettings' => 'App\\Domains\\PhysicalMail\\Models\\PhysicalMailSettings',
    'PortalNotification' => 'App\\Domains\\Core\\Models\\PortalNotification',
    'PricingRule' => 'App\\Domains\\Product\\Models\\PricingRule',
    'ProductBundle' => 'App\\Domains\\Product\\Models\\ProductBundle',
    'ProductTaxData' => 'App\\Domains\\Tax\\Models\\ProductTaxData',
    'QuickActionFavorite' => 'App\\Domains\\Core\\Models\\QuickActionFavorite',
    'Quote' => 'App\\Domains\\Financial\\Models\\Quote',
    'QuoteApproval' => 'App\\Domains\\Financial\\Models\\QuoteApproval',
    'QuoteTemplate' => 'App\\Domains\\Financial\\Models\\QuoteTemplate',
    'QuoteVersion' => 'App\\Domains\\Financial\\Models\\QuoteVersion',
    'Recurring' => 'App\\Domains\\Financial\\Models\\Recurring',
    'Role' => 'App\\Domains\\Core\\Models\\Role',
    'Service' => 'App\\Domains\\Product\\Models\\Service',
    'ServiceTaxRate' => 'App\\Domains\\Tax\\Models\\ServiceTaxRate',
    'Setting' => 'App\\Domains\\Core\\Models\\Setting',
    'SettingsConfiguration' => 'App\\Domains\\Core\\Models\\SettingsConfiguration',
    'Tag' => 'App\\Domains\\Core\\Models\\Tag',
    'Tax' => 'App\\Domains\\Tax\\Models\\Tax',
    'TaxApiQueryCache' => 'App\\Domains\\Tax\\Models\\TaxApiQueryCache',
    'TaxApiSettings' => 'App\\Domains\\Tax\\Models\\TaxApiSettings',
    'TaxCalculation' => 'App\\Domains\\Tax\\Models\\TaxCalculation',
    'TaxProfile' => 'App\\Domains\\Tax\\Models\\TaxProfile',
    'TicketRating' => 'App\\Domains\\Ticket\\Models\\TicketRating',
    'TimeEntry' => 'App\\Domains\\Ticket\\Models\\TimeEntry',
    'UsageRecord' => 'App\\Domains\\Product\\Models\\UsageRecord',
    'UsageTier' => 'App\\Domains\\Product\\Models\\UsageTier',
    'UserSetting' => 'App\\Domains\\Core\\Models\\UserSetting',
    'Vendor' => 'App\\Domains\\Project\\Models\\Vendor'
];

foreach ($modelMap as $modelName => $class) {
    
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
            
            if ($field === 'currency_code') {
                $definition[] = "'{$field}' => 'USD'";
            } elseif (str_contains($field, 'email')) {
                $definition[] = "'{$field}' => \$this->faker->safeEmail";
            } elseif (str_contains($field, 'name') || str_contains($field, 'title')) {
                $definition[] = "'{$field}' => \$this->faker->words(3, true)";
            } elseif (str_contains($field, 'description') || str_contains($field, 'notes')) {
                $definition[] = "'{$field}' => \$this->faker->optional()->sentence";
            } elseif (str_contains($field, 'amount') || str_contains($field, 'balance') || str_contains($field, 'price') || str_contains($field, 'total')) {
                $definition[] = "'{$field}' => \$this->faker->randomFloat(2, 0, 10000)";
            } elseif (str_contains($field, 'status')) {
                $definition[] = "'{$field}' => 'active'";
            } elseif (str_contains($field, 'is_') || str_contains($field, 'active') || str_contains($field, 'enabled')) {
                $definition[] = "'{$field}' => \$this->faker->boolean(70)";
            } elseif (str_contains($field, 'code') && strlen($field) < 20) {
                $definition[] = "'{$field}' => \$this->faker->word";
            } elseif (str_contains($field, 'url') || str_contains($field, 'link')) {
                $definition[] = "'{$field}' => \$this->faker->optional()->url";
            } elseif (str_contains($field, 'phone')) {
                $definition[] = "'{$field}' => \$this->faker->optional()->phoneNumber";
            } elseif (str_contains($field, 'type')) {
                $definition[] = "'{$field}' => \$this->faker->numberBetween(1, 5)";
            } elseif (str_contains($field, '_at') || str_contains($field, 'date')) {
                $definition[] = "'{$field}' => \$this->faker->optional()->dateTimeBetween('-1 year', 'now')";
            } else {
                $definition[] = "'{$field}' => \$this->faker->optional()->word";
            }
        }
        
        $defString = implode(",\n            ", $definition);
        
        $factoryContent = <<<PHP
<?php

namespace Database\Factories;

use {$class};
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
