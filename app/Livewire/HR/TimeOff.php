<?php

namespace App\Livewire\HR;

use App\Domains\HR\Models\TimeOffRequest;
use Livewire\Component;
use Livewire\WithPagination;

class TimeOff extends Component
{
    use WithPagination;

    public $showCreateModal = false;
    
    public $filterStatus = 'all';
    
    public $form = [
        'type' => TimeOffRequest::TYPE_VACATION,
        'start_date' => null,
        'end_date' => null,
        'is_full_day' => true,
        'start_time' => '09:00',
        'end_time' => '17:00',
        'reason' => null,
    ];

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function getRequestsProperty()
    {
        $query = TimeOffRequest::where('company_id', auth()->user()->company_id)
            ->where('user_id', auth()->id())
            ->with(['reviewedBy']);

        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        return $query->orderBy('start_date', 'desc')->paginate(15);
    }

    public function createRequest()
    {
        $this->validate([
            'form.type' => 'required|in:vacation,sick,personal,unpaid,holiday,bereavement',
            'form.start_date' => 'required|date',
            'form.end_date' => 'required|date|after_or_equal:form.start_date',
            'form.reason' => 'nullable|string|max:500',
        ]);

        $totalHours = 8;
        if (!$this->form['is_full_day']) {
            $start = \Carbon\Carbon::parse($this->form['start_time']);
            $end = \Carbon\Carbon::parse($this->form['end_time']);
            $totalHours = $end->diffInHours($start);
        } else {
            $startDate = \Carbon\Carbon::parse($this->form['start_date']);
            $endDate = \Carbon\Carbon::parse($this->form['end_date']);
            $totalHours = ($endDate->diffInDays($startDate) + 1) * 8;
        }

        TimeOffRequest::create([
            'company_id' => auth()->user()->company_id,
            'user_id' => auth()->id(),
            'type' => $this->form['type'],
            'start_date' => $this->form['start_date'],
            'end_date' => $this->form['end_date'],
            'is_full_day' => $this->form['is_full_day'],
            'start_time' => $this->form['is_full_day'] ? null : $this->form['start_time'],
            'end_time' => $this->form['is_full_day'] ? null : $this->form['end_time'],
            'total_hours' => $totalHours,
            'reason' => $this->form['reason'],
            'status' => TimeOffRequest::STATUS_PENDING,
        ]);

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('success', message: 'Time off request submitted successfully');
    }

    public function cancelRequest($requestId)
    {
        $request = TimeOffRequest::where('company_id', auth()->user()->company_id)
            ->where('user_id', auth()->id())
            ->findOrFail($requestId);

        if (!$request->isPending()) {
            $this->dispatch('error', message: 'Only pending requests can be cancelled');
            return;
        }

        $request->delete();
        $this->dispatch('success', message: 'Time off request cancelled');
    }

    public function resetForm()
    {
        $this->form = [
            'type' => TimeOffRequest::TYPE_VACATION,
            'start_date' => null,
            'end_date' => null,
            'is_full_day' => true,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'reason' => null,
        ];
    }

    public function render()
    {
        return view('livewire.hr.time-off')->layout('components.layouts.app', [
            'sidebarContext' => 'hr',
        ]);
    }
}
