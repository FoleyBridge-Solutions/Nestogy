# Factory Fixes Needed for All Tests to Pass

## Strategy
For each factory with errors, we need to:
1. Add default values for NOT NULL columns
2. Remove references to columns that don't exist in the database
3. Update check constraint values to match database constraints

## Errors by Category

### Missing Columns (remove from factory):
- company_hierarchies.company_id
- quick_action_favorites.company_id  
- dashboard_widgets.dashboard_type
- recurring.proration_enabled
- quote_invoice_conversions.quote_id
- refund_requests.sla_deadline
- usage_records.usage_category
- usage_buckets.code
- usage_pools.code
- usage_tiers.description

### NOT NULL Violations (add defaults):
- expense_categories.code
- company_mail_settings.rate_limit_per_minute
- physical_mail_settings.default_mailing_class
- networks.gateway
- services.min_notice_hours
- pricing_rules.priority
- subsidiary_permissions.granter_company_id
- tax_api_settings.provider
- tax_api_query_cache.api_provider
- tax_api_query_cache.api_response
- quote_versions.quote_data
- quote_versions.version_number
- quote_approvals.user_id
- ticket_ratings.client_id
- service_tax_rates.calculation_method

### Check Constraint Violations (fix enum values):
- dunning_actions.action_type (check allowed values)
- dunning_sequences.action_type (check allowed values)
- pricing_rules.discount_type (check allowed values)

Total: 62 errors to fix
