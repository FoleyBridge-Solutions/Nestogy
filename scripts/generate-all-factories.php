#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$models = [
    'Account', 'AccountHold', 'Address', 'AnalyticsSnapshot', 'AuditLog', 'AutoPayment',
    'CashFlowProjection', 'Category', 'ClientDocument', 'ClientPortalAccess', 
    'ClientPortalSession', 'ClientPortalUser', 'CollectionNote', 'CommunicationLog',
    'CompanyCustomization', 'CompanyHierarchy', 'CompanyMailSettings', 'CompanySubscription',
    'ComplianceCheck', 'ComplianceRequirement', 'ContractConfiguration', 'CreditApplication',
    'CreditNote', 'CreditNoteApproval', 'CreditNoteItem', 'CrossCompanyUser', 
    'CustomQuickAction', 'DashboardWidget', 'Document', 'DunningAction', 'DunningCampaign',
    'DunningSequence', 'ExpenseCategory', 'File', 'FinancialReport', 'InAppNotification',
    'InvoiceItem', 'KpiCalculation', 'MailQueue', 'MailTemplate', 'Network',
    'NotificationPreference', 'PaymentMethod', 'PaymentPlan', 'Permission', 'PermissionGroup',
    'PhysicalMailSettings', 'PortalNotification', 'PricingRule', 'ProductBundle',
    'ProductTaxData', 'QuickActionFavorite', 'Quote', 'QuoteApproval', 'QuoteInvoiceConversion',
    'QuoteTemplate', 'QuoteVersion', 'Recurring', 'RecurringInvoice', 'RefundRequest',
    'RefundTransaction', 'RevenueMetric', 'Role', 'Service', 'ServiceTaxRate', 'Setting',
    'SettingsConfiguration', 'SubsidiaryPermission', 'Tag', 'Tax', 'TaxApiQueryCache',
    'TaxApiSettings', 'TaxCalculation', 'TaxCategory', 'TaxExemption', 'TaxExemptionUsage',
    'TaxProfile', 'TaxRateHistory', 'TicketRating', 'TimeEntry', 'UsageAlert', 'UsageBucket',
    'UsagePool', 'UsageRecord', 'UsageTier', 'UserSetting', 'Vendor'
];

foreach ($models as $model) {
    $factoryPath = __DIR__ . "/../database/factories/{$model}Factory.php";
    
    if (file_exists($factoryPath)) {
        echo "Skipping {$model}Factory (exists)\n";
        continue;
    }

    $content = <<<PHP
<?php

namespace Database\Factories;

use App\Models\\{$model};
use Illuminate\Database\Eloquent\Factories\Factory;

class {$model}Factory extends Factory
{
    protected \$model = {$model}::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
        ];
    }
}

PHP;

    file_put_contents($factoryPath, $content);
    echo "Created {$model}Factory\n";
}

echo "\nCreated " . count($models) . " factories!\n";
