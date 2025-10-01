<?php

namespace App\Livewire\Settings;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Silber\Bouncer\BouncerFacade as Bouncer;

class PermissionMatrix extends Component
{
    public $roles = [];
    public $abilitiesByCategory = [];
    public $matrix = [];
    
    public $filterDomain = '';
    public $searchTerm = '';
    
    public $expandedCategories = [];
    
    public function mount()
    {
        $this->loadMatrix();
        
        foreach (array_keys($this->abilitiesByCategory) as $category) {
            $this->expandedCategories[] = $category;
        }
    }
    
    public function loadMatrix()
    {
        $user = Auth::user();
        
        $this->roles = Bouncer::role()
            ->withCount(['users' => function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            }])
            ->with('abilities')
            ->get();
        
        $abilities = Bouncer::ability()->orderBy('name')->get();
        $categorized = [];
        
        foreach ($abilities as $ability) {
            $parts = explode('.', $ability->name);
            $category = ucfirst($parts[0]);
            
            if ($this->filterDomain && $category !== $this->filterDomain) {
                continue;
            }
            
            if ($this->searchTerm && 
                !str_contains(strtolower($ability->name), strtolower($this->searchTerm)) &&
                !str_contains(strtolower($ability->title ?? ''), strtolower($this->searchTerm))) {
                continue;
            }
            
            if (!isset($categorized[$category])) {
                $categorized[$category] = [];
            }
            
            $categorized[$category][] = [
                'name' => $ability->name,
                'title' => $ability->title ?? $this->generateAbilityTitle($ability->name),
            ];
        }
        
        $this->abilitiesByCategory = $categorized;
        
        $this->matrix = [];
        foreach ($this->abilitiesByCategory as $category => $abilities) {
            $this->matrix[$category] = [];
            foreach ($abilities as $ability) {
                $this->matrix[$category][$ability['name']] = [
                    'title' => $ability['title'],
                    'roles' => [],
                ];
                foreach ($this->roles as $role) {
                    $hasAbility = $role->abilities->contains('name', $ability['name']);
                    $this->matrix[$category][$ability['name']]['roles'][$role->name] = $hasAbility;
                }
            }
        }
    }
    
    public function updatedFilterDomain()
    {
        $this->loadMatrix();
    }
    
    public function updatedSearchTerm()
    {
        $this->loadMatrix();
    }
    
    public function togglePermission($roleName, $abilityName)
    {
        if (in_array($roleName, ['super-admin'])) {
            session()->flash('error', 'Cannot modify super-admin permissions');
            return;
        }
        
        $role = $this->roles->firstWhere('name', $roleName);
        if (!$role) {
            return;
        }
        
        DB::beginTransaction();
        
        try {
            $hasAbility = $role->abilities->contains('name', $abilityName);
            
            if ($hasAbility) {
                Bouncer::disallow($roleName)->to($abilityName);
            } else {
                Bouncer::allow($roleName)->to($abilityName);
            }
            
            DB::commit();
            
            $granted = !$hasAbility;
            
            Log::info('Permission matrix updated', [
                'role' => $roleName,
                'ability' => $abilityName,
                'granted' => $granted,
                'updated_by' => Auth::id(),
            ]);
            
            AuditLog::create([
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'event_type' => AuditLog::EVENT_SECURITY,
                'action' => 'permission_changed',
                'metadata' => [
                    'role' => $roleName,
                    'ability' => $abilityName,
                    'granted' => $granted,
                ],
                'old_values' => ['granted' => $hasAbility],
                'new_values' => ['granted' => $granted],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'session_id' => session()->getId(),
                'request_method' => request()->method(),
                'request_url' => request()->fullUrl(),
                'severity' => AuditLog::SEVERITY_WARNING,
            ]);
            
            $this->loadMatrix();
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Permission matrix update failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Failed to update permission');
        }
    }
    
    public function toggleCategory($category)
    {
        if (in_array($category, $this->expandedCategories)) {
            $this->expandedCategories = array_values(array_diff($this->expandedCategories, [$category]));
        } else {
            $this->expandedCategories[] = $category;
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
        return view('livewire.settings.permission-matrix');
    }
}
