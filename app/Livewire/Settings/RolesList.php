<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Silber\Bouncer\BouncerFacade as Bouncer;

class RolesList extends Component
{
    public $roles = [];
    public $selectedRole = null;
    public $abilitiesByCategory = [];
    
    public $isCreating = false;
    public $isEditing = false;
    
    public $form = [
        'name' => '',
        'title' => '',
        'description' => '',
        'abilities' => [],
    ];
    
    public $expandedCategories = [];
    
    public function mount()
    {
        $this->loadRoles();
        $this->loadAbilities();
    }
    
    public function loadRoles()
    {
        $user = Auth::user();
        
        $this->roles = Bouncer::role()
            ->withCount(['users' => function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            }])
            ->with('abilities')
            ->get()
            ->toArray();
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
    
    public function selectRole($roleName)
    {
        $this->selectedRole = Bouncer::role()
            ->where('name', $roleName)
            ->with('abilities')
            ->first();
        
        if ($this->selectedRole) {
            $this->form = [
                'name' => $this->selectedRole->name,
                'title' => $this->selectedRole->title ?? '',
                'description' => $this->selectedRole->description ?? '',
                'abilities' => $this->selectedRole->abilities->pluck('name')->toArray(),
            ];
        }
        
        $this->isEditing = false;
        $this->isCreating = false;
    }
    
    public function createRole()
    {
        $this->isCreating = true;
        $this->isEditing = false;
        $this->selectedRole = null;
        $this->form = [
            'name' => '',
            'title' => '',
            'description' => '',
            'abilities' => [],
        ];
    }
    
    public function editRole()
    {
        if (!$this->selectedRole) {
            return;
        }
        
        if (in_array($this->selectedRole->name, ['super-admin'])) {
            session()->flash('error', 'Cannot edit super-admin role');
            return;
        }
        
        $this->isEditing = true;
    }
    
    public function toggleAbility($abilityName)
    {
        if (in_array($abilityName, $this->form['abilities'])) {
            $this->form['abilities'] = array_values(array_diff($this->form['abilities'], [$abilityName]));
        } else {
            $this->form['abilities'][] = $abilityName;
        }
    }
    
    public function toggleCategory($category)
    {
        $categoryAbilities = collect($this->abilitiesByCategory[$category] ?? [])->pluck('name')->toArray();
        
        $allSelected = empty(array_diff($categoryAbilities, $this->form['abilities']));
        
        if ($allSelected) {
            $this->form['abilities'] = array_values(array_diff($this->form['abilities'], $categoryAbilities));
        } else {
            $this->form['abilities'] = array_unique(array_merge($this->form['abilities'], $categoryAbilities));
        }
    }
    
    public function toggleExpandCategory($category)
    {
        if (in_array($category, $this->expandedCategories)) {
            $this->expandedCategories = array_values(array_diff($this->expandedCategories, [$category]));
        } else {
            $this->expandedCategories[] = $category;
        }
    }
    
    public function saveRole()
    {
        $this->validate([
            'form.title' => 'required|string|max:255',
            'form.name' => 'required|string|max:255|regex:/^[a-z0-9\-]+$/|' . ($this->isCreating ? 'unique:bouncer_roles,name' : ''),
            'form.description' => 'nullable|string|max:1000',
            'form.abilities' => 'array',
        ]);
        
        DB::beginTransaction();
        
        try {
            if ($this->isCreating) {
                $role = Bouncer::role()->create([
                    'name' => $this->form['name'],
                    'title' => $this->form['title'],
                    'description' => $this->form['description'],
                ]);
                
                foreach ($this->form['abilities'] as $abilityName) {
                    Bouncer::allow($role->name)->to($abilityName);
                }
                
                session()->flash('success', "Role '{$role->title}' created successfully");
            } else {
                $role = $this->selectedRole;
                $role->update([
                    'title' => $this->form['title'],
                    'description' => $this->form['description'],
                ]);
                
                $currentAbilities = $role->abilities->pluck('name')->toArray();
                
                foreach (array_diff($currentAbilities, $this->form['abilities']) as $abilityToRemove) {
                    Bouncer::disallow($role->name)->to($abilityToRemove);
                }
                
                foreach (array_diff($this->form['abilities'], $currentAbilities) as $abilityToAdd) {
                    Bouncer::allow($role->name)->to($abilityToAdd);
                }
                
                session()->flash('success', "Role '{$role->title}' updated successfully");
            }
            
            DB::commit();
            
            $this->loadRoles();
            $this->selectRole($role->name);
            $this->isCreating = false;
            $this->isEditing = false;
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to save role', ['error' => $e->getMessage()]);
            session()->flash('error', 'Failed to save role: ' . $e->getMessage());
        }
    }
    
    public function cancelEdit()
    {
        $this->isCreating = false;
        $this->isEditing = false;
        
        if ($this->selectedRole) {
            $this->selectRole($this->selectedRole->name);
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
        return view('livewire.settings.roles-list');
    }
}
