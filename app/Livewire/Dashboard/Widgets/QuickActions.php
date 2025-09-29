<?php

namespace App\Livewire\Dashboard\Widgets;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Services\QuickActionService;
use App\Models\CustomQuickAction;

class QuickActions extends Component
{
    public string $view = 'executive';
    public array $actions = [];
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
        
        // Use the QuickActionService to get actions
        $this->actions = QuickActionService::getActionsForUser($user, $this->view)
            ->take(12) // Limit for dashboard display
            ->toArray();
    }
    
    public function toggleFavorite($actionIdentifier)
    {
        $user = Auth::user();
        
        $isFavorite = QuickActionService::toggleFavorite($actionIdentifier, $user);
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $isFavorite ? 'Added to favorites' : 'Removed from favorites',
        ]);
        
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
        
        try {
            QuickActionService::saveCustomAction($this->actionForm, $user);
            
            $this->showCreateModal = false;
            $this->resetActionForm();
            $this->loadActions();
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Quick action saved successfully',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
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
        try {
            QuickActionService::deleteCustomAction($actionId, Auth::user());
            $this->loadActions();
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Quick action deleted',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
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
        try {
            // Use the service to find and execute the action
            $actionIdentifier = $customId ?? $actionKey;
            $action = QuickActionService::executeAction($actionIdentifier, Auth::user());
            
            // Handle custom action execution
            if (isset($action['custom_id'])) {
                $customAction = CustomQuickAction::find($action['custom_id']);
                
                if ($customAction) {
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
                }
                return;
            }
            
            // Handle system actions
            if (isset($action['route'])) {
                return redirect()->route($action['route'], $action['parameters'] ?? []);
            } elseif (isset($action['action'])) {
                switch ($action['action']) {
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
                        $this->dispatch('quick-action-executed', ['action' => $action['action']]);
                        break;
                }
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to execute action: ' . $e->getMessage(),
            ]);
        }
    }
    
    public function render()
    {
        return view('livewire.dashboard.widgets.quick-actions');
    }
}