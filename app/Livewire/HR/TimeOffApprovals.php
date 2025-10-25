<?php

namespace App\Livewire\HR;

use App\Domains\HR\Models\TimeOffRequest;
use Livewire\Component;
use Livewire\WithPagination;

class TimeOffApprovals extends Component
{
    use WithPagination;

    public $filterStatus = 'pending';
    
    public $selectedRequest = null;
    
    public $reviewNotes = '';

    public function mount()
    {
        $this->authorize('manage-hr');
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function getRequestsProperty()
    {
        $query = TimeOffRequest::where('company_id', auth()->user()->company_id)
            ->with(['user', 'reviewedBy']);

        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    public function approveRequest($requestId, $notes = null)
    {
        $request = TimeOffRequest::where('company_id', auth()->user()->company_id)
            ->findOrFail($requestId);

        if (!$request->isPending()) {
            $this->dispatch('error', message: 'Only pending requests can be approved');
            return;
        }

        $request->update([
            'status' => TimeOffRequest::STATUS_APPROVED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $notes ?? $this->reviewNotes,
        ]);

        $this->reviewNotes = '';
        $this->selectedRequest = null;
        $this->dispatch('success', message: 'Time off request approved');
    }

    public function denyRequest($requestId, $notes = null)
    {
        $request = TimeOffRequest::where('company_id', auth()->user()->company_id)
            ->findOrFail($requestId);

        if (!$request->isPending()) {
            $this->dispatch('error', message: 'Only pending requests can be denied');
            return;
        }

        if (!$notes && !$this->reviewNotes) {
            $this->dispatch('error', message: 'Please provide a reason for denial');
            return;
        }

        $request->update([
            'status' => TimeOffRequest::STATUS_DENIED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $notes ?? $this->reviewNotes,
        ]);

        $this->reviewNotes = '';
        $this->selectedRequest = null;
        $this->dispatch('success', message: 'Time off request denied');
    }

    public function selectRequest($requestId)
    {
        $this->selectedRequest = $requestId;
        $this->reviewNotes = '';
    }

    public function render()
    {
        return view('livewire.hr.time-off-approvals')->layout('components.layouts.app', [
            'sidebarContext' => 'hr',
        ]);
    }
}
