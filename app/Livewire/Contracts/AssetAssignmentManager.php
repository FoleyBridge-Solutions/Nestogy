<?php

namespace App\Livewire\Contracts;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractAssetAssignment;
use App\Models\Asset;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AssetAssignmentManager extends Component
{
    use AuthorizesRequests, WithPagination;

    private const REQUIRED_ARRAY_VALIDATION = 'required|array|min:1';

    public Contract $contract;
    public $assignments = [];
    
    // UI state
    public $showAddModal = false;
    public $showEditModal = false;
    public $editingAssignment = null;
    
    // Search and filters
    public $searchAssets = '';
    public $selectedAssetIds = [];
    public $availableAssets = [];
    
    // Assignment form
    public $asset_id;
    public $assigned_services = [];
    public $billing_rate = 0;
    public $base_monthly_rate = 0;
    public $billing_frequency = 'monthly';
    public $start_date;
    public $status = 'active';
    
    // Available services
    public $availableServices = [
        'monitoring' => 'Monitoring & Alerting',
        'patching' => 'Patch Management',
        'backup' => 'Backup & Recovery',
        'antivirus' => 'Antivirus Management',
        'support' => 'Help Desk Support',
        'maintenance' => 'Preventive Maintenance',
    ];
    
    public function mount(Contract $contract)
    {
        $this->authorize('update', $contract);
        $this->contract = $contract;
        $this->start_date = now()->format('Y-m-d');
        $this->loadAssignments();
    }
    
    public function loadAssignments()
    {
        $this->assignments = $this->contract->assetAssignments()
            ->with(['asset'])
            ->get()
            ->toArray();
    }
    
    public function loadAvailableAssets()
    {
        $query = Asset::where('company_id', $this->contract->company_id)
            ->where('client_id', $this->contract->client_id);
        
        if ($this->searchAssets) {
            $query->where(function($q) {
                $q->where('name', 'ilike', '%' . $this->searchAssets . '%')
                  ->orWhere('serial', 'ilike', '%' . $this->searchAssets . '%')
                  ->orWhere('ip', 'ilike', '%' . $this->searchAssets . '%');
            });
        }
        
        // Exclude already assigned assets
        $assignedAssetIds = collect($this->assignments)->pluck('asset_id')->toArray();
        $query->whereNotIn('id', $assignedAssetIds);
        
        $this->availableAssets = $query->limit(50)->get()->toArray();
    }
    
    public function updatedSearchAssets()
    {
        $this->loadAvailableAssets();
    }
    
    public function openAddModal()
    {
        $this->resetForm();
        $this->loadAvailableAssets();
        $this->showAddModal = true;
    }
    
    public function openEditModal($assignmentId)
    {
        $assignment = ContractAssetAssignment::findOrFail($assignmentId);
        $this->authorize('update', $assignment);
        
        $this->editingAssignment = $assignment;
        $this->asset_id = $assignment->asset_id;
        $this->assigned_services = $assignment->assigned_services ?? [];
        $this->billing_rate = $assignment->billing_rate;
        $this->base_monthly_rate = $assignment->base_monthly_rate;
        $this->billing_frequency = $assignment->billing_frequency;
        $this->start_date = $assignment->start_date->format('Y-m-d');
        $this->status = $assignment->status;
        
        $this->showEditModal = true;
    }
    
    public function assignAssets()
    {
        $this->validate([
            'selectedAssetIds' => self::REQUIRED_ARRAY_VALIDATION,
            'assigned_services' => self::REQUIRED_ARRAY_VALIDATION,
            'base_monthly_rate' => 'required|numeric|min:0',
            'billing_frequency' => 'required|string',
            'start_date' => 'required|date',
        ]);
        
        try {
            foreach ($this->selectedAssetIds as $assetId) {
                $this->contract->assetAssignments()->create([
                    'company_id' => $this->contract->company_id,
                    'asset_id' => $assetId,
                    'assigned_services' => $this->assigned_services,
                    'billing_rate' => $this->base_monthly_rate,
                    'base_monthly_rate' => $this->base_monthly_rate,
                    'billing_frequency' => $this->billing_frequency,
                    'status' => $this->status,
                    'start_date' => $this->start_date,
                    'auto_assigned' => false,
                    'assigned_by' => auth()->id(),
                ]);
            }
            
            $this->loadAssignments();
            $this->showAddModal = false;
            $this->resetForm();
            
            // Notify parent to recalculate contract value
            $this->dispatch('assignmentsUpdated');
            
            session()->flash('success', count($this->selectedAssetIds) . ' asset(s) assigned successfully!');
            
        } catch (\Exception $e) {
            \Log::error('Failed to assign assets', [
                'contract_id' => $this->contract->id,
                'error' => $e->getMessage(),
            ]);
            
            session()->flash('error', 'Failed to assign assets: ' . $e->getMessage());
        }
    }
    
    public function updateAssignment()
    {
        if (!$this->editingAssignment) {
            return;
        }
        
        $this->validate([
            'assigned_services' => self::REQUIRED_ARRAY_VALIDATION,
            'base_monthly_rate' => 'required|numeric|min:0',
            'billing_frequency' => 'required|string',
            'start_date' => 'required|date',
        ]);
        
        try {
            $this->editingAssignment->update([
                'assigned_services' => $this->assigned_services,
                'billing_rate' => $this->base_monthly_rate,
                'base_monthly_rate' => $this->base_monthly_rate,
                'billing_frequency' => $this->billing_frequency,
                'start_date' => $this->start_date,
                'status' => $this->status,
                'updated_by' => auth()->id(),
            ]);
            
            $this->loadAssignments();
            $this->showEditModal = false;
            $this->resetForm();
            
            // Notify parent to recalculate contract value
            $this->dispatch('assignmentsUpdated');
            
            session()->flash('success', 'Assignment updated successfully!');
            
        } catch (\Exception $e) {
            \Log::error('Failed to update assignment', [
                'assignment_id' => $this->editingAssignment->id,
                'error' => $e->getMessage(),
            ]);
            
            session()->flash('error', 'Failed to update assignment: ' . $e->getMessage());
        }
    }
    
    public function removeAssignment($assignmentId)
    {
        $assignment = ContractAssetAssignment::findOrFail($assignmentId);
        $this->authorize('delete', $assignment);
        
        try {
            $assignment->delete();
            
            $this->loadAssignments();
            
            // Notify parent to recalculate contract value
            $this->dispatch('assignmentsUpdated');
            
            session()->flash('success', 'Asset removed from contract.');
            
        } catch (\Exception $e) {
            \Log::error('Failed to remove assignment', [
                'assignment_id' => $assignmentId,
                'error' => $e->getMessage(),
            ]);
            
            session()->flash('error', 'Failed to remove assignment: ' . $e->getMessage());
        }
    }
    
    public function toggleService($service)
    {
        if (in_array($service, $this->assigned_services)) {
            $this->assigned_services = array_values(array_diff($this->assigned_services, [$service]));
        } else {
            $this->assigned_services[] = $service;
        }
    }
    
    protected function resetForm()
    {
        $this->asset_id = null;
        $this->selectedAssetIds = [];
        $this->assigned_services = [];
        $this->billing_rate = 0;
        $this->base_monthly_rate = 0;
        $this->billing_frequency = 'monthly';
        $this->start_date = now()->format('Y-m-d');
        $this->status = 'active';
        $this->editingAssignment = null;
        $this->searchAssets = '';
        $this->availableAssets = [];
    }
    
    public function render()
    {
        return view('livewire.contracts.asset-assignment-manager');
    }
}
