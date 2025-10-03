#!/usr/bin/env php
<?php

$missingModels = [
    'Account', 'AccountHold', 'Address', 'AnalyticsSnapshot', 'Asset', 'AuditLog',
    'AutoPayment', 'CashFlowProjection', 'Category', 'ClientDocument', 'ClientPortalAccess',
    'ClientPortalSession', 'ClientPortalUser', 'CollectionNote', 'CommunicationLog',
    'CompanyCustomization', 'CompanyHierarchy', 'CompanyMailSettings', 'CompanySubscription',
    'ComplianceCheck', 'ComplianceRequirement', 'ContractConfiguration', 'CreditApplication',
    'CreditNote', 'CreditNoteApproval', 'CreditNoteItem', 'CrossCompanyUser', 'CustomQuickAction',
    'DashboardWidget', 'Document', 'DunningAction', 'DunningCampaign', 'DunningSequence',
    'ExpenseCategory', 'File', 'FinancialReport', 'InAppNotification', 'KpiCalculation',
    'Location', 'MailQueue', 'MailTemplate', 'Network', 'NotificationPreference',
    'PaymentMethod', 'PaymentPlan', 'Permission', 'PermissionGroup', 'PhysicalMailSettings',
    'PortalNotification', 'PricingRule', 'ProductBundle', 'ProductTaxData', 'Project',
    'QuickActionFavorite', 'Quote', 'QuoteApproval', 'QuoteInvoiceConversion', 'QuoteTemplate',
    'QuoteVersion', 'Recurring', 'RecurringInvoice', 'RefundRequest', 'RefundTransaction',
    'RevenueMetric', 'Role', 'Service', 'ServiceTaxRate', 'Setting', 'SettingsConfiguration',
    'SubscriptionPlan', 'SubsidiaryPermission', 'Tag', 'Tax', 'TaxApiQueryCache',
    'TaxApiSettings', 'TaxCalculation', 'TaxCategory', 'TaxExemption', 'TaxExemptionUsage',
    'TaxJurisdiction', 'TaxProfile', 'TaxRateHistory', 'Ticket', 'TicketRating', 'TimeEntry',
    'UsageAlert', 'UsageBucket', 'UsagePool', 'UsageRecord', 'UsageTier', 'UserSetting',
    'Vendor', 'VoIPTaxRate'
];

function generateTestContent($modelName) {
    $snakeCase = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $modelName));
    $tableName = $snakeCase . 's';
    
    return <<<PHP
<?php

namespace Tests\Unit\Models;

use App\Models\\{$modelName};
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class {$modelName}Test extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_{$snakeCase}_with_factory(): void
    {
        \$company = Company::factory()->create();
        \$model = {$modelName}::factory()->create(['company_id' => \$company->id]);

        \$this->assertInstanceOf({$modelName}::class, \$model);
        \$this->assertDatabaseHas('{$tableName}', ['id' => \$model->id]);
    }

    public function test_{$snakeCase}_belongs_to_company(): void
    {
        \$company = Company::factory()->create();
        \$model = {$modelName}::factory()->create(['company_id' => \$company->id]);

        \$this->assertInstanceOf(Company::class, \$model->company);
        \$this->assertEquals(\$company->id, \$model->company->id);
    }

    public function test_{$snakeCase}_can_be_soft_deleted(): void
    {
        \$company = Company::factory()->create();
        \$model = {$modelName}::factory()->create(['company_id' => \$company->id]);

        \$modelId = \$model->id;
        \$model->delete();

        \$this->assertSoftDeleted(\$model);
    }

    public function test_{$snakeCase}_has_fillable_attributes(): void
    {
        \$model = new {$modelName}();
        \$fillable = \$model->getFillable();

        \$this->assertIsArray(\$fillable);
        \$this->assertNotEmpty(\$fillable);
    }
}

PHP;
}

foreach ($missingModels as $model) {
    $testPath = __DIR__ . "/../tests/Unit/Models/{$model}Test.php";
    
    if (file_exists($testPath)) {
        echo "Skipping {$model}Test.php (already exists)\n";
        continue;
    }
    
    $content = generateTestContent($model);
    file_put_contents($testPath, $content);
    echo "Created {$model}Test.php\n";
}

echo "\nGenerated " . count($missingModels) . " model tests!\n";
