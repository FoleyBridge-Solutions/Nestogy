<?php

namespace App\Domains\Core\Services;

use Illuminate\Support\Facades\Cache;

class SidebarConfigProvider
{
    protected array $registeredSections = [];

    protected array $configCache = [];

    /**
     * Get sidebar configuration for a given context
     */
    public function getConfiguration(?string $context = null, ?array $customConfig = null): array
    {
        if ($customConfig) {
            return $this->mergeConfigurations($this->getDefaultConfiguration($context), $customConfig);
        }

        // Cache configuration per context
        if (isset($this->configCache[$context])) {
            return $this->configCache[$context];
        }

        $config = $this->loadConfiguration($context);
        $this->configCache[$context] = $config;

        return $config;
    }

    /**
     * Register a new sidebar section dynamically
     */
    public function registerSection(string $context, string $key, array $section): void
    {
        if (! isset($this->registeredSections[$context])) {
            $this->registeredSections[$context] = [];
        }

        $this->registeredSections[$context][$key] = $section;

        // Clear cache for this context
        unset($this->configCache[$context]);
    }

    /**
     * Load configuration from registered sections and built-in configs
     */
    protected function loadConfiguration(?string $context): array
    {
        // If no context, return empty configuration
        if (! $context) {
            return [];
        }

        // Map old domain names to new sidebar contexts for backward compatibility
        $context = $this->mapLegacyContext($context);

        // Get built-in configuration for the context
        $baseConfig = $this->getBuiltInConfig($context);

        // Merge with dynamically registered sections
        if (isset($this->registeredSections[$context])) {
            $baseConfig['sections'] = array_merge(
                $baseConfig['sections'] ?? [],
                $this->registeredSections[$context]
            );
        }

        // Apply permission filters
        return $this->filterByPermissions($baseConfig);
    }

    /**
     * Map legacy domain names to new context names
     */
    protected function mapLegacyContext(string $context): string
    {
        // For now, keep the same names but this allows future remapping
        return $context;
    }

    /**
     * Get built-in configuration for a context
     */
    protected function getBuiltInConfig(string $context): array
    {
        // Import the sidebar config from the blade template
        // This is temporary until we fully migrate to config files

        $selectedClient = NavigationService::getSelectedClient();

        switch ($context) {
            case 'clients':
                return $this->getClientsConfig($selectedClient);
            case 'tickets':
                return $this->getTicketsConfig();
            case 'email':
                return $this->getEmailConfig();
            case 'assets':
                return $this->getAssetsConfig();
            case 'financial':
                return $this->getFinancialConfig();
            case 'projects':
                return $this->getProjectsConfig();
            case 'reports':
                return $this->getReportsConfig();
            case 'manager':
                return $this->getManagerConfig();
            case 'settings':
                return $this->getSettingsConfig();
            case 'physical-mail':
                return $this->getPhysicalMailConfig();
            default:
                return [];
        }
    }

