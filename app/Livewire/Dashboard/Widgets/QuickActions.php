<?php

namespace App\Livewire\Dashboard\Widgets;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\CustomQuickAction;
use App\Models\QuickActionFavorite;
use Silber\Bouncer\BouncerFacade as Bouncer;

class QuickActions extends Component
{
    public string $view = 'executive';
    public array $actions = [];
    public array $customActions = [];
    public array $favoriteActions = [];
    public bool $showManageModal = false;
    public bool $showCreateModal = false;
    
    // Form properties for creating/editing custom actions
    public $actionForm = [
        'id' => null,
        'title' => '',
        'description' => '',
        'icon' => 'bolt',
        'color' => 'blue',
        'type' => 'route',
        'target' => '',
        'parameters' => [],
        'open_in' => 'same_tab',
        'visibility' => 'private',
    ];
    
    public function mount(string $view = 'executive')
    {
        $this->view = $view;
        $this->loadActions();
    }
    
    public function loadActions()
    {
        $user = Auth::user();
        
        // Load custom actions
        $this->loadCustomActions();
        
        // Load favorites
        $this->loadFavorites();
        
        $actionSets = [
            'executive' => [
                [
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
                    'title' => 'Client Portal',
                    'description' => 'Access client portal',
                    'icon' => 'building-office',
                    'color' => 'purple',
                    'action' => 'clientPortal',
                    'permission' => 'access-client-portal',
                ],
            ],
        ];
        
        $actions = $actionSets[$this->view] ?? $actionSets['executive'];
        
        // Filter actions based on permissions and route availability
        $this->actions = collect($actions)->filter(function ($action) use ($user) {
            // Check permissions
            if (isset($action['permission']) && !$user->can($action['permission'])) {
                return false;
            }
            
            // Check if route exists
            if (isset($action['route'])) {
                try {
                    route($action['route']);
                } catch (\Exception $e) {
                    // Route doesn't exist, skip this action
                    return false;
                }
            }
            
            return true;
        })->values()->toArray();
        
        // Merge with custom actions
        $this->actions = array_merge($this->actions, $this->customActions);
        
        // Sort by favorites first, then by position
        $this->sortActionsByFavorites();
    }
    
    protected function loadCustomActions()
    {
        $user = Auth::user();
        
        $customActions = CustomQuickAction::active()
            ->visibleTo($user)
            ->orderBy('position')
            ->get();
            
        $this->customActions = $customActions->filter(function ($action) use ($user) {
            // Check if user has permission to execute this action
            if ($action->permission && !$user->can($action->permission)) {
                return false;
            }
            
            // Check if route exists for route-based actions
            if ($action->type === 'route') {
                try {
                    route($action->target);
                } catch (\Exception $e) {
                    // Route doesn't exist, skip this action
                    return false;
                }
            }
            
            return true;
        })->map(function ($action) {
            return $action->getActionConfig();
        })->toArray();
    }
    
    protected function loadFavorites()
    {
        $user = Auth::user();
        
        $favorites = QuickActionFavorite::where('user_id', $user->id)
            ->orderBy('position')
            ->get();
            
        $this->favoriteActions = $favorites->pluck('system_action')
            ->merge($favorites->pluck('custom_quick_action_id'))
            ->filter()
            ->toArray();
    }
    
    protected function sortActionsByFavorites()
    {
        $favorited = [];
        $regular = [];
        
        foreach ($this->actions as $action) {
            $isFavorite = false;
            
            // Check if it's a favorited system action
            if (isset($action['route'])) {
                $isFavorite = in_array($action['route'], $this->favoriteActions);
            } elseif (isset($action['action'])) {
                $isFavorite = in_array($action['action'], $this->favoriteActions);
            } elseif (isset($action['custom_id'])) {
                $isFavorite = in_array($action['custom_id'], $this->favoriteActions);
            }
            
            if ($isFavorite) {
                $action['is_favorite'] = true;
                $favorited[] = $action;
            } else {
                $action['is_favorite'] = false;
                $regular[] = $action;
            }
        }
        
        $this->actions = array_merge($favorited, $regular);
    }
    
