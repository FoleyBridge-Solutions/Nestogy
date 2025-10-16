<?php

namespace App\Domains\Core\Services;

use App\Domains\Core\Models\CustomQuickAction;
use App\Domains\Core\Models\QuickActionFavorite;
use App\Domains\Core\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Silber\Bouncer\BouncerFacade as Bouncer;

class QuickActionService
{
    /**
     * Get all available quick actions for a user with a specific view context
     */
    public static function getActionsForUser(?User $user = null, string $view = 'executive'): Collection
    {
        $user = $user ?? Auth::user();
        if (! $user) {
            return collect();
        }

        // Get system actions for the view
        $systemActions = static::getSystemActions($user, $view);

        // Get custom actions
        $customActions = static::getCustomActions($user);

        // Merge all actions
        $allActions = $systemActions->merge($customActions);

        // Sort by favorites and return
        return static::sortByFavorites($allActions, $user);
    }

    /**
     * Get system-defined quick actions based on user permissions and view
     */
    public static function getSystemActions(User $user, string $view = 'executive'): Collection
    {
        $actionSets = [
            'executive' => [
                [
                    'id' => 'sys_email_inbox',
                    'type' => 'system',
                    'title' => 'Email Inbox',
                    'description' => 'Check your email messages',
                    'icon' => 'envelope',
                    'color' => 'blue',
                    'route' => 'email.inbox.index',
                    'permission' => 'view-email',
                ],
            ],
            'operations' => [
                [
                    'id' => 'sys_remote_access',
                    'type' => 'system',
                    'title' => 'Remote Access',
                    'description' => 'Connect to client systems',
                    'icon' => 'globe-alt',
                    'color' => 'red',
                    'action' => 'remoteAccess',
                    'permission' => 'remote-access',
                ],
            ],
            'financial' => [],
            'support' => [
                [
                    'id' => 'sys_client_portal',
                    'type' => 'system',
                    'title' => 'Client Portal',
                    'description' => 'Access client portal',
                    'icon' => 'building-office',
                    'color' => 'purple',
                    'action' => 'clientPortal',
                    'permission' => 'access-client-portal',
                ],
            ],
        ];

        $actions = $actionSets[$view] ?? $actionSets['executive'];

        // Filter actions based on permissions and route availability
        return collect($actions)->filter(function ($action) use ($user) {
            // Check permissions
            if (isset($action['permission']) && ! $user->can($action['permission'])) {
                return false;
            }

            // Check if route exists
            if (isset($action['route'])) {
                try {
                    route($action['route']);
                } catch (\Exception $e) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    /**
     * Get custom quick actions from database
     */
    public static function getCustomActions(User $user): Collection
    {
        $customActions = CustomQuickAction::active()
            ->visibleTo($user)
            ->orderBy('position')
            ->get();

        return $customActions->filter(function ($action) use ($user) {
            // Check if user has permission to execute this action
            if ($action->permission && ! $user->can($action->permission)) {
                return false;
            }

            // Check if route exists for route-based actions
            if ($action->type === 'route') {
                try {
                    route($action->target);
                } catch (\Exception $e) {
                    return false;
                }
            }

            return true;
        })->map(function ($action) {
            $config = $action->getActionConfig();
            // Standardize route field for consistency
            if ($action->type === 'route' && isset($config['target'])) {
                $config['route'] = $config['target'];
            }

            // Preserve the original type (url, route) in the config
            return array_merge($config, [
                'id' => 'custom_'.$action->id,
                'source' => 'custom', // Mark source as custom but preserve type
            ]);
        });
    }

    /**
     * Search quick actions by query
     */
    public static function searchActions(string $query, ?User $user = null, string $view = 'executive'): Collection
    {
        $user = $user ?? Auth::user();
        if (! $user) {
            return collect();
        }

        $allActions = static::getActionsForUser($user, $view);
        $queryLower = strtolower($query);

        return $allActions->filter(function ($action) use ($queryLower) {
            $titleMatch = str_contains(strtolower($action['title'] ?? ''), $queryLower);
            $descMatch = str_contains(strtolower($action['description'] ?? ''), $queryLower);

            return $titleMatch || $descMatch;
        })->values();
    }

    /**
     * Toggle favorite status for an action
     */
    public static function toggleFavorite($actionIdentifier, User $user): bool
    {
        if (is_numeric($actionIdentifier)) {
            return static::toggleCustomActionFavorite($actionIdentifier, $user);
        }

        return static::toggleSystemActionFavorite($actionIdentifier, $user);
    }

    /**
     * Toggle favorite for a custom action
     */
    protected static function toggleCustomActionFavorite($actionId, User $user): bool
    {
        $favorite = QuickActionFavorite::where('user_id', $user->id)
            ->where('custom_quick_action_id', $actionId)
            ->first();

        if ($favorite) {
            $favorite->delete();
            return false;
        }

        QuickActionFavorite::create([
            'user_id' => $user->id,
            'custom_quick_action_id' => $actionId,
            'position' => QuickActionFavorite::where('user_id', $user->id)->count(),
        ]);

        return true;
    }

    /**
     * Toggle favorite for a system action
     */
    protected static function toggleSystemActionFavorite($actionIdentifier, User $user): bool
    {
        $systemAction = static::resolveSystemActionIdentifier($actionIdentifier, $user);

        $favorite = QuickActionFavorite::where('user_id', $user->id)
            ->where('system_action', $systemAction)
            ->first();

        if ($favorite) {
            $favorite->delete();
            return false;
        }

        QuickActionFavorite::create([
            'user_id' => $user->id,
            'system_action' => $systemAction,
            'position' => QuickActionFavorite::where('user_id', $user->id)->count(),
        ]);

        return true;
    }

    /**
     * Resolve the system action identifier from action data
     */
    protected static function resolveSystemActionIdentifier($actionIdentifier, User $user): string
    {
        $action = static::findActionByIdentifier($actionIdentifier, $user);

        if ($action) {
            return $action['route'] ?? $action['action'] ?? $actionIdentifier;
        }

        return $actionIdentifier;
    }



    /**
     * Get favorite actions for a user
     */
    public static function getFavorites(User $user): Collection
    {
        return QuickActionFavorite::where('user_id', $user->id)
            ->orderBy('position')
            ->get();
    }

    /**
     * Get favorite action identifiers
     */
    public static function getFavoriteIdentifiers(User $user): array
    {
        $favorites = static::getFavorites($user);

        $identifiers = [];
        foreach ($favorites as $fav) {
            if ($fav->custom_quick_action_id) {
                $identifiers[] = 'custom_'.$fav->custom_quick_action_id;
            }
            if ($fav->system_action) {
                $identifiers[] = $fav->system_action;
            }
        }

        return $identifiers;
    }

    /**
     * Check if an action is favorited
     */
    public static function isFavorite($actionIdentifier, User $user): bool
    {
        $favorites = static::getFavoriteIdentifiers($user);

        // Check direct match
        if (in_array($actionIdentifier, $favorites)) {
            return true;
        }

        // For custom actions with custom_ prefix
        if (static::isCustomActionFavorited($actionIdentifier, $favorites)) {
            return true;
        }

        // For system actions, check by route or action key
        return static::isSystemActionFavorited($actionIdentifier, $user, $favorites);
    }

    /**
     * Check if a custom action is favorited
     */
    protected static function isCustomActionFavorited(string $actionIdentifier, array $favorites): bool
    {
        if (str_starts_with($actionIdentifier, 'custom_')) {
            $customId = str_replace('custom_', '', $actionIdentifier);

            return in_array('custom_'.$customId, $favorites);
        }

        return false;
    }

    /**
     * Check if a system action is favorited
     */
    protected static function isSystemActionFavorited(string $actionIdentifier, User $user, array $favorites): bool
    {
        $action = static::findActionByIdentifier($actionIdentifier, $user);

        if (!$action) {
            return false;
        }

        $checkKeys = static::getActionCheckKeys($action);

        foreach ($checkKeys as $key) {
            if ($key && in_array($key, $favorites)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find an action by its identifier
     */
    protected static function findActionByIdentifier(string $actionIdentifier, User $user): ?array
    {
        return static::getActionsForUser($user)
            ->first(function ($a) use ($actionIdentifier) {
                return (isset($a['id']) && $a['id'] === $actionIdentifier) ||
                       (isset($a['route']) && $a['route'] === $actionIdentifier) ||
                       (isset($a['action']) && $a['action'] === $actionIdentifier) ||
                       (isset($a['custom_id']) && $a['custom_id'] == $actionIdentifier);
            });
    }

    /**
     * Get all possible check keys from an action
     */
    protected static function getActionCheckKeys(array $action): array
    {
        return [
            $action['id'] ?? null,
            $action['route'] ?? null,
            $action['action'] ?? null,
            isset($action['custom_id']) ? 'custom_'.$action['custom_id'] : null,
        ];
    }

    /**
     * Sort actions with favorites first
     */
    protected static function sortByFavorites(Collection $actions, User $user): Collection
    {
        $favorites = static::getFavoriteIdentifiers($user);

        $favorited = [];
        $regular = [];

        foreach ($actions as $action) {
            $isFavorite = false;

            // Check various identifiers
            $identifiers = [
                $action['id'] ?? null,
                $action['route'] ?? null,
                $action['action'] ?? null,
                isset($action['custom_id']) ? 'custom_'.$action['custom_id'] : null,
            ];

            foreach ($identifiers as $id) {
                if ($id && in_array($id, $favorites)) {
                    $isFavorite = true;
                    break;
                }
            }

            $action['is_favorite'] = $isFavorite;

            if ($isFavorite) {
                $favorited[] = $action;
            } else {
                $regular[] = $action;
            }
        }

        return collect(array_merge($favorited, $regular));
    }

    /**
     * Execute a quick action and return the action data
     */
    public static function executeAction($actionIdentifier, User $user)
    {
        // Find the action
        $action = static::getActionsForUser($user)
            ->first(function ($a) use ($actionIdentifier) {
                return (isset($a['id']) && $a['id'] === $actionIdentifier) ||
                       (isset($a['custom_id']) && $a['custom_id'] == $actionIdentifier) ||
                       (isset($a['action']) && $a['action'] === $actionIdentifier);
            });

        if (! $action) {
            throw new \Exception('Action not found');
        }

        // Record usage for custom actions
        if (isset($action['custom_id'])) {
            $customAction = CustomQuickAction::find($action['custom_id']);
            if ($customAction && $customAction->canBeExecutedBy($user)) {
                $customAction->recordUsage();
            }
        }

        return $action;
    }

    /**
     * Save a custom quick action
     */
    public static function saveCustomAction(array $data, User $user)
    {
        $actionData = [
            'company_id' => $user->company_id,
            'user_id' => $data['visibility'] === 'private' ? $user->id : null,
            'title' => $data['title'],
            'description' => $data['description'],
            'icon' => $data['icon'],
            'color' => $data['color'],
            'type' => $data['type'],
            'target' => $data['target'],
            'parameters' => $data['parameters'] ?? [],
            'open_in' => $data['open_in'],
            'visibility' => $data['visibility'],
        ];

        if (isset($data['id']) && $data['id']) {
            return static::updateExistingAction($data['id'], $actionData, $user);
        }

        return CustomQuickAction::create($actionData);
    }

    /**
     * Update an existing custom quick action
     */
    protected static function updateExistingAction($actionId, array $actionData, User $user)
    {
        $action = CustomQuickAction::find($actionId);
        
        if (!$action) {
            return null;
        }

        if (!static::canEditAction($action, $user)) {
            throw new \Exception('You do not have permission to edit this action');
        }

        $action->update($actionData);

        return $action;
    }

    /**
     * Check if a user can edit a custom quick action
     */
    protected static function canEditAction(CustomQuickAction $action, User $user): bool
    {
        if ($action->user_id === $user->id) {
            return true;
        }

        if ($action->visibility !== 'company') {
            return false;
        }

        if (Bouncer::is($user)->an('super-admin')) {
            return true;
        }

        if ($user->can('manage-quick-actions')) {
            return true;
        }

        if ($action->company_id === $user->company_id &&
            (Bouncer::is($user)->an('admin') || Bouncer::is($user)->an('company-admin'))) {
            return true;
        }

        return false;
    }

    /**
     * Delete a custom quick action
     */
    public static function deleteCustomAction($actionId, User $user)
    {
        $action = CustomQuickAction::find($actionId);

        if (! $action) {
            throw new \Exception('Action not found');
        }

        // Check permissions
        $canDelete = $action->user_id === $user->id ||
                    (Bouncer::is($user)->an('super-admin') && $action->visibility === 'company');

        if (! $canDelete) {
            throw new \Exception('You do not have permission to delete this action');
        }

        $action->delete();

        return true;
    }

    /**
     * Get popular quick actions to show when command palette opens
     */
    public static function getPopularActions(User $user): Collection
    {
        $actions = collect();

        // Add favorited actions first (limit to 3)
        $favorites = static::getFavorites($user)->take(3);
        $allActions = static::getActionsForUser($user);

        $actions = static::addFavoriteActions($favorites, $allActions, $actions);

        // Add some common actions if we have room
        $actions = static::addCommonActions($user, $actions);

        return $actions->take(5);
    }

    /**
     * Add favorite actions to the collection
     */
    protected static function addFavoriteActions($favorites, Collection $allActions, Collection $actions): Collection
    {
        foreach ($favorites as $favorite) {
            $action = static::findFavoriteAction($favorite, $allActions);
            
            if ($action) {
                $action['is_favorite'] = true;
                $actions->push($action);
            }
        }

        return $actions;
    }

    /**
     * Find a favorite action in the collection
     */
    protected static function findFavoriteAction($favorite, Collection $allActions)
    {
        if ($favorite->custom_quick_action_id) {
            return $allActions->first(function ($a) use ($favorite) {
                return isset($a['custom_id']) && $a['custom_id'] == $favorite->custom_quick_action_id;
            });
        }

        if ($favorite->system_action) {
            return $allActions->first(function ($a) use ($favorite) {
                return (isset($a['route']) && $a['route'] === $favorite->system_action) ||
                       (isset($a['action']) && $a['action'] === $favorite->system_action);
            });
        }

        return null;
    }

    /**
     * Add common actions if there's room
     */
    protected static function addCommonActions(User $user, Collection $actions): Collection
    {
        if ($actions->count() >= 5) {
            return $actions;
        }

        if ($user->can('create-tickets')) {
            $actions->push([
                'id' => 'sys_create_ticket',
                'type' => 'system',
                'title' => 'Create New Ticket',
                'description' => 'Create a support ticket',
                'icon' => 'plus-circle',
                'color' => 'blue',
                'route' => 'tickets.create',
            ]);
        }

        if ($user->can('create-invoices')) {
            $actions->push([
                'id' => 'sys_create_invoice',
                'type' => 'system',
                'title' => 'Create Invoice',
                'description' => 'Create a new invoice',
                'icon' => 'plus-circle',
                'color' => 'green',
                'route' => 'financial.invoices.create',
            ]);
        }

        return $actions;
    }
}
