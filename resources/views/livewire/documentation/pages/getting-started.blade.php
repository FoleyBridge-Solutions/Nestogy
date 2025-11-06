{{-- Getting Started Guide --}}

<div class="prose prose-zinc dark:prose-invert max-w-none">
    <h2>Welcome to Nestogy!</h2>

    <p>
        Nestogy is a comprehensive ERP system designed specifically for Managed Service Providers (MSPs). 
        This guide will help you get started with the platform and learn the basics of managing your MSP business with Nestogy.
    </p>

    <h2>What You'll Learn</h2>

    <ul>
        <li>Logging in and accessing your account</li>
        <li>Understanding the dashboard and navigation</li>
        <li>Setting up your first client</li>
        <li>Creating your first ticket</li>
        <li>Generating your first invoice</li>
    </ul>
</div>

<flux:separator class="my-8" />

<div class="space-y-12">
    {{-- Step 1: Logging In --}}
    <div>
        <flux:heading size="lg" class="mb-4">Step 1: Logging In</flux:heading>
        
        <flux:text class="mb-4">
            To access Nestogy, navigate to your Nestogy URL and enter your credentials:
        </flux:text>

        <div class="prose prose-zinc dark:prose-invert max-w-none">
            <ol>
                <li>Go to <code>https://your-domain.com</code></li>
                <li>Enter your email address</li>
                <li>Enter your password</li>
                <li>Click <strong>Login</strong></li>
            </ol>
        </div>

        <flux:callout icon="information-circle" color="blue" class="mt-6">
            <flux:callout.heading>First Time Login</flux:callout.heading>
            <flux:callout.text>
                If this is your first time logging in, you may be prompted to set up two-factor authentication (2FA) 
                for enhanced security. We highly recommend enabling this feature.
            </flux:callout.text>
        </flux:callout>
    </div>

    {{-- Step 2: Exploring the Dashboard --}}
    <div>
        <flux:heading size="lg" class="mb-4">Step 2: Exploring the Dashboard</flux:heading>
        
        <flux:text class="mb-4">
            Once logged in, you'll see the Nestogy dashboard. The dashboard provides an overview of your business including:
        </flux:text>

        <div class="prose prose-zinc dark:prose-invert max-w-none">
            <ul>
                <li><strong>Open Tickets</strong> - Active support requests that need attention</li>
                <li><strong>Recent Activity</strong> - Latest updates across clients and tickets</li>
                <li><strong>Quick Actions</strong> - Shortcuts to common tasks like creating tickets or invoices</li>
                <li><strong>Performance Metrics</strong> - Key performance indicators (KPIs) for your MSP</li>
            </ul>
        </div>

        <flux:text class="mt-4">
            Learn more about the dashboard in the <a href="{{ route('docs.show', 'dashboard') }}" wire:navigate class="text-blue-600 dark:text-blue-400 hover:underline">Dashboard & Navigation</a> guide.
        </flux:text>
    </div>

    {{-- Step 3: Understanding Navigation --}}
    <div>
        <flux:heading size="lg" class="mb-4">Step 3: Understanding Navigation</flux:heading>
        
        <flux:text class="mb-4">
            Nestogy uses a clean, intuitive navigation system:
        </flux:text>

        <div class="prose prose-zinc dark:prose-invert max-w-none">
            <ul>
                <li><strong>Sidebar Navigation</strong> - Access all major features from the left sidebar</li>
                <li><strong>Command Palette</strong> - Press <kbd>Cmd/Ctrl + K</kbd> to quickly search and navigate</li>
                <li><strong>Client Switcher</strong> - Easily switch between different client contexts</li>
                <li><strong>User Menu</strong> - Access your profile, settings, and logout from the top right</li>
            </ul>
        </div>
    </div>

    {{-- Step 4: Creating Your First Client --}}
    <div>
        <flux:heading size="lg" class="mb-4">Step 4: Creating Your First Client</flux:heading>
        
        <flux:text class="mb-4">
            Before you can create tickets or invoices, you need to set up at least one client:
        </flux:text>

        <div class="prose prose-zinc dark:prose-invert max-w-none">
            <ol>
                <li>Click <strong>Clients</strong> in the sidebar navigation</li>
                <li>Click the <strong>Create Client</strong> button</li>
                <li>Fill in the client information:
                    <ul>
                        <li>Company name (required)</li>
                        <li>Primary contact name and email</li>
                        <li>Phone number</li>
                        <li>Address information</li>
                    </ul>
                </li>
                <li>Click <strong>Save</strong> to create the client</li>
            </ol>
        </div>

        <flux:text class="mt-4">
            For detailed information about managing clients, see the <a href="{{ route('docs.show', 'clients') }}" wire:navigate class="text-blue-600 dark:text-blue-400 hover:underline">Client Management</a> guide.
        </flux:text>
    </div>

    {{-- Step 5: Creating Your First Ticket --}}
    <div>
        <flux:heading size="lg" class="mb-4">Step 5: Creating Your First Ticket</flux:heading>
        
        <flux:text class="mb-4">
            Tickets are the core of your support workflow in Nestogy:
        </flux:text>

        <div class="prose prose-zinc dark:prose-invert max-w-none">
            <ol>
                <li>Select a client using the client switcher</li>
                <li>Click <strong>Tickets</strong> in the sidebar or use the <strong>New Ticket</strong> quick action</li>
                <li>Fill in the ticket details:
                    <ul>
                        <li>Title (brief description of the issue)</li>
                        <li>Description (detailed information)</li>
                        <li>Priority (Low, Medium, High, Critical)</li>
                        <li>Assign to a technician</li>
                    </ul>
                </li>
                <li>Click <strong>Create Ticket</strong></li>
            </ol>
        </div>

        <flux:text class="mt-4">
            Learn more in the <a href="{{ route('docs.show', 'tickets') }}" wire:navigate class="text-blue-600 dark:text-blue-400 hover:underline">Ticket System</a> guide.
        </flux:text>
    </div>

    {{-- Step 6: Generating Your First Invoice --}}
    <div>
        <flux:heading size="lg" class="mb-4">Step 6: Generating Your First Invoice</flux:heading>
        
        <flux:text class="mb-4">
            Create and send professional invoices to your clients:
        </flux:text>

        <div class="prose prose-zinc dark:prose-invert max-w-none">
            <ol>
                <li>Select a client using the client switcher</li>
                <li>Navigate to <strong>Financial</strong> → <strong>Invoices</strong></li>
                <li>Click <strong>Create Invoice</strong></li>
                <li>Add line items (services, products, or time entries)</li>
                <li>Review the totals and tax calculations</li>
                <li>Click <strong>Save & Send</strong> to email the invoice to your client</li>
            </ol>
        </div>

        <flux:text class="mt-4">
            See the <a href="{{ route('docs.show', 'invoices') }}" wire:navigate class="text-blue-600 dark:text-blue-400 hover:underline">Invoice & Billing</a> guide for more details.
        </flux:text>
    </div>
