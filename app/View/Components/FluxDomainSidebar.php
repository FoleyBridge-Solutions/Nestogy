<?php

namespace App\View\Components;

use Illuminate\View\Component;
use App\Services\NavigationService;

class FluxDomainSidebar extends Component
{
    public $activeDomain;
    public $activeItem;
    public $mobile;
    public $selectedClient;

    public function __construct($activeDomain = null, $activeItem = null, $mobile = false)
    {
        $this->activeDomain = $activeDomain;
        $this->activeItem = $activeItem;
        $this->mobile = $mobile ?? false;
        $this->selectedClient = NavigationService::getSelectedClient();
    }

    public function render()
    {
        return view('components.flux-domain-sidebar');
    }

    /**
     * Helper method to resolve contextual parameters
     */
    public function resolveContextualParams($params, $selectedClient)
    {
        $resolvedParams = [];
        
        foreach ($params as $key => $value) {
            if ($value === 'current' && $selectedClient) {
                $resolvedParams[$key] = $selectedClient->id;
            } else {
                $resolvedParams[$key] = $value;
            }
        }
        
        return $resolvedParams;
    }

    /**
     * Helper method to calculate badge data
     */
    public function calculateBadgeData($item, $selectedClient)
    {
        $count = 0;
        $variant = 'zinc';
        
        if (!$selectedClient) {
            return ['count' => $count, 'variant' => $variant];
        }
        
        $badgeType = $item['badge_type'] ?? 'info';
        
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
        }
        
        // Apply badge type styling
        $variant = match($badgeType) {
            'urgent' => 'red',
            'warning' => 'amber',
            'success' => 'green',
            'info' => 'blue',
            default => 'zinc'
        };
        
        return ['count' => $count, 'variant' => $variant];
    }

    /**
     * Helper method to determine if item should be displayed
     */
    public function shouldDisplayItem($item, $selectedClient)
    {
        if (!isset($item['show_if'])) {
            return true;
        }
        
        if (!$selectedClient) {
            return false;
        }
        
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
}