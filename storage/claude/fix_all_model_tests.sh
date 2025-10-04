#!/bin/bash

# This script fixes all model test factory issues

cd /opt/nestogy

echo "Starting comprehensive factory fixes..."

# List of all fixes needed based on error analysis:

# 1. CompanyHierarchyTest - already fixed

# 2. CompanyMailSettings - add rate_limit_per_minute
# 3. DashboardWidget - remove dashboard_type
# 4. DunningAction - fix action_type enum
# 5. DunningSequence - fix action_type enum
# 6. Network - add gateway
# 7. PhysicalMailSettings - add default_mailing_class
# 8. PricingRule - add priority, fix discount_type
# 9. QuickActionFavorite - remove company_id
# 10. QuoteApproval - add user_id
# 11. QuoteInvoiceConversion - remove quote_id
# 12. QuoteVersion - add quote_data, version_number
# 13. Recurring - remove proration_enabled
# 14. RefundRequest - remove sla_deadline
# 15. ServiceTaxRate - add calculation_method
# 16. Service - add min_notice_hours
# 17. SubsidiaryPermission - add granter_company_id
# 18. TaxApiQueryCache - add api_provider, api_response
# 19. TaxApiSettings - add provider
# 20. TicketRating - add client_id
# 21. UsageBucket - remove code
# 22. UsagePool - remove code
# 23. UsageRecord - remove usage_category
# 24. UsageTier - remove description
# 25. RefundTransaction - check if has issues
# 26. PortalNotification - check if has issues
# 27. Setting - check if has issues
# 28. TaxCalculation - check if has issues
# 29. TimeEntry - check if has issues

echo "All model test fixes will be applied via individual edit commands"
echo "Run individual factory fixes from the main session"

