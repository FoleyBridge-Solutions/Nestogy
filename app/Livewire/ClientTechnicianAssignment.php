<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientTechnicianAssignment extends Component
{
    public $clientId;
    public $client;
    public $technicians = [];
    public $availableTechnicians = [];
    public $selectedTechnicianId;
    public $accessLevel = 'view';
    public $isPrimary = false;
    public $expiresAt;
    public $notes;
    public $showAddForm = false;
    
    protected $rules = [
        'selectedTechnicianId' => 'required|exists:users,id',
        'accessLevel' => 'required|in:view,manage,admin',
        'isPrimary' => 'boolean',
        'expiresAt' => 'nullable|date|after:today',
        'notes' => 'nullable|string|max:500',
    ];
    
    public function mount($clientId)
    {
        $this->clientId = $clientId;
        $this->loadClient();
        $this->loadTechnicians();
        $this->loadAvailableTechnicians();
    }
    
    public function loadClient()
    {
        $this->client = Client::findOrFail($this->clientId);
        
        // Check permissions
        if (!Auth::user()->isA('admin') && !Auth::user()->isA('super-admin')) {
            abort(403, 'Unauthorized');
        }
    }
    
    public function loadTechnicians()
    {
        $this->technicians = $this->client->assignedTechnicians()
            ->with(['company'])
            ->get()
            ->map(function ($tech) {
                return [
                    'id' => $tech->id,
                    'name' => $tech->name,
                    'email' => $tech->email,
                    'access_level' => $tech->pivot->access_level,
                    'is_primary' => $tech->pivot->is_primary,
                    'assigned_at' => $tech->pivot->assigned_at,
                    'expires_at' => $tech->pivot->expires_at,
                    'notes' => $tech->pivot->notes,
                ];
            })
            ->toArray();
    }
    
    public function loadAvailableTechnicians()
    {
        $user = Auth::user();
        
        // Get users who are technicians but not yet assigned to this client
        $assignedIds = collect($this->technicians)->pluck('id')->toArray();
        
        $this->availableTechnicians = User::where('company_id', $user->company_id)
            ->whereNotIn('id', $assignedIds)
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['technician', 'admin', 'super-admin']);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->toArray();
    }
    
    public function toggleAddForm()
    {
        $this->showAddForm = !$this->showAddForm;
        
        if ($this->showAddForm) {
            $this->resetForm();
        }
    }
    
    public function resetForm()
    {
        $this->selectedTechnicianId = null;
        $this->accessLevel = 'view';
        $this->isPrimary = false;
        $this->expiresAt = null;
        $this->notes = null;
        $this->resetValidation();
    }
    
    public function assignTechnician()
    {
        $this->validate();
        
        try {
            DB::beginTransaction();
            
            // If setting as primary, remove primary flag from others
            if ($this->isPrimary) {
                DB::table('user_clients')
                    ->where('client_id', $this->clientId)
                    ->update(['is_primary' => false]);
            }
            
            // Assign the technician
            $this->client->assignTechnician(
                User::find($this->selectedTechnicianId),
                [
                    'access_level' => $this->accessLevel,
                    'is_primary' => $this->isPrimary,
                    'expires_at' => $this->expiresAt,
                    'notes' => $this->notes,
                ]
            );
            
            DB::commit();
            
            // Reload data
            $this->loadTechnicians();
            $this->loadAvailableTechnicians();
            $this->showAddForm = false;
            $this->resetForm();
            
            $this->dispatch('technician-assigned', [
                'message' => 'Technician assigned successfully'
            ]);
            
            Log::info('Technician assigned to client', [
                'client_id' => $this->clientId,
                'technician_id' => $this->selectedTechnicianId,
                'assigned_by' => Auth::id(),
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Failed to assign technician', [
                'client_id' => $this->clientId,
                'error' => $e->getMessage(),
            ]);
            
            $this->dispatch('assignment-failed', [
                'message' => 'Failed to assign technician: ' . $e->getMessage()
            ]);
        }
    }
    
    public function updateAccessLevel($technicianId, $newLevel)
    {
        try {
            DB::table('user_clients')
                ->where('client_id', $this->clientId)
                ->where('user_id', $technicianId)
                ->update(['access_level' => $newLevel]);
            
            $this->loadTechnicians();
            
            $this->dispatch('access-updated', [
                'message' => 'Access level updated successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to update access level', [
                'client_id' => $this->clientId,
                'technician_id' => $technicianId,
                'error' => $e->getMessage(),
            ]);
            
            $this->dispatch('update-failed', [
                'message' => 'Failed to update access level'
            ]);
        }
    }
    
    public function setPrimary($technicianId)
    {
        try {
            DB::beginTransaction();
            
            // Remove primary flag from all technicians for this client
            DB::table('user_clients')
                ->where('client_id', $this->clientId)
                ->update(['is_primary' => false]);
            
            // Set the new primary technician
            DB::table('user_clients')
                ->where('client_id', $this->clientId)
                ->where('user_id', $technicianId)
                ->update(['is_primary' => true]);
            
            DB::commit();
            
            $this->loadTechnicians();
            
            $this->dispatch('primary-updated', [
                'message' => 'Primary technician updated successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Failed to set primary technician', [
                'client_id' => $this->clientId,
                'technician_id' => $technicianId,
                'error' => $e->getMessage(),
            ]);
            
            $this->dispatch('update-failed', [
                'message' => 'Failed to set primary technician'
            ]);
        }
    }
    
    public function removeTechnician($technicianId)
    {
        try {
            $this->client->removeTechnician(User::find($technicianId));
            
            $this->loadTechnicians();
            $this->loadAvailableTechnicians();
            
            $this->dispatch('technician-removed', [
                'message' => 'Technician removed successfully'
            ]);
            
            Log::info('Technician removed from client', [
                'client_id' => $this->clientId,
                'technician_id' => $technicianId,
                'removed_by' => Auth::id(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to remove technician', [
                'client_id' => $this->clientId,
                'technician_id' => $technicianId,
                'error' => $e->getMessage(),
            ]);
            
            $this->dispatch('removal-failed', [
                'message' => 'Failed to remove technician'
            ]);
        }
    }
    
    public function render()
    {
        return view('livewire.client-technician-assignment');
    }
}
