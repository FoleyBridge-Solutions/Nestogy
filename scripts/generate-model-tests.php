#!/usr/bin/env php
<?php

$modelMap = [
    'Account' => 'App\\Domains\\Company\\Models\\Account',
    'AccountHold' => 'App\\Domains\\Company\\Models\\AccountHold',
    'Address' => 'App\\Domains\\Client\\Models\\Address',
    'AnalyticsSnapshot' => 'App\\Domains\\Core\\Models\\AnalyticsSnapshot',
    'Asset' => 'App\\Domains\\Asset\\Models\\Asset',
    'AuditLog' => 'App\\Domains\\Core\\Models\\AuditLog',
    'AutoPayment' => 'App\\Domains\\Financial\\Models\\AutoPayment',
    'CashFlowProjection' => 'App\\Domains\\Financial\\Models\\CashFlowProjection',
    'Category' => 'App\\Domains\\Financial\\Models\\Category',
    'ClientDocument' => 'App\\Domains\\Client\\Models\\ClientDocument',
    'ClientPortalAccess' => 'App\\Domains\\Client\\Models\\ClientPortalAccess',
    'ClientPortalSession' => 'App\\Domains\\Client\\Models\\ClientPortalSession',
    'ClientPortalUser' => 'App\\Domains\\Client\\Models\\ClientPortalUser',
    'CollectionNote' => 'App\\Domains\\Collections\\Models\\CollectionNote',
    'CommunicationLog' => 'App\\Domains\\Client\\Models\\CommunicationLog',
    'CompanyCustomization' => 'App\\Domains\\Company\\Models\\CompanyCustomization',
    'CompanyHierarchy' => 'App\\Domains\\Company\\Models\\CompanyHierarchy',
    'CompanyMailSettings' => 'App\\Domains\\Company\\Models\\CompanyMailSettings',
    'CompanySubscription' => 'App\\Domains\\Company\\Models\\CompanySubscription',
    'ComplianceCheck' => 'App\\Domains\\Tax\\Models\\ComplianceCheck',
    'ComplianceRequirement' => 'App\\Domains\\Tax\\Models\\ComplianceRequirement',
    'ContractConfiguration' => 'App\\Domains\\Contract\\Models\\ContractConfiguration',
    'CreditApplication' => 'App\\Domains\\Company\\Models\\CreditApplication',
    'CreditNote' => 'App\\Domains\\Financial\\Models\\CreditNote',
    'CreditNoteApproval' => 'App\\Domains\\Financial\\Models\\CreditNoteApproval',
    'CreditNoteItem' => 'App\\Domains\\Financial\\Models\\CreditNoteItem',
    'CrossCompanyUser' => 'App\\Domains\\Company\\Models\\CrossCompanyUser',
    'CustomQuickAction' => 'App\\Domains\\Core\\Models\\CustomQuickAction',
    'DashboardWidget' => 'App\\Domains\\Core\\Models\\DashboardWidget',
    'Document' => 'App\\Domains\\Core\\Models\\Document',
    'DunningAction' => 'App\\Domains\\Collections\\Models\\DunningAction',
    'DunningCampaign' => 'App\\Domains\\Collections\\Models\\DunningCampaign',
    'DunningSequence' => 'App\\Domains\\Collections\\Models\\DunningSequence',
    'ExpenseCategory' => 'App\\Domains\\Financial\\Models\\Category',
    'File' => 'App\\Domains\\Core\\Models\\File',
    'FinancialReport' => 'App\\Domains\\Financial\\Models\\FinancialReport',
    'InAppNotification' => 'App\\Domains\\Core\\Models\\InAppNotification',
    'KpiCalculation' => 'App\\Domains\\Core\\Models\\KpiCalculation',
    'Location' => 'App\\Domains\\Client\\Models\\Location',
    'MailQueue' => 'App\\Domains\\Core\\Models\\MailQueue',
    'MailTemplate' => 'App\\Domains\\Core\\Models\\MailTemplate',
    'Network' => 'App\\Domains\\Client\\Models\\ClientNetwork',
    'NotificationPreference' => 'App\\Domains\\Core\\Models\\NotificationPreference',
    'PaymentMethod' => 'App\\Domains\\Financial\\Models\\PaymentMethod',
    'PaymentPlan' => 'App\\Domains\\Financial\\Models\\PaymentPlan',
    'Permission' => 'App\\Domains\\Core\\Models\\Permission',
    'PermissionGroup' => 'App\\Domains\\Core\\Models\\PermissionGroup',
    'PhysicalMailSettings' => 'App\\Domains\\PhysicalMail\\Models\\PhysicalMailSettings',
    'PortalNotification' => 'App\\Domains\\Core\\Models\\PortalNotification',
    'PricingRule' => 'App\\Domains\\Product\\Models\\PricingRule',
    'ProductBundle' => 'App\\Domains\\Product\\Models\\ProductBundle',
    'ProductTaxData' => 'App\\Domains\\Tax\\Models\\ProductTaxData',
    'Project' => 'App\\Domains\\Project\\Models\\Project',
    'QuickActionFavorite' => 'App\\Domains\\Core\\Models\\QuickActionFavorite',
    'Quote' => 'App\\Domains\\Financial\\Models\\Quote',
    'QuoteApproval' => 'App\\Domains\\Financial\\Models\\QuoteApproval',
    'QuoteInvoiceConversion' => 'App\\Domains\\Financial\\Models\\QuoteInvoiceConversion',
    'QuoteTemplate' => 'App\\Domains\\Financial\\Models\\QuoteTemplate',
    'QuoteVersion' => 'App\\Domains\\Financial\\Models\\QuoteVersion',
    'Recurring' => 'App\\Domains\\Financial\\Models\\Recurring',
    'RecurringInvoice' => 'App\\Domains\\Financial\\Models\\RecurringInvoice',
    'RefundRequest' => 'App\\Domains\\Financial\\Models\\RefundRequest',
    'RefundTransaction' => 'App\\Domains\\Financial\\Models\\RefundTransaction',
    'RevenueMetric' => 'App\\Domains\\Financial\\Models\\RevenueMetric',
    'Role' => 'App\\Domains\\Core\\Models\\Role',
    'Service' => 'App\\Domains\\Product\\Models\\Service',
    'ServiceTaxRate' => 'App\\Domains\\Tax\\Models\\ServiceTaxRate',
    'Setting' => 'App\\Domains\\Core\\Models\\Setting',
    'SettingsConfiguration' => 'App\\Domains\\Core\\Models\\SettingsConfiguration',
    'SubscriptionPlan' => 'App\\Domains\\Product\\Models\\SubscriptionPlan',
    'SubsidiaryPermission' => 'App\\Domains\\Company\\Models\\SubsidiaryPermission',
    'Tag' => 'App\\Domains\\Core\\Models\\Tag',
    'Tax' => 'App\\Domains\\Tax\\Models\\Tax',
    'TaxApiQueryCache' => 'App\\Domains\\Tax\\Models\\TaxApiQueryCache',
    'TaxApiSettings' => 'App\\Domains\\Tax\\Models\\TaxApiSettings',
    'TaxCalculation' => 'App\\Domains\\Tax\\Models\\TaxCalculation',
    'TaxCategory' => 'App\\Domains\\Tax\\Models\\TaxCategory',
    'TaxExemption' => 'App\\Domains\\Tax\\Models\\TaxExemption',
    'TaxExemptionUsage' => 'App\\Domains\\Tax\\Models\\TaxExemptionUsage',
    'TaxJurisdiction' => 'App\\Domains\\Tax\\Models\\TaxJurisdiction',
    'TaxProfile' => 'App\\Domains\\Tax\\Models\\TaxProfile',
    'TaxRateHistory' => 'App\\Domains\\Tax\\Models\\TaxRateHistory',
    'Ticket' => 'App\\Domains\\Ticket\\Models\\Ticket',
    'TicketRating' => 'App\\Domains\\Ticket\\Models\\TicketRating',
    'TimeEntry' => 'App\\Domains\\Ticket\\Models\\TimeEntry',
    'UsageAlert' => 'App\\Domains\\Product\\Models\\UsageAlert',
    'UsageBucket' => 'App\\Domains\\Product\\Models\\UsageBucket',
    'UsagePool' => 'App\\Domains\\Product\\Models\\UsagePool',
    'UsageRecord' => 'App\\Domains\\Product\\Models\\UsageRecord',
    'UsageTier' => 'App\\Domains\\Product\\Models\\UsageTier',
    'UserSetting' => 'App\\Domains\\Core\\Models\\UserSetting',
    'Vendor' => 'App\\Domains\\Project\\Models\\Vendor',
    'VoIPTaxRate' => 'App\\Domains\\Tax\\Models\\VoIPTaxRate'
];

function generateTestContent($modelName, $fqn) {
    $snakeCase = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $modelName));
    $tableName = $snakeCase . 's';
    
    return <<<PHP
<?php

namespace Tests\Unit\Models;

use {$fqn};
use App\Domains\Company\Models\Company;
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

foreach ($modelMap as $modelName => $fqn) {
    $testPath = __DIR__ . "/../tests/Unit/Models/{$modelName}Test.php";
    
    if (file_exists($testPath)) {
        echo "Skipping {$modelName}Test.php (already exists)\n";
        continue;
    }
    
    $content = generateTestContent($modelName, $fqn);
    file_put_contents($testPath, $content);
    echo "Created {$modelName}Test.php\n";
}

echo "\nGenerated " . count($modelMap) . " model tests!\n";
