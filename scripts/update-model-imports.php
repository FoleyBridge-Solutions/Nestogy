#!/usr/bin/env php
<?php

/**
 * Model Import Update Script
 *
 * Updates all `use App\Models\*` imports to new domain-based namespaces
 */

// Model to new namespace mapping
$modelMapping = [
    // Core Domain
    'User' => 'App\Domains\Core\Models\User',
    'Role' => 'App\Domains\Core\Models\Role',
    'Permission' => 'App\Domains\Core\Models\Permission',
    'PermissionGroup' => 'App\Domains\Core\Models\PermissionGroup',
    'Setting' => 'App\Domains\Core\Models\Setting',
    'SettingsConfiguration' => 'App\Domains\Core\Models\SettingsConfiguration',
    'UserSetting' => 'App\Domains\Core\Models\UserSetting',
    'Tag' => 'App\Domains\Core\Models\Tag',
    'File' => 'App\Domains\Core\Models\File',
    'Document' => 'App\Domains\Core\Models\Document',
    'AuditLog' => 'App\Domains\Core\Models\AuditLog',
    'NotificationPreference' => 'App\Domains\Core\Models\NotificationPreference',
    'InAppNotification' => 'App\Domains\Core\Models\InAppNotification',
    'MailTemplate' => 'App\Domains\Core\Models\MailTemplate',
    'MailQueue' => 'App\Domains\Core\Models\MailQueue',
    'DashboardWidget' => 'App\Domains\Core\Models\DashboardWidget',
    'CustomQuickAction' => 'App\Domains\Core\Models\CustomQuickAction',
    'QuickActionFavorite' => 'App\Domains\Core\Models\QuickActionFavorite',
    'AnalyticsSnapshot' => 'App\Domains\Core\Models\AnalyticsSnapshot',
    'KpiCalculation' => 'App\Domains\Core\Models\KpiCalculation',
    'PortalNotification' => 'App\Domains\Core\Models\PortalNotification',

    // Company Domain
    'Company' => 'App\Domains\Company\Models\Company',
    'CompanyCustomization' => 'App\Domains\Company\Models\CompanyCustomization',
    'CompanyHierarchy' => 'App\Domains\Company\Models\CompanyHierarchy',
    'CompanyMailSettings' => 'App\Domains\Company\Models\CompanyMailSettings',
    'CompanySubscription' => 'App\Domains\Company\Models\CompanySubscription',
    'CrossCompanyUser' => 'App\Domains\Company\Models\CrossCompanyUser',
    'SubsidiaryPermission' => 'App\Domains\Company\Models\SubsidiaryPermission',
    'Account' => 'App\Domains\Company\Models\Account',
    'AccountHold' => 'App\Domains\Company\Models\AccountHold',
    'CreditApplication' => 'App\Domains\Company\Models\CreditApplication',

    // Tax Domain
    'Tax' => 'App\Domains\Tax\Models\Tax',
    'TaxCalculation' => 'App\Domains\Tax\Models\TaxCalculation',
    'TaxProfile' => 'App\Domains\Tax\Models\TaxProfile',
    'TaxJurisdiction' => 'App\Domains\Tax\Models\TaxJurisdiction',
    'TaxRateHistory' => 'App\Domains\Tax\Models\TaxRateHistory',
    'TaxExemption' => 'App\Domains\Tax\Models\TaxExemption',
    'TaxExemptionUsage' => 'App\Domains\Tax\Models\TaxExemptionUsage',
    'TaxApiSettings' => 'App\Domains\Tax\Models\TaxApiSettings',
    'TaxApiQueryCache' => 'App\Domains\Tax\Models\TaxApiQueryCache',
    'ServiceTaxRate' => 'App\Domains\Tax\Models\ServiceTaxRate',
    'ProductTaxData' => 'App\Domains\Tax\Models\ProductTaxData',
    'ComplianceCheck' => 'App\Domains\Tax\Models\ComplianceCheck',
    'ComplianceRequirement' => 'App\Domains\Tax\Models\ComplianceRequirement',

    // Collections Domain
    'DunningSequence' => 'App\Domains\Collections\Models\DunningSequence',
    'DunningCampaign' => 'App\Domains\Collections\Models\DunningCampaign',
    'DunningAction' => 'App\Domains\Collections\Models\DunningAction',
    'CollectionNote' => 'App\Domains\Collections\Models\CollectionNote',

    // Financial Domain
    'Invoice' => 'App\Domains\Financial\Models\Invoice',
    'InvoiceItem' => 'App\Domains\Financial\Models\InvoiceItem',
    'RecurringInvoice' => 'App\Domains\Financial\Models\RecurringInvoice',
    'Recurring' => 'App\Domains\Financial\Models\Recurring',
    'Payment' => 'App\Domains\Financial\Models\Payment',
    'PaymentMethod' => 'App\Domains\Financial\Models\PaymentMethod',
    'PaymentApplication' => 'App\Domains\Financial\Models\PaymentApplication',
    'PaymentPlan' => 'App\Domains\Financial\Models\PaymentPlan',
    'AutoPayment' => 'App\Domains\Financial\Models\AutoPayment',
    'CreditNote' => 'App\Domains\Financial\Models\CreditNote',
    'CreditNoteItem' => 'App\Domains\Financial\Models\CreditNoteItem',
    'CreditNoteApproval' => 'App\Domains\Financial\Models\CreditNoteApproval',
    'ClientCredit' => 'App\Domains\Financial\Models\ClientCredit',
    'ClientCreditApplication' => 'App\Domains\Financial\Models\ClientCreditApplication',
    'RefundRequest' => 'App\Domains\Financial\Models\RefundRequest',
    'RefundTransaction' => 'App\Domains\Financial\Models\RefundTransaction',
    'Quote' => 'App\Domains\Financial\Models\Quote',
    'QuoteApproval' => 'App\Domains\Financial\Models\QuoteApproval',
    'QuoteTemplate' => 'App\Domains\Financial\Models\QuoteTemplate',
    'QuoteVersion' => 'App\Domains\Financial\Models\QuoteVersion',
    'QuoteInvoiceConversion' => 'App\Domains\Financial\Models\QuoteInvoiceConversion',
    'Category' => 'App\Domains\Financial\Models\Category',
    'CashFlowProjection' => 'App\Domains\Financial\Models\CashFlowProjection',
    'RevenueMetric' => 'App\Domains\Financial\Models\RevenueMetric',
    'FinancialReport' => 'App\Domains\Financial\Models\FinancialReport',
    'Expense' => 'App\Domains\Financial\Models\Expense',

    // Client Domain
    'Contact' => 'App\Domains\Client\Models\Contact',
    'Address' => 'App\Domains\Client\Models\Address',
    'Location' => 'App\Domains\Client\Models\Location',
    'Network' => 'App\Domains\Client\Models\Network',
    'ClientDocument' => 'App\Domains\Client\Models\ClientDocument',
    'CommunicationLog' => 'App\Domains\Client\Models\CommunicationLog',
    'ClientPortalAccess' => 'App\Domains\Client\Models\ClientPortalAccess',
    'ClientPortalSession' => 'App\Domains\Client\Models\ClientPortalSession',
    'ClientPortalUser' => 'App\Domains\Client\Models\ClientPortalUser',

    // Product Domain
    'Product' => 'App\Domains\Product\Models\Product',
    'Service' => 'App\Domains\Product\Models\Service',
    'ProductBundle' => 'App\Domains\Product\Models\ProductBundle',
    'SubscriptionPlan' => 'App\Domains\Product\Models\SubscriptionPlan',
    'PricingRule' => 'App\Domains\Product\Models\PricingRule',
    'UsageRecord' => 'App\Domains\Product\Models\UsageRecord',
    'UsagePool' => 'App\Domains\Product\Models\UsagePool',
    'UsageBucket' => 'App\Domains\Product\Models\UsageBucket',
    'UsageAlert' => 'App\Domains\Product\Models\UsageAlert',
    'UsageTier' => 'App\Domains\Product\Models\UsageTier',

    // Asset Domain
    'Asset' => 'App\Domains\Asset\Models\Asset',

    // Ticket Domain
    'TicketRating' => 'App\Domains\Ticket\Models\TicketRating',
    'TimeEntry' => 'App\Domains\Ticket\Models\TimeEntry',

    // Project Domain
    'Project' => 'App\Domains\Project\Models\Project',
    'Vendor' => 'App\Domains\Project\Models\Vendor',

    // Contract Domain
    'ContractConfiguration' => 'App\Domains\Contract\Models\ContractConfiguration',

    // PhysicalMail Domain
    'PhysicalMailSettings' => 'App\Domains\PhysicalMail\Models\PhysicalMailSettings',
];

