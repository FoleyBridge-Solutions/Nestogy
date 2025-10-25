<?php

namespace App\Domains\Core\Services;

use App\Domains\Core\Services\Navigation\NavigationContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class NavigationService
{
    protected static array $domainMappings = [
        'financial' => ['financial.*', 'billing.*', 'products.*', 'services.*', 'bundles.*'],
        'clients' => ['clients.*'],
        'tickets' => ['tickets.*'],
        'assets' => ['assets.*'],
        'projects' => ['projects.*'],
        'reports' => ['reports.*'],
        'settings' => ['settings.*', 'users.*', 'admin.*'],
        'email' => ['email.*'],
        'physical-mail' => ['mail.*', 'physical-mail.*'],
        'manager' => ['manager.*'],
        'marketing' => ['marketing.*', 'leads.*'],
        'hr' => ['hr.*', 'time-clock.*'],
    ];

    public static function getCurrentDomain(): ?string
    {
        return NavigationContext::getCurrentDomain();
    }

    public static function getActiveDomain(): ?string
    {
        $routeName = Route::currentRouteName();

        if (!$routeName) {
            return null;
        }

        foreach (static::$domainMappings as $domain => $patterns) {
            foreach ($patterns as $pattern) {
                if (Str::is($pattern, $routeName)) {
                    return $domain;
                }
            }
        }

        return null;
    }

    public static function getSidebarContext(): ?string
    {
        $routeName = Route::currentRouteName();
        
        if ($routeName === 'clients.index' && !static::hasSelectedClient()) {
            return null;
        }
        
        if ($routeName === 'clients.create') {
            return null;
        }
        
        return static::getActiveDomain() ?? NavigationContext::getCurrentDomain();
    }

    public static function getSelectedClientId(): ?int
    {
        return NavigationContext::getSelectedClientId();
    }

    public static function getSelectedClient()
    {
        return NavigationContext::getSelectedClient();
    }

    public static function setSelectedClient(?int $clientId): void
    {
        NavigationContext::setSelectedClient($clientId);
    }

    public static function clearSelectedClient(): void
    {
        NavigationContext::clearSelectedClient();
    }

    public static function hasSelectedClient(): bool
    {
        return NavigationContext::hasSelectedClient();
    }

    public static function getBreadcrumbs(): array
    {
        $breadcrumbs = [];
        $routeName = Route::currentRouteName();

        if (!$routeName) {
            return [];
        }

        // If on clients.index without selection, return empty
        if ($routeName === 'clients.index' && !static::hasSelectedClient()) {
            return [];
        }

        $selectedClientId = static::getSelectedClientId();

        // Add client breadcrumb if client is selected
        if ($selectedClientId) {
            try {
                $client = \App\Domains\Client\Models\Client::find($selectedClientId);
                if ($client) {
                    $breadcrumbs[] = [
                        'name' => $client->name,
                        'url' => route('clients.show', $client),
                        'active' => $routeName === 'clients.index',
                    ];
                }
            } catch (\Exception $e) {
                // Client not found or other error
            }
        }

        // Add domain breadcrumb based on route (but not for clients.index)
        $domain = static::getActiveDomain();
        
        if ($domain && $routeName !== 'clients.index') {
            $breadcrumbs[] = [
                'name' => ucfirst($domain),
                'url' => '#',
                'active' => true,
            ];
        }

        return $breadcrumbs;
    }

    public static function getActiveNavigationItem(): ?string
    {
        $routeName = Route::currentRouteName();

        if (!$routeName || !str_contains($routeName, '.')) {
            return null;
        }

        // Check if the route belongs to a known domain
        if (!static::getActiveDomain()) {
            return null;
        }

        $parts = explode('.', $routeName);

        return isset($parts[1]) ? $parts[1] : null;
    }

    public static function isRouteActive(string $routeName, array $params = []): bool
    {
        if (Route::currentRouteName() !== $routeName) {
            return false;
        }

        if (empty($params)) {
            return true;
        }

        // Check if all required parameters match
        foreach ($params as $key => $value) {
            if (request()->query($key) !== $value) {
                return false;
            }
        }

        return true;
    }

    public static function getNavigationRegistry(string $scope = 'all', $user = null): array
    {
        return \App\Domains\Core\Services\Navigation\NavigationRegistry::all();
    }

    public static function setWorkflowContext(string $workflow): void
    {
        session()->put('current_workflow', $workflow);
    }

    public static function getWorkflowContext(): string
    {
        return session()->get('current_workflow', 'default');
    }

    public static function clearWorkflowContext(): void
    {
        session()->forget('current_workflow');
    }

    public static function isWorkflowActive(string $workflow): bool
    {
        return static::getWorkflowContext() === $workflow;
    }

    public static function addToRecentClients(int $clientId): void
    {
        $recent = session()->get('recent_client_ids', []);
        
        $recent = array_filter($recent, fn($id) => $id !== $clientId);
        
        array_unshift($recent, $clientId);
        
        $recent = array_slice($recent, 0, 10);
        
        session()->put('recent_client_ids', $recent);
    }

    public static function getRecentClientIds(): array
    {
        return session()->get('recent_client_ids', []);
    }

    public static function getWorkflowNavigationState(): array
    {
        $workflow = static::getWorkflowContext();
        $selectedClientId = static::getSelectedClientId();
        $client = null;
        
        if ($selectedClientId) {
            try {
                $client = \App\Domains\Client\Models\Client::find($selectedClientId);
            } catch (\Exception $e) {
                $client = null;
            }
        }
        
        return [
            'workflow' => $workflow,
            'client_id' => $selectedClientId,
            'client_name' => $client?->name,
            'recent_clients' => static::getRecentClientIds(),
            'active_domain' => static::getActiveDomain(),
        ];
    }

    public static function getWorkflowRouteParams(?string $workflow = null): array
    {
        $params = [];
        $workflow = $workflow ?? static::getWorkflowContext();
        
        if (static::hasSelectedClient()) {
            $params['client_id'] = static::getSelectedClientId();
        }
        
        if ($workflow === 'urgent') {
            $params['priority'] = 'Critical,High';
            $params['status'] = 'Open,In Progress';
        } elseif ($workflow === 'today') {
            $params['date'] = \Carbon\Carbon::now()->toDateString();
        } elseif ($workflow === 'scheduled') {
            $params['scheduled'] = '1';
            $params['date_from'] = \Carbon\Carbon::now()->toDateString();
            $params['date_to'] = \Carbon\Carbon::now()->addWeek()->toDateString();
        } elseif ($workflow === 'financial') {
            $params['status'] = 'Draft,Sent,Overdue';
        }
        
        return $params;
    }

    public static function canAccessDomain($user, string $domain): bool
    {
        if (!$user) {
            return false;
        }
        
        return true;
    }

    public static function canAccessNavigationItem($user, string $domain, string $item): bool
    {
        if (!$user) {
            return false;
        }
        
        if (!method_exists($user, 'can')) {
            return false;
        }
        
        return $user->can("$domain.view");
    }

    public static function getFilteredNavigationItems($user = null, string $scope = 'all'): array
    {
        if (!$user && !auth()->check()) {
            return [];
        }
        
        $user = $user ?? auth()->user();
        
        if (!$user) {
            return [];
        }
        
        $items = static::getNavigationRegistry($scope, $user);
        
        return array_filter($items, function ($item, $key) use ($user) {
            if (!isset($item['domain']) || !isset($item['name'])) {
                return true;
            }
            
            return static::canAccessNavigationItem($user, $item['domain'], $key);
        }, ARRAY_FILTER_USE_BOTH);
    }

    public static function getSidebarRegistration(): array
    {
        return [];
    }

    public static function registerSidebarSection(string $domain, string $section, array $config): void
    {
        // Store sidebar section configuration
    }

    public static function registerSidebarSections(string $domain, array $sections): void
    {
        // Register multiple sidebar sections
    }

    public static function getFavoriteClients($user = null, int $limit = 5): Collection
    {
        return collect();
    }

    public static function toggleClientFavorite(int $clientId, $user = null): bool
    {
        return false;
    }

    public static function isClientFavorite(int $clientId, $user = null): bool
    {
        return false;
    }

    public static function getTodaysWork($user = null): array
    {
        if (!$user && !auth()->check()) {
            return [];
        }

        return [
            'total' => 0,
            'upcoming' => [],
            'client' => null,
            'scheduled' => [],
        ];
    }

    public static function getUrgentItems($user = null): array
    {
        if (!$user && !auth()->check()) {
            return [];
        }

        return [
            'total' => 0,
            'financial' => [],
            'notifications' => [],
            'client' => null,
            'items' => [],
        ];
    }

    public static function getDomainStats(string $domain, $user = null): array
    {
        return [];
    }

    public static function getClientNavigationItems($user = null): array
    {
        $items = [
            'index' => ['name' => 'All Clients', 'route' => 'clients.index'],
        ];
        
        if (!static::hasSelectedClient()) {
            return $items;
        }

        return array_merge($items, [
            'client-dashboard' => ['name' => 'Client Dashboard', 'route' => 'clients.dashboard'],
            'switch' => ['name' => 'Switch Client', 'route' => 'clients.switch'],
            'tickets' => ['name' => 'Tickets', 'route' => 'tickets.index'],
            'contracts' => ['name' => 'Contracts', 'route' => 'contracts.index'],
        ]);
    }

    public static function getClientWorkflowContext($client = null): ?array
    {
        if (!auth()->check()) {
            return null;
        }

        if (!$client && !static::hasSelectedClient()) {
            return null;
        }
        
        if (is_int($client)) {
            $client = \App\Domains\Client\Models\Client::find($client);
        }
        
        if (!$client) {
            $clientId = static::getSelectedClientId();
            $client = $clientId ? \App\Domains\Client\Models\Client::find($clientId) : null;
        }
        
        if (!$client) {
            return null;
        }
        
        return [
            'client_id' => $client->id,
            'client_name' => $client->name,
            'current_workflow' => static::getWorkflowContext(),
            'status' => $client->status ?? 'active',
        ];
    }

    public static function getClientSpecificBadgeCounts(int $companyId, ?int $clientId): array
    {
        $counts = [];

        if (!$clientId) {
            return $counts;
        }

        // Contacts
        $counts['contacts'] = \App\Domains\Client\Models\Contact::where('company_id', $companyId)
            ->where('client_id', $clientId)
            ->count();

        // Locations
        $counts['locations'] = \App\Domains\Client\Models\Location::where('company_id', $companyId)
            ->where('client_id', $clientId)
            ->count();

        // Recurring invoices
        $counts['recurring-invoices'] = \App\Domains\Financial\Models\Recurring::where('company_id', $companyId)
            ->where('client_id', $clientId)
            ->count();

        // Quotes
        $counts['quotes'] = \App\Domains\Financial\Models\Quote::where('company_id', $companyId)
            ->where('client_id', $clientId)
            ->count();

        // Contracts
        $counts['contracts'] = \App\Domains\Contract\Models\Contract::where('company_id', $companyId)
            ->where('client_id', $clientId)
            ->count();

        return $counts;
    }

    public static function getBadgeCounts($user = null): array
    {
        if (!$user && !auth()->check()) {
            return [];
        }
        
        $user = $user ?? auth()->user();
        
        if (!$user || !method_exists($user, 'can')) {
            return [];
        }
        
        return [
            'unread_notifications' => 0,
            'pending_tasks' => 0,
            'unassigned_tickets' => 0,
        ];
    }

    public static function getSmartClientSuggestions($user = null, int $limit = 5): array
    {
        return [
            'favorites' => [],
            'recent' => [],
            'total' => 0,
        ];
    }

    public static function getWorkflowNavigationHighlights($workflow = null): array
    {
        if (!auth()->check()) {
            return [
                'urgent_count' => 0,
                'today_count' => 0,
                'scheduled_count' => 0,
                'financial_count' => 0,
                'alerts' => [],
                'badges' => [],
            ];
        }

        return [
            'urgent_count' => 0,
            'today_count' => 0,
            'scheduled_count' => 0,
            'financial_count' => 0,
            'alerts' => [],
            'badges' => [],
        ];
    }

    public static function getWorkflowQuickActions($workflow = null, $role = null): array
    {
        return [];
    }

    public static function getWorkflowBreadcrumbs(): array
    {
        $breadcrumbs = [];
        $workflow = static::getWorkflowContext();
        
        if ($workflow) {
            $breadcrumbs[] = [
                'name' => ucfirst($workflow) . ' Items',
                'url' => '#',
                'active' => true,
            ];
        }
        
        return $breadcrumbs;
    }

    public static function getRecentClients(): Collection
    {
        return collect(static::getRecentClientIds());
    }
}
