<?php

namespace App\Livewire;

use App\Domains\Core\Services\Navigation\NavigationContext;
use App\Domains\Core\Services\Navigation\SidebarBuilder;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class Sidebar extends Component
{
    public ?string $context = null;

    public ?string $activeSection = null;

    public bool $mobile = false;

    public array $expandedSections = [];

    public string $currentRoute = '';

    public array $currentParams = [];

    protected bool $initialized = false;

    public function mount(?string $context = null, ?string $activeSection = null, bool $mobile = false)
    {
        $this->context = $context ?? NavigationContext::getCurrentDomain();
        $this->activeSection = $activeSection;
        $this->mobile = $mobile;
        $this->currentRoute = Route::currentRouteName() ?? '';
        // Get route parameters (URL segments) not query parameters
        $this->currentParams = Route::current()->parameters() ?? [];

        $this->initializeExpandedSections();
        $this->initialized = true;
    }

    protected function initializeExpandedSections()
    {
        $sidebarBuilder = new SidebarBuilder($this->context);
        $sidebarConfig = $sidebarBuilder->build();

        foreach ($sidebarConfig['sections'] ?? [] as $sectionIndex => $section) {
            if ($section['type'] === 'section') {
                $sectionId = 'section_'.$sectionIndex;
                // Only initialize if not already set (preserves user toggles)
                if (! array_key_exists($sectionId, $this->expandedSections)) {
                    // Check if any item in this section is active
                    $hasActiveItem = false;
                    if (isset($section['items']) && is_array($section['items'])) {
                        foreach ($section['items'] as $item) {
                            if ($this->isItemActive($item)) {
                                $hasActiveItem = true;
                                break;
                            }
                        }
                    }

                    // Expand if it has an active item, otherwise use default_expanded setting
                    $this->expandedSections[$sectionId] = $hasActiveItem || ($section['default_expanded'] ?? false);
                }
            }
        }
    }

    public function toggleSection(string $sectionId)
    {
        if (! isset($this->expandedSections[$sectionId])) {
            $this->expandedSections[$sectionId] = false;
        }

        $this->expandedSections[$sectionId] = ! $this->expandedSections[$sectionId];
    }

    public function expandAll()
    {
        foreach (array_keys($this->expandedSections) as $sectionId) {
            $this->expandedSections[$sectionId] = true;
        }
    }

    public function collapseAll()
    {
        foreach (array_keys($this->expandedSections) as $sectionId) {
            $this->expandedSections[$sectionId] = false;
        }
    }

    public function render()
    {
        $sidebarBuilder = new SidebarBuilder($this->context);
        $sidebarConfig = $sidebarBuilder->build();
        $selectedClient = NavigationContext::getSelectedClient();

        return view('livewire.sidebar', [
            'sidebarConfig' => $sidebarConfig,
            'selectedClient' => $selectedClient,
            'sidebarContext' => $this->context,
        ]);
    }

    public function resolveContextualParams(array $params, $selectedClient): array
    {
        if (! $selectedClient) {
            return $params;
        }

        $resolved = [];
        foreach ($params as $key => $value) {
            if ($value === '{client_id}') {
                $resolved[$key] = $selectedClient->id;
            } else {
                $resolved[$key] = $value;
            }
        }

        return $resolved;
    }

    public function calculateBadgeData(array $item, $selectedClient): array
    {
        if (! isset($item['badge'])) {
            return ['count' => 0];
        }

        $badge = $item['badge'];

        if (is_numeric($badge)) {
            return ['count' => $badge, 'variant' => 'default'];
        }

        if (is_array($badge)) {
            return [
                'count' => $badge['count'] ?? 0,
                'variant' => $badge['variant'] ?? 'default',
            ];
        }

        return ['count' => 0];
    }

    public function shouldDisplayItem(array $item, $selectedClient): bool
    {
        if (isset($item['requires_client']) && $item['requires_client'] && ! $selectedClient) {
            return false;
        }

        if (isset($item['permission']) && ! auth()->user()->can($item['permission'])) {
            return false;
        }

        return true;
    }

    public function isItemActive(array $item): bool
    {
        // Check if route matches
        if (! isset($item['route'])) {
            return false;
        }

        if ($this->currentRoute !== $item['route'] && ! str_starts_with($this->currentRoute, $item['route'] . '.')) {
            return false;
        }

        // If there are required params, check if they match the current route parameters
        if (isset($item['params']) && is_array($item['params']) && ! empty($item['params'])) {
            foreach ($item['params'] as $key => $value) {
                // Skip special tokens like {client_id}
                if ($value === '{client_id}') {
                    continue;
                }

                // Check if current route parameter matches the item parameter
                $currentValue = $this->currentParams[$key] ?? null;
                if ($currentValue !== $value) {
                    return false;
                }
            }
        }

        return true;
    }
}
