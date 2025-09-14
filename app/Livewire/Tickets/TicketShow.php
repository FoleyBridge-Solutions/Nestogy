<?php

namespace App\Livewire\Tickets;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketComment;
use App\Domains\Ticket\Services\CommentService;
use App\Domains\Ticket\Services\TimeTrackingService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;

class TicketShow extends Component
{
    use WithFileUploads;

    public Ticket $ticket;
    public $comment = '';
    public $internalNote = false;
    public $attachments = [];
    public $status;
    public $priority;
    public $assignedTo;
    
    public $timeTracking = false;
    public $timeSpent = '';
    public $timeDescription = '';
    
    public $showStatusChangeModal = false;
    public $newStatus = '';
    public $statusChangeReason = '';

    protected $listeners = ['refreshTicket' => '$refresh'];

    protected $rules = [
        'comment' => 'required|min:3',
        'attachments.*' => 'file|max:10240', // 10MB max
        'status' => 'required|in:open,in_progress,pending,resolved,closed',
        'priority' => 'required|in:low,medium,high,urgent',
        'assignedTo' => 'nullable|exists:users,id',
        'timeSpent' => 'nullable|numeric|min:0',
        'timeDescription' => 'nullable|string|max:255',
    ];

    public function mount(Ticket $ticket)
    {
        $this->ticket = $ticket;
        $this->status = $ticket->status;
        $this->priority = $ticket->priority;
        $this->assignedTo = $ticket->assigned_to;
        
        // Load relationships
        $this->ticket->load([
            'client',
            'contact',
            'assignee',
            'requester',
            'asset',
            'project',
            'comments.user',
            'comments.attachments',
            'watchers.user',
            'timeLogs.user',
            'activities.user'
        ]);
    }

    public function addComment()
    {
        $this->validate([
            'comment' => 'required|min:3',
            'attachments.*' => 'file|max:10240',
        ]);

        $commentService = app(CommentService::class);
        
        $newComment = $commentService->createComment($this->ticket, [
            'body' => $this->comment,
            'is_internal' => $this->internalNote,
            'user_id' => Auth::id(),
        ]);

        // Handle attachments
        if ($this->attachments) {
            foreach ($this->attachments as $attachment) {
                $path = $attachment->store('ticket-attachments', 'public');
                $newComment->attachments()->create([
                    'filename' => $attachment->getClientOriginalName(),
                    'path' => $path,
                    'size' => $attachment->getSize(),
                    'mime_type' => $attachment->getMimeType(),
                ]);
            }
        }

        // Log time if provided
        if ($this->timeTracking && $this->timeSpent) {
            $timeService = app(TimeTrackingService::class);
            $timeService->logTime($this->ticket, [
                'user_id' => Auth::id(),
                'minutes' => $this->timeSpent * 60,
                'description' => $this->timeDescription,
                'date' => now(),
            ]);
        }

        // Reset form
        $this->reset(['comment', 'internalNote', 'attachments', 'timeSpent', 'timeDescription']);
        
        // Refresh ticket data
        $this->ticket->refresh();
        $this->ticket->load('comments.user', 'comments.attachments', 'timeLogs.user');
        
        session()->flash('message', 'Comment added successfully.');
    }

    public function updateStatus()
    {
        $this->validate([
            'newStatus' => 'required|in:open,in_progress,pending,resolved,closed',
            'statusChangeReason' => 'required|min:10',
        ]);

        $oldStatus = $this->ticket->status;
        $this->ticket->status = $this->newStatus;
        $this->ticket->save();

        // Add system comment about status change
        $commentService = app(CommentService::class);
        $commentService->createComment($this->ticket, [
            'body' => "Status changed from {$oldStatus} to {$this->newStatus}. Reason: {$this->statusChangeReason}",
            'is_internal' => false,
            'is_system' => true,
            'user_id' => Auth::id(),
        ]);

        $this->status = $this->newStatus;
        $this->reset(['showStatusChangeModal', 'newStatus', 'statusChangeReason']);
        
        session()->flash('message', 'Ticket status updated successfully.');
    }

    public function updatePriority()
    {
        $this->validate(['priority' => 'required|in:low,medium,high,urgent']);
        
        $oldPriority = $this->ticket->priority;
        $this->ticket->priority = $this->priority;
        $this->ticket->save();
        
        // Log activity
        if ($oldPriority !== $this->priority) {
            $commentService = app(CommentService::class);
            $commentService->createComment($this->ticket, [
                'body' => "Priority changed from {$oldPriority} to {$this->priority}",
                'is_internal' => false,
                'is_system' => true,
                'user_id' => Auth::id(),
            ]);
        }
        
        session()->flash('message', 'Priority updated successfully.');
    }

    public function updateAssignee()
    {
        $this->validate(['assignedTo' => 'nullable|exists:users,id']);
        
        $oldAssignee = $this->ticket->assignee?->name ?? 'Unassigned';
        $this->ticket->assigned_to = $this->assignedTo;
        $this->ticket->save();
        
        // Log activity
        $newAssignee = $this->ticket->fresh()->assignee?->name ?? 'Unassigned';
        if ($oldAssignee !== $newAssignee) {
            $commentService = app(CommentService::class);
            $commentService->createComment($this->ticket, [
                'body' => "Ticket reassigned from {$oldAssignee} to {$newAssignee}",
                'is_internal' => false,
                'is_system' => true,
                'user_id' => Auth::id(),
            ]);
        }
        
        $this->ticket->refresh();
        session()->flash('message', 'Assignee updated successfully.');
    }

    public function toggleWatch()
    {
        $watcher = $this->ticket->watchers()->where('user_id', Auth::id())->first();
        
        if ($watcher) {
            $watcher->delete();
            session()->flash('message', 'You are no longer watching this ticket.');
        } else {
            $this->ticket->watchers()->create(['user_id' => Auth::id()]);
            session()->flash('message', 'You are now watching this ticket.');
        }
        
        $this->ticket->load('watchers.user');
    }

    public function deleteComment($commentId)
    {
        $comment = TicketComment::where('id', $commentId)
            ->where('ticket_id', $this->ticket->id)
            ->where('user_id', Auth::id())
            ->first();
            
        if ($comment && $comment->created_at->diffInMinutes(now()) < 30) {
            $comment->delete();
            $this->ticket->load('comments.user', 'comments.attachments');
            session()->flash('message', 'Comment deleted successfully.');
        } else {
            session()->flash('error', 'You can only delete your own comments within 30 minutes of posting.');
        }
    }

    public function render()
    {
        $technicians = \App\Models\User::where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get();
            
        $isWatching = $this->ticket->watchers()->where('user_id', Auth::id())->exists();
        
        return view('livewire.tickets.ticket-show', [
            'technicians' => $technicians,
            'isWatching' => $isWatching,
            'statuses' => ['open', 'in_progress', 'pending', 'resolved', 'closed'],
            'priorities' => ['low', 'medium', 'high', 'urgent'],
        ]);
    }
}