#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$modelMap = [
    'Account' => 'App\\Domains\\Company\\Models\\Account',
    'AccountHold' => 'App\\Domains\\Company\\Models\\AccountHold',
    'Address' => 'App\\Domains\\Client\\Models\\Address',
    'AnalyticsSnapshot' => 'App\\Domains\\Core\\Models\\AnalyticsSnapshot',
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
    'InvoiceItem' => 'App\\Domains\\Financial\\Models\\InvoiceItem',
    'KpiCalculation' => 'App\\Domains\\Core\\Models\\KpiCalculation',
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
    'SubsidiaryPermission' => 'App\\Domains\\Company\\Models\\SubsidiaryPermission',
    'Tag' => 'App\\Domains\\Core\\Models\\Tag',
    'Tax' => 'App\\Domains\\Tax\\Models\\Tax',
    'TaxApiQueryCache' => 'App\\Domains\\Tax\\Models\\TaxApiQueryCache',
    'TaxApiSettings' => 'App\\Domains\\Tax\\Models\\TaxApiSettings',
    'TaxCalculation' => 'App\\Domains\\Tax\\Models\\TaxCalculation',
    'TaxCategory' => 'App\\Domains\\Tax\\Models\\TaxCategory',
    'TaxExemption' => 'App\\Domains\\Tax\\Models\\TaxExemption',
    'TaxExemptionUsage' => 'App\\Domains\\Tax\\Models\\TaxExemptionUsage',
    'TaxProfile' => 'App\\Domains\\Tax\\Models\\TaxProfile',
    'TaxRateHistory' => 'App\\Domains\\Tax\\Models\\TaxRateHistory',
    'TicketRating' => 'App\\Domains\\Ticket\\Models\\TicketRating',
    'TimeEntry' => 'App\\Domains\\Ticket\\Models\\TimeEntry',
    'UsageAlert' => 'App\\Domains\\Product\\Models\\UsageAlert',
    'UsageBucket' => 'App\\Domains\\Product\\Models\\UsageBucket',
    'UsagePool' => 'App\\Domains\\Product\\Models\\UsagePool',
    'UsageRecord' => 'App\\Domains\\Product\\Models\\UsageRecord',
    'UsageTier' => 'App\\Domains\\Product\\Models\\UsageTier',
    'UserSetting' => 'App\\Domains\\Core\\Models\\UserSetting',
    'Vendor' => 'App\\Domains\\Project\\Models\\Vendor'
];

foreach ($modelMap as $model => $fqn) {
    $factoryPath = __DIR__ . "/../database/factories/{$model}Factory.php";
    
    if (file_exists($factoryPath)) {
        echo "Skipping {$model}Factory (exists)\n";
        continue;
    }

    $content = <<<PHP
<?php

namespace Database\Factories;

use {$fqn};
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
