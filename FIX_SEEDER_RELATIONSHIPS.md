# Seeder Relationship Fixes Needed

## Issues Found

### 1. CompanyHierarchy
- **Issue**: Uses ancestor_id/descendant_id, not parent relationship
- **Status**: ✅ FIXED

### 2. CrossCompanyUser  
- **Check**: Uses user_id and company_id
- **Need to verify**: Factory relationships

### 3. PortalNotification
- **Check**: Uses client_portal_user_id
- **Need to verify**: Correct relationship name

### 4. QuickActionFavorite
- **Check**: Uses user_id and custom_quick_action_id
- **Need to verify**: Factory setup

### 5. SubsidiaryPermission
- **Check**: Relationships with Company
- **Need to verify**: Foreign keys

## Let me check each one systematically...
