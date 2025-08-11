# 🚀 Nestogy Navigation System Guide

## How to Access the New Navigation System

### 1. **Command Palette** (Primary Access Method)
The command palette is the main way to navigate and execute commands in Nestogy. It provides comprehensive search across ALL entities in the system.

#### Opening the Command Palette:
- **Windows/Linux/Mac**: Press `Ctrl + /`
- **Alternative**: Press `/` (forward slash) anywhere on the page
- **Click Method**: Click the "Search" button in the top navigation bar

#### Using the Command Palette:

##### Natural Language Commands:

**Creating Items:**
- `create ticket` - Create a new support ticket
- `create invoice` - Create a new invoice
- `create quote` - Create a new quote
- `create project` - Create a new project
- `create asset` - Add a new asset
- `create contract` - Create a new contract
- `create expense` - Add a new expense
- `create payment` - Record a payment
- `create user` - Add a new user
- `create article` - Create knowledge base article

**Viewing/Showing Items:**
- `show urgent` - View all urgent items
- `show today` - Display today's schedule
- `show tickets` - View all tickets
- `show invoices` - View all invoices
- `show quotes` - View all quotes
- `show projects` - View all projects
- `show assets` - View all assets
- `show contracts` - View all contracts
- `show expenses` - View all expenses
- `show payments` - View all payments
- `show overdue invoices` - View overdue invoices
- `show pending quotes` - View pending quotes
- `show active projects` - View active projects

**Navigation:**
- `go to dashboard` - Navigate to dashboard
- `go to clients` - Navigate to clients page
- `go to billing` - Navigate to billing section
- `go to assets` - Navigate to assets
- `go to projects` - Navigate to projects
- `go to knowledge base` - Navigate to KB

**Searching:**
- `find server down` - Search for tickets containing "server down"
- `search invoices` - Search through invoices
- `search clients` - Search through clients
- Just type any text to search across ALL entities

##### Quick Actions:
When you open the command palette without typing, you'll see:
- 🔥 **Show urgent** - Critical items needing attention (Alt+U)
- 📅 **Show today** - Today's scheduled work (Alt+T)
- 🎫 **Create ticket** - Quick ticket creation
- 📝 **Create quote** - Quick quote creation
- 💰 **Create invoice** - Quick invoice creation
- Recent items you've accessed
- Context-specific actions based on selected client

##### Search Mode:
Just start typing to search comprehensively across:
- **Tickets** (by ID, title, description)
- **Clients** (by name, email, phone)
- **Invoices** (by number, client)
- **Quotes** (by number, description)
- **Assets** (by name, tag, serial number, model)
- **Projects** (by name, description, code)
- **Contracts** (by number, title, description)
- **Expenses** (by description, vendor, reference)
- **Payments** (by reference, notes)
- **Users** (by name, email)
- **Knowledge Base Articles** (by title, content, tags)
- **IT Documentation** (by network info, server details)
- **Client Contacts** (by name, email, phone)

### 2. **Workflow Navigation Bar**
Located at the top of the screen, showing contextual workflow badges:

- **🔥 Urgent Badge** - Shows count of critical/urgent items
- **⚡ Today Badge** - Shows today's scheduled work
- **📋 Scheduled Badge** - Shows upcoming scheduled items
- **💰 Financial Badge** - Shows pending financial tasks
- **📊 Reports Badge** - Quick access to reports

Click any badge to instantly filter to that workflow view.

### 3. **Smart Client Switcher**
Located in the top navigation bar:
- Shows currently selected client
- Click to switch between clients
- Recently accessed clients appear at top
- Search for any client

### 4. **Domain Sidebar**
The left sidebar adapts based on your current context:

#### When No Client Selected:
- Dashboard
- Quick Actions (New Ticket, New Client, etc.)
- Browse sections (Clients, Tickets, Billing, etc.)

#### When Client Selected:
- **⚠️ NEEDS ACTION** - Items requiring immediate attention
- **🛠️ MANAGE** - Tickets, Assets, Contacts
- **💼 BUSINESS** - Invoices, Contracts, Projects
- **🔐 SECURE** - Passwords, Certificates, Licenses

### 5. **Keyboard Shortcuts**

| Shortcut | Action |
|----------|--------|
| `Ctrl + /` | Open command palette |
| `/` | Open command palette (when not in input) |
| `Esc` | Close command palette |
| `↑ ↓` | Navigate suggestions |
| `Enter` | Select item |
| `Alt + U` | Show urgent items |
| `Alt + T` | Show today's schedule |

## Workflow Examples

### Morning Routine Workflow
1. Open command palette (`⌘ + K`)
2. Type `start morning workflow`
3. System shows:
   - Overnight alerts
   - Backup status
   - Today's scheduled maintenance
   - Urgent tickets

### Billing Day Workflow
1. Open command palette
2. Type `start billing workflow`
3. System displays:
   - Clients ready for invoicing
   - Overdue payments
   - Pending quotes
   - Credit notes to process

### Emergency Response
1. See red notification badge
2. Click or type `show urgent`
3. View all critical items sorted by priority
4. Quick actions available for each item

## Available Domains & Searchable Entities

The navigation system provides comprehensive access to ALL features:

### Core Business Entities
- **Clients** - Complete CRM with contacts, locations, documents
  - Client Contacts
  - Client Addresses
  - IT Documentation
  - Credentials
  - Networks
  - Services
  - Vendors