    /**
     * Get clients sidebar configuration
     */
    protected function getClientsConfig($selectedClient): array
    {
        return [
            'title' => 'Client Management',
            'icon' => 'user-group',
            'sections' => [
                [
                    'type' => 'primary',
                    'items' => [
                        [
                            'name' => 'Client Details',
                            'route' => 'clients.show',
                            'icon' => 'chart-pie',
                            'key' => 'details',
                            'params' => ['client' => 'current'],
                            'description' => 'Central hub with client health and quick actions',
                        ],
                    ],
                ],
                [
                    'type' => 'section',
                    'title' => 'IMMEDIATE ACTIONS',
                    'priority' => true,
                    'expandable' => false,
                    'items' => [
                        [
                            'name' => 'Open Tickets',
                            'route' => 'tickets.index',
                            'icon' => 'exclamation-triangle',
                            'key' => 'open-tickets',
                            'params' => ['status' => 'open'],
                            'badge_type' => 'urgent',
                            'show_if' => 'has_open_tickets',
                        ],
                        [
                            'name' => 'Pending Items',
                            'route' => 'clients.show',
                            'icon' => 'clock',
                            'key' => 'pending',
                            'params' => ['client' => 'current', 'section' => 'pending'],
                            'badge_type' => 'warning',
                            'show_if' => 'has_pending_items',
                        ],
                    ],
                ],
                [
                    'type' => 'section',
                    'title' => 'COMMUNICATION',
                    'expandable' => true,
                    'default_expanded' => true,
                    'items' => [
                        [
                            'name' => 'Contacts',
                            'route' => 'clients.contacts.index',
                            'icon' => 'users',
                            'key' => 'contacts',
                            'params' => [],
                        ],
                        [
                            'name' => 'Locations',
                            'route' => 'clients.locations.index',
                            'icon' => 'map-pin',
                            'key' => 'locations',
                            'params' => [],
                        ],
                        [
                            'name' => 'Communication Log',
                            'route' => 'clients.communications.index',
                            'icon' => 'chat-bubble-left-right',
                            'key' => 'communications',
                            'params' => [],
                        ],
                        [
                            'name' => 'Physical Mail',
                            'route' => 'mail.index',
                            'icon' => 'envelope',
                            'key' => 'physical-mail',
                            'params' => [],
                            'description' => 'Send letters and documents',
                        ],
                    ],
                ],
                [
                    'type' => 'section',
                    'title' => 'SERVICE MANAGEMENT',
                    'expandable' => true,
                    'default_expanded' => true,
                    'items' => [
                        [
                            'name' => 'Support Tickets',
                            'route' => 'tickets.index',
                            'icon' => 'ticket',
                            'key' => 'tickets',
                            'params' => [],
                        ],
                        [
                            'name' => 'Assets & Equipment',
                            'route' => 'assets.index',
                            'icon' => 'computer-desktop',
                            'key' => 'assets',
                            'params' => [],
                        ],
                        [
                            'name' => 'Projects',
                            'route' => 'projects.index',
                            'icon' => 'folder',
                            'key' => 'projects',
                            'params' => [],
                        ],
                    ],
                ],
                [
                    'type' => 'section',
                    'title' => 'IT INFRASTRUCTURE',
                    'expandable' => true,
                    'default_expanded' => true,
                    'items' => [
                        [
                            'name' => 'IT Documentation',
                            'route' => 'clients.it-documentation.client-index',
                            'icon' => 'document-text',
                            'key' => 'it-documentation',
                            'params' => [],
                        ],
                        [
                            'name' => 'Documents',
                            'route' => 'clients.documents.index',
                            'icon' => 'folder-open',
                            'key' => 'documents',
                            'params' => [],
                        ],
                        [
                            'name' => 'Files',
                            'route' => 'clients.files.index',
                            'icon' => 'paper-clip',
                            'key' => 'files',
                            'params' => [],
                        ],
                        [
                            'name' => 'Domains',
                            'route' => 'clients.domains.index',
                            'icon' => 'globe-alt',
                            'key' => 'domains',
                            'params' => [],
                        ],
                        [
                            'name' => 'Credentials',
                            'route' => 'clients.credentials.index',
                            'icon' => 'key',
                            'key' => 'credentials',
                            'params' => [],
                        ],
                        [
                            'name' => 'Licenses',
                            'route' => 'clients.licenses.index',
                            'icon' => 'identification',
                            'key' => 'licenses',
                            'params' => [],
                        ],
                        [
                            'name' => 'Vendors',
                            'route' => 'clients.vendors.index',
                            'icon' => 'building-office',
                            'key' => 'vendors',
                            'params' => [],
                        ],
                        [
                            'name' => 'Services',
                            'route' => 'clients.services.index',
                            'icon' => 'cog-6-tooth',
                            'key' => 'services',
                            'params' => [],
                        ],
                    ],
                ],
                [
                    'type' => 'section',
                    'title' => 'BILLING & FINANCE',
                    'expandable' => true,
                    'default_expanded' => true,
                    'items' => [
                        [
                            'name' => 'Contracts',
                            'route' => 'financial.contracts.index',
                            'icon' => 'document-check',
                            'key' => 'contracts',
                            'params' => [],
                        ],
                        [
                            'name' => 'Quotes',
                            'route' => 'financial.quotes.index',
                            'icon' => 'document-currency-dollar',
                            'key' => 'quotes',
                            'params' => [],
                        ],
                        [
                            'name' => 'Invoices',
                            'route' => 'financial.invoices.index',
                            'icon' => 'document-text',
                            'key' => 'invoices',
                            'params' => [],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get tickets sidebar configuration
     */
    protected function getTicketsConfig(): array
    {
        $user = auth()->user();

        // Get real-time statistics for badges
        $activeTimersCount = 0;
        $slaViolationsCount = 0;
        $unassignedCount = 0;
        $dueTodayCount = 0;

        if ($user && $user->company_id) {
            try {
                // Active timers count
                $activeTimersQuery = \App\Domains\Ticket\Models\TicketTimeEntry::runningTimers()
                    ->where('company_id', $user->company_id);
                if (! $user->hasRole('admin')) {
                    $activeTimersQuery->where('user_id', $user->id);
                }
                $activeTimersCount = $activeTimersQuery->count();

                // SLA violations count (tickets with priority queue that have breached SLA)
                $slaViolationsCount = \App\Domains\Ticket\Models\Ticket::where('company_id', $user->company_id)
                    ->whereHas('priorityQueue', function ($q) {
                        $q->where('sla_deadline', '<', now());
                    })
                    ->whereNotIn('status', ['closed', 'resolved'])
                    ->count();

                // Unassigned tickets count
                $unassignedCount = \App\Domains\Ticket\Models\Ticket::where('company_id', $user->company_id)
                    ->whereNull('assigned_to')
                    ->whereNotIn('status', ['closed', 'resolved'])
                    ->count();

                // Due today count
                $dueTodayCount = \App\Domains\Ticket\Models\Ticket::where('company_id', $user->company_id)
                    ->whereDate('scheduled_at', today())
                    ->whereNotIn('status', ['closed', 'resolved'])
                    ->count();
            } catch (\Exception $e) {
                // Silently handle any database issues
            }
        }

        return [
            'title' => 'Ticket Management',
            'icon' => 'ticket',
            'sections' => [
                [
                    'type' => 'primary',
                    'items' => [
                        [
                            'name' => 'Overview',
                            'route' => 'tickets.index',
                            'icon' => 'home',
                            'key' => 'overview',
                        ],
                    ],
                ],
                [
                    'type' => 'section',
                    'title' => 'MY WORK',
                    'expandable' => false,
                    'items' => [
                        [
                            'name' => 'My Tickets',
                            'route' => 'tickets.index',
                            'icon' => 'user',
                            'key' => 'my-tickets',
                            'params' => ['filter' => 'my'],
                        ],
                        [
                            'name' => 'Assigned to Me',
                            'route' => 'tickets.index',
                            'icon' => 'user-circle',
                            'key' => 'assigned',
                            'params' => ['assignee' => 'me'],
                        ],
                        [
                            'name' => 'Active Timers',
                            'route' => 'tickets.active-timers',
                            'icon' => 'clock',
                            'key' => 'active-timers',
                            'badge' => $activeTimersCount > 0 ? $activeTimersCount : null,
                            'badge_type' => 'success',
                            'description' => 'Running time trackers',
                        ],
                        [
                            'name' => 'Due Today',
                            'route' => 'tickets.due-today',
                            'icon' => 'calendar',
                            'key' => 'due-today',
                            'badge' => $dueTodayCount > 0 ? $dueTodayCount : null,
                            'badge_type' => 'warning',
                            'description' => 'Tickets due today',
                        ],
                        [
                            'name' => 'My Watched Tickets',
                            'route' => 'tickets.watched',
                            'icon' => 'eye',
                            'key' => 'watched',
                            'description' => 'Tickets you are watching',
                        ],
                    ],
                ],
                [
                    'type' => 'section',
                    'title' => 'CRITICAL ITEMS',
                    'expandable' => false,
                    'items' => [
                        [
                            'name' => 'SLA Violations',
                            'route' => 'tickets.sla-violations',
                            'icon' => 'exclamation-triangle',
                            'key' => 'sla-violations',
                            'badge' => $slaViolationsCount > 0 ? $slaViolationsCount : null,
                            'badge_type' => 'danger',
                            'description' => 'Tickets breaching SLA',
                        ],
                        [
                            'name' => 'Unassigned Tickets',
                            'route' => 'tickets.unassigned',
                            'icon' => 'user-minus',
                            'key' => 'unassigned',
                            'badge' => $unassignedCount > 0 ? $unassignedCount : null,
                            'badge_type' => 'warning',
                            'description' => 'Tickets needing assignment',
                        ],
                        [
                            'name' => 'Priority Queue',
                            'route' => 'tickets.priority-queue.index',
                            'icon' => 'fire',
                            'key' => 'priority-queue',
                            'description' => 'High priority tickets',
                        ],
                        [
                            'name' => 'Escalated Tickets',
                            'route' => 'tickets.escalated',
                            'icon' => 'arrow-trending-up',
                            'key' => 'escalated',
                            'description' => 'Escalated for review',
                        ],
                    ],
                ],
                [
                    'type' => 'section',
                    'title' => 'STATUS VIEWS',
                    'expandable' => true,
                    'default_expanded' => true,
                    'items' => [
                        [
                            'name' => 'Open Tickets',
                            'route' => 'tickets.index',
                            'icon' => 'exclamation-circle',
                            'key' => 'open',
                            'params' => ['status' => 'open'],
                        ],
                        [
                            'name' => 'In Progress',
                            'route' => 'tickets.index',
                            'icon' => 'arrow-right',
                            'key' => 'in-progress',
                            'params' => ['status' => 'in-progress'],
                        ],
                        [
                            'name' => 'Customer Waiting',
                            'route' => 'tickets.customer-waiting',
                            'icon' => 'pause-circle',
                            'key' => 'customer-waiting',
                            'description' => 'Awaiting customer response',
                        ],
                        [
                            'name' => 'Recurring Tickets',
                            'route' => 'tickets.recurring.index',
                            'icon' => 'arrow-path',
                            'key' => 'recurring',
                            'description' => 'Scheduled maintenance',
                        ],
                        [
                            'name' => 'Closed Tickets',
                            'route' => 'tickets.index',
                            'icon' => 'check-circle',
                            'key' => 'closed',
                            'params' => ['status' => 'closed'],
                        ],
                    ],
                ],
                [
                    'type' => 'section',
                    'title' => 'TEAM MANAGEMENT',
                    'expandable' => true,
                    'default_expanded' => false,
                    'items' => [
                        [
                            'name' => 'Team Queue',
                            'route' => 'tickets.team-queue',
                            'icon' => 'user-group',
                            'key' => 'team-queue',
                            'description' => 'Department tickets',
                        ],
                        [
                            'name' => 'Time & Billing',
                            'route' => 'tickets.time-billing',
                            'icon' => 'currency-dollar',
                            'key' => 'time-billing',
                            'description' => 'Time entries & invoicing',
                        ],
                        [
                            'name' => 'Reports & Analytics',
                            'route' => 'tickets.analytics',
                            'icon' => 'chart-bar',
                            'key' => 'analytics',
                            'description' => 'Performance metrics',
                        ],
                        [
                            'name' => 'Calendar View',
                            'route' => 'tickets.calendar.index',
                            'icon' => 'calendar-days',
                            'key' => 'calendar',
                            'description' => 'Schedule overview',
                        ],
                    ],
                ],
                [
                    'type' => 'section',
                    'title' => 'TOOLS & RESOURCES',
                    'expandable' => true,
                    'default_expanded' => false,
                    'items' => [
                        [
                            'name' => 'Knowledge Base',
                            'route' => 'tickets.knowledge-base',
                            'icon' => 'book-open',
                            'key' => 'knowledge-base',
                            'description' => 'Solutions & articles',
                        ],
                        [
                            'name' => 'Ticket Templates',
                            'route' => 'tickets.templates.index',
                            'icon' => 'document-duplicate',
                            'key' => 'templates',
                            'description' => 'Quick ticket creation',
                        ],
                        [
                            'name' => 'Automation Rules',
                            'route' => 'tickets.automation-rules',
                            'icon' => 'bolt',
                            'key' => 'automation',
                            'description' => 'Workflow automation',
                        ],
                        [
                            'name' => 'Merged/Related',
                            'route' => 'tickets.merged',
                            'icon' => 'link',
                            'key' => 'merged',
                            'description' => 'Linked tickets',
                        ],
                        [
                            'name' => 'Archive & History',
                            'route' => 'tickets.archive',
                            'icon' => 'archive-box',
                            'key' => 'archive',
                            'description' => 'Historical data',
                        ],
                    ],
                ],
                [
                    'type' => 'section',
                    'title' => 'MOBILE TOOLS',
                    'expandable' => true,
                    'default_expanded' => false,
                    'items' => [
                        [
                            'name' => 'Mobile Time Tracker',
                            'route' => 'mobile.time-tracker',
                            'icon' => 'device-phone-mobile',
                            'key' => 'mobile-tracker',
                            'description' => 'Mobile-optimized time tracking interface',
                        ],
                        [
                            'name' => 'Quick Ticket View',
                            'route' => 'tickets.index',
                            'params' => ['mobile' => '1'],
                            'icon' => 'list-bullet',
                            'key' => 'mobile-tickets',
                            'description' => 'Mobile-friendly ticket list',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get email sidebar configuration
     */
    protected function getEmailConfig(): array
    {
        $user = auth()->user();
        $accountsCount = $user ? \App\Domains\Email\Models\EmailAccount::forUser($user->id)->active()->count() : 0;
        $unreadCount = $user ? \App\Domains\Email\Models\EmailMessage::whereHas('emailAccount', function ($q) use ($user) {
            $q->where('user_id', $user->id)->where('is_active', true);
        })->unread()->count() : 0;

        // Get user's email accounts for dynamic sidebar
        $emailAccounts = $user ? \App\Domains\Email\Models\EmailAccount::forUser($user->id)->active()->with('folders')->get() : collect();

        $sections = [
            [
                'type' => 'primary',
                'items' => [
                    [
                        'name' => 'Inbox',
                        'route' => 'email.inbox.index',
                        'icon' => 'inbox',
                        'key' => 'inbox',
                        'badge' => $unreadCount > 0 ? $unreadCount : null,
                        'badge_type' => 'info',
                    ],
                    [
                        'name' => 'Compose',
                        'route' => 'email.compose.index',
                        'icon' => 'pencil',
                        'key' => 'compose',
                    ],
                ],
            ],
        ];

        // Add email accounts section if user has accounts
        if ($emailAccounts->isNotEmpty()) {
            $accountItems = [];
            foreach ($emailAccounts as $account) {
                $accountUnreadCount = $account->messages()->unread()->count();
                $accountItems[] = [
                    'name' => $account->name,
                    'route' => 'email.inbox.index',
                    'route_params' => ['account_id' => $account->id],
                    'icon' => 'envelope',
                    'key' => 'account-'.$account->id,
                    'badge' => $accountUnreadCount > 0 ? $accountUnreadCount : null,
                    'badge_type' => 'info',
                    'description' => $account->email_address,
                ];
            }

            $sections[] = [
                'type' => 'section',
                'title' => 'ACCOUNTS',
                'expandable' => true,
                'default_expanded' => false,
                'items' => $accountItems,
            ];
        }

        // Always include folders section with dynamic content
        $folderItems = [
            [
                'name' => 'Inbox',
                'route' => 'email.inbox.index',
                'icon' => 'inbox',
                'key' => 'inbox-folder',
                'badge' => $unreadCount > 0 ? $unreadCount : null,
                'badge_type' => 'info',
            ],
            [
                'name' => 'Sent',
                'route' => 'email.inbox.index',
                'icon' => 'paper-airplane',
                'key' => 'sent-folder',
            ],
            [
                'name' => 'Drafts',
                'route' => 'email.inbox.index',
                'icon' => 'document-text',
                'key' => 'drafts-folder',
            ],
            [
                'name' => 'Trash',
                'route' => 'email.inbox.index',
                'icon' => 'trash',
                'key' => 'trash-folder',
            ],
        ];

        // Add dynamic folders from email accounts
        if ($emailAccounts->isNotEmpty()) {
            foreach ($emailAccounts as $account) {
                // Add synced folders from this account
                foreach ($account->folders as $folder) {
                    $folderUnreadCount = $folder->unread_count;
                    $folderName = $folder->getDisplayName();

                    // Add account name for non-standard folders
                    if (! in_array($folder->type, ['inbox', 'sent', 'drafts', 'trash'])) {
                        $folderName .= ' ('.$account->name.')';
                    }

                    $folderItems[] = [
                        'name' => $folderName,
                        'route' => 'email.inbox.index',
                        'route_params' => ['account_id' => $account->id, 'folder_id' => $folder->id],
                        'icon' => $folder->getIcon(),
                        'key' => 'folder-'.$folder->id,
                        'badge' => $folderUnreadCount > 0 ? $folderUnreadCount : null,
                        'badge_type' => 'info',
                        'description' => $account->email_address,
                    ];
                }
            }
        }

        $sections[] = [
            'type' => 'section',
            'title' => 'FOLDERS',
            'expandable' => true,
            'default_expanded' => false,
            'items' => $folderItems,
        ];

        // Add management section
        $sections[] = [
            'type' => 'section',
            'title' => 'MANAGEMENT',
            'expandable' => true,
            'default_expanded' => false,
            'items' => [
                [
                    'name' => 'Email Accounts',
                    'route' => 'email.accounts.index',
                    'icon' => 'cog',
                    'key' => 'accounts',
                    'badge' => $accountsCount > 0 ? $accountsCount : null,
                    'badge_type' => 'success',
                ],
                [
                    'name' => 'Signatures',
                    'route' => 'email.signatures.index',
                    'icon' => 'pencil-square',
                    'key' => 'signatures',
                ],
            ],
        ];

        return [
            'title' => 'Email Management',
            'icon' => 'envelope',
            'sections' => $sections,
        ];
    }

    /**
     * Get physical mail sidebar configuration
     */
    protected function getPhysicalMailConfig(): array
    {
        $user = auth()->user();
        $selectedClient = NavigationService::getSelectedClient();

        // Get physical mail statistics (with safe fallback)
        $totalMails = 0;
        $pendingMails = 0;

        if ($user && $user->company_id) {
            try {
                $query = \App\Domains\PhysicalMail\Models\PhysicalMailOrder::query();

                // Filter by selected client if present
                if ($selectedClient) {
                    $query->where('client_id', $selectedClient->id);
                }

                $totalMails = (clone $query)->count();
                $pendingMails = (clone $query)->whereIn('status', ['pending', 'processing'])->count();
            } catch (\Exception $e) {
                // Silently handle any issues with querying the model
                $totalMails = 0;
                $pendingMails = 0;
            }
        }

        return [
            'title' => 'Physical Mail',
            'icon' => 'paper-airplane',
            'sections' => [
                [
                    'type' => 'primary',
                    'items' => [
                        [
                            'name' => 'Dashboard',
                            'route' => 'mail.index',
                            'icon' => 'chart-pie',
                            'key' => 'dashboard',
                            'description' => 'Overview of physical mail activity',
                        ],
                        [
                            'name' => 'Send Mail',
                            'route' => 'mail.send',
                            'icon' => 'plus-circle',
                            'key' => 'send',
                            'description' => 'Send new physical mail',
                        ],
                    ],
                ],
                [
                    'type' => 'section',
                    'title' => 'MAIL MANAGEMENT',
                    'expandable' => true,
                    'default_expanded' => true,
                    'items' => [
                        [
                            'name' => 'Tracking',
                            'route' => 'mail.tracking',
                            'icon' => 'map-pin',
                            'key' => 'tracking',
                            'badge' => $pendingMails > 0 ? $pendingMails : null,
                            'badge_type' => 'warning',
                            'description' => 'Track delivery status',
                        ],
                        [
                            'name' => 'Templates',
                            'route' => 'mail.templates',
                            'icon' => 'document-text',
                            'key' => 'templates',
                            'description' => 'Manage mail templates',
                        ],
                        [
                            'name' => 'Contacts',
                            'route' => 'mail.contacts',
                            'icon' => 'user-group',
                            'key' => 'contacts',
                            'description' => 'Manage mailing addresses',
                        ],
                    ],
                ],
                [
                    'type' => 'section',
                    'title' => 'STATISTICS',
                    'expandable' => true,
                    'default_expanded' => false,
                    'items' => [
                        [
                            'name' => 'Total Sent',
                            'route' => 'mail.index',
                            'icon' => 'chart-bar',
                            'key' => 'stats-total',
                            'badge' => $totalMails,
                            'badge_type' => 'info',
                            'description' => 'All time mail count',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get assets sidebar configuration
     */
    protected function getAssetsConfig(): array
    {
        return [
            'title' => 'Asset Management',
            'icon' => 'computer-desktop',
            'sections' => [
                [
                    'type' => 'primary',
                    'items' => [
                        [
                            'name' => 'Overview',
                            'route' => 'assets.index',
                            'icon' => 'home',
                            'key' => 'overview',
                        ],
                        [
                            'name' => 'Add New Asset',
                            'route' => 'assets.create',
                            'icon' => 'plus',
                            'key' => 'create',
                        ],
                    ],
                ],
                [
                    'type' => 'section',
                    'title' => 'CATEGORIES',
                    'expandable' => true,
                    'default_expanded' => true,
                    'items' => [
                        [
                            'name' => 'Hardware',
                            'route' => 'assets.index',
                            'icon' => 'computer-desktop',
                            'key' => 'hardware',
                            'params' => ['category' => 'hardware'],
                        ],
                        [
                            'name' => 'Software',
                            'route' => 'assets.index',
                            'icon' => 'code-bracket',
                            'key' => 'software',
                            'params' => ['category' => 'software'],
                        ],
                        [
                            'name' => 'Mobile Devices',
                            'route' => 'assets.index',
                            'icon' => 'device-phone-mobile',
                            'key' => 'mobile',
                            'params' => ['category' => 'mobile'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get financial sidebar configuration (truncated for brevity)
     */
    protected function getFinancialConfig(): array
    {
        return [
            'title' => 'Financial Management',
            'icon' => 'currency-dollar',
            'sections' => [
                [
                    'type' => 'primary',
                    'items' => [
                        [
                            'name' => 'Financial Dashboard',
                            'route' => 'financial.dashboard',
                            'icon' => 'chart-bar-square',
                            'key' => 'dashboard',
                            'description' => 'Overview of financial metrics and KPIs',
                        ],
                    ],
                ],
                [
                    'type' => 'section',
                    'title' => 'BILLING & INVOICING',
                    'expandable' => true,
                    'default_expanded' => true,
                    'items' => [
                        [
                            'name' => 'Invoices',
                            'route' => 'financial.invoices.index',
                            'icon' => 'document-text',
                            'key' => 'invoices',
                            'description' => 'Manage customer invoices',
                        ],
                        [
                            'name' => 'Time Entry Approval',
                            'route' => 'billing.time-entries',
                            'icon' => 'clock',
                            'key' => 'time-entries',
                            'description' => 'Review and approve billable time for invoicing',
                        ],
                        [
                            'name' => 'Payments',
                            'route' => 'financial.payments.index',
                            'icon' => 'credit-card',
                            'key' => 'payments',
                            'description' => 'Track payment history',
                        ],
                        [
                            'name' => 'Recurring Billing',
                            'route' => 'financial.recurring-invoices.index',
                            'icon' => 'arrow-path',
                            'key' => 'recurring',
                            'description' => 'Manage subscriptions',
                        ],
                        [
                            'name' => 'Rate Cards',
                            'route' => 'financial.invoices.index',
                            'params' => ['tab' => 'rate-cards'],
                            'icon' => 'currency-dollar',
                            'key' => 'rate-cards',
                            'description' => 'Manage client-specific billing rates',
                        ],
                    ],
                ],
                [
                    'type' => 'section',
                    'title' => 'ACCOUNTING',
                    'expandable' => true,
                    'default_expanded' => false,
                    'items' => [
                        [
                            'name' => 'Chart of Accounts',
                            'route' => 'financial.accounts.index',
                            'icon' => 'list-bullet',
                            'key' => 'accounts',
                            'description' => 'Manage GL accounts',
                        ],
                        [
                            'name' => 'Journal Entries',
                            'route' => 'financial.journal.index',
                            'icon' => 'book-open',
                            'key' => 'journal',
                            'description' => 'View journal entries',
                        ],
                        [
                            'name' => 'Tax Settings',
                            'route' => 'financial.tax.index',
                            'icon' => 'calculator',
                            'key' => 'tax',
                            'description' => 'Configure tax rates',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get projects sidebar configuration
     */
    protected function getProjectsConfig(): array
    {
        return [
            'title' => 'Project Management',
            'icon' => 'folder',
            'sections' => [
                [
                    'type' => 'primary',
                    'items' => [
                        [
                            'name' => 'Project Overview',
                            'route' => 'projects.index',
                            'icon' => 'home',
                            'key' => 'overview',
                        ],
                        [
                            'name' => 'Create Project',
                            'route' => 'projects.create',
                            'icon' => 'plus',
                            'key' => 'create',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get reports sidebar configuration
     */
    protected function getReportsConfig(): array
    {
        return [
            'title' => 'Reports & Analytics',
            'icon' => 'chart-bar',
            'sections' => [
                [
                    'type' => 'primary',
                    'items' => [
                        [
                            'name' => 'Reports Overview',
                            'route' => 'reports.index',
                            'icon' => 'home',
                            'key' => 'overview',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get manager sidebar configuration
     */
    protected function getManagerConfig(): array
    {
        $user = auth()->user();

        // Real-time statistics for badges
        $overdueTicketsCount = 0;
        $unassignedCount = 0;
        $atRiskCount = 0;

        if ($user && $user->company_id) {
            try {
                // SLA breached tickets
                $overdueTicketsCount = \App\Domains\Ticket\Models\Ticket::where('company_id', $user->company_id)
                    ->whereNotIn('status', ['closed', 'resolved'])
                    ->whereHas('priorityQueue', function ($q) {
                        $q->where('sla_deadline', '<', now());
                    })
                    ->count();

                // Unassigned tickets
                $unassignedCount = \App\Domains\Ticket\Models\Ticket::where('company_id', $user->company_id)
                    ->whereNull('assigned_to')
                    ->whereNotIn('status', ['closed', 'resolved'])
                    ->count();

                // At-risk tickets (within 2 hours of SLA deadline)
                $atRiskCount = \App\Domains\Ticket\Models\Ticket::where('company_id', $user->company_id)
                    ->whereNotIn('status', ['closed', 'resolved'])
                    ->whereHas('priorityQueue', function ($q) {
                        $q->where('sla_deadline', '>', now())
                          ->where('sla_deadline', '<=', now()->addHours(2));
                    })
                    ->count();
            } catch (\Exception $e) {
                // Silently handle any database issues
            }
        }

        return [
            'title' => 'Manager Tools',
            'icon' => 'briefcase',
            'sections' => [
                [
                    'type' => 'primary',
                    'items' => [
                        [
                            'name' => 'Team Dashboard',
                            'route' => 'manager.dashboard',
                            'icon' => 'chart-bar',
                            'key' => 'dashboard',
                            'description' => 'Real-time team performance and ticket overview',
                        ],
                    ],
                ],
                [
                    'type' => 'section',
                    'title' => 'TEAM MANAGEMENT',
                    'expandable' => true,
                    'default_expanded' => true,
                    'items' => [
                        [
                            'name' => 'Tech Capacity',
                            'route' => 'manager.capacity',
                            'icon' => 'users',
                            'key' => 'capacity',
                            'description' => 'View workload and capacity across technicians',
                        ],
                        [
                            'name' => 'Unassigned Tickets',
                            'route' => 'tickets.index',
                            'params' => ['filter' => 'unassigned'],
                            'icon' => 'exclamation-triangle',
                            'key' => 'unassigned',
                            'badge' => $unassignedCount > 0 ? $unassignedCount : null,
                            'badge_type' => 'warning',
                            'description' => 'Tickets awaiting assignment',
                        ],
                    ],
                ],
                [
                    'type' => 'section',
                    'title' => 'SLA MONITORING',
                    'expandable' => true,
                    'default_expanded' => true,
                    'items' => [
                        [
                            'name' => 'SLA Breaches',
                            'route' => 'tickets.index',
                            'params' => ['filter' => 'sla_breached'],
                            'icon' => 'shield-exclamation',
                            'key' => 'sla-breaches',
                            'badge' => $overdueTicketsCount > 0 ? $overdueTicketsCount : null,
                            'badge_type' => 'danger',
                            'description' => 'Tickets that have breached SLA deadlines',
                        ],
                        [
                            'name' => 'At Risk',
                            'route' => 'tickets.index',
                            'params' => ['filter' => 'sla_at_risk'],
                            'icon' => 'clock',
                            'key' => 'sla-at-risk',
                            'badge' => $atRiskCount > 0 ? $atRiskCount : null,
                            'badge_type' => 'warning',
                            'description' => 'Tickets approaching SLA deadlines (< 2 hours)',
                        ],
                    ],
                ],
                [
                    'type' => 'section',
                    'title' => 'REPORTS',
                    'expandable' => true,
                    'default_expanded' => false,
                    'items' => [
                        [
                            'name' => 'Team Performance',
                            'route' => 'reports.index',
                            'params' => ['type' => 'team-performance'],
                            'icon' => 'chart-line',
                            'key' => 'team-performance',
                            'description' => 'Technician productivity and performance metrics',
                        ],
                        [
                            'name' => 'SLA Compliance',
                            'route' => 'reports.index',
                            'params' => ['type' => 'sla-compliance'],
                            'icon' => 'shield-check',
                            'key' => 'sla-compliance',
                            'description' => 'SLA compliance rates and trends',
                        ],
                        [
                            'name' => 'Client Satisfaction',
                            'route' => 'reports.index',
                            'params' => ['type' => 'satisfaction'],
                            'icon' => 'face-smile',
                            'key' => 'satisfaction',
                            'description' => 'Customer satisfaction survey results',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get settings sidebar configuration
     */
    protected function getSettingsConfig(): array
    {
        return [
            'title' => 'Settings',
            'icon' => 'cog-6-tooth',
            'sections' => [
                [
                    'type' => 'primary',
                    'items' => [
                        ['name' => 'Settings Overview', 'route' => 'settings.index', 'icon' => 'home', 'key' => 'overview', 'description' => 'All settings at a glance'],
                    ],
                ],
                [
                    'type' => 'section',
                    'title' => 'Company',
                    'expandable' => true,
                    'default_expanded' => true,
                    'items' => [
                        ['name' => 'General', 'route' => 'settings.category.show', 'params' => ['domain' => 'company', 'category' => 'general'], 'icon' => 'adjustments-horizontal', 'key' => 'general'],
                        ['name' => 'Branding', 'route' => 'settings.category.show', 'params' => ['domain' => 'company', 'category' => 'branding'], 'icon' => 'paint-brush', 'key' => 'branding'],
                        ['name' => 'Users', 'route' => 'settings.category.show', 'params' => ['domain' => 'company', 'category' => 'users'], 'icon' => 'users', 'key' => 'users'],
                        ['name' => 'Subsidiaries', 'route' => 'subsidiaries.index', 'icon' => 'building-office-2', 'key' => 'subsidiaries'],
                    ],
                ],
                [
                    'type' => 'section',
                    'title' => 'Security',
                    'expandable' => true,
                    'default_expanded' => false,
                    'items' => [
                        ['name' => 'Access Control', 'route' => 'settings.category.show', 'params' => ['domain' => 'security', 'category' => 'access'], 'icon' => 'shield-check', 'key' => 'access'],
                        ['name' => 'Authentication', 'route' => 'settings.category.show', 'params' => ['domain' => 'security', 'category' => 'auth'], 'icon' => 'finger-print', 'key' => 'auth'],
                        ['name' => 'Compliance', 'route' => 'settings.category.show', 'params' => ['domain' => 'security', 'category' => 'compliance'], 'icon' => 'clipboard-document-check', 'key' => 'compliance'],
                        ['name' => 'Permissions', 'url' => '/settings/permissions/manage?tab=matrix', 'icon' => 'key', 'key' => 'permissions'],
                        ['name' => 'Roles', 'url' => '/settings/permissions/manage?tab=roles', 'icon' => 'identification', 'key' => 'roles'],
                    ],
                ],
                [
                    'type' => 'section',
                    'title' => 'Communication',
                    'expandable' => true,
                    'default_expanded' => false,
                    'items' => [
                        ['name' => 'Email', 'route' => 'settings.category.show', 'params' => ['domain' => 'communication', 'category' => 'email'], 'icon' => 'envelope', 'key' => 'email'],
                        ['name' => 'Notification Preferences', 'route' => 'settings.notifications', 'icon' => 'bell', 'key' => 'notifications', 'description' => 'Configure email and in-app notification preferences'],
                        ['name' => 'Physical Mail', 'route' => 'settings.category.show', 'params' => ['domain' => 'communication', 'category' => 'physical-mail'], 'icon' => 'paper-airplane', 'key' => 'physical-mail'],
                        ['name' => 'Templates', 'route' => 'settings.category.show', 'params' => ['domain' => 'communication', 'category' => 'templates'], 'icon' => 'document-duplicate', 'key' => 'templates'],
                    ],
                ],
                [
                    'type' => 'section',
                    'title' => 'Financial',
                    'expandable' => true,
                    'default_expanded' => false,
                    'items' => [
                        ['name' => 'Billing', 'route' => 'settings.category.show', 'params' => ['domain' => 'financial', 'category' => 'billing'], 'icon' => 'credit-card', 'key' => 'billing'],
                        ['name' => 'Accounting', 'route' => 'settings.category.show', 'params' => ['domain' => 'financial', 'category' => 'accounting'], 'icon' => 'book-open', 'key' => 'accounting'],
                        ['name' => 'Payments', 'route' => 'settings.category.show', 'params' => ['domain' => 'financial', 'category' => 'payments'], 'icon' => 'banknotes', 'key' => 'payments'],
                        ['name' => 'Taxes', 'route' => 'settings.category.show', 'params' => ['domain' => 'financial', 'category' => 'taxes'], 'icon' => 'calculator', 'key' => 'taxes'],
                    ],
                ],
                [
                    'type' => 'section',
                    'title' => 'Operations',
                    'expandable' => true,
                    'default_expanded' => false,
                    'items' => [
                        ['name' => 'Ticketing', 'route' => 'settings.category.show', 'params' => ['domain' => 'operations', 'category' => 'ticketing'], 'icon' => 'ticket', 'key' => 'ticketing'],
                        ['name' => 'Projects', 'route' => 'settings.category.show', 'params' => ['domain' => 'operations', 'category' => 'projects'], 'icon' => 'folder', 'key' => 'projects'],
                        ['name' => 'Assets', 'route' => 'settings.category.show', 'params' => ['domain' => 'operations', 'category' => 'assets'], 'icon' => 'computer-desktop', 'key' => 'assets'],
                        ['name' => 'Contracts', 'route' => 'settings.category.show', 'params' => ['domain' => 'operations', 'category' => 'contracts'], 'icon' => 'document-text', 'key' => 'contracts'],
                        ['name' => 'Contract Clauses', 'route' => 'settings.category.show', 'params' => ['domain' => 'operations', 'category' => 'clauses'], 'icon' => 'document-check', 'key' => 'clauses'],
                        ['name' => 'Portal', 'route' => 'settings.category.show', 'params' => ['domain' => 'operations', 'category' => 'portal'], 'icon' => 'user-group', 'key' => 'portal'],
                        ['name' => 'Reports', 'route' => 'settings.category.show', 'params' => ['domain' => 'operations', 'category' => 'reports'], 'icon' => 'chart-bar', 'key' => 'reports'],
                        ['name' => 'Knowledge Base', 'route' => 'settings.category.show', 'params' => ['domain' => 'operations', 'category' => 'knowledge'], 'icon' => 'book-open', 'key' => 'knowledge'],
                        ['name' => 'Training', 'route' => 'settings.category.show', 'params' => ['domain' => 'operations', 'category' => 'training'], 'icon' => 'academic-cap', 'key' => 'training'],
                    ],
                ],
                [
                    'type' => 'section',
                    'title' => 'Integrations',
                    'expandable' => true,
                    'default_expanded' => false,
                    'items' => [
                        ['name' => 'Overview', 'route' => 'settings.category.show', 'params' => ['domain' => 'integrations', 'category' => 'overview'], 'icon' => 'puzzle-piece', 'key' => 'integrations-overview'],
                        ['name' => 'RMM', 'route' => 'settings.category.show', 'params' => ['domain' => 'integrations', 'category' => 'rmm'], 'icon' => 'computer-desktop', 'key' => 'rmm'],
                        ['name' => 'API', 'route' => 'settings.category.show', 'params' => ['domain' => 'integrations', 'category' => 'api'], 'icon' => 'link', 'key' => 'api'],
                        ['name' => 'Webhooks', 'route' => 'settings.category.show', 'params' => ['domain' => 'integrations', 'category' => 'webhooks'], 'icon' => 'arrow-path', 'key' => 'webhooks'],
                    ],
                ],
                [
                    'type' => 'section',
                    'title' => 'System',
                    'expandable' => true,
                    'default_expanded' => false,
                    'items' => [
                        ['name' => 'Performance', 'route' => 'settings.category.show', 'params' => ['domain' => 'system', 'category' => 'performance'], 'icon' => 'rocket-launch', 'key' => 'performance'],
                        ['name' => 'Database', 'route' => 'settings.category.show', 'params' => ['domain' => 'system', 'category' => 'database'], 'icon' => 'circle-stack', 'key' => 'database'],
                        ['name' => 'Backup', 'route' => 'settings.category.show', 'params' => ['domain' => 'system', 'category' => 'backup'], 'icon' => 'archive-box', 'key' => 'backup'],
                        ['name' => 'Automation', 'route' => 'settings.category.show', 'params' => ['domain' => 'system', 'category' => 'automation'], 'icon' => 'bolt', 'key' => 'automation'],
                        ['name' => 'Mobile Access', 'route' => 'settings.category.show', 'params' => ['domain' => 'system', 'category' => 'mobile'], 'icon' => 'device-phone-mobile', 'key' => 'mobile'],
                        ['name' => 'Mail Queue', 'route' => 'mail-queue.index', 'icon' => 'envelope', 'key' => 'mail-queue'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Filter configuration based on user permissions
     */
    protected function filterByPermissions(array $config): array
    {
        if (empty($config['sections'])) {
            return $config;
        }

        $user = auth()->user();
        if (! $user) {
            return [];
        }

        $config['sections'] = $this->filterSections($config['sections'], $user);

        return $config;
    }

    /**
     * Filter sections based on user permissions
     */
    protected function filterSections(array $sections, $user): array
    {
        $filteredSections = [];

        foreach ($sections as $section) {
            $filteredSection = $this->filterSection($section, $user);
            
            if ($filteredSection !== null) {
                $filteredSections[] = $filteredSection;
            }
        }

        return $filteredSections;
    }

    /**
     * Filter a single section based on permissions
     */
    protected function filterSection(array $section, $user): ?array
    {
        if ($this->sectionHasNoPermission($section, $user)) {
            return null;
        }

        if (! isset($section['items'])) {
            return $section;
        }

        $filteredItems = $this->filterSectionItems($section['items'], $user);

        if (empty($filteredItems)) {
            return null;
        }

        $section['items'] = $filteredItems;

        return $section;
    }

    /**
     * Check if section has no permission
     */
    protected function sectionHasNoPermission(array $section, $user): bool
    {
        return isset($section['permission']) && ! $this->userHasPermission($user, $section['permission']);
    }

    /**
     * Filter section items based on permissions
     */
    protected function filterSectionItems(array $items, $user): array
    {
        $filteredItems = [];

        foreach ($items as $item) {
            if (! isset($item['permission']) || $this->userHasPermission($user, $item['permission'])) {
                $filteredItems[] = $item;
            }
        }

        return $filteredItems;
    }

    /**
     * Check if user has permission
     * More lenient check that doesn't require all permissions to be defined
     */
    protected function userHasPermission($user, string $permission): bool
    {
        // Super admin always has access
        if ($user->company_id === 1 || $user->id === 1) {
            return true;
        }

        // For settings permissions, allow access if user can access settings in general
        if (str_starts_with($permission, 'settings.')) {
            // If user can view settings, allow all settings sub-permissions
            if (method_exists($user, 'hasPermission')) {
                return $user->hasPermission('settings.view') || $user->hasPermission($permission);
            }

            // Fallback to can() method
            return $user->can('settings.view') || $user->can($permission);
        }

        // Use the appropriate permission checking method
        if (method_exists($user, 'hasPermission')) {
            return $user->hasPermission($permission);
        }

        return $user->can($permission);
    }

    /**
     * Merge multiple configurations
     */
    protected function mergeConfigurations(array $base, array $custom): array
    {
        return array_merge_recursive($base, $custom);
    }

    /**
     * Get default configuration for a context
     */
    protected function getDefaultConfiguration(?string $context): array
    {
        return config("sidebar.contexts.{$context}", config('sidebar.default', []));
    }
}
