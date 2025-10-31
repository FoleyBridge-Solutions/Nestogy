# Remaining Database Seeders - TODO

## Current Status
After implementing 40+ seeders, there are **23 additional stub seeders** that need implementation.

## Already Completed (41 seeders)
✅ HR Domain (3 created + implemented)
✅ Tax Domain (10 implemented)  
✅ Collections Domain (4 implemented)
✅ Usage Domain (5 implemented)
✅ Financial Domain Part 1 (9 implemented)
✅ Financial Domain Part 2 (7 implemented)
✅ Compliance Domain (2 implemented)
✅ Company extras (2 implemented: CompanyHierarchy, CompanySubscription)

## Still TODO (23 seeders)

### High Priority - Core/Settings (6 seeders)
These affect foundational system functionality:
1. **SettingSeeder.php** - System settings
2. **SettingsConfigurationSeeder.php** - Settings metadata
3. **PermissionSeeder.php** - Granular permissions
4. **PermissionGroupSeeder.php** - Permission organization
5. **RoleSeeder.php** - User roles (likely covered by RolesAndPermissionsSeeder)
6. **SubsidiaryPermissionSeeder.php** - Multi-company permissions

### High Priority - Products/Services (5 seeders)
Core business functionality:
7. **ServiceSeeder.php** - Service offerings
8. **ProductBundleSeeder.php** - Product bundles
9. **PricingRuleSeeder.php** - Dynamic pricing
10. **SubscriptionPlanSeeder.php** - Recurring subscription plans
11. **RecurringSeeder.php** - Recurring billing base

### Medium Priority - Contracts/Domains (2 seeders)
12. **ContractConfigurationSeeder.php** - Contract templates/config
13. **DomainsSeeder.php** - Domain management

### Medium Priority - User/Portal Features (4 seeders)
14. **CrossCompanyUserSeeder.php** - Multi-company user access
15. **PortalNotificationSeeder.php** - Client portal notifications
16. **CustomQuickActionSeeder.php** - User quick actions
17. **QuickActionFavoriteSeeder.php** - Favorited quick actions

### Medium Priority - Communication (2 seeders)
18. **MailQueueSeeder.php** - Queued emails
19. **PhysicalMailSettingsSeeder.php** - Physical mail config (PostGrid)

### Lower Priority - Misc (4 seeders)
20. **FileSeeder.php** - File attachments
21. **IntegrationSeeder.php** - Third-party integrations
22. **KpiCalculationSeeder.php** - KPI metrics
23. **FinancialSeeder.php** - Already updated (composite seeder)

## Recommended Approach

### Option 1: Implement Critical Ones Only (Recommended for MVP)
Focus on the 11 high-priority seeders that affect core business logic:
- Settings/Permissions (6 seeders)
- Products/Services (5 seeders)

**Time estimate:** 2-3 hours  
**Impact:** Core business functionality fully seeded

### Option 2: Implement All 23 (Complete Coverage)
Implement every remaining seeder for 100% coverage.

**Time estimate:** 5-7 hours  
**Impact:** Absolutely complete seeding system

### Option 3: Batch Implementation
Create simple implementations for all 23 seeders that:
- Call the factory
- Create realistic volumes
- Skip gracefully if dependencies missing

**Time estimate:** 1-2 hours  
**Impact:** Seeders exist but may be basic

## Dependencies to Check

Before implementing, need to verify:
- [ ] Do factories exist for all 23 models?
- [ ] Are some covered by existing seeders? (e.g., RoleSeeder vs RolesAndPermissionsSeeder)
- [ ] Which ones are critical vs nice-to-have?

## Next Steps

1. Verify which seeders are truly needed (some may be duplicates)
2. Check which factories exist
3. Prioritize based on business impact
4. Implement in batches by priority

---

## Quick Reference: All Stubs

```bash
ContractConfigurationSeeder.php
CrossCompanyUserSeeder.php
CustomQuickActionSeeder.php
DomainsSeeder.php
FileSeeder.php
FinancialSeeder.php               # Already handled (composite)
IntegrationSeeder.php
KpiCalculationSeeder.php
MailQueueSeeder.php
PermissionGroupSeeder.php
PermissionSeeder.php
PhysicalMailSettingsSeeder.php
PortalNotificationSeeder.php
PricingRuleSeeder.php
ProductBundleSeeder.php
QuickActionFavoriteSeeder.php
RecurringSeeder.php
RoleSeeder.php                     # Might be covered by RolesAndPermissionsSeeder
ServiceSeeder.php
SettingSeeder.php
SettingsConfigurationSeeder.php
SubscriptionPlanSeeder.php
SubsidiaryPermissionSeeder.php
```
