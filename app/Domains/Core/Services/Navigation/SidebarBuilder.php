<?php

namespace App\Domains\Core\Services\Navigation;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class SidebarBuilder
{
    protected string $context;
    protected $user;
    protected $selectedClient;
    protected string $currentRoute;

    public function __construct(string $context)
    {
        $this->context = $context;
        $this->user = Auth::user();
        $this->selectedClient = NavigationContext::getSelectedClient();
        $this->currentRoute = Route::currentRouteName() ?? '';
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
                $this->buildRegistrySection('tickets', 'primary', null),
                $this->buildTicketsSectionWithBadges('my-work', 'MY WORK', $badges),
                $this->buildTicketsSectionWithBadges('critical', 'CRITICAL ITEMS', $badges),
            ],
        ];
    }

    protected function buildTicketsSectionWithBadges(string $section, string $title, array $badges): array
    {
        $items = $this->getFilteredItems('tickets', $section);
        
        foreach ($items as &$item) {
            if ($item['key'] === 'active-timers' && isset($badges['active_timers'])) {
                $item['badge'] = $badges['active_timers'];
                $item['badge_type'] = 'success';
            } elseif ($item['key'] === 'sla-violations' && isset($badges['sla_violations'])) {
                $item['badge'] = $badges['sla_violations'];
                $item['badge_type'] = 'danger';
            } elseif ($item['key'] === 'unassigned' && isset($badges['unassigned'])) {
                $item['badge'] = $badges['unassigned'];
                $item['badge_type'] = 'warning';
            }
        }

        $isActive = $this->isSectionActive($items);

        return [
            'type' => 'section',
            'title' => $title,
            'expandable' => false,
            'default_expanded' => $isActive,
            'items' => $items,
        ];
    }

    protected function buildClientsSidebar(): array
    {
        if (!$this->selectedClient) {
            return [];
        }

        return [
            'title' => $this->selectedClient->name,
            'subtitle' => 'Client Workspace',
            'icon' => 'building-office',
            'sections' => [
                $this->buildRegistrySection('clients', 'client-info', 'CLIENT INFORMATION'),
                $this->buildRegistrySection('clients', 'tickets', 'SUPPORT TICKETS'),
                $this->buildRegistrySection('clients', 'assets', 'ASSETS'),
                $this->buildRegistrySection('clients', 'projects', 'PROJECTS'),
                $this->buildRegistrySection('clients', 'infrastructure', 'IT INFRASTRUCTURE'),
                $this->buildRegistrySection('clients', 'billing', 'BILLING & FINANCE'),
            ],
        ];
    }

    protected function buildAssetsSidebar(): array
    {
        return [
            'title' => 'Asset Management',
            'icon' => 'server',
            'sections' => [
                $this->buildRegistrySection('assets', 'primary', null),
            ],
        ];
    }

    protected function buildProjectsSidebar(): array
    {
        return [
            'title' => 'Project Management',
            'icon' => 'briefcase',
            'sections' => [
                $this->buildRegistrySection('projects', 'primary', null),
                $this->buildRegistrySection('projects', 'filters', 'FILTER BY STATUS'),
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

    protected function buildRegistrySection(string $domain, string $section, ?string $title): array
    {
        $items = $this->getFilteredItems($domain, $section);

        if ($title === null) {
            return [
                'type' => 'primary',
                'items' => $items,
            ];
        }

        $isActive = $this->isSectionActive($items);

        return [
            'type' => 'section',
            'title' => $title,
            'expandable' => true,
            'default_expanded' => $isActive,
            'items' => $items,
        ];
    }

    protected function isSectionActive(array $items): bool
    {
        foreach ($items as $item) {
            if ($item['route'] === $this->currentRoute) {
                return true;
            }
            
            if (str_starts_with($this->currentRoute, $item['route'] . '.')) {
                return true;
            }
        }
        
        return false;
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
