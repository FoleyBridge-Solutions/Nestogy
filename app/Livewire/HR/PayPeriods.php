<?php

namespace App\Livewire\HR;

use App\Domains\HR\Models\PayPeriod;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class PayPeriods extends Component
{
    use WithPagination;

    public $showCreateModal = false;
    
    public $filterStatus = 'all';
    
    public $form = [
        'start_date' => null,
        'end_date' => null,
        'frequency' => PayPeriod::FREQUENCY_BIWEEKLY,
        'status' => PayPeriod::STATUS_OPEN,
        'notes' => null,
    ];

    public function mount()
    {
        $this->authorize('manage-hr');
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    #[\Livewire\Attributes\Computed]
    public function payPeriods()
    {
        $query = PayPeriod::where('company_id', auth()->user()->company_id)
            ->with(['timeEntries', 'approvedBy']);

        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        return $query->orderBy('start_date', 'desc')->paginate(15);
    }

    public function createPayPeriod()
    {
        $this->validate([
            'form.start_date' => 'required|date',
            'form.end_date' => 'required|date|after:form.start_date',
            'form.frequency' => 'required|in:weekly,biweekly,semimonthly,monthly',
        ]);

        PayPeriod::create([
            'company_id' => auth()->user()->company_id,
            'start_date' => $this->form['start_date'],
            'end_date' => $this->form['end_date'],
            'frequency' => $this->form['frequency'],
            'status' => $this->form['status'],
            'notes' => $this->form['notes'],
        ]);

        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('success', message: 'Pay period created successfully');
    }

    public function generateNextPeriod()
    {
        $lastPeriod = PayPeriod::where('company_id', auth()->user()->company_id)
            ->orderBy('end_date', 'desc')
            ->first();

        if (!$lastPeriod) {
            $this->dispatch('error', message: 'No previous pay period found to generate from');
            return;
        }

        $startDate = Carbon::parse($lastPeriod->end_date)->addDay();
        $endDate = match ($lastPeriod->frequency) {
            PayPeriod::FREQUENCY_WEEKLY => $startDate->copy()->addDays(6),
            PayPeriod::FREQUENCY_BIWEEKLY => $startDate->copy()->addDays(13),
            PayPeriod::FREQUENCY_SEMIMONTHLY => $startDate->copy()->addDays(14),
            PayPeriod::FREQUENCY_MONTHLY => $startDate->copy()->endOfMonth(),
            default => $startDate->copy()->addDays(13),
        };

        PayPeriod::create([
            'company_id' => auth()->user()->company_id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'frequency' => $lastPeriod->frequency,
            'status' => PayPeriod::STATUS_OPEN,
        ]);

        $this->dispatch('success', message: 'Pay period generated successfully');
    }

    public function approvePayPeriod($payPeriodId)
    {
        $payPeriod = PayPeriod::where('company_id', auth()->user()->company_id)
            ->findOrFail($payPeriodId);

        $payPeriod->update([
            'status' => PayPeriod::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        $this->dispatch('success', message: 'Pay period approved successfully');
    }

    public function markAsPaid($payPeriodId)
    {
        $payPeriod = PayPeriod::where('company_id', auth()->user()->company_id)
            ->findOrFail($payPeriodId);

        $payPeriod->update([
            'status' => PayPeriod::STATUS_PAID,
        ]);

        $this->dispatch('success', message: 'Pay period marked as paid');
    }

    public function deletePayPeriod($payPeriodId)
    {
        $payPeriod = PayPeriod::where('company_id', auth()->user()->company_id)
            ->findOrFail($payPeriodId);

        if ($payPeriod->timeEntries()->count() > 0) {
            $this->dispatch('error', message: 'Cannot delete pay period with time entries');
            return;
        }

        $payPeriod->delete();
        $this->dispatch('success', message: 'Pay period deleted successfully');
    }

    public function resetForm()
    {
        $this->form = [
            'start_date' => null,
            'end_date' => null,
            'frequency' => PayPeriod::FREQUENCY_BIWEEKLY,
            'status' => PayPeriod::STATUS_OPEN,
            'notes' => null,
        ];
    }

    public function render()
    {
        return view('livewire.hr.pay-periods')->layout('components.layouts.app', [
            'sidebarContext' => 'hr',
        ]);
    }
}