- **Tickets** - Support tickets with workflows, templates, time tracking
  - Ticket Templates
  - Recurring Tickets
  - Time Entries
  - Workflows

### Financial Management
- **Invoices** - Full invoice management
- **Quotes** - Quote creation and management
- **Contracts** - Contract management with milestones
- **Expenses** - Expense tracking
- **Payments** - Payment recording and tracking
- **Credit Notes** - Credit management
- **Recurring Invoices** - Subscription billing

### Operations
- **Assets** - Asset management with maintenance, warranties
  - Asset Tags
  - Serial Numbers
  - Maintenance Schedules
  - Warranties
- **Projects** - Project management with tasks and milestones
  - Project Tasks
  - Milestones
  - Time Tracking
- **Users** - User management
  - Permissions
  - Roles
  - Settings

### Extended Features
- **Knowledge Base** - Articles, categories, search
  - KB Articles
  - Categories
  - Tags
  - Related Articles
- **Integrations** - RMM, webhooks, API management
  - Device Mappings
  - API Keys
  - Webhooks
- **IT Documentation** - Network diagrams, credentials, compliance
  - Network Diagrams
  - Server Information
  - Network Equipment
  - Security Policies
- **Infrastructure** - Licenses, certificates, domains, networks
  - Software Licenses
  - SSL Certificates
  - Domain Names
  - Network Configurations
- **Reports** - Comprehensive reporting and analytics
  - Financial Reports
  - Operational Reports
  - Custom Reports

## Tips & Tricks

1. **Use Natural Language**: Instead of memorizing commands, just type what you want to do
   - "find overdue invoices for acme corp"
   - "show me urgent tickets"
   - "create quote for current client"

2. **Context Awareness**: The system remembers your selected client and shows relevant options
   - When a client is selected, "create invoice" automatically associates it
   - Search results prioritize the selected client's data

3. **Entity-Specific Searches**: Use entity prefixes for targeted searching
   - `quote:` - Search only quotes
   - `asset:` - Search only assets
   - `project:` - Search only projects
   - `contract:` - Search only contracts

4. **Smart Filtering**: Commands understand context
   - "show pending" - Shows pending items across all types
   - "show overdue" - Shows overdue invoices, projects, tickets
   - "show my" - Shows items assigned to you

5. **Comprehensive Search**: One search queries ALL entities
   - Type "server" to find:
     - Tickets mentioning server
     - Assets named server
     - IT Documentation about servers
     - Knowledge articles about servers

6. **Quick Creation**: Create anything from anywhere
   - No need to navigate to specific pages
   - Context is preserved (selected client, current view)

## Troubleshooting

### Command Palette Not Opening?
- Ensure you're not in a text input field
- Try the alternative shortcut (`/`)
- Click the "Search" button in the navigation bar (shows Ctrl+/)
- Refresh the page if shortcuts aren't working

### Search Not Working?
- Check your internet connection
- Refresh the page (`Ctrl + R` or `F5`)
- Clear browser cache
- Ensure you have permissions to view the entities you're searching

### Missing Features?
- Check your user permissions
- Some features require specific roles
- Admin features only visible to super-admins
- Some entities may not have data yet (create some first)

### Search Results Empty?
- Try broader search terms
- Check if you have the right client selected
- Ensure the entity type exists in your system
- Some models might not be deployed yet

## API Access

Developers can access the navigation system programmatically:

```javascript
// Execute a command
fetch('/api/navigation/command', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    },
    body: JSON.stringify({ command: 'show urgent' })
});

// Get suggestions
fetch('/api/navigation/suggestions?q=create');

// Get badge counts
fetch('/api/navigation/badges');

// Search
fetch('/api/search/query?query=server+down');
```

## Complete Command Reference

### Creation Commands
| Command | Description | Shortcut |
|---------|-------------|----------|
| `create ticket` | New support ticket | - |
| `create invoice` | New invoice | - |
| `create quote` | New quote | - |
| `create project` | New project | - |
| `create asset` | New asset | - |
| `create contract` | New contract | - |
| `create expense` | New expense | - |
| `create payment` | New payment | - |
| `create user` | New user | - |
| `create article` | New KB article | - |

### Navigation Commands
| Command | Description | Route |
|---------|-------------|-------|
| `go to dashboard` | Main dashboard | / |
| `go to clients` | Client list | /clients |
| `go to tickets` | Ticket list | /tickets |
| `go to billing` | Billing section | /financial/invoices |
| `go to quotes` | Quote list | /financial/quotes |
| `go to assets` | Asset list | /assets |
| `go to projects` | Project list | /projects |
| `go to knowledge base` | KB articles | /knowledge |

### Show/Filter Commands
| Command | Description |
|---------|-------------|
| `show urgent` | Critical items |
| `show today` | Today's work |
| `show overdue invoices` | Past due invoices |
| `show pending quotes` | Awaiting approval |
| `show active projects` | In progress |
| `show my tickets` | Assigned to you |
| `show client tickets` | Current client's tickets |

## Support

For issues or feature requests related to the navigation system:
- Use command palette: Type "help"
- Contact support: Type "create ticket navigation issue"
- Check documentation: Type "show navigation docs"
- View this guide: Type "navigation guide"

---

The new navigation system is designed to reduce clicks, increase efficiency, and make every feature easily accessible. With practice, you'll find that most tasks can be completed in seconds rather than minutes.