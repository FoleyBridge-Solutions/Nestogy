<?php

namespace App\Livewire\HR;

use App\Domains\Core\Models\User;
use App\Domains\HR\Models\EmployeeSchedule;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class Schedules extends Component
{
    use WithPagination;

    public $selectedDate;
    public $viewMode = 'week';
    public $selectedUser = null;
    public $showCreateModal = false;
    public $canManageHR = false;

    public $form = [
        'user_id' => null,
        'scheduled_date' => null,
        'start_time' => '09:00',
        'end_time' => '17:00',
        'status' => EmployeeSchedule::STATUS_SCHEDULED,
        'notes' => null,
    ];

    public function mount()
    {
        $this->selectedDate = Carbon::now()->startOfWeek();
        $this->canManageHR = auth()->user()->can('manage-hr');
    }

    public function previousWeek()
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->subWeek();
    }

    public function nextWeek()
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->addWeek();
    }

    public function today()
    {
        $this->selectedDate = Carbon::now()->startOfWeek();
    }

    public function getSchedulesProperty()
    {
        $query = EmployeeSchedule::where('company_id', auth()->user()->company_id)
            ->with(['user', 'shift']);

        if ($this->viewMode === 'week') {
            $start = Carbon::parse($this->selectedDate)->startOfWeek();
            $end = Carbon::parse($this->selectedDate)->endOfWeek();
            $query->whereBetween('scheduled_date', [$start, $end]);
        } elseif ($this->viewMode === 'day') {
            $query->whereDate('scheduled_date', $this->selectedDate);
        }

        if ($this->selectedUser) {
            $query->where('user_id', $this->selectedUser);
        }

        return $query->orderBy('scheduled_date')
            ->orderBy('start_time')
            ->get();
    }

    public function getEmployeesProperty()
    {
        return User::where('company_id', auth()->user()->company_id)
            ->where('status', true)
            ->orderBy('name')
            ->get();
    }

    public function createSchedule()
    {
        if (!$this->canManageHR) {
            $this->dispatch('error', message: 'You do not have permission to create schedules');
            return;
        }

        $this->validate([
            'form.user_id' => 'required|exists:users,id',
            'form.scheduled_date' => 'required|date',
            'form.start_time' => 'required',
            'form.end_time' => 'required',
        ]);

        EmployeeSchedule::create([
            'company_id' => auth()->user()->company_id,
            'user_id' => $this->form['user_id'],
            'scheduled_date' => $this->form['scheduled_date'],
            'start_time' => $this->form['start_time'],
            'end_time' => $this->form['end_time'],
            'status' => $this->form['status'],
            'notes' => $this->form['notes'],
        ]);

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('success', message: 'Schedule created successfully');
    }

    public function deleteSchedule($scheduleId)
    {
        if (!$this->canManageHR) {
            $this->dispatch('error', message: 'You do not have permission to delete schedules');
            return;
        }

        $schedule = EmployeeSchedule::where('company_id', auth()->user()->company_id)
            ->findOrFail($scheduleId);
        
        $schedule->delete();
        
        $this->dispatch('success', message: 'Schedule deleted successfully');
    }

    public function resetForm()
    {
        $this->form = [
            'user_id' => null,
            'scheduled_date' => null,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'status' => EmployeeSchedule::STATUS_SCHEDULED,
            'notes' => null,
        ];
    }

    public function render()
    {
        return view('livewire.hr.schedules')->layout('components.layouts.app', [
            'sidebarContext' => 'hr',
        ]);
    }
}
