{{-- Dashboard & Navigation Guide --}}

<div class="prose prose-zinc dark:prose-invert max-w-none">
    <h2>Understanding Your Dashboard</h2>

    <p>
        The Nestogy dashboard is your command center, providing a comprehensive overview of your MSP business 
        at a glance. Learn how to navigate the interface efficiently and customize your workspace.
    </p>
</div>

<flux:separator class="my-8" />

<div class="space-y-12">
    {{-- Dashboard Overview --}}
    <div>
        <flux:heading size="lg" class="mb-4">Dashboard Overview</flux:heading>
        
        <flux:text class="mb-4">
            The dashboard is divided into several key sections:
        </flux:text>

        <div class="prose prose-zinc dark:prose-invert max-w-none">
            <ul>
                <li><strong>Performance Metrics</strong> - Revenue, ticket count, client satisfaction, and other KPIs</li>
                <li><strong>Open Tickets</strong> - Active support tickets requiring attention, sorted by priority</li>
                <li><strong>Recent Activity</strong> - Timeline of recent updates across all clients and projects</li>
                <li><strong>Quick Actions</strong> - One-click access to frequently used features</li>
                <li><strong>Upcoming Tasks</strong> - Tasks and appointments scheduled for today and this week</li>
            </ul>
        </div>

        <flux:callout icon="sparkles" color="blue" class="mt-6">
            <flux:callout.heading>Customizable Widgets</flux:callout.heading>
            <flux:callout.text>
                You can customize which widgets appear on your dashboard through Settings → Dashboard Preferences.
            </flux:callout.text>
        </flux:callout>
    </div>

    {{-- Navigation System --}}
    <div>
        <flux:heading size="lg" class="mb-4">Navigation System</flux:heading>
        
        <flux:text class="mb-4">
            Nestogy offers multiple ways to navigate efficiently:
        </flux:text>

        <div class="space-y-6">
            <div>
                <flux:heading size="base" class="mb-2">Sidebar Navigation</flux:heading>
                <flux:text>
                    The left sidebar provides access to all major modules. Click any menu item to access that feature. 
                    The sidebar collapses on mobile devices and can be toggled with the menu button.
                </flux:text>
            </div>

            <div>
                <flux:heading size="base" class="mb-2">Command Palette (⌘K)</flux:heading>
                <flux:text class="mb-3">
                    The fastest way to navigate Nestogy. Press <kbd>Cmd+K</kbd> (Mac) or <kbd>Ctrl+K</kbd> (Windows/Linux) 
                    to open the command palette and search for:
                </flux:text>
                <div class="prose prose-zinc dark:prose-invert max-w-none">
                    <ul>
                        <li>Features and pages</li>
                        <li>Clients by name or company</li>
                        <li>Tickets by number or title</li>
                        <li>Settings and preferences</li>
                    </ul>
                </div>
            </div>

            <div>
                <flux:heading size="base" class="mb-2">Client Switcher</flux:heading>
                <flux:text>
                    The client switcher in the top navigation lets you quickly switch between client contexts. 
                    When a client is selected, all tickets, invoices, and assets are filtered to that client.
                </flux:text>
            </div>

            <div>
                <flux:heading size="base" class="mb-2">Breadcrumbs</flux:heading>
                <flux:text>
                    Breadcrumb navigation appears at the top of most pages, showing your current location and 
                    allowing you to quickly navigate back to parent pages.
                </flux:text>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div>
        <flux:heading size="lg" class="mb-4">Quick Actions</flux:heading>
        
        <flux:text class="mb-4">
            Quick action buttons provide one-click access to common tasks:
        </flux:text>

        <div class="prose prose-zinc dark:prose-invert max-w-none">
            <ul>
                <li><strong>New Ticket</strong> - Create a support ticket for the selected client</li>
                <li><strong>New Invoice</strong> - Generate an invoice</li>
                <li><strong>Log Time</strong> - Start a time entry</li>
                <li><strong>Add Client</strong> - Register a new client</li>
            </ul>
        </div>
    </div>

    {{-- User Menu --}}
    <div>
        <flux:heading size="lg" class="mb-4">User Menu</flux:heading>
        
        <flux:text class="mb-4">
            Click your profile picture or name in the top right to access:
        </flux:text>

        <div class="prose prose-zinc dark:prose-invert max-w-none">
            <ul>
                <li><strong>Profile</strong> - View and edit your profile information</li>
                <li><strong>Settings</strong> - Configure your account preferences</li>
                <li><strong>Help & Documentation</strong> - Access this documentation</li>
                <li><strong>Switch Company</strong> - If you have access to multiple companies</li>
                <li><strong>Logout</strong> - Sign out of your account</li>
            </ul>
        </div>
    </div>

    {{-- Notifications --}}
    <div>
        <flux:heading size="lg" class="mb-4">Notifications</flux:heading>
        
        <flux:text class="mb-4">
            The notification bell in the top navigation shows real-time updates:
        </flux:text>

        <div class="prose prose-zinc dark:prose-invert max-w-none">
            <ul>
                <li>New tickets assigned to you</li>
                <li>Comments on tickets you're following</li>
                <li>Invoice payment confirmations</li>
                <li>Client portal activity</li>
                <li>System announcements</li>
            </ul>
        </div>

        <flux:callout icon="bell" color="purple" class="mt-6">
            <flux:callout.heading>Notification Preferences</flux:callout.heading>
            <flux:callout.text>
                Customize which notifications you receive in Settings → Notifications. You can choose email, 
                in-app, or push notifications for each event type.
            </flux:callout.text>
        </flux:callout>
    </div>

    {{-- Search Functionality --}}
    <div>
        <flux:heading size="lg" class="mb-4">Global Search</flux:heading>
        
        <flux:text class="mb-4">
            The search bar at the top of the dashboard provides instant access to:
        </flux:text>

        <div class="prose prose-zinc dark:prose-invert max-w-none">
            <ul>
                <li><strong>Clients</strong> - Search by company name, contact name, or email</li>
                <li><strong>Tickets</strong> - Find tickets by number, title, or description</li>
                <li><strong>Invoices</strong> - Locate invoices by number or client</li>
                <li><strong>Assets</strong> - Search for devices by name, type, or serial number</li>
            </ul>
        </div>
    </div>
