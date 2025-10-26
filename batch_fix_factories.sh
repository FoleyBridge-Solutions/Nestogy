#!/bin/bash

# List of models that commonly fail - fix their factories to match actual schemas

fix_factory_if_exists() {
    local model_name=$1
    local factory_path=$(find database/factories -name "${model_name}Factory.php" 2>/dev/null | head -1)
    
    if [ -n "$factory_path" ]; then
        echo "Found: $factory_path"
        return 0
    else
        echo "Not found: ${model_name}Factory"
        return 1
    fi
}

# Check which factories exist
models=(
    "CashFlowProjection"
    "CompanyCustomization"
    "CompanyHierarchy"
    "CompanyMailSettings"
    "CompanySubscription"
    "ComplianceCheck"
    "ComplianceRequirement"
    "ContractConfiguration"
    "CreditApplication"
    "CreditNoteApproval"
    "CreditNoteItem"
    "CreditNote"
    "CrossCompanyUser"
    "CustomQuickAction"
    "DashboardWidget"
    "InAppNotification"
    "MailQueue"
    "NotificationPreference"
    "PaymentMethod"
    "PaymentPlan"
    "PermissionGroup"
    "Permission"
    "PhysicalMailSettings"
    "PortalNotification"
    "QuickActionFavorite"
    "QuoteApproval"
    "RefundRequest"
    "RefundTransaction"
    "Tag"
    "UserSetting"
)

echo "=== Checking factories ==="
for model in "${models[@]}"; do
    fix_factory_if_exists "$model"
done

