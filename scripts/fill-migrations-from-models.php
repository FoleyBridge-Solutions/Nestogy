#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$modelToTable = [
    'AccountHold' => 'account_holds',
    'AnalyticsSnapshot' => 'analytics_snapshots',
    'AutoPayment' => 'auto_payments',
    'CashFlowProjection' => 'cash_flow_projections',
    'ClientPortalSession' => 'client_portal_sessions',
    'ClientPortalUser' => 'client_portal_users',
    'CollectionNote' => 'collection_notes',
    'ComplianceCheck' => 'compliance_checks',
    'ComplianceRequirement' => 'compliance_requirements',
    'CreditApplication' => 'credit_applications',
    'CreditNoteApproval' => 'credit_note_approvals',
    'CreditNoteItem' => 'credit_note_items',
    'CreditNote' => 'credit_notes',
    'DunningAction' => 'dunning_actions',
    'DunningCampaign' => 'dunning_campaigns',
    'DunningSequence' => 'dunning_sequences',
    'FinancialReport' => 'financial_reports',
    'KpiCalculation' => 'kpi_calculations',
    'PaymentPlan' => 'payment_plans',
    'PermissionGroup' => 'permission_groups',
    'QuoteInvoiceConversion' => 'quote_invoice_conversions',
    'RecurringInvoice' => 'recurring_invoices',
    'RefundRequest' => 'refund_requests',
    'RefundTransaction' => 'refund_transactions',
    'RevenueMetric' => 'revenue_metrics',
    'TaxCategory' => 'tax_categories',
    'TaxExemption' => 'tax_exemptions',
    'UsageAlert' => 'usage_alerts',
    'UsageBucket' => 'usage_buckets',
    'UsagePool' => 'usage_pools',
    'UsageRecord' => 'usage_records',
    'UsageTier' => 'usage_tiers',
];

foreach ($modelToTable as $model => $table) {
    $class = "App\\Models\\{$model}";
    
    if (!class_exists($class)) {
        echo "SKIP: {$model} (class doesn't exist)\n";
        continue;
    }
    
    try {
        $instance = new $class;
        $fillable = $instance->getFillable();
        
        // Generate table columns based on fillable
        $columns = [];
        $columns[] = "\$table->id();";
        $columns[] = "\$table->unsignedBigInteger('company_id');";
        
        foreach ($fillable as $field) {
            if ($field === 'company_id') continue;
            
            if (str_ends_with($field, '_id')) {
                $columns[] = "\$table->unsignedBigInteger('{$field}')->nullable();";
            } elseif (str_contains($field, 'email')) {
                $columns[] = "\$table->string('{$field}')->nullable();";
            } elseif (str_contains($field, 'name') || str_contains($field, 'title')) {
                $columns[] = "\$table->string('{$field}');";
            } elseif (str_contains($field, 'description') || str_contains($field, 'notes')) {
                $columns[] = "\$table->text('{$field}')->nullable();";
            } elseif (str_contains($field, 'amount') || str_contains($field, 'price') || str_contains($field, 'total')) {
                $columns[] = "\$table->decimal('{$field}', 15, 2)->default(0);";
            } elseif (str_contains($field, 'is_') || str_contains($field, 'active') || str_contains($field, 'enabled')) {
                $columns[] = "\$table->boolean('{$field}')->default(false);";
            } elseif (str_contains($field, 'date') || str_contains($field, '_at')) {
                $columns[] = "\$table->timestamp('{$field}')->nullable();";
            } elseif (str_contains($field, 'status')) {
                $columns[] = "\$table->string('{$field}')->default('active');";
            } else {
                $columns[] = "\$table->string('{$field}')->nullable();";
            }
        }
        
        $columns[] = "\$table->timestamps();";
        $columns[] = "\$table->softDeletes('archived_at');";
        $columns[] = "";
        $columns[] = "\$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');";
        
        $columnString = implode("\n            ", $columns);
        
        // Find the migration file
        $migrations = glob(__DIR__ . "/../database/migrations/*create_{$table}_table.php");
        if (empty($migrations)) {
            echo "NO MIGRATION FILE: {$model}\n";
            continue;
        }
        
        $migrationFile = $migrations[0];
        $content = file_get_contents($migrationFile);
        
        // Replace the up() method
        $newContent = preg_replace(
            '/public function up\(\): void\s*\{.*?Schema::create.*?\{.*?\$table->id\(\);.*?\$table->timestamps\(\);.*?\}\);.*?\}/s',
            "public function up(): void\n    {\n        Schema::create('{$table}', function (Blueprint \$table) {\n            {$columnString}\n        });\n    }",
            $content
        );
        
        file_put_contents($migrationFile, $newContent);
        echo "UPDATED: {$model} migration ({$table})\n";
        
    } catch (Exception $e) {
        echo "ERROR: {$model} - " . $e->getMessage() . "\n";
    }
}

echo "\nDONE MASTER!\n";
