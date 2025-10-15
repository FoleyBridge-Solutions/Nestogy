<?php

namespace App\Domains\Core\Services\Navigation;

use Illuminate\Support\Facades\Auth;

class SidebarBuilder
{
    protected string $context;
    protected $user;
    protected $selectedClient;

    public function __construct(string $context)
    {
        $this->context = $context;
        $this->user = Auth::user();
        $this->selectedClient = NavigationContext::getSelectedClient();
    }

    public function build(): array
    {
        $method = 'build' . ucfirst($this->context) . 'Sidebar';
        
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        return $this->buildGenericSidebar();
    }

    protected function buildFinancialSidebar(): array
    {
        return [
            'title' => 'Financial Management',
            'icon' => 'currency-dollar',
            'sections' => [
                $this->buildPrimarySection('financial', 'primary'),
                $this->buildRegistrySection('financial', 'billing', 'BILLING & INVOICING'),
                $this->buildRegistrySection('financial', 'products', 'PRODUCTS & SERVICES'),
            ],
        ];
    }

    protected function buildTicketsSidebar(): array
    {
        $badges = $this->calculateTicketBadges();

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
                            'name' => 'Active Timers',
                            'route' => 'tickets.active-timers',
                            'icon' => 'clock',
                            'key' => 'active-timers',
                            'badge' => $badges['active_timers'] ?? null,
                            'badge_type' => 'success',
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
                            'badge' => $badges['sla_violations'] ?? null,
                            'badge_type' => 'danger',
                        ],
                        [
                            'name' => 'Unassigned Tickets',
                            'route' => 'tickets.unassigned',
                            'icon' => 'user-minus',
                            'key' => 'unassigned',
                            'badge' => $badges['unassigned'] ?? null,
                            'badge_type' => 'warning',
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function buildClientsSidebar(): array
    {
        if (!$this->selectedClient) {
            return [];
        }

        return [
            'title' => 'Client Management',
            'icon' => 'user-group',
            'sections' => [
                $this->buildPrimarySection('clients', 'primary'),
                $this->buildRegistrySection('clients', 'communication', 'COMMUNICATION'),
                $this->buildRegistrySection('clients', 'service', 'SERVICE MANAGEMENT'),
                $this->buildClientInfrastructureSection(),
                $this->buildClientBillingSection(),
            ],
        ];
    }

    protected function buildAssetsSidebar(): array
    {
        return [
            'title' => 'Asset Management',
            'icon' => 'computer-desktop',
            'sections' => [
                $this->buildPrimarySection('assets', 'primary'),
            ],
        ];
    }

    protected function buildProjectsSidebar(): array
    {
        return [
            'title' => 'Project Management',
            'icon' => 'folder',
            'sections' => [
                $this->buildPrimarySection('projects', 'primary'),
            ],
        ];
    }

    protected function buildEmailSidebar(): array
    {
        return [
            'title' => 'Email Management',
            'icon' => 'envelope',
            'sections' => [
                [
                    'type' => 'primary',
                    'items' => [
                        [
                            'name' => 'Inbox',
                            'route' => 'email.inbox.index',
                            'icon' => 'inbox',
                            'key' => 'inbox',
                        ],
                        [
                            'name' => 'Compose',
                            'route' => 'email.compose.index',
                            'icon' => 'pencil',
                            'key' => 'compose',
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function buildSettingsSidebar(): array
    {
        return [
            'title' => 'Settings',
            'icon' => 'cog-6-tooth',
            'sections' => [
                [
                    'type' => 'primary',
                    'items' => [
                        [
                            'name' => 'Settings Overview',
                            'route' => 'settings.index',
                            'icon' => 'home',
                            'key' => 'overview',
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function buildGenericSidebar(): array
    {
        return [
            'title' => ucfirst($this->context),
            'icon' => 'home',
            'sections' => [],
        ];
    }

    protected function buildPrimarySection(string $domain, string $section): array
    {
        $items = $this->getFilteredItems($domain, $section);

        return [
            'type' => 'primary',
            'items' => $items,
        ];
    }

    protected function buildRegistrySection(string $domain, string $section, string $title): array
    {
        $items = $this->getFilteredItems($domain, $section);

        return [
            'type' => 'section',
            'title' => $title,
            'expandable' => true,
            'default_expanded' => true,
            'items' => $items,
        ];
    }

    protected function getFilteredItems(string $domain, string $section): array
    {
        $items = NavigationRegistry::getBySection($domain, $section);
        $filtered = [];

        foreach ($items as $key => $item) {
            if ($this->canAccessItem($item)) {
                $filtered[] = [
                    'name' => $item['label'],
                    'route' => $item['route'],
                    'icon' => $item['icon'],
                    'key' => $key,
                    'params' => $item['params'] ?? [],
                ];
            }
        }

        usort($filtered, fn($a, $b) => ($items[array_search($a['key'], array_keys($items))]['order'] ?? 999) <=> 
                                         ($items[array_search($b['key'], array_keys($items))]['order'] ?? 999));

        return $filtered;
    }

    protected function canAccessItem(array $item): bool
    {
        if (!isset($item['permission'])) {
            return true;
        }

        if (!$this->user) {
            return false;
        }

        return $this->user->can($item['permission']);
    }

    protected function buildClientInfrastructureSection(): array
    {
        return [
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
                ],
                [
                    'name' => 'Documents',
                    'route' => 'clients.documents.index',
                    'icon' => 'folder-open',
                    'key' => 'documents',
                ],
                [
                    'name' => 'Domains',
                    'route' => 'clients.domains.index',
                    'icon' => 'globe-alt',
                    'key' => 'domains',
                ],
                [
                    'name' => 'Credentials',
                    'route' => 'clients.credentials.index',
                    'icon' => 'key',
                    'key' => 'credentials',
                ],
                [
                    'name' => 'Licenses',
                    'route' => 'clients.licenses.index',
                    'icon' => 'identification',
                    'key' => 'licenses',
                ],
            ],
        ];
    }

    protected function buildClientBillingSection(): array
    {
        return [
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
                ],
                [
                    'name' => 'Quotes',
                    'route' => 'financial.quotes.index',
                    'icon' => 'document-currency-dollar',
                    'key' => 'quotes',
                ],
                [
                    'name' => 'Invoices',
                    'route' => 'financial.invoices.index',
                    'icon' => 'document-text',
                    'key' => 'invoices',
                ],
            ],
        ];
    }

    protected function calculateTicketBadges(): array
    {
        if (!$this->user || !$this->user->company_id) {
            return [];
        }

        try {
            $activeTimers = \App\Domains\Ticket\Models\TicketTimeEntry::runningTimers()
                ->where('company_id', $this->user->company_id);
            
            if (!$this->user->hasRole('admin')) {
                $activeTimers->where('user_id', $this->user->id);
            }

            $slaViolations = \App\Domains\Ticket\Models\Ticket::where('company_id', $this->user->company_id)
                ->whereHas('priorityQueue', function ($q) {
                    $q->where('sla_deadline', '<', now());
                })
                ->whereNotIn('status', ['closed', 'resolved']);

            $unassigned = \App\Domains\Ticket\Models\Ticket::where('company_id', $this->user->company_id)
                ->whereNull('assigned_to')
                ->whereNotIn('status', ['closed', 'resolved']);

            return [
                'active_timers' => $activeTimers->count() ?: null,
                'sla_violations' => $slaViolations->count() ?: null,
                'unassigned' => $unassigned->count() ?: null,
            ];
        } catch (\Exception $e) {
            return [];
        }
    }
}
