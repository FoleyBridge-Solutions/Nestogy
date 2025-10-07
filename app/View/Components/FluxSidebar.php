<?php

namespace App\View\Components;

use App\Domains\Core\Services\NavigationService;
use App\Domains\Core\Services\SidebarConfigProvider;
use Illuminate\View\Component;

class FluxSidebar extends Component
{
    public $sidebarContext;

    public $activeSection;

    public $mobile;

    public $selectedClient;

    public $sidebarConfig;

    public $customConfig;

    /**
     * Create a new component instance.
     *
     * @param  string|null  $sidebarContext  The context for the sidebar (e.g., 'clients', 'settings', 'admin')
     * @param  string|null  $activeSection  The active section/item in the sidebar
     * @param  bool  $mobile  Whether this is a mobile sidebar
     * @param  array|null  $customConfig  Custom configuration to override defaults
     */
    public function __construct($sidebarContext = null, $activeSection = null, $mobile = false, $customConfig = null)
    {
        $this->sidebarContext = $sidebarContext;
        $this->activeSection = $activeSection;
        $this->mobile = $mobile ?? false;
        $this->customConfig = $customConfig;
        $this->selectedClient = NavigationService::getSelectedClient();

        // Load sidebar configuration
        $configProvider = app(SidebarConfigProvider::class);
        $this->sidebarConfig = $configProvider->getConfiguration($sidebarContext, $customConfig);
    }

    public function render()
    {
        return view('components.flux-sidebar');
    }

    /**
     * Helper method to resolve contextual parameters
     */
    public function resolveContextualParams($params, $selectedClient = null)
    {
        $resolvedParams = [];

        foreach ($params as $key => $value) {
            // Skip client-related parameters - we use session-based client selection
            // as per the architectural decision documented in docs/CLAUDE.md
            if (in_array($key, ['client', 'client_id'])) {
                // For routes that require a 'client' parameter (like clients.show),
                // we still need to pass it, but not 'client_id' which is for filtering
                if ($key === 'client' && $value === 'current' && $selectedClient) {
                    $resolvedParams[$key] = $selectedClient->id;
                }

                // Skip 'client_id' parameters entirely - these are handled via session
                continue;
            }

            // Keep non-client parameters as-is
            $resolvedParams[$key] = $value;
        }

        return $resolvedParams;
    }

    /**
     * Helper method to calculate badge data
     */
    public function calculateBadgeData($item, $selectedClient = null)
    {
        $count = 0;
        $variant = 'zinc';

        // Handle client-specific badges
        if ($selectedClient && isset($item['badge_type'])) {
            switch ($item['key']) {
                case 'open-tickets':
                    $count = $selectedClient->tickets()->whereIn('status', ['open', 'in-progress'])->count();
                    $variant = 'red';
                    break;
                case 'pending':
                    $count = $selectedClient->invoices()->where('status', 'overdue')->count();
                    $variant = 'amber';
                    break;
                case 'contacts':
                    $count = $selectedClient->contacts()->count();
                    break;
                case 'locations':
                    $count = $selectedClient->locations()->count();
                    break;
                case 'tickets':
                    $count = $selectedClient->tickets()->whereIn('status', ['open', 'in-progress'])->count();
                    break;
                case 'assets':
                    $count = $selectedClient->assets()->count();
                    break;
                case 'invoices':
                    $count = $selectedClient->invoices()->whereIn('status', ['draft', 'sent'])->count();
                    break;
                case 'communications':
                    $count = $selectedClient->communicationLogs()->count();
                    break;
            }

            // Apply badge type styling
            $variant = match ($item['badge_type']) {
                'urgent' => 'red',
                'warning' => 'amber',
                'success' => 'green',
                'info' => 'blue',
                default => 'zinc'
            };
        }

        // Handle custom badge callbacks
        if (isset($item['badge_callback']) && is_callable($item['badge_callback'])) {
            $badgeData = call_user_func($item['badge_callback'], $item);
            if (is_array($badgeData)) {
                $count = $badgeData['count'] ?? 0;
                $variant = $badgeData['variant'] ?? 'zinc';
            } else {
                $count = $badgeData;
            }
        }

        return ['count' => $count, 'variant' => $variant];
    }

    /**
     * Helper method to determine if item should be displayed
     */
    public function shouldDisplayItem($item, $selectedClient = null)
    {
        // Check permission-based visibility
        if (isset($item['permission']) && ! auth()->user()->can($item['permission'])) {
            return false;
        }

        // Check role-based visibility
        if (isset($item['roles'])) {
            $userRoles = auth()->user()->roles->pluck('name')->toArray();
            if (! array_intersect($userRoles, $item['roles'])) {
                return false;
            }
        }

        // Check if item requires a selected client
        if (isset($item['params']) && isset($item['params']['client']) && $item['params']['client'] === 'current' && ! $selectedClient) {
            return false;
        }

        // Check conditional visibility
        if (! isset($item['show_if'])) {
            return true;
        }

        // Handle client-specific conditions
        if ($selectedClient) {
            $condition = $item['show_if'];

            switch ($condition) {
                case 'has_open_tickets':
                    return $selectedClient->tickets()->whereIn('status', ['open', 'in-progress'])->exists();
                case 'has_pending_items':
                    return $selectedClient->invoices()->where('status', 'overdue')->exists() ||
                           $selectedClient->invoices()->where('status', 'draft')->exists();
                case 'has_assets':
                    return $selectedClient->assets()->count() > 0;
                default:
                    return true;
            }
        }

        // Handle custom visibility callbacks
        if (isset($item['visible_callback']) && is_callable($item['visible_callback'])) {
            return call_user_func($item['visible_callback'], $item, $selectedClient);
        }

        return true;
    }

    /**
     * Check if sidebar should be shown for the current context
     */
    public function shouldShow()
    {
        return ! empty($this->sidebarConfig);
    }
}
