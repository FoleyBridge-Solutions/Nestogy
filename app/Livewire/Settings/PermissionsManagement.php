<?php

namespace App\Livewire\Settings;

use App\Domains\Security\Services\PermissionService;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Silber\Bouncer\BouncerFacade as Bouncer;

class PermissionsManagement extends Component
{
    use WithPagination;
    
    #[Url(as: 'tab', except: 'overview', keep: true)]
    public $activeTab = 'overview';
    
    public $search = '';
    public $roleFilter = '';
    
    public $stats = [];
    public $roles = [];
    public $users = [];
    public $abilitiesByCategory = [];
    
    public $selectedRole = null;
    public $selectedUser = null;
    
    public $auditSearch = '';
    public $auditActionFilter = '';
    
    public function boot()
    {
        // Boot runs on EVERY request before mount/hydrate, ensuring URL is read first
        if (!Auth::user()->can('system.permissions.manage') && 
            !Auth::user()->can('settings.roles.view')) {
            abort(403, 'Unauthorized access to permissions management.');
        }
    }
    
    public function mount()
    {
        $this->loadData();
    }
    
    public function loadData()
    {
        $user = Auth::user();
        
        $this->stats = [
            'total_users' => User::where('company_id', $user->company_id)->count(),
            'total_roles' => Bouncer::role()->count(),
            'total_abilities' => Bouncer::ability()->count(),
            'users_without_roles' => User::where('company_id', $user->company_id)
                ->whereDoesntHave('roles')
                ->count(),
        ];
        
        $this->roles = Bouncer::role()
            ->withCount(['users' => function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            }])
            ->with('abilities')
            ->get()
            ->toArray();
        
        $query = User::where('company_id', $user->company_id)
            ->with(['roles', 'abilities']);
        
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }
        
        if ($this->roleFilter) {
            $query->whereHas('roles', function ($q) {
                $q->where('name', $this->roleFilter);
            });
        }
        
        $this->users = $query->orderBy('name')->get()->toArray();
        
        $this->abilitiesByCategory = $this->getAbilitiesByCategory();
    }
    
    public function updatedSearch()
    {
        $this->loadData();
    }
    
    public function updatedRoleFilter()
    {
        $this->loadData();
    }
    
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }
    
    public function selectRole($roleName)
    {
        $this->selectedRole = Bouncer::role()
            ->where('name', $roleName)
            ->with('abilities')
            ->first()
            ->toArray();
    }
    
    public function selectUser($userId)
    {
        $user = User::where('company_id', Auth::user()->company_id)
            ->where('id', $userId)
            ->with(['roles', 'abilities'])
            ->first();
        
        $this->selectedUser = $user ? $user->toArray() : null;
    }
    
    private function getAbilitiesByCategory(): array
    {
        $abilities = Bouncer::ability()->orderBy('name')->get();
        $categorized = [];
        
        foreach ($abilities as $ability) {
            $parts = explode('.', $ability->name);
            $category = ucfirst($parts[0]);
            
            if (!isset($categorized[$category])) {
                $categorized[$category] = [];
            }
            
            $categorized[$category][] = [
                'name' => $ability->name,
                'title' => $ability->title ?? $this->generateAbilityTitle($ability->name),
                'description' => $this->generateAbilityDescription($ability->name),
            ];
        }
        
        return $categorized;
    }
    
    private function generateAbilityTitle(string $abilityName): string
    {
        $parts = explode('.', $abilityName);
        
        $formatted = array_map(function ($part) {
            return ucwords(str_replace(['_', '-'], ' ', $part));
        }, $parts);
        
        return implode(' - ', $formatted);
    }
    
    private function generateAbilityDescription(string $abilityName): string
    {
        $descriptions = [
            'view' => 'View and list',
            'create' => 'Create new',
            'edit' => 'Edit existing',
            'delete' => 'Delete',
            'manage' => 'Full management access',
            'export' => 'Export data',
            'import' => 'Import data',
            'approve' => 'Approve requests',
        ];
        
        $parts = explode('.', $abilityName);
        $action = end($parts);
        $resource = ucfirst($parts[0]);
        
        return ($descriptions[$action] ?? ucfirst($action)) . ' ' . $resource;
    }
    
    public function getAuditLogsProperty()
    {
        $query = AuditLog::where('company_id', Auth::user()->company_id)
            ->where('event_type', AuditLog::EVENT_SECURITY)
            ->whereIn('action', ['permission_changed', 'role_assigned', 'role_removed'])
            ->with('user')
            ->latest();
        
        if ($this->auditSearch) {
            $query->where(function ($q) {
                $q->whereHas('user', function ($userQuery) {
                    $userQuery->where('name', 'like', '%' . $this->auditSearch . '%')
                              ->orWhere('email', 'like', '%' . $this->auditSearch . '%');
                })
                ->orWhereJsonContains('metadata->role', $this->auditSearch)
                ->orWhereJsonContains('metadata->ability', $this->auditSearch);
            });
        }
        
        if ($this->auditActionFilter) {
            $query->where('action', $this->auditActionFilter);
        }
        
        return $query->paginate(20);
    }
    
    public function updatedAuditSearch()
    {
        $this->resetPage();
    }
    
    public function updatedAuditActionFilter()
    {
        $this->resetPage();
    }
    
    public function render()
    {
        return view('livewire.settings.permissions-management');
    }
}