</div>

<flux:separator class="my-8" />

{{-- Keyboard Shortcuts --}}
<div>
    <flux:heading size="lg" class="mb-4">Keyboard Shortcuts</flux:heading>
    
    <flux:text class="mb-4">
        Power users can navigate faster with these keyboard shortcuts:
    </flux:text>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <flux:card>
            <div class="flex items-center justify-between">
                <flux:text><kbd>⌘K</kbd> or <kbd>Ctrl+K</kbd></flux:text>
                <flux:text variant="subtle">Open command palette</flux:text>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between">
                <flux:text><kbd>⌘N</kbd> or <kbd>Ctrl+N</kbd></flux:text>
                <flux:text variant="subtle">New ticket</flux:text>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between">
                <flux:text><kbd>⌘/</kbd> or <kbd>Ctrl+/</kbd></flux:text>
                <flux:text variant="subtle">Toggle sidebar</flux:text>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between">
                <flux:text><kbd>Esc</kbd></flux:text>
                <flux:text variant="subtle">Close modals</flux:text>
            </div>
        </flux:card>
    </div>
</div>

<flux:separator class="my-8" />

{{-- Next Steps --}}
<div>
    <flux:heading size="lg" class="mb-4">Next Steps</flux:heading>
    
    <flux:text class="mb-4">
        Now that you understand the dashboard and navigation, continue learning about:
    </flux:text>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <a href="{{ route('docs.show', 'clients') }}" wire:navigate class="block p-4 rounded-lg border border-zinc-200 dark:border-zinc-800 hover:border-blue-500 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-950/20 transition-all">
            <div class="flex items-start gap-3">
                <flux:icon name="users" class="size-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-1" />
                <div>
                    <flux:heading size="base" class="mb-1">Client Management</flux:heading>
                    <flux:text variant="subtle" class="text-sm">
                        Learn how to manage your clients
                    </flux:text>
                </div>
            </div>
        </a>

        <a href="{{ route('docs.show', 'tickets') }}" wire:navigate class="block p-4 rounded-lg border border-zinc-200 dark:border-zinc-800 hover:border-blue-500 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-950/20 transition-all">
            <div class="flex items-start gap-3">
                <flux:icon name="ticket" class="size-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-1" />
                <div>
                    <flux:heading size="base" class="mb-1">Ticket System</flux:heading>
                    <flux:text variant="subtle" class="text-sm">
                        Master support ticket workflow
                    </flux:text>
                </div>
            </div>
        </a>
    </div>
</div>
