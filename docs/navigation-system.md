# Domain-Based Navigation System

This document outlines the new domain-based navigation system implemented for Nestogy ERP.

## Overview

The navigation system has been redesigned to provide:
- **Top Navigation Bar**: For switching between domains (Clients, Tickets, Assets, Financial, Projects, Reports, Manager)
- **Domain-Specific Sidebars**: Each domain has its own sidebar with relevant sections and actions
- **Responsive Design**: Mobile-friendly with collapsible sidebars
- **Active State Management**: Automatic highlighting of active domains and navigation items
- **Real-time Notifications**: Integrated notification center with badge counts
- **Role-Based Access**: Manager tools and specialized features shown based on permissions

## Architecture

### Components

1. **Domain Navigation Bar** (`resources/views/components/domain-nav.blade.php`)
   - Displays domain tabs in the top navigation
   - Includes user profile dropdown with settings and logout
   - Features quick search functionality
   - Shows **NotificationCenter** with live badge counts (NEW)
   - Responsive with mobile hamburger menu
   - Role-based domain visibility (e.g., Manager domain for managers only)

2. **Domain Sidebar** (`resources/views/components/domain-sidebar.blade.php`)
   - Dynamic sidebar content based on active domain
   - Hierarchical navigation with sections and dividers
   - Active state highlighting for current page
   - Domain-specific actions and shortcuts

3. **Navigation Service** (`app/Services/NavigationService.php`)
   - Detects active domain from current route
   - Determines active navigation item
   - Generates breadcrumbs automatically
   - Provides route and parameter matching logic

4. **View Composer** (`app/Http/ViewComposers/NavigationComposer.php`)
   - Automatically injects navigation data into layouts
   - Ensures consistent navigation state across all pages

### Domain Configuration

Each domain has its own navigation configuration in the sidebar component:

#### Clients Domain
- All Clients
- Add New Client
- Client Leads
- Import/Export functionality
- Download templates

#### Tickets Domain
- All Tickets
- Create Ticket
- My Tickets (filtered)
- Open Tickets (filtered)
- Scheduled Tickets
- Export functionality
- **Mobile Tools section** (NEW):
  - Mobile Time Tracker
  - Quick Ticket View

#### Assets Domain
- All Assets
- Add New Asset
- Import/Export functionality
- QR Code Generator
- Print Labels

#### Financial Domain
- Dashboard overview
- **Billing & Invoicing section**: 
  - All Invoices
  - Time Entry Approval (NEW)
  - Payments
  - Recurring Billing
  - Rate Cards (NEW)
- **Accounting section**: Chart of Accounts, Journal Entries, Tax Settings

#### Projects Domain
- All Projects
- Create Project
- Active Projects (filtered)
- Completed Projects (filtered)
- Project Timeline view

#### Reports Domain
- Reports Dashboard
- **Financial Reports**: Overview, Invoices, Payments
- **Operational Reports**: Tickets, Assets, Clients, Projects, Users

#### Manager Domain (NEW - Role-Based)
- **Team Dashboard**: Real-time team performance and ticket overview
- **Team Management section**:
  - Tech Capacity view
  - Unassigned Tickets (with badge count)
- **SLA Monitoring section**:
  - SLA Breaches (with badge count)
  - At Risk tickets (with badge count)
- **Reports section**:
  - Team Performance
  - SLA Compliance
  - Client Satisfaction

## Usage

### Auto-Detection
The system automatically detects the active domain and navigation item based on the current route. No manual configuration is needed in controllers or views.

### Breadcrumbs
Breadcrumbs are automatically generated based on the domain and current page, providing clear navigation context.

### Mobile Experience
- Collapsible sidebar with overlay
- Mobile hamburger menu for domain switching
- Floating action button to toggle sidebar on mobile
- Touch-friendly interface elements

## Technical Implementation

### Route-Based Detection
The system uses pattern matching on route names to determine:
- Which domain is currently active
- Which sidebar item should be highlighted
- How to generate appropriate breadcrumbs

### State Management
- Domain state is maintained through URL routes
- Active states are computed on each page load
- No JavaScript state management required (server-side only)

### Performance
- View composer ensures navigation data is only computed once per request
- Minimal overhead with efficient route pattern matching
- Cached navigation configurations

## Customization

### Adding New Domains
1. Add domain configuration to `$domainMappings` in NavigationService
2. Add sidebar configuration to `$sidebarConfig` in domain-sidebar component
3. Add route mappings to `$navigationMappings` in NavigationService
4. Add domain link to domain-nav component

### Modifying Sidebar Content
Update the `$sidebarConfig` array in `domain-sidebar.blade.php` with new navigation items, sections, or dividers.

### Styling
The navigation uses Tailwind CSS classes and can be customized by modifying the component templates.

## Mobile Features

### Responsive Behavior
- **Desktop**: Fixed sidebar always visible
- **Tablet**: Collapsible sidebar
- **Mobile**: Overlay sidebar with floating toggle button

### Touch Interactions
- Tap outside sidebar to close (mobile)
- Swipe gestures supported
- Large touch targets for mobile usability

## Browser Support
- Modern browsers with CSS Grid and Flexbox support
- Alpine.js for interactive components
- Graceful degradation for older browsers

## Testing
- Test navigation between all domains
- Verify active state highlighting
- Check mobile responsiveness
- Validate breadcrumb generation
- Test filtered views (My Tickets, Active Projects, etc.)

## Recent Updates (October 2025)

### Navigation Enhancements v1.0
- ✅ Added **Manager Domain** for team management and SLA monitoring
- ✅ Integrated **NotificationCenter** component into top navigation
- ✅ Added **Time Entry Approval** to Financial domain
- ✅ Added **Rate Cards** to Financial domain
- ✅ Updated Settings with direct **Notification Preferences** link
- ✅ Added **Mobile Tools** section to Tickets domain
- ✅ Fixed broken `/notifications` route (redirects to settings)
- ✅ Real-time badge counts for SLA breaches, unassigned tickets, and at-risk items

### Component Integration
- **Manager Dashboard**: `/manager/dashboard` - Team performance overview
- **Tech Capacity View**: `/manager/capacity` - Technician workload analysis
- **Time Entry Approval**: `/billing/time-entries` - Review and approve billable time
- **Mobile Time Tracker**: `/mobile/time-tracker` - Mobile-optimized time tracking
- **Notification Preferences**: `/settings/notifications` - Configure notification settings
- **Notification Center**: Live dropdown in top navigation

### Routes Added
```
GET /manager/dashboard → App\Livewire\Manager\TeamDashboard
GET /manager/capacity → App\Livewire\Manager\TechCapacityView
GET /billing/time-entries → App\Livewire\Billing\TimeEntryApproval
GET /mobile/time-tracker/{ticketId?} → App\Livewire\MobileTimeTracker
GET /settings/notifications → App\Livewire\Settings\NotificationPreferences
GET /notifications → Redirect to /settings/notifications
```

## Future Enhancements
- Domain-specific dashboards
- Saved navigation states
- Recently accessed items
- Navigation analytics
- Quick action shortcuts
- Keyboard navigation support
- Rate Cards management UI
- Enhanced mobile navigation gestures