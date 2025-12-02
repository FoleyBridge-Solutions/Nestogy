<?php

namespace App\Livewire\Leads;

use App\Domains\Lead\Models\Lead;
use App\Traits\HasAutomaticAI;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LeadShow extends Component
{
    use HasAutomaticAI;

    public Lead $lead;

    public $showConvertModal = false;
    public $showDeleteModal = false;
    public $showEditModal = false;

    public function mount(Lead $lead)
    {
        $this->lead = $lead;
        
        $this->initializeAI($lead);
        
        $this->lead->load([
            'leadSource',
            'assignedUser',
            'client',
            'activities',
            'activities.user',
        ]);
    }

    public function convertToClient()
    {
        if (!Auth::user()->can('convert', $this->lead)) {
            $this->dispatch('error', 'You are not authorized to convert this lead.');
            return;
        }

        $client = $this->lead->convertToClient();
        
        return redirect()->route('clients.show', $client);
    }

    public function updateStatus($status)
    {
        $this->lead->update(['status' => $status]);
        $this->lead->refresh();
        
        $this->dispatch('success', 'Lead status updated.');
    }

    public function updatePriority($priority)
    {
        $this->lead->update(['priority' => $priority]);
        $this->lead->refresh();
        
        $this->dispatch('success', 'Lead priority updated.');
    }

    public function addActivity()
    {
        $this->validate([
            'activityType' => 'required|in:call,email,meeting,note',
            'activityNotes' => 'required|min:3',
        ]);

        $this->lead->activities()->create([
            'user_id' => Auth::id(),
            'type' => $this->activityType,
            'notes' => $this->activityNotes,
            'activity_date' => now(),
        ]);

        $this->reset(['activityType', 'activityNotes']);
        $this->lead->load('activities.user');
        
        $this->dispatch('success', 'Activity added.');
    }

    public function render()
    {
        return view('livewire.leads.lead-show');
    }

    protected function getModel()
    {
        return $this->lead;
    }

    protected function getAIAnalysisType(): string
    {
        return 'lead';
    }
}
