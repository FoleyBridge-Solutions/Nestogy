<?php

namespace App\Livewire\Contracts;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractSchedule;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ScheduleManager extends Component
{
    use AuthorizesRequests;

    private const NULLABLE_STRING_RULE = 'nullable|string';

    public Contract $contract;
    public $schedules = [];
    public $selectedScheduleId = null;
    public $editingSchedule = null;
    public $showCreateModal = false;
    public $showEditModal = false;
    
    // Form fields for new/edit schedule
    public $schedule_type;
    public $title;
    public $description;
    public $content;
    public $status;
    public $effective_date;
    public $expiration_date;
    
    // Advanced fields
    public $supported_asset_types = [];
    public $service_levels = [];
    public $pricing_structure = [];
    public $auto_assign_assets = false;
    
    public function mount(Contract $contract)
    {
        $this->authorize('update', $contract);
        $this->contract = $contract;
        $this->loadSchedules();
    }
    
    public function loadSchedules()
    {
        $this->schedules = $this->contract->schedules()
            ->orderBy('schedule_type')
            ->orderBy('created_at')
            ->get()
            ->toArray();
    }
    
    public function openCreateModal($type = null)
    {
        $this->resetForm();
        $this->schedule_type = $type;
        $this->showCreateModal = true;
    }
    
    public function openEditModal($scheduleId)
    {
        $schedule = ContractSchedule::findOrFail($scheduleId);
        $this->authorize('update', $schedule);
        
        $this->editingSchedule = $schedule;
        $this->selectedScheduleId = $scheduleId;
        
        // Populate form
        $this->schedule_type = $schedule->schedule_type;
        $this->title = $schedule->title;
        $this->description = $schedule->description;
        $this->content = $schedule->content;
        $this->status = $schedule->status;
        $this->effective_date = $schedule->effective_date?->format('Y-m-d');
        $this->expiration_date = $schedule->expiration_date?->format('Y-m-d');
        $this->supported_asset_types = $schedule->supported_asset_types ?? [];
        $this->service_levels = $schedule->service_levels ?? [];
        $this->pricing_structure = $schedule->pricing_structure ?? [];
        $this->auto_assign_assets = $schedule->auto_assign_assets;
        
        $this->showEditModal = true;
    }
    
    public function createSchedule()
    {
        $this->validate([
            'schedule_type' => 'required|string|in:A,B,C,D,E',
            'title' => 'required|string|max:255',
            'description' => self::NULLABLE_STRING_RULE,
            'content' => self::NULLABLE_STRING_RULE,
            'status' => 'required|string',
            'effective_date' => 'nullable|date',
            'expiration_date' => 'nullable|date|after:effective_date',
        ]);
        
        try {
            $schedule = $this->contract->schedules()->create([
                'company_id' => $this->contract->company_id,
                'schedule_type' => $this->schedule_type,
                'schedule_letter' => $this->schedule_type,
                'title' => $this->title,
                'description' => $this->description,
                'content' => $this->content ?? '',
                'status' => $this->status,
                'approval_status' => ContractSchedule::APPROVAL_PENDING,
                'effective_date' => $this->effective_date,
                'expiration_date' => $this->expiration_date,
                'supported_asset_types' => $this->supported_asset_types,
                'service_levels' => $this->service_levels,
                'pricing_structure' => $this->pricing_structure,
                'auto_assign_assets' => $this->auto_assign_assets,
                'version' => '1.0',
            ]);
            
            $this->loadSchedules();
            $this->showCreateModal = false;
            $this->resetForm();
            
            session()->flash('success', "Schedule {$this->schedule_type} created successfully!");
            
        } catch (\Exception $e) {
            \Log::error('Failed to create schedule', [
                'contract_id' => $this->contract->id,
                'error' => $e->getMessage(),
            ]);
            
            session()->flash('error', 'Failed to create schedule: ' . $e->getMessage());
        }
    }
    
    public function updateSchedule()
    {
        if (!$this->editingSchedule) {
            return;
        }
        
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => self::NULLABLE_STRING_RULE,
            'content' => self::NULLABLE_STRING_RULE,
            'status' => 'required|string',
            'effective_date' => 'nullable|date',
            'expiration_date' => 'nullable|date|after:effective_date',
        ]);
        
        try {
            $this->editingSchedule->update([
                'title' => $this->title,
                'description' => $this->description,
                'content' => $this->content,
                'status' => $this->status,
                'effective_date' => $this->effective_date,
                'expiration_date' => $this->expiration_date,
                'supported_asset_types' => $this->supported_asset_types,
                'service_levels' => $this->service_levels,
                'pricing_structure' => $this->pricing_structure,
                'auto_assign_assets' => $this->auto_assign_assets,
            ]);
            
            $this->loadSchedules();
            $this->showEditModal = false;
            $this->resetForm();
            
            session()->flash('success', 'Schedule updated successfully!');
            
        } catch (\Exception $e) {
            \Log::error('Failed to update schedule', [
                'schedule_id' => $this->editingSchedule->id,
                'error' => $e->getMessage(),
            ]);
            
            session()->flash('error', 'Failed to update schedule: ' . $e->getMessage());
        }
    }
    
    public function deleteSchedule($scheduleId)
    {
        $schedule = ContractSchedule::findOrFail($scheduleId);
        $this->authorize('delete', $schedule);
        
        try {
            $scheduleType = $schedule->schedule_type;
            $schedule->delete();
            
            $this->loadSchedules();
            
            session()->flash('success', "Schedule {$scheduleType} deleted successfully!");
            
        } catch (\Exception $e) {
            \Log::error('Failed to delete schedule', [
                'schedule_id' => $scheduleId,
                'error' => $e->getMessage(),
            ]);
            
            session()->flash('error', 'Failed to delete schedule: ' . $e->getMessage());
        }
    }
    
    public function activateSchedule($scheduleId)
    {
        $schedule = ContractSchedule::findOrFail($scheduleId);
        $this->authorize('update', $schedule);
        
        try {
            $schedule->activate();
            $this->loadSchedules();
            
            session()->flash('success', 'Schedule activated successfully!');
            
        } catch (\Exception $e) {
            \Log::error('Failed to activate schedule', [
                'schedule_id' => $scheduleId,
                'error' => $e->getMessage(),
            ]);
            
            session()->flash('error', 'Failed to activate schedule: ' . $e->getMessage());
        }
    }
    
    public function approveSchedule($scheduleId)
    {
        $schedule = ContractSchedule::findOrFail($scheduleId);
        $this->authorize('update', $schedule);
        
        try {
            $schedule->approve();
            $this->loadSchedules();
            
            session()->flash('success', 'Schedule approved successfully!');
            
        } catch (\Exception $e) {
            \Log::error('Failed to approve schedule', [
                'schedule_id' => $scheduleId,
                'error' => $e->getMessage(),
            ]);
            
            session()->flash('error', 'Failed to approve schedule: ' . $e->getMessage());
        }
    }
    
    protected function resetForm()
    {
        $this->schedule_type = null;
        $this->title = '';
        $this->description = '';
        $this->content = '';
        $this->status = ContractSchedule::STATUS_DRAFT;
        $this->effective_date = null;
        $this->expiration_date = null;
        $this->supported_asset_types = [];
        $this->service_levels = [];
        $this->pricing_structure = [];
        $this->auto_assign_assets = false;
        $this->editingSchedule = null;
        $this->selectedScheduleId = null;
    }
    
    public function render()
    {
        return view('livewire.contracts.schedule-manager');
    }
}