    public function toggleFavorite($actionIdentifier)
    {
        $user = Auth::user();
        
        // Determine if this is a custom action (numeric ID) or system action (string)
        if (is_numeric($actionIdentifier)) {
            // Custom action
            $favorite = QuickActionFavorite::where('user_id', $user->id)
                ->where('custom_quick_action_id', $actionIdentifier)
                ->first();
                
            if ($favorite) {
                $favorite->delete();
                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => 'Removed from favorites',
                ]);
            } else {
                QuickActionFavorite::create([
                    'user_id' => $user->id,
                    'custom_quick_action_id' => $actionIdentifier,
                    'position' => QuickActionFavorite::where('user_id', $user->id)->count(),
                ]);
                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => 'Added to favorites',
                ]);
            }
        } else {
            // System action
            $favorite = QuickActionFavorite::where('user_id', $user->id)
                ->where('system_action', $actionIdentifier)
                ->first();
                
            if ($favorite) {
                $favorite->delete();
                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => 'Removed from favorites',
                ]);
            } else {
                QuickActionFavorite::create([
                    'user_id' => $user->id,
                    'system_action' => $actionIdentifier,
                    'position' => QuickActionFavorite::where('user_id', $user->id)->count(),
                ]);
                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => 'Added to favorites',
                ]);
            }
        }
        
        $this->loadActions();
    }
    
    public function openCreateModal()
    {
        $this->resetActionForm();
        $this->showCreateModal = true;
    }
    
    public function openEditModal($actionId)
    {
        $action = CustomQuickAction::find($actionId);
        
        if (!$action || !$action->canBeExecutedBy(Auth::user())) {
            return;
        }
        
        $this->actionForm = [
            'id' => $action->id,
            'title' => $action->title,
            'description' => $action->description,
            'icon' => $action->icon,
            'color' => $action->color,
            'type' => $action->type,
            'target' => $action->target,
            'parameters' => $action->parameters ?? [],
            'open_in' => $action->open_in,
            'visibility' => $action->visibility,
        ];
        
        $this->showCreateModal = true;
    }
    
    public function saveCustomAction()
    {
        $user = Auth::user();
        
        $this->validate([
            'actionForm.title' => 'required|string|max:50',
            'actionForm.description' => 'required|string|max:255',
            'actionForm.icon' => 'required|string|max:50',
            'actionForm.color' => 'required|in:blue,green,purple,orange,red,yellow,gray',
            'actionForm.type' => 'required|in:route,url',
            'actionForm.target' => 'required|string|max:255',
            'actionForm.open_in' => 'required|in:same_tab,new_tab',
            'actionForm.visibility' => 'required|in:private,role,company',
        ]);
        
        // Validate route exists if type is route
        if ($this->actionForm['type'] === 'route') {
            try {
                route($this->actionForm['target']);
            } catch (\Exception $e) {
                $this->addError('actionForm.target', 'Route does not exist');
                return;
            }
        }
        
        // Validate URL if type is URL
        if ($this->actionForm['type'] === 'url') {
            if (!filter_var($this->actionForm['target'], FILTER_VALIDATE_URL)) {
                $this->addError('actionForm.target', 'Invalid URL format');
                return;
            }
        }
        
        $data = [
            'company_id' => $user->company_id,
            'user_id' => $this->actionForm['visibility'] === 'private' ? $user->id : null,
            'title' => $this->actionForm['title'],
            'description' => $this->actionForm['description'],
            'icon' => $this->actionForm['icon'],
            'color' => $this->actionForm['color'],
            'type' => $this->actionForm['type'],
            'target' => $this->actionForm['target'],
            'parameters' => $this->actionForm['parameters'],
            'open_in' => $this->actionForm['open_in'],
            'visibility' => $this->actionForm['visibility'],
        ];
        
        if ($this->actionForm['id']) {
            // Update existing
            $action = CustomQuickAction::find($this->actionForm['id']);
            if ($action) {
                // Allow editing if user created it OR if user is super-admin and it's a company action
                $canEdit = $action->user_id === $user->id || 
                          (Bouncer::is($user)->an('super-admin') && $action->visibility === 'company');
                
                if ($canEdit) {
                    $action->update($data);
                } else {
                    $this->dispatch('notify', [
                        'type' => 'error',
                        'message' => 'You do not have permission to edit this action',
                    ]);
                    return;
                }
            }
        } else {
            // Create new
            CustomQuickAction::create($data);
        }
        
        $this->showCreateModal = false;
        $this->resetActionForm();
        $this->loadActions();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Quick action saved successfully',
        ]);
    }
    
    public function recordUsage($customId)
    {
        $customAction = CustomQuickAction::find($customId);
        if ($customAction && $customAction->canBeExecutedBy(Auth::user())) {
            $customAction->recordUsage();
        }
    }
    
    public function deleteCustomAction($actionId)
    {
        $user = Auth::user();
        $action = CustomQuickAction::find($actionId);
        
        if (!$action) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Action not found',
            ]);
            return;
        }
        
        // Allow deletion if user created it OR if user is super-admin and it's a company action
        $canDelete = $action->user_id === $user->id || 
                    (Bouncer::is($user)->an('super-admin') && $action->visibility === 'company');
        
        if ($canDelete) {
            $action->delete();
            $this->loadActions();
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Quick action deleted',
            ]);
        } else {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You do not have permission to delete this action',
            ]);
        }
    }
    
    protected function resetActionForm()
    {
        $this->actionForm = [
            'id' => null,
            'title' => '',
            'description' => '',
            'icon' => 'bolt',
            'color' => 'blue',
            'type' => 'route',
            'target' => '',
            'parameters' => [],
            'open_in' => 'same_tab',
            'visibility' => 'private',
        ];
    }
    
    public function executeAction($actionKey, $customId = null)
    {
        // Handle custom action execution
        if ($customId) {
            $customAction = CustomQuickAction::find($customId);
            
            if (!$customAction) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Action not found',
                ]);
                return;
            }
            
            if (!$customAction->canBeExecutedBy(Auth::user())) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'You do not have permission to execute this action',
                ]);
                return;
            }
            
            $customAction->recordUsage();
            
            try {
                if ($customAction->type === 'route') {
                    return redirect()->route($customAction->target, $customAction->parameters ?? []);
                } elseif ($customAction->type === 'url') {
                    $url = $customAction->target;
                    if (!empty($customAction->parameters)) {
                        $url .= '?' . http_build_query($customAction->parameters);
                    }
                    
                    if ($customAction->open_in === 'new_tab') {
                        $this->dispatch('open-url', ['url' => $url, 'target' => '_blank']);
                    } else {
                        return redirect()->away($url);
                    }
                }
            } catch (\Exception $e) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Failed to execute action: ' . $e->getMessage(),
                ]);
            }
            return;
        }
        
        // Handle system actions
        $action = collect($this->actions)->firstWhere('action', $actionKey);
        
        if (!$action) {
            return;
        }
        
        switch ($actionKey) {
            case 'exportReports':
                $this->dispatch('export-reports');
                break;
                
            case 'remoteAccess':
                $this->dispatch('open-remote-access');
                break;
                
            case 'clientPortal':
                $this->dispatch('open-client-portal');
                break;
                
            default:
                $this->dispatch('quick-action-executed', ['action' => $actionKey]);
                break;
        }
    }
    
    public function render()
    {
        return view('livewire.dashboard.widgets.quick-actions');
    }
}