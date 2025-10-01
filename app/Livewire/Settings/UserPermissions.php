<?php

namespace App\Livewire\Settings;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Silber\Bouncer\BouncerFacade as Bouncer;

class UserPermissions extends Component
{
    use WithPagination;
    
    public $selectedUser = null;
    public $roles = [];
    public $abilitiesByCategory = [];
    
    public $search = '';
    public $roleFilter = '';
    public $showOnlyWithoutRoles = false;
    
    public $perPage = 15;
    
    public $editMode = false;
    public $form = [
        'assigned_roles' => [],
        'direct_abilities' => [],
    ];
    
    public $effectivePermissions = [];
    
    public function mount()
    {
        $this->loadRoles();
        $this->loadAbilities();
    }
    
    #[Computed]
    public function users()
    {
        $user = Auth::user();
        
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
        
        if ($this->showOnlyWithoutRoles) {
            $query->whereDoesntHave('roles');
        }
        
        return $query->orderBy('name')->paginate($this->perPage);
    }
    
    public function loadRoles()
    {
        $this->roles = Bouncer::role()->get()->toArray();
    }
    
    public function loadAbilities()
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
            ];
        }
        
        $this->abilitiesByCategory = $categorized;
    }
    
    public function updatedSearch()
    {
        $this->resetPage();
    }
    
    public function updatedRoleFilter()
    {
        $this->resetPage();
    }
    
    public function updatedShowOnlyWithoutRoles()
    {
        $this->resetPage();
    }
    
    public function selectUser($userId)
    {
        $user = User::where('company_id', Auth::user()->company_id)
            ->where('id', $userId)
            ->with(['roles', 'abilities'])
            ->first();
        
        if (!$user) {
            return;
        }
        
        $this->selectedUser = $user;
        
        $this->form = [
            'assigned_roles' => $user->roles->pluck('name')->toArray(),
            'direct_abilities' => $user->abilities->pluck('name')->toArray(),
        ];
        
        $this->calculateEffectivePermissions();
        $this->editMode = false;
    }
    
    public function editUser()
    {
        if (!$this->selectedUser) {
            return;
        }
        
        $this->editMode = true;
    }
    
    public function cancelEdit()
    {
        $this->editMode = false;
        if ($this->selectedUser) {
            $this->selectUser($this->selectedUser->id);
        }
    }
    
    public function toggleRole($roleName)
    {
        if (in_array($roleName, $this->form['assigned_roles'])) {
            $this->form['assigned_roles'] = array_values(array_diff($this->form['assigned_roles'], [$roleName]));
        } else {
            $this->form['assigned_roles'][] = $roleName;
        }
        
        $this->calculateEffectivePermissions();
    }
    
    public function toggleDirectAbility($abilityName)
    {
        if (in_array($abilityName, $this->form['direct_abilities'])) {
            $this->form['direct_abilities'] = array_values(array_diff($this->form['direct_abilities'], [$abilityName]));
        } else {
            $this->form['direct_abilities'][] = $abilityName;
        }
        
        $this->calculateEffectivePermissions();
    }
    
    public function calculateEffectivePermissions()
    {
        $this->effectivePermissions = [];
        
        foreach ($this->form['assigned_roles'] as $roleName) {
            $role = collect($this->roles)->firstWhere('name', $roleName);
            if ($role) {
                $roleObj = Bouncer::role()->where('name', $roleName)->with('abilities')->first();
                foreach ($roleObj->abilities as $ability) {
                    $this->effectivePermissions[$ability->name] = [
                        'source' => 'role',
                        'role' => $role['title'] ?? $roleName,
                    ];
                }
            }
        }
        
        foreach ($this->form['direct_abilities'] as $abilityName) {
            $this->effectivePermissions[$abilityName] = [
                'source' => 'direct',
                'role' => null,
            ];
        }
    }
    
    public function saveUserPermissions()
    {
        if (!$this->selectedUser) {
            return;
        }
        
        DB::beginTransaction();
        
        try {
            Bouncer::scope()->to($this->selectedUser->company_id);
            
            $currentRoles = $this->selectedUser->roles->pluck('name')->toArray();
            $newRoles = $this->form['assigned_roles'];
            
            foreach (array_diff($currentRoles, $newRoles) as $roleToRemove) {
                Bouncer::retract($roleToRemove)->from($this->selectedUser);
            }
            
            foreach (array_diff($newRoles, $currentRoles) as $roleToAdd) {
                Bouncer::assign($roleToAdd)->to($this->selectedUser);
            }
            
            $currentAbilities = $this->selectedUser->abilities->pluck('name')->toArray();
            $newAbilities = $this->form['direct_abilities'];
            
            foreach (array_diff($currentAbilities, $newAbilities) as $abilityToRemove) {
                Bouncer::disallow($this->selectedUser)->to($abilityToRemove);
            }
            
            foreach (array_diff($newAbilities, $currentAbilities) as $abilityToAdd) {
                Bouncer::allow($this->selectedUser)->to($abilityToAdd);
            }
            
            DB::commit();
            
            Log::info('User permissions updated', [
                'user_id' => $this->selectedUser->id,
                'updated_by' => Auth::id(),
            ]);
            
            session()->flash('success', "Permissions for {$this->selectedUser->name} updated successfully");
            
            $this->selectUser($this->selectedUser->id);
            $this->editMode = false;
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to update user permissions', ['error' => $e->getMessage()]);
            session()->flash('error', 'Failed to update user permissions: ' . $e->getMessage());
        }
    }
    
    private function generateAbilityTitle(string $abilityName): string
    {
        $parts = explode('.', $abilityName);
        $formatted = array_map(fn($part) => ucwords(str_replace(['_', '-'], ' ', $part)), $parts);
        return implode(' - ', $formatted);
    }
    
    public function render()
    {
        return view('livewire.settings.user-permissions');
    }
}
