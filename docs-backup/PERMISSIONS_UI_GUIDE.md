# Permissions Management UI Guide

## Overview

The Permissions Management interface provides a comprehensive, user-friendly system for managing roles, permissions, and user access control in Nestogy ERP. Built with Livewire and FluxUI, it offers a modern, reactive experience without page reloads.

## Accessing the Interface

**URL**: `/settings/permissions/manage`

**Required Permission**: `system.permissions.manage` (Admin/Super-Admin only)

## Architecture

### Components

The permissions UI is composed of multiple Livewire components:

1. **PermissionsManagement** (`app/Livewire/Settings/PermissionsManagement.php`)
   - Main container component
   - Tab navigation and overview dashboard

2. **RolesList** (`app/Livewire/Settings/RolesList.php`)
   - Role CRUD operations
   - Permission assignment to roles

3. **PermissionMatrix** (`app/Livewire/Settings/PermissionMatrix.php`)
   - Grid view of all roles and permissions
   - Quick toggle permissions

4. **UserPermissions** (`app/Livewire/Settings/UserPermissions.php`)
   - User-specific role and permission assignment
   - View effective permissions

### FluxUI Components Used

- **Tabs**: Main navigation
- **Cards**: Content containers
- **Tables**: Data display
- **Badges**: Visual indicators
- **Checkboxes**: Permission toggles
- **Accordion**: Collapsible permission groups
- **Buttons**: Actions
- **Inputs**: Search and filters
- **Select**: Dropdown filters
- **Icons**: Visual indicators
- **Modals**: Future use for confirmations

## Features

### 1. Overview Tab

**Purpose**: Dashboard with statistics and quick actions

**Features**:
- Statistics cards showing:
  - Total roles
  - Total users
  - Total permissions
  - Users without roles (warning)
- Quick action buttons
- Role summary cards with user counts

**Use Cases**:
- Quick health check of permission system
- Identify users without roles
- Jump to specific role or user management

### 2. Roles Tab

**Purpose**: Create, edit, and manage roles

**Layout**: Two-column layout
- Left: Role list with search
- Right: Role details/edit form

**Features**:
- **Create Role**:
  - Name and slug (lowercase-hyphen)
  - Description
  - Permission selection via accordion
  - Category-level selection (select all in category)
  - Individual permission selection

- **Edit Role**:
  - Update name and description
  - Modify permission assignments
  - Real-time permission count
  - Cannot edit super-admin role

- **View Role**:
  - Display assigned permissions by category
  - Show user count
  - Permission badges

**Permission Tree**:
- Organized by domain (Clients, Assets, Financial, etc.)
- Expandable/collapsible categories
- Checkbox to select all in category
- Badge showing selected count per category

**Workflow**:
```
1. Click "New Role" or select existing role
2. Enter role details
3. Expand categories and select permissions
4. Save changes
5. Users assigned this role immediately get new permissions
```

### 3. Permission Matrix Tab

**Purpose**: Visual grid showing which roles have which permissions

**Layout**: Spreadsheet-style table
- Rows: Permissions (grouped by category)
- Columns: Roles
- Cells: Checkboxes (or check icon for super-admin)

**Features**:
- **Filters**:
  - Search permissions by name
  - Filter by domain
  
- **Category Management**:
  - Collapsible categories
  - Quick overview of permission groups
  
- **Toggle Permissions**:
  - Click checkbox to grant/revoke permission
  - Super-admin permissions shown as check icon (not editable)
  - Immediate update with visual feedback

**Use Cases**:
- Compare permissions across roles
- Bulk permission management
- Audit role capabilities
- Identify permission gaps

**Workflow**:
```
1. Optionally filter by domain or search
2. Expand category to view permissions
3. Click checkbox to toggle permission for role
4. Changes save immediately
```

### 4. Users Tab

**Purpose**: Assign roles and permissions to individual users

**Layout**: Two-column layout
- Left: User list with filters
- Right: User details/edit form

**Features**:
- **User List Filters**:
  - Search by name or email
  - Filter by role
  - Show only users without roles

- **User Display**:
  - Avatar
  - Name and email
  - Role badges
  - Warning badge for users without roles

- **Edit Mode**:
  - **Assigned Roles**: Select multiple roles
  - **Direct Permissions**: Override role permissions
  - **Effective Permissions**: Real-time calculation showing total permissions
  - Summary box showing permission count breakdown

- **View Mode**:
  - Display assigned roles
  - Display direct permissions
  - Show total effective permissions count

**Permission Sources**:
1. **Role-based**: Inherited from assigned roles (blue badge)
2. **Direct**: Explicitly granted to user (purple badge)

**Workflow**:
```
1. Search/filter to find user
2. Click user to view details
3. Click "Edit Permissions"
4. Select roles (primary method)
5. Optionally add direct permissions (overrides)
6. Review effective permissions summary
7. Save changes
```

### 5. Audit Log Tab

**Purpose**: Track all permission changes (placeholder for future implementation)

**Planned Features**:
- Date/time of change
- User who made change
- Action performed
- Details of change
- Export audit log

## User Experience Principles

### 1. Progressive Disclosure
- Start with overview → drill into specific areas
- Collapsible permission trees
- Expandable role details
- Two-column layouts (list + details)