</div>

<flux:separator class="my-8" />

{{-- Next Steps --}}
<div>
    <flux:heading size="lg" class="mb-4">Next Steps</flux:heading>
    
    <flux:text class="mb-6">
        Now that you understand the basics, explore these topics to get more out of Nestogy:
    </flux:text>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <a href="{{ route('docs.show', 'dashboard') }}" wire:navigate class="block p-4 rounded-lg border border-zinc-200 dark:border-zinc-800 hover:border-blue-500 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-950/20 transition-all">
            <div class="flex items-start gap-3">
                <flux:icon name="home" class="size-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-1" />
                <div>
                    <flux:heading size="base" class="mb-1">Dashboard & Navigation</flux:heading>
                    <flux:text variant="subtle" class="text-sm">
                        Master the Nestogy interface and navigation
                    </flux:text>
                </div>
            </div>
        </a>

        <a href="{{ route('docs.show', 'contracts') }}" wire:navigate class="block p-4 rounded-lg border border-zinc-200 dark:border-zinc-800 hover:border-blue-500 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-950/20 transition-all">
            <div class="flex items-start gap-3">
                <flux:icon name="document-text" class="size-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-1" />
                <div>
                    <flux:heading size="base" class="mb-1">Contract Management</flux:heading>
                    <flux:text variant="subtle" class="text-sm">
                        Set up service agreements and SLAs
                    </flux:text>
                </div>
            </div>
        </a>

        <a href="{{ route('docs.show', 'assets') }}" wire:navigate class="block p-4 rounded-lg border border-zinc-200 dark:border-zinc-800 hover:border-blue-500 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-950/20 transition-all">
            <div class="flex items-start gap-3">
                <flux:icon name="server" class="size-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-1" />
                <div>
                    <flux:heading size="base" class="mb-1">Asset Management</flux:heading>
                    <flux:text variant="subtle" class="text-sm">
                        Track equipment and integrate with RMM tools
                    </flux:text>
                </div>
            </div>
        </a>

        <a href="{{ route('docs.show', 'time-tracking') }}" wire:navigate class="block p-4 rounded-lg border border-zinc-200 dark:border-zinc-800 hover:border-blue-500 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-950/20 transition-all">
            <div class="flex items-start gap-3">
                <flux:icon name="clock" class="size-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-1" />
                <div>
                    <flux:heading size="base" class="mb-1">Time Tracking</flux:heading>
                    <flux:text variant="subtle" class="text-sm">
                        Track time and generate accurate timesheets
                    </flux:text>
                </div>
            </div>
        </a>
    </div>
</div>

<flux:separator class="my-8" />

{{-- Need Help --}}
<div>
    <flux:heading size="lg" class="mb-4">Need Help?</flux:heading>
    
    <flux:text class="mb-4">
        If you have questions or need assistance:
    </flux:text>

    <div class="prose prose-zinc dark:prose-invert max-w-none">
        <ul>
            <li>Check the <a href="{{ route('docs.show', 'faq') }}" wire:navigate class="text-blue-600 dark:text-blue-400 hover:underline">FAQ page</a> for common questions</li>
            <li>Contact our support team at <a href="mailto:support@nestogy.com" class="text-blue-600 dark:text-blue-400 hover:underline">support@nestogy.com</a></li>
            <li>Browse the full documentation using the sidebar navigation</li>
        </ul>
    </div>

    <flux:callout icon="check-circle" color="green" variant="success" class="mt-6">
        <flux:callout.heading>You're All Set!</flux:callout.heading>
        <flux:callout.text>
            You now know the basics of Nestogy. Explore the documentation to discover all the powerful features 
            available to help you manage your MSP business efficiently.
        </flux:callout.text>
    </flux:callout>
</div>
