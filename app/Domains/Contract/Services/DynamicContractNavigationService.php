<?php

namespace App\Domains\Contract\Services;

use App\Domains\Contract\Models\ContractNavigationItem;
use App\Domains\Contract\Models\ContractMenuSection;
use App\Domains\Contract\Models\ContractTypeDefinition;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/**
 * DynamicContractNavigationService
 * 
 * Manages dynamic navigation generation for contract management.
 * Creates navigation menus, routes, and breadcrumbs based on company configuration.
 */
class DynamicContractNavigationService
{
    /**
     * Generate navigation menu for current company
     */
    public function generateNavigationMenu(): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        $navigationItems = ContractNavigationItem::where('company_id', $user->company_id)
            ->active()
            ->roots()
            ->with(['children' => function ($query) {
                $query->active()->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        return $this->buildMenuTree($navigationItems, $user);
    }

    /**
     * Build hierarchical menu tree
     */
    protected function buildMenuTree(Collection $items, $user): array
    {
        $menu = [];

        foreach ($items as $item) {
            if (!$item->hasPermission($user) || !$item->conditionsMet()) {
                continue;
            }

            $menuItem = [
                'slug' => $item->slug,
                'label' => $item->label,
                'icon' => $item->icon,
                'route' => $item->route,
                'url' => $item->route ? route($item->route) : null,
                'children' => [],
            ];

            if ($item->children->isNotEmpty()) {
                $menuItem['children'] = $this->buildMenuTree($item->children, $user);
            }

            $menu[] = $menuItem;
        }

        return $menu;
    }

    /**
     * Get contract type routes for current company
     */
    public function getContractTypeRoutes(): Collection
    {
        $user = Auth::user();
        if (!$user) {
            return collect();
        }

        return ContractTypeDefinition::where('company_id', $user->company_id)
            ->active()
            ->orderBy('sort_order')
            ->get()
            ->map(function ($type) {
                return [
                    'slug' => $type->slug,
                    'name' => $type->name,
                    'routes' => [
                        'index' => "contracts.{$type->slug}.index",
                        'create' => "contracts.{$type->slug}.create",
                        'show' => "contracts.{$type->slug}.show",
                        'edit' => "contracts.{$type->slug}.edit",
                    ],
                    'urls' => [
                        'index' => url("/contracts/{$type->slug}"),
                        'create' => url("/contracts/{$type->slug}/create"),
                    ],
                ];
            });
    }

    /**
     * Generate breadcrumbs for contract pages
     */
    public function getBreadcrumbs(string $contractType, string $action, $contract = null): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        $typeDefinition = ContractTypeDefinition::where('company_id', $user->company_id)
            ->where('slug', $contractType)
            ->first();

        if (!$typeDefinition) {
            return [];
        }

        $breadcrumbs = [
            [
                'label' => 'Contracts',
                'url' => route('contracts.dashboard'),
            ],
            [
                'label' => $typeDefinition->name,
                'url' => route("contracts.{$contractType}.index"),
            ],
        ];

        switch ($action) {
            case 'create':
                $breadcrumbs[] = [
                    'label' => 'Create',
                    'url' => null,
                ];
                break;

            case 'show':
                if ($contract) {
                    $breadcrumbs[] = [
                        'label' => $contract->title ?? $contract->contract_number,
                        'url' => null,
                    ];
                }
                break;

            case 'edit':
                if ($contract) {
                    $breadcrumbs[] = [
                        'label' => $contract->title ?? $contract->contract_number,
                        'url' => route("contracts.{$contractType}.show", $contract),
                    ];
                    $breadcrumbs[] = [
                        'label' => 'Edit',
                        'url' => null,
                    ];
                }
                break;
        }

        return $breadcrumbs;
    }

    /**
     * Get action buttons for contract
     */
    public function getActionButtons($contract): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        $typeDefinition = ContractTypeDefinition::where('company_id', $user->company_id)
            ->where('slug', $contract->contract_type)
            ->first();

        $actions = [];

        // Edit action
        if ($user->can('update', $contract)) {
            $actions[] = [
                'label' => 'Edit',
                'icon' => 'edit',
                'url' => route("contracts.{$contract->contract_type}.edit", $contract),
                'class' => 'btn-secondary',
            ];
        }

        // Delete action
        if ($user->can('delete', $contract)) {
            $actions[] = [
                'label' => 'Delete',
                'icon' => 'trash',
                'url' => route("contracts.{$contract->contract_type}.destroy", $contract),
                'class' => 'btn-danger',
                'confirm' => 'Are you sure you want to delete this contract?',
                'method' => 'DELETE',
            ];
        }

        // Generate PDF action
        $actions[] = [
            'label' => 'Download PDF',
            'icon' => 'download',
            'url' => route("contracts.{$contract->contract_type}.pdf", $contract),
            'class' => 'btn-outline',
        ];

        // Status transition actions
        $statusTransitions = $this->getAvailableStatusTransitions($contract);
        foreach ($statusTransitions as $transition) {
            $actions[] = [
                'label' => $transition['label'],
                'icon' => $transition['icon'] ?? 'arrow-right',
                'url' => route("contracts.{$contract->contract_type}.status", $contract),
                'class' => 'btn-primary',
                'data' => [
                    'status' => $transition['to_status'],
                ],
            ];
        }

        return $actions;
    }

    /**
     * Get available status transitions for contract
     */
    protected function getAvailableStatusTransitions($contract): array
    {
        // This would integrate with the status transition service
        // For now, return empty array
        return [];
    }

    /**
     * Register dynamic routes for all contract types
     */
    public function registerDynamicRoutes(): void
    {
        $contractTypes = $this->getContractTypeRoutes();

        foreach ($contractTypes as $typeInfo) {
            $slug = $typeInfo['slug'];
            
            Route::middleware(['web', 'auth', 'company'])->group(function () use ($slug) {
                Route::prefix("contracts/{$slug}")->name("contracts.{$slug}.")->group(function () use ($slug) {
                    Route::get('/', 'DynamicContractController@index')->name('index');
                    Route::get('/create', 'DynamicContractController@create')->name('create');
                    Route::post('/', 'DynamicContractController@store')->name('store');
                    Route::get('/{contract}', 'DynamicContractController@show')->name('show');
                    Route::get('/{contract}/edit', 'DynamicContractController@edit')->name('edit');
                    Route::put('/{contract}', 'DynamicContractController@update')->name('update');
                    Route::delete('/{contract}', 'DynamicContractController@destroy')->name('destroy');
                    Route::get('/{contract}/pdf', 'DynamicContractController@pdf')->name('pdf');
                    Route::post('/{contract}/status', 'DynamicContractController@updateStatus')->name('status');
                });
            });
        }
    }
}