### 2. Visual Hierarchy
- Tab-based navigation for major sections
- Card-based overview for quick scanning
- Clear visual indicators (✓, badges, icons)
- Color-coded states

### 3. Efficient Workflows
- Search and filter everywhere
- Bulk actions (select all in category)
- Quick actions from overview
- Immediate feedback on changes

### 4. Clear Feedback
- Permission counts on roles
- User counts on roles
- Warning indicators for issues
- Success/error messages on actions
- Real-time effective permission calculation

### 5. Safety Features
- Prevent editing super-admin role (except by super-admin)
- Confirm before destructive actions
- Show effective permissions before saving
- Cannot delete system roles
- Audit trail of all changes

## Best Practices

### For Administrators

1. **Start with Roles**:
   - Define roles based on job functions
   - Use descriptive names
   - Add meaningful descriptions

2. **Use Role Templates**:
   - Start from existing role and duplicate
   - Modify to fit specific needs

3. **Assign Roles, Not Permissions**:
   - Assign users to roles (primary method)
   - Use direct permissions sparingly (exceptions only)

4. **Regular Audits**:
   - Review users without roles
   - Check permission matrix for inconsistencies
   - Review audit log for unusual changes

5. **Document Custom Roles**:
   - Use description field
   - Document purpose and use cases

### For Users

1. **Finding Information**:
   - Use search boxes to find quickly
   - Use filters to narrow results
   - Check overview tab for quick stats

2. **Making Changes**:
   - Edit one thing at a time
   - Review effective permissions before saving
   - Check changes in permission matrix

3. **Understanding Permissions**:
   - Blue badges = from roles
   - Purple badges = direct permissions
   - Green checkmarks = granted
   - Gray = not granted

## Technical Details

### Livewire Wire Methods

**PermissionsManagement**:
- `wire:model="activeTab"` - Switch between tabs
- `loadData()` - Refresh all data
- `selectRole($roleName)` - Load role details
- `selectUser($userId)` - Load user details

**RolesList**:
- `selectRole($roleName)` - Select role
- `createRole()` - Enter create mode
- `editRole()` - Enter edit mode
- `toggleAbility($name)` - Toggle single permission
- `toggleCategory($category)` - Toggle all in category
- `saveRole()` - Save changes
- `cancelEdit()` - Cancel and reset

**PermissionMatrix**:
- `togglePermission($role, $ability)` - Toggle permission
- `toggleCategory($category)` - Expand/collapse
- `wire:model.live="filterDomain"` - Filter by domain
- `wire:model.live.debounce="searchTerm"` - Search

**UserPermissions**:
- `selectUser($userId)` - Select user
- `editUser()` - Enter edit mode
- `toggleRole($roleName)` - Toggle role assignment
- `toggleDirectAbility($name)` - Toggle direct permission
- `saveUserPermissions()` - Save changes
- `calculateEffectivePermissions()` - Recalculate totals

### Data Flow

```
User Action → Livewire Method → Database Update → Bouncer Refresh → UI Update
```

All changes are:
1. Validated
2. Wrapped in DB transaction
3. Logged to Laravel log
4. Reflected immediately in UI
5. Cached by Bouncer

### Permission Structure

Permissions follow the pattern: `{domain}.{resource}.{action}`

Examples:
- `clients.view` - View clients
- `clients.contacts.manage` - Manage client contacts
- `financial.expenses.approve` - Approve expenses

Wildcards are supported:
- `clients.*` - All client permissions
- `*` - All permissions (super-admin)

## Troubleshooting

### Users Can't See Permissions Page

**Check**:
1. User has `system.permissions.manage` permission
2. User is admin or super-admin
3. Route is registered

### Changes Not Saving

**Check**:
1. Browser console for JavaScript errors
2. Laravel log for PHP errors
3. Database connection
4. Bouncer cache issues (run `php artisan cache:clear`)

### Permissions Not Taking Effect

**Check**:
1. Bouncer cache - clear it
2. User logged out and back in
3. Correct company scope
4. Permission name is correct

### UI Not Loading

**Check**:
1. Livewire is installed and configured
2. FluxUI components are available
3. Assets are compiled (`npm run build`)
4. No JavaScript errors in console

## Future Enhancements

1. **Audit Log Implementation**:
   - Full audit trail
   - Export to CSV
   - Advanced filtering

2. **Bulk Actions**:
   - Select multiple users
   - Assign role to multiple users at once
   - Bulk permission updates

3. **Permission Templates**:
   - Save permission sets as templates
   - Quick apply to new roles

4. **Advanced Filters**:
   - Filter by permission count
   - Filter by last modified
   - Filter by creator

5. **Visual Improvements**:
   - Permission dependency tree
   - Visual permission comparison
   - Permission heatmap

6. **Export/Import**:
   - Export roles configuration
   - Import roles from JSON
   - Copy roles between companies

## Support

For issues or questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Enable debug mode: `APP_DEBUG=true`
3. Check Livewire documentation: https://livewire.laravel.com
4. Check FluxUI documentation: https://fluxui.dev
5. Review Bouncer documentation: https://github.com/JosephSilber/bouncer

## Conclusion

The Permissions Management UI provides a comprehensive, user-friendly interface for managing the complex authorization system in Nestogy ERP. By following the principles of progressive disclosure, clear feedback, and efficient workflows, administrators can easily manage roles and permissions for their organization.
