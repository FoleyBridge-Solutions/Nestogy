<?php

namespace App\Domains\Core\Services;

use App\Models\CustomQuickAction;
use App\Models\QuickActionFavorite;
use App\Models\User;
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
        // Determine if this is a custom action (numeric ID) or system action
        if (is_numeric($actionIdentifier)) {
            // Custom action
            $favorite = QuickActionFavorite::where('user_id', $user->id)
                ->where('custom_quick_action_id', $actionIdentifier)
                ->first();

            if ($favorite) {
                $favorite->delete();

                return false; // Removed from favorites
            } else {
                QuickActionFavorite::create([
                    'user_id' => $user->id,
                    'custom_quick_action_id' => $actionIdentifier,
                    'position' => QuickActionFavorite::where('user_id', $user->id)->count(),
                ]);

                return true; // Added to favorites
            }
        } else {
            // System action or action with string ID
            $systemAction = null;

            // Try to find the action to get its identifier
            $action = static::getActionsForUser($user)
                ->first(function ($a) use ($actionIdentifier) {
                    if (isset($a['id']) && $a['id'] === $actionIdentifier) {
                        return true;
                    }
                    if (isset($a['route']) && $a['route'] === $actionIdentifier) {
                        return true;
                    }
                    if (isset($a['action']) && $a['action'] === $actionIdentifier) {
                        return true;
                    }

                    return false;
                });

            if ($action) {
                // Use route or action as the system identifier
                $systemAction = $action['route'] ?? $action['action'] ?? $actionIdentifier;
            } else {
                $systemAction = $actionIdentifier;
            }

            $favorite = QuickActionFavorite::where('user_id', $user->id)
                ->where('system_action', $systemAction)
                ->first();

            if ($favorite) {
                $favorite->delete();

                return false;
            } else {
                QuickActionFavorite::create([
                    'user_id' => $user->id,
                    'system_action' => $systemAction,
                    'position' => QuickActionFavorite::where('user_id', $user->id)->count(),
                ]);

                return true;
            }
        }
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
        if (str_starts_with($actionIdentifier, 'custom_')) {
            $customId = str_replace('custom_', '', $actionIdentifier);

            return in_array('custom_'.$customId, $favorites);
        }

        // For system actions, check by route or action key
        $action = static::findActionByIdentifier($actionIdentifier, $user);

        return $action ? static::isActionInFavorites($action, $favorites) : false;
    }

    /**
     * Find an action by its identifier
     */
    protected static function findActionByIdentifier($actionIdentifier, User $user): ?array
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
     * Check if an action's identifiers match any favorites
     */
    protected static function isActionInFavorites(array $action, array $favorites): bool
    {
        $checkKeys = [
            $action['id'] ?? null,
            $action['route'] ?? null,
            $action['action'] ?? null,
            isset($action['custom_id']) ? 'custom_'.$action['custom_id'] : null,
        ];

        foreach ($checkKeys as $key) {
            if ($key && in_array($key, $favorites)) {
                return true;
            }
        }

        return false;
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
            // Update existing
            $action = CustomQuickAction::find($data['id']);
            if ($action) {
                // Check permissions
                $canEdit = false;

                // User can edit their own actions
                if ($action->user_id === $user->id) {
                    $canEdit = true;
                }
                // Super-admin can edit company actions
                elseif ($action->visibility === 'company' && Bouncer::is($user)->an('super-admin')) {
                    $canEdit = true;
                }
                // Admin can edit company actions if they have manage-quick-actions permission
                elseif ($action->visibility === 'company' && $user->can('manage-quick-actions')) {
                    $canEdit = true;
                }
                // Company admin can edit company actions
                elseif ($action->visibility === 'company' &&
                        $action->company_id === $user->company_id &&
                        (Bouncer::is($user)->an('admin') || Bouncer::is($user)->an('company-admin'))) {
                    $canEdit = true;
                }

                if (! $canEdit) {
                    throw new \Exception('You do not have permission to edit this action');
                }

                $action->update($actionData);

                return $action;
            }
        } else {
            // Create new
            return CustomQuickAction::create($actionData);
        }
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

        foreach ($favorites as $favorite) {
            if ($favorite->custom_quick_action_id) {
                $action = $allActions->first(function ($a) use ($favorite) {
                    return isset($a['custom_id']) && $a['custom_id'] == $favorite->custom_quick_action_id;
                });
                if ($action) {
                    $action['is_favorite'] = true;
                    $actions->push($action);
                }
            } elseif ($favorite->system_action) {
                $action = $allActions->first(function ($a) use ($favorite) {
                    return (isset($a['route']) && $a['route'] === $favorite->system_action) ||
                           (isset($a['action']) && $a['action'] === $favorite->system_action);
                });
                if ($action) {
                    $action['is_favorite'] = true;
                    $actions->push($action);
                }
            }
        }

        // Add some common actions if we have room
        if ($actions->count() < 5) {
            // Add create ticket if user has permission
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

            // Add create invoice if user has permission
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
        }

        return $actions->take(5);
    }
}
