<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

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
        if (!isset($this->registeredSections[$context])) {
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
        if (!$context) {
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
            case 'settings':
                return $this->getSettingsConfig();
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
                            'description' => 'Central hub with client health and quick actions'
                        ]
                    ]
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
                            'params' => ['client_id' => 'current', 'status' => 'open'],
                            'badge_type' => 'urgent',
                            'show_if' => 'has_open_tickets'
                        ],
                        [
                            'name' => 'Pending Items',
                            'route' => 'clients.show',
                            'icon' => 'clock',
                            'key' => 'pending',
                            'params' => ['client' => 'current', 'section' => 'pending'],
                            'badge_type' => 'warning',
                            'show_if' => 'has_pending_items'
                        ]
                    ]
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
                            'params' => ['client' => 'current']
                        ],
                        [
                            'name' => 'Locations',
                            'route' => 'clients.locations.index',
                            'icon' => 'map-pin',
                            'key' => 'locations',
                            'params' => ['client' => 'current']
                        ],
                        [
                            'name' => 'Communication Log',
                            'route' => 'clients.communications.index',
                            'icon' => 'chat-bubble-left-right',
                            'key' => 'communications',
                            'params' => []
                        ]
                    ]
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
                            'params' => ['client_id' => 'current']
                        ],
                        [
                            'name' => 'Assets & Equipment',
                            'route' => 'assets.index',
                            'icon' => 'computer-desktop',
                            'key' => 'assets',
                            'params' => ['client_id' => 'current']
                        ],
                        [
                            'name' => 'Projects',
                            'route' => 'projects.index',
                            'icon' => 'folder',
                            'key' => 'projects',
                            'params' => ['client_id' => 'current']
                        ]
                    ]
                ],
                [
                    'type' => 'section',
                    'title' => 'BILLING & FINANCE',
                    'expandable' => true,
                    'default_expanded' => false,
                    'items' => [
                        [
                            'name' => 'Contracts',
                            'route' => 'financial.contracts.index',
                            'icon' => 'document-check',
                            'key' => 'contracts',
                            'params' => ['client_id' => 'current']
                        ],
                        [
                            'name' => 'Quotes',
                            'route' => 'financial.quotes.index',
                            'icon' => 'document-currency-dollar',
                            'key' => 'quotes',
                            'params' => ['client_id' => 'current']
                        ],
                        [
                            'name' => 'Invoices',
                            'route' => 'financial.invoices.index',
                            'icon' => 'document-text',
                            'key' => 'invoices',
                            'params' => ['client_id' => 'current']
                        ]
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Get tickets sidebar configuration
     */
    protected function getTicketsConfig(): array
    {
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
                            'key' => 'overview'
                        ]
                    ]
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
                            'params' => ['filter' => 'my']
                        ],
                        [
                            'name' => 'Assigned to Me',
                            'route' => 'tickets.index',
                            'icon' => 'user-circle',
                            'key' => 'assigned',
                            'params' => ['assignee' => 'me']
                        ]
                    ]
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
                            'params' => ['status' => 'open']
                        ],
                        [
                            'name' => 'In Progress',
                            'route' => 'tickets.index',
                            'icon' => 'arrow-right',
                            'key' => 'in-progress',
                            'params' => ['status' => 'in-progress']
                        ],
                        [
                            'name' => 'Closed Tickets',
                            'route' => 'tickets.index',
                            'icon' => 'check-circle',
                            'key' => 'closed',
                            'params' => ['status' => 'closed']
                        ]
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Get email sidebar configuration
     */
    protected function getEmailConfig(): array
    {
        $user = auth()->user();
        $accountsCount = $user ? \App\Domains\Email\Models\EmailAccount::forUser($user->id)->active()->count() : 0;
        $unreadCount = $user ? \App\Domains\Email\Models\EmailMessage::whereHas('emailAccount', function($q) use ($user) {
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
                        'badge_type' => 'info'
                    ],
                    [
                        'name' => 'Compose',
                        'route' => 'email.compose.index',
                        'icon' => 'pencil',
                        'key' => 'compose'
                    ]
                ]
            ]
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
                    'key' => 'account-' . $account->id,
                    'badge' => $accountUnreadCount > 0 ? $accountUnreadCount : null,
                    'badge_type' => 'info',
                    'description' => $account->email_address
                ];
            }

            $sections[] = [
                'type' => 'section',
                'title' => 'ACCOUNTS',
                'expandable' => true,
                'default_expanded' => true,
                'items' => $accountItems
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
                'badge_type' => 'info'
            ],
            [
                'name' => 'Sent',
                'route' => 'email.inbox.index',
                'icon' => 'paper-airplane',
                'key' => 'sent-folder'
            ],
            [
                'name' => 'Drafts',
                'route' => 'email.inbox.index',
                'icon' => 'document-text',
                'key' => 'drafts-folder'
            ],
            [
                'name' => 'Trash',
                'route' => 'email.inbox.index',
                'icon' => 'trash',
                'key' => 'trash-folder'
            ]
        ];

        // Add dynamic folders from email accounts
        if ($emailAccounts->isNotEmpty()) {
            foreach ($emailAccounts as $account) {
                // Add synced folders from this account
                foreach ($account->folders as $folder) {
                    $folderUnreadCount = $folder->unread_count;
                    $folderName = $folder->getDisplayName();

                    // Add account name for non-standard folders
                    if (!in_array($folder->type, ['inbox', 'sent', 'drafts', 'trash'])) {
                        $folderName .= ' (' . $account->name . ')';
                    }

                    $folderItems[] = [
                        'name' => $folderName,
                        'route' => 'email.inbox.index',
                        'route_params' => ['account_id' => $account->id, 'folder_id' => $folder->id],
                        'icon' => $folder->getIcon(),
                        'key' => 'folder-' . $folder->id,
                        'badge' => $folderUnreadCount > 0 ? $folderUnreadCount : null,
                        'badge_type' => 'info',
                        'description' => $account->email_address
                    ];
                }
            }
        }

        $sections[] = [
            'type' => 'section',
            'title' => 'FOLDERS',
            'expandable' => true,
            'default_expanded' => true,
            'items' => $folderItems
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
                    'badge_type' => 'success'
                ],
                [
                    'name' => 'Signatures',
                    'route' => 'email.signatures.index',
                    'icon' => 'pencil-square',
                    'key' => 'signatures'
                ]
            ]
        ];

        return [
            'title' => 'Email Management',
            'icon' => 'envelope',
            'sections' => $sections
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
                            'key' => 'overview'
                        ],
                        [
                            'name' => 'Add New Asset',
                            'route' => 'assets.create',
                            'icon' => 'plus',
                            'key' => 'create'
                        ]
                    ]
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
                            'params' => ['category' => 'hardware']
                        ],
                        [
                            'name' => 'Software',
                            'route' => 'assets.index',
                            'icon' => 'code-bracket',
                            'key' => 'software',
                            'params' => ['category' => 'software']
                        ],
                        [
                            'name' => 'Mobile Devices',
                            'route' => 'assets.index',
                            'icon' => 'device-phone-mobile',
                            'key' => 'mobile',
                            'params' => ['category' => 'mobile']
                        ]
                    ]
                ]
            ]
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
                            'description' => 'Overview of financial metrics and KPIs'
                        ]
                    ]
                ]
            ]
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
                            'key' => 'overview'
                        ],
                        [
                            'name' => 'Create Project',
                            'route' => 'projects.create',
                            'icon' => 'plus',
                            'key' => 'create'
                        ]
                    ]
                ]
            ]
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
                            'key' => 'overview'
                        ]
                    ]
                ]
            ]
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
                    'type' => 'section',
                    'title' => 'CONFIGURATION',
                    'expandable' => false,
                    'items' => [
                        [
                            'name' => 'General',
                            'route' => 'settings.general',
                            'icon' => 'adjustments-horizontal',
                            'key' => 'general'
                        ],
                        [
                            'name' => 'Security',
                            'route' => 'settings.security',
                            'icon' => 'shield-check',
                            'key' => 'security'
                        ],
                        [
                            'name' => 'Email',
                            'route' => 'settings.email',
                            'icon' => 'envelope',
                            'key' => 'email'
                        ],
                        [
                            'name' => 'Billing & Financial',
                            'route' => 'settings.billing-financial',
                            'icon' => 'credit-card',
                            'key' => 'billing'
                        ]
                    ]
                ],
                [
                    'type' => 'section',
                    'title' => 'USER MANAGEMENT',
                    'expandable' => false,
                    'items' => [
                        [
                            'name' => 'Users',
                            'route' => 'users.index',
                            'icon' => 'users',
                            'key' => 'users'
                        ],
                        [
                            'name' => 'Roles',
                            'route' => 'settings.roles.index',
                            'icon' => 'identification',
                            'key' => 'roles'
                        ],
                        [
                            'name' => 'Permissions',
                            'route' => 'settings.permissions.index',
                            'icon' => 'key',
                            'key' => 'permissions'
                        ]
                    ]
                ],
                [
                    'type' => 'section',
                    'title' => 'SYSTEM',
                    'expandable' => false,
                    'items' => [
                        [
                            'name' => 'RMM & Monitoring',
                            'route' => 'settings.rmm-monitoring',
                            'icon' => 'computer-desktop',
                            'key' => 'rmm-monitoring'
                        ],
                        [
                            'name' => 'API & Webhooks',
                            'route' => 'settings.api-webhooks',
                            'icon' => 'link',
                            'key' => 'api-webhooks'
                        ]
                    ]
                ]
            ]
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
        if (!$user) {
            return [];
        }
        
        $filteredSections = [];
        
        foreach ($config['sections'] as $sectionKey => $section) {
            // Check section-level permissions
            if (isset($section['permission']) && !$this->userHasPermission($user, $section['permission'])) {
                continue;
            }
            
            // Filter items within section
            if (isset($section['items'])) {
                $filteredItems = [];
                foreach ($section['items'] as $item) {
                    if (!isset($item['permission']) || $this->userHasPermission($user, $item['permission'])) {
                        $filteredItems[] = $item;
                    }
                }
                
                if (!empty($filteredItems)) {
                    $section['items'] = $filteredItems;
                    $filteredSections[] = $section;
                }
            } else {
                $filteredSections[] = $section;
            }
        }
        
        $config['sections'] = $filteredSections;
        return $config;
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