// Directories to scan
$directories = [
    'app/Http',
    'app/Livewire',
    'app/Services',
    'app/Policies',
    'app/Observers',
    'app/Jobs',
    'app/Events',
    'app/Listeners',
    'app/Console',
    'app/Providers',
    'app/Domains',
    'database/factories',
    'database/seeders',
    'tests',
    'config',
];

$stats = [
    'files_scanned' => 0,
    'files_updated' => 0,
    'replacements' => 0,
];

echo "Model Import Update Script\n";
echo "==========================\n\n";

foreach ($directories as $directory) {
    if (!is_dir($directory)) {
        echo "⚠ Skipping $directory (not found)\n";
        continue;
    }

    echo "Scanning $directory...\n";
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($files as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $stats['files_scanned']++;
        $filePath = $file->getPathname();
        $content = file_get_contents($filePath);
        $originalContent = $content;
        $fileUpdated = false;

        // Replace each model import
        foreach ($modelMapping as $model => $newNamespace) {
            $pattern = '/use\s+App\\\\Models\\\\' . preg_quote($model, '/') . '\s*;/';
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, 'use ' . $newNamespace . ';', $content);
                $fileUpdated = true;
                $stats['replacements']++;
            }
        }

        // Also handle Traits namespace
        $content = preg_replace('/use\s+App\\\\Models\\\\Traits\\\\/', 'use App\\Domains\\Core\\Traits\\', $content);

        // Handle Settings namespace
        $content = preg_replace('/use\s+App\\\\Models\\\\Settings\\\\/', 'use App\\Domains\\Core\\Models\\Settings\\', $content);

        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $stats['files_updated']++;
            echo "  ✓ Updated: $filePath\n";
        }
    }
}

echo "\n";
echo "Summary\n";
echo "=======\n";
echo "Files scanned: {$stats['files_scanned']}\n";
echo "Files updated: {$stats['files_updated']}\n";
echo "Total replacements: {$stats['replacements']}\n";
echo "\n✓ Migration complete!\n";
