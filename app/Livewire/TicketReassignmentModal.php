<?php

namespace App\Livewire;

use App\Domains\Ticket\Models\Ticket;
use App\Models\User;
use Livewire\Component;
use App\Traits\HasFluxToasts;

class TicketReassignmentModal extends Component
{
    use HasFluxToasts;
    public $ticketId;

    public $ticket;

    public $selectedTechnicianId;

    public $reason = '';

    public $notify = true;

    public $show = false;

    public $technicians = [];

    protected $rules = [
        'selectedTechnicianId' => 'required|exists:users,id',
        'reason' => 'nullable|string|max:500',
        'notify' => 'boolean',
    ];

    public function openModal($ticketId)
    {
        $this->ticketId = $ticketId;
        $this->ticket = Ticket::with(['assignee', 'client'])->find($ticketId);

        if (! $this->ticket) {
            $this->dispatch('error', message: 'Ticket not found');
            return;
        }

        $this->selectedTechnicianId = $this->ticket->assigned_to;
        $this->reason = '';
        $this->notify = true;

        $this->loadTechnicians();

        $this->show = true;
    }

    public function closeModal()
    {
        $this->show = false;
        $this->reset(['ticketId', 'ticket', 'selectedTechnicianId', 'reason', 'notify', 'technicians']);
    }

    protected function loadTechnicians()
    {
        $companyId = auth()->user()->company_id;

        $techs = User::where('company_id', $companyId)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['technician', 'admin', 'manager']);
            })
            ->get();

        $this->technicians = $techs->map(function ($tech) {
            $activeTickets = Ticket::where('assigned_to', $tech->id)
                ->whereNotIn('status', [Ticket::STATUS_CLOSED, Ticket::STATUS_RESOLVED])
                ->count();

            $overdueTickets = Ticket::where('assigned_to', $tech->id)
                ->whereHas('priorityQueue', function ($q) {
                    $q->where('sla_deadline', '<', now());
                })
                ->whereNotIn('status', [Ticket::STATUS_CLOSED, Ticket::STATUS_RESOLVED])
                ->count();

            $workloadScore = ($activeTickets * 1) + ($overdueTickets * 3);

            return [
                'id' => $tech->id,
                'name' => $tech->name,
                'email' => $tech->email,
                'active_tickets' => $activeTickets,
                'overdue_tickets' => $overdueTickets,
                'workload_score' => $workloadScore,
                'is_current' => $tech->id === $this->ticket->assigned_to,
            ];
        })->sortBy('workload_score')->values()->toArray();
    }

    public function reassign()
    {
        $this->validate();

        if ($this->selectedTechnicianId == $this->ticket->assigned_to) {
            $this->dispatch('error', message: 'Ticket is already assigned to this technician');
            return;
        }

        $newTechnician = User::find($this->selectedTechnicianId);

        if (! $newTechnician) {
            $this->dispatch('error', message: 'Selected technician not found');
            return;
        }

        $oldTechnician = $this->ticket->assignee;

        $this->ticket->update([
            'assigned_to' => $this->selectedTechnicianId,
        ]);

        $this->ticket->assignments()->create([
            'company_id' => $this->ticket->company_id,
            'assigned_to' => $this->selectedTechnicianId,
            'assigned_by' => auth()->id(),
            'assigned_at' => now(),
            'reason' => $this->reason,
        ]);

        $this->ticket->comments()->create([
            'company_id' => $this->ticket->company_id,
            'content' => sprintf(
                'Ticket reassigned from %s to %s%s',
                $oldTechnician?->name ?? 'Unassigned',
                $newTechnician->name,
                $this->reason ? '. Reason: ' . $this->reason : ''
            ),
            'visibility' => 'internal',
            'source' => 'system',
            'author_id' => auth()->id(),
            'author_type' => 'user',
        ]);

        if ($this->notify) {
            $notificationService = app(\App\Services\NotificationService::class);
            $notificationService->notifyTicketAssigned($this->ticket, $newTechnician);
        }

        $this->dispatch('ticket-reassigned', ticketId: $this->ticketId);
        $this->dispatch('success', message: "Ticket #{$this->ticket->number} reassigned to {$newTechnician->name}");

        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.ticket-reassignment-modal');
    }
